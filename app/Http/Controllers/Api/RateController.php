<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RateController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Rate'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Rate';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all rates from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched rates.
     */
    public function index()
    {
        // Retrieve all rates from the database
        $rates = Rate::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $rates,
            'message' => 'Successfully fetched all rates'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Rate resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new Rate record in the database, and returning a
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
                'ConnectFee' => 'required|numeric',
                'Rate' => 'required|numeric|between:0,9999999.9999',
                'RateUnit' => 'required|string',
                'RateIncrement' => 'required|string',
                'GroupIntervalStart' => 'required|string',
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

        // Create a new Rate record with validated data
        $data = Rate::create($validated);

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
     * Update the specified Rate resource in storage.
     *
     * This method retrieves the Rate with the given ID, checks if it exists,
     * validates the incoming request data, and updates the Rate record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the Rate to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Find the Rate with the given ID
        $rate = Rate::find($id);

        // Check if the Rate exists
        if (!$rate) {
            // If the Rate is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Rate not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string',
                'ConnectFee' => 'numeric',
                'Rate' => 'numeric|between:0,9999999.9999',
                'RateUnit' => 'string',
                'RateIncrement' => 'string',
                'GroupIntervalStart' => 'string',
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

        // Update the Rate record with validated data
        $rate->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $rate,
            'message' => 'Successfully updated Rate',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function show($id)
    {
        // Find the Rate with the given ID
        $rate = Rate::find($id);

        // Check if the Rate exists
        if (!$rate) {
            // If Rate is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Rate not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with Rate details
        $response = [
            'status' => true,
            'data' => ($rate) ? $rate : '', // Include Rate details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the Rate details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the Rate with the given ID from the database.
     *
     * @param  int $id The ID of the Rate to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the Rate by ID
        $rate = Rate::find($id);

        // Check if the Rate exists
        if (!$rate) {
            // If the Rate is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Rate not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the Rate
        $rate->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
