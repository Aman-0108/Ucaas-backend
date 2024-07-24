<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DestinationController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Destination'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Destination';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all Destinations from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched Destinations.
     */
    public function index()
    {
        // Retrieve all Destinations from the database
        $destinations = Destination::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $destinations,
            'message' => 'Successfully fetched all Destinations'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Destination resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new Destination record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string',
                'prefix' => 'required|numeric',
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Create a new destination record with validated data
        $data = Destination::create($validated);

        // Commit the database transaction
        DB::commit();

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
     * Update the specified Destination resource in storage.
     *
     * This method retrieves the Destination with the given ID, checks if it exists,
     * validates the incoming request data, and updates the Destination record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the Destination to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Find the Destination with the given ID
        $destination = Destination::find($id);

        // Check if the Destination exists
        if (!$destination) {
            // If the Destination is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Destination not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string',
                'prefix' => 'numeric',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Update the Destination record with validated data
        $destination->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $destination,
            'message' => 'Successfully updated Destination',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function show($id)
    {
        // Find the Destination with the given ID
        $destination = Destination::find($id);

        // Check if the Destination exists
        if (!$destination) {
            // If Destination is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Destination not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with Destination details
        $response = [
            'status' => true,
            'data' => ($destination) ? $destination : '', // Include Destination details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the Destination details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the Destination with the given ID from the database.
     *
     * @param  int $id The ID of the Destination to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the Destination by ID
        $destination = Destination::find($id);

        // Check if the Destination exists
        if (!$destination) {
            // If the Destination is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Destination not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the Destination
        $destination->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
