<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\DestinationRate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DestinationRateController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'DestinationRate'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'DestinationRate';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all destination rates from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched destination rates.
     */
    public function index()
    {
        // Retrieve all destination rates from the database
        $destinationRates = DestinationRate::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $destinationRates,
            'message' => 'Successfully fetched all destination rates'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Destination Rate resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new DestinationRate record in the database, and returning a
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
                'DestinationId' => 'required|exists:destinations,id',
                'RatesTag' => 'required|exists:rates,id',
                'RoundingMethod' => 'required|string',
                'RoundingDecimals' => 'required|numeric',
                'MaxCost' => 'required|numeric|between:0,9999999.99',
                'MaxCostStrategy' => 'required|string',
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

        // Create a new DestinationRate record with validated data
        $data = DestinationRate::create($validated);

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
     * Update the specified DestinationRate resource in storage.
     *
     * This method retrieves the DestinationRate with the given ID, checks if it exists,
     * validates the incoming request data, and updates the DestinationRate record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the DestinationRate to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Find the DestinationRate with the given ID
        $destinationRate = DestinationRate::find($id);

        // Check if the DestinationRate exists
        if (!$destinationRate) {
            // If the DestinationRate is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'DestinationRate not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string',
                'DestinationId' => 'exists:destinations,id',
                'RatesTag' => 'exists:rates,id',
                'RoundingMethod' => 'string',
                'RoundingDecimals' => 'numeric',
                'MaxCost' => 'numeric|between:0,9999999.99',
                'MaxCostStrategy' => 'string',
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

        // Update the DestinationRate record with validated data
        $destinationRate->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $destinationRate,
            'message' => 'Successfully updated Destination Rate',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function show($id)
    {
        // Find the DestinationRate with the given ID
        $destinationRate = DestinationRate::find($id);

        // Check if the DestinationRate exists
        if (!$destinationRate) {
            // If DestinationRate is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Destination rate not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with DestinationRate details
        $response = [
            'status' => true,
            'data' => ($destinationRate) ? $destinationRate : '', // Include DestinationRate details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the DestinationRate details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the DestinationRate with the given ID from the database.
     *
     * @param  int $id The ID of the DestinationRate to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the DestinationRate by ID
        $destinationRate = DestinationRate::find($id);

        // Check if the DestinationRate exists
        if (!$destinationRate) {
            // If the DestinationRate is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'DestinationRate not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the DestinationRate
        $destinationRate->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
