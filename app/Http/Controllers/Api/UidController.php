<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Uid;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class UidController extends Controller
{
    /**
     * Display a listing of the uids.
     *
     * This method retrieves all uids from the database and returns a JSON response
     * containing the uids data. It indicates success or failure along with a
     * relevant message.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the uids data
     */
    public function index()
    {
        // Retrieve all uids from the database
        $uids = Uid::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $uids,
            'message' => 'Successfully fetched all uids'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified uid.
     *
     * This method retrieves the uid with the given ID, checks if it exists,
     * and returns a JSON response containing the uid data. It indicates success
     * or failure along with a relevant message.
     *
     * @param  int  $id The ID of the uid to display
     * @return \Illuminate\Http\JsonResponse JSON response containing the uid data
     */
    public function show($id)
    {
        // Find the uid with the given ID
        $uid = Uid::find($id);

        // If the uid is not found, return an error response
        if (!$uid) {
            $response = [
                'status' => false,
                'error' => 'Uid not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data with uid data
        $response = [
            'status' => true,
            'data' => ($uid) ? $uid : '',
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created UID resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new UID record in the database, and returning a JSON
     * response indicating success or failure along with any relevant data
     * or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'uid_no' => 'required|unique:uids,uid_no',
                'date' => 'required|date',
                'time' => 'required|date_format:H:i:s',
                'server_timezone' => 'required|date_format:Y-m-d H:i:s',
                'description' => 'required'
            ]
        );

        // If validation fails, return validation errors
        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Retrieve a portion of the validated input...
        // $validated = $validator->safe()->only(['uid_no', 'date', 'time']);
        // $validated = $validator->safe()->except(['name', 'email']);

        // Create a new UID record with validated data
        $data = Uid::create($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update the specified UID resource in storage.
     *
     * This method retrieves the UID with the given ID, checks if it exists,
     * validates the incoming request data, and updates the UID record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the UID to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Find the UID with the given ID
        $uid = Uid::find($id);

        // If the UID is not found, return an error response
        if (!$uid) {
            $response = [
                'status' => false,
                'error' => 'Uid not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'uid_no' => 'unique:uids,uid_no,' . $id,
                'date' => 'date',
                'time' => 'date_format:H:i:s',
                'server_timezone' => 'date_format:Y-m-d H:i:s',
            ]
        );

        // If validation fails, return validation errors
        if ($validator->fails()) {
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Update the UID record with validated data
        $uid->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $uid,
            'message' => 'Successfully updated Uid',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, response::HTTP_OK);
    }

    /**
     * Remove the specified UID resource from storage.
     *
     * This method retrieves the UID with the given ID, checks if it exists,
     * deletes the UID record from the database, and returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the UID to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the UID with the given ID
        $uid = Uid::find($id);

        // If the UID is not found, return an error response
        if (!$uid) {
            $response = [
                'status' => false,
                'error' => 'Uid not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the UID record
        $uid->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
