<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\RatingPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RatingPlanController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'RatingPlan'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'RatingPlan';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all rating plans from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched rating plans.
     */
    public function index()
    {
        // Retrieve all rating plan from the database
        $ratingplan = RatingPlan::with([
            'destinationRate:id,name,DestinationId,RatesTag,RoundingMethod,RoundingDecimals,MaxCost,MaxCostStrategy',
        ])->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ratingplan,
            'message' => 'Successfully fetched all rating plan'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created RatingPlan resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new RatingPlan record in the database, and returning a
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
                'DestinationRatesId' => 'required|exists:destination_rates,id',
                'TimingTag' => 'required|string',
                'Weight' => 'required|numeric',
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

        // Create a new RatingPlan record with validated data
        $data = RatingPlan::create($validated);

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
     * Update the specified RatingPlan resource in storage.
     *
     * This method retrieves the RatingPlan with the given ID, checks if it exists,
     * validates the incoming request data, and updates the RatingPlan record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the RatingPlan to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Find the RatingPlan with the given ID
        $ratingPlan = RatingPlan::find($id);

        // Check if the RatingPlan exists
        if (!$ratingPlan) {
            // If the RatingPlan is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Rating plan not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string',
                'DestinationRatesId' => 'exists:destination_rates,id',
                'TimingTag' => 'string',
                'Weight' => 'numeric',
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

        // Update the RatingPlan record with validated data
        $ratingPlan->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ratingPlan,
            'message' => 'Successfully updated Rating plan',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function show($id)
    {
        // Find the RatingPlan with the given ID
        $ratingPlan = RatingPlan::find($id);

        // Check if the RatingPlan exists
        if (!$ratingPlan) {
            // If RatingPlan is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Rating plan not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with RatingPlan details
        $response = [
            'status' => true,
            'data' => ($ratingPlan) ? $ratingPlan : '', // Include RatingPlan details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the RatingPlan details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the RatingPlan with the given ID from the database.
     *
     * @param  int $id The ID of the RatingPlan to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the RatingPlan by ID
        $ratingPlan = RatingPlan::find($id);

        // Check if the RatingPlan exists
        if (!$ratingPlan) {
            // If the Rating plan is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Rating plan not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the RatingPlan
        $ratingPlan->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
