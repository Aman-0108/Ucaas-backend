<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaxFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class FaxController extends Controller
{
    /**
     * Retrieve all fax.
     *
     * This method retrieves all fax from the database.
     * It returns a JSON response containing the list of fax.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the list of fax
     */
    public function index(Request $request)
    {
        // Retrieve the authenticated user's ID
        $account_id = $request->user()->account_id;

        $query = FaxFile::query();

        // Filter fax by user ID if provided
        $fax = $account_id ? $query->where('account_id', $account_id)->get() : $query->get();

        // Prepare a success response with the list of fax
        $response = [
            'status' => true,
            'data' => $fax,
            'message' => 'Successfully fetched all fax'
        ];

        // Return a JSON response with the list of fax
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieve an Fax by ID.
     *
     * This method finds and retrieves an Fax based on the provided ID.
     * If the Fax is not found, it returns a 404 Not Found response.
     * If the Fax is found, it returns a JSON response containing the Fax data.
     *
     * @param  int  $id The ID of the Fax to retrieve
     * @return \Illuminate\Http\JsonResponse The JSON response containing the Fax data or an error message
     */
    public function show($id)
    {
        // Find the Fax by ID
        $fax = FaxFile::where('id', $id)->first();

        // Find the Fax by ID
        if (!$fax) {
            // If the Fax is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Fax not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare a success response with the Fax data
        $response = [
            'status' => true,
            'data' => ($fax) ? $fax : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with the Fax data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new Fax.
     *
     * This method validates the incoming request data and stores a new Fax in the database.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the Fax is successfully stored, it returns a success message along with the stored Fax data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the Fax data
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function store(Request $request)
    {
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Perform validation on the request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'file_path' => 'required|file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:2048',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        $path = 'fax';

        $file = $request->file('file_path');
        $fileName = time() . '_' . $file->getClientOriginalName();
        // Get the file size
        $fileSize = $file->getSize(); // Size in bytes

        // Upload file to S3
        $filePath = $file->storeAs($path, $fileName, 's3'); // Specify 's3' disk

        // Retrieve the S3 URL of the uploaded file
        $s3Url = Storage::disk('s3')->url($filePath);

        $validated = [
            'account_id' => $request->account_id,
            'file_name' => $fileName,
            'file_path' => $s3Url,
            'file_size' => $fileSize
        ];

        DB::beginTransaction();

        // Create a new Fax with the validated input
        $data = FaxFile::create($validated);

        DB::commit();

        // Prepare a success response with the stored Fax data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with the success message and stored Fax data
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update an Fax by ID.
     *
     * This method finds and updates an Fax based on the provided ID and request data.
     * If the Fax is not found, it returns a 404 Not Found response.
     * If the validation fails, it returns a 403 Forbidden response with validation errors.
     * If the update is successful, it returns a success message along with the updated Fax data.
     *
     * @param  \Illuminate\Http\Request  $request The request object containing the update data
     * @param  int  $id The ID of the Fax to update
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function update(Request $request, $id)
    {
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Find the Fax by ID
        $fax = FaxFile::find($id);

        // Check if the Fax exists
        if (!$fax) {
            // If the Fax is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Fax not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'exists:accounts,id',
                'file_path' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:2048'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Check if the authenticated user has permission to edit the fax file
        if ($fax->account_id !== $account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to edit.'
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        $path = 'fax';

        if ($request->hasFile('file_path')) {

            $filePath = parse_url($fax->file_path, PHP_URL_PATH); // Get the path part of the URL
            // Check if the file exists on S3
            if (Storage::disk('s3')->exists($filePath)) {
                // Delete the file from S3
                Storage::disk('s3')->delete($filePath);
            }

            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            // Get the file size
            $fileSize = $file->getSize(); // Size in bytes

            // Upload file to S3
            $filePath = $file->storeAs($path, $fileName, 's3'); // Specify 's3' disk

            // Retrieve the S3 URL of the uploaded file
            $s3Url = Storage::disk('s3')->url($filePath);

            $validated = [
                'account_id' => $request->account_id,
                'file_name' => $fileName,
                'file_path' => $s3Url,
                'file_size' => $fileSize
            ];
        }

        // Update the Fax with the validated input
        $fax->update($validated);

        // Prepare a success response with updated Fax data
        $response = [
            'status' => true,
            'data' => $fax,
            'message' => 'Successfully updated Fax',
        ];

        // Return a JSON response with the success message and updated Fax data with status(200)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Delete an Fax by ID.
     *
     * This method finds and deletes an Fax based on the provided ID.
     * If the Fax is not found, it returns a 404 Not Found response.
     * If the Fax is successfully deleted, it returns a success message.
     *
     * @param  int  $id The ID of the Fax to delete
     * @return \Illuminate\Http\JsonResponse The JSON response indicating the status of the operation
     */
    public function destroy($id)
    {
        $account_id = auth()->user()->account_id;

        // Find the Fax by ID
        $fax = FaxFile::find($id);

        // Check if the Fax exists
        if (!$fax) {
            // If the Fax is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Fax not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to edit the audio
        if ($fax->account_id !== $account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You dont have access to delete.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $filePath = parse_url($fax->file_path, PHP_URL_PATH); // Get the path part of the URL
        // Check if the file exists on S3
        if (Storage::disk('s3')->exists($filePath)) {
            // Delete the file from S3
            Storage::disk('s3')->delete($filePath);
        }

        // Delete the Fax
        $fax->delete();

        // Prepare a success message
        $response = [
            'status' => true,
            'message' => 'Successfully deleted Fax'
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
