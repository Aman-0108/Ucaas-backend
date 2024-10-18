<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IvrMaster;
use App\Models\Sound;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SoundController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Audio'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Audio';
    }

    /**
     * Retrieves a list of audios.
     *
     * This method retrieves a list of audios based on optional query parameters.
     * If a specific account ID is provided in the request, it filters audios by that account.
     * It then returns a JSON response containing the list of audios.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of audios.
     */
    public function index(Request $request)
    {
        $account_id = $request->user()->account_id;

        // Start building the query to fetch audios
        $query = Sound::query();

        if ($account_id) {
            $query->where('account_id', $account_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Execute the query to fetch audios
        $audios = $query->orderBy('id', 'desc')->get();

        // $audios->each(function ($audio) {
        //     if ($audio->path && $audio->name) {
        //         $audio->url = Storage::url($audio->path . '/' . $audio->name);
        //     }
        // });

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $audios,
            'message' => 'Successfully fetched all audios'
        ];

        // Return a JSON response containing the list of audios
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieves details of a specific audio.
     *
     * This method retrieves details of a audio with the given ID.
     * If the audio is found, it returns a JSON response containing
     * the audio details. If the audio is not found, it returns
     * a JSON response with an error message and a 404 status code.
     *
     * @param int $id The ID of the audio to retrieve.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the audio details or an error message.
     */
    public function show($id)
    {
        // Find the audio with the given ID
        $audio = Sound::find($id);

        // Check if the audio exists
        if (!$audio) {
            // If audio is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Audio not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with audio details
        $response = [
            'status' => true,
            'data' => ($audio) ? $audio : '', // Include audio details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the audio details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Stores a new audio.
     *
     * This method attempts to store a new audio based on the provided data.
     * It validates the request data and checks for validation errors. If validation
     * fails, it returns a JSON response with validation errors. If validation succeeds,
     * it creates a new audio record in the database and returns a JSON response
     * indicating successful storage.
     *
     * @param Request $request The HTTP request object containing audio data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the storage attempt.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'path' => 'required|file|mimes:wav,mp3|max:2048',
                'type' => 'required|in:hold,busy,ringback,ivr,announcement',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        $path = 'audios';

        $file = $request->file('path');
        $fileName = $file->getClientOriginalName();

        // $filePath = $file->storeAs($path, $fileName); // Store file in storage/app/audios

        // Upload file to S3
        $filePath = $file->storeAs('audios', $fileName, 's3'); // Specify 's3' disk

        // Retrieve the S3 URL of the uploaded file
        $s3Url = Storage::disk('s3')->url($filePath);

        $validated = [
            'account_id' => $request->account_id,
            'name' => $fileName,
            // 'path' => $path,
            'path' => $s3Url,
            'type' => $request->type
        ];

        // Begin a database transaction
        DB::beginTransaction();

        // Create a new audio record in the database
        $data = Sound::create($validated);

        $action = 'create';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Commit the database transaction
        DB::commit();

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response indicating successful storage and 201 status code
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Updates an existing audio.
     *
     * This method attempts to update an existing audio based on the provided data.
     * It first checks if the audio exists and if the authenticated user has permission
     * to edit it. If the audio doesn't exist or the user doesn't have permission,
     * it returns an appropriate error response. If validation fails, it returns
     * a JSON response with validation errors. If validation succeeds and the audio
     * is successfully updated, it returns a JSON response indicating success.
     *
     * @param Request $request The HTTP request object containing audio data.
     * @param int $id The ID of the audio to update.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update attempt.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->account_id;

        $path = 'audios';

        // Find the audio with the given ID
        $audio = Sound::find($id);

        // Check if the audio exists
        if (!$audio) {
            // If audio is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Audio not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the audio
        if ($audio->account_id !== $userId) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to edit.'
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'path' => 'file|mimes:wav,mp3|max:2048',
                'type' => 'in:hold,busy,ringback,ivr,announcement|nullable'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        if ($request->hasFile('path')) {

            // $existingpath = $audio->path . '/' . $audio->name;

            // $exists = Storage::exists($existingpath);

            // if ($exists) {
            //     // Delete associated file from storage
            //     Storage::delete($path);
            // }
            $filePath = parse_url($audio->path, PHP_URL_PATH); // Get the path part of the URL
            // Check if the file exists on S3
            if (Storage::disk('s3')->exists($filePath)) {
                // Delete the file from S3
                Storage::disk('s3')->delete($filePath);
            }

            $file = $request->file('path');
            $fileName = $file->getClientOriginalName();
            // $filePath = $file->storeAs($path, $fileName);

            // Upload file to S3
            $filePath = $file->storeAs('audios', $fileName, 's3'); // Specify 's3' disk

            // Retrieve the S3 URL of the uploaded file
            $s3Url = Storage::disk('s3')->url($filePath);

            $validated = [
                'account_id' => $request->account_id,
                'name' => $fileName,
                'path' => $s3Url,
                'type' => null
            ];
        }

        // Update the audio with the validated data
        $audio->update($validated);

        $action = 'update';
        $type = $this->type;

        // Log the update action
        accessLog($action, $type, $validated, $userId);

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $audio,
            'message' => 'Successfully updated audio',
        ];

        // Return a JSON response indicating successful update
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Deletes a audio.
     *
     * This method attempts to delete a audio with the provided ID.
     * It first checks if the audio exists. If the audio doesn't exist,
     * it returns an appropriate error response. If the audio exists, it
     * generates a UID for the deletion action, deletes the audio from
     * the database, and returns a JSON response indicating successful deletion.
     *
     * @param Request $request The HTTP request object.
     * @param int $id The ID of the audio to delete.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion attempt.
     */
    public function destroy(Request $request, $id)
    {
        $action = 'delete';
        $type = $this->type;

        // Retrieve the ID of the authenticated user making the request
        $account_id = $request->user()->account_id;

        $userId = $request->user()->id;

        // Find the audio with the given ID
        $audio = Sound::find($id);

        // Check if the audio exists
        if (!$audio) {
            // If audio is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Audio not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the audio
        if ($audio->account_id !== $account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to delete.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // $path = $audio->path . '/' . $audio->name;

        // $exists = Storage::exists($path);

        // if ($exists) {
        //     // Delete associated file from storage
        //     Storage::delete($path);
        // }

        // Delete the file from S3
        $filePath = parse_url($audio->path, PHP_URL_PATH); // Get the path part of the URL
        // Check if the file exists on S3
        if (Storage::disk('s3')->exists($filePath)) {
            // Delete the file from S3
            Storage::disk('s3')->delete($filePath);
        }

        DB::beginTransaction();

        $audioId = $audio->id;

        // Retrieve the records that match the audio ID
        $ivrRecords = IvrMaster::where(function ($query) use ($audioId) {
            $query->where('greet_long', $audioId)
                ->orWhere('greet_short', $audioId)
                ->orWhere('invalid_sound', $audioId)
                ->orWhere('exit_sound', $audioId);
        })->get();

        // Prepare the updates array based on the fields that match
        $updates = [];
        foreach ($ivrRecords as $record) {
            if ($record->greet_long === $audioId) {
                $updates['greet_long'] = null;
            }
            if ($record->greet_short === $audioId) {
                $updates['greet_short'] = null;
            }
            if ($record->invalid_sound === $audioId) {
                $updates['invalid_sound'] = null;
            }
            if ($record->exit_sound === $audioId) {
                $updates['exit_sound'] = null;
            }
        }

        // Update only if there are any fields to update
        if (!empty($updates)) {
            IvrMaster::where(function ($query) use ($audioId) {
                $query->where('greet_long', $audioId)
                    ->orWhere('greet_short', $audioId)
                    ->orWhere('invalid_sound', $audioId)
                    ->orWhere('exit_sound', $audioId);
            })->update($updates);
        }

        // Delete the audio from the database
        $audio->delete();

        // Generate access log
        accessLog($action, $type, $audio, $userId);

        DB::commit();

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $filePath,
            'message' => 'Successfully deleted audio'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }
}
