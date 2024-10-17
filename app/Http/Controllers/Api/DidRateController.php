<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidRateChart;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class DidRateController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Did rate'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'did_rate';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all DID rates from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched DID rates.
     */
    public function index(Request $request)
    {
        // Retrieve all DID rates from the database
        $rates = DidRateChart::with(['vendor']);

        // Execute the query to fetch DID rates
        $rates = $rates->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $rates,
            'message' => 'Successfully fetched all DID rates'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created did rate resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new did rate record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'vendor_id' => 'required|exists:did_vendors,id',
                'rate_type' => 'required|in:random,blocks',
                'rate' => 'required|numeric|between:0,9999999.99',
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

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Create a new group record with validated data
        $data = DidRateChart::create($validated);

        // Log the action and type
        accessLog($action, $type, $validated, $userId);

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
     * Update the specified did rate resource in storage.
     *
     * This method retrieves the did rate with the given ID, checks if it exists,
     * validates the incoming request data, and updates the did rate record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the did rate to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the did rate with the given ID
        $didRateChart = DidRateChart::find($id);

        // Check if the did rate exists
        if (!$didRateChart) {
            // If the did rate is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Did rate not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'vendor_id' => 'exists:did_vendors,id',
                'rate_type' => 'in:random,blocks',
                'rate' => 'numeric|between:0,9999999.99',
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

        // Call the compareValues function to generate a formatted description based on the did rate and validated data
        $formattedDescription = compareValues($didRateChart, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Update the did rate record with validated data
        $didRateChart->update($validated);

        // Log the action and type
        accessLog($action, $type, $formattedDescription, $userId);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $didRateChart,
            'message' => 'Successfully updated did rate',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the did rate with the given ID from the database.
     *
     * @param  int $id The ID of the did rate to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the did rate by ID
        $didRateChart = DidRateChart::find($id);

        // Check if the did rate exists
        if (!$didRateChart) {
            // If the did rate is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Did rate not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the did rate
        $didRateChart->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }


    public function show($id, $rateType)
    {
        if (empty($rateType)) {
            $rateChart = DidRateChart::where('vendor_id', $id)->get();
        } else {
            $rateChart =  DidRateChart::where('rate_type', $rateType)->where('vendor_id', $id)->first();
        }
        // Find the user with the given ID

        if (!$rateChart) {
            // If user is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'ratechart not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with user data
        $response = [
            'status' => true,
            'data' => ($rateChart) ? $rateChart : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with user data and success message
        return response()->json($response, Response::HTTP_OK);
    }
}
