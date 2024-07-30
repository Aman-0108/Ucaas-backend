<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\RatingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RatingProfileController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'RatingProfile'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'RatingProfile';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all rating profiles from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched rating profiles.
     */
    public function index()
    {
        // Retrieve all rating profiles from the database
        $ratingProfiles = RatingProfile::with([
            'ratingPlan:id,name'
        ])->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ratingProfiles,
            'message' => 'Successfully fetched all rating profiles'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created RatingProfile resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new RatingProfile record in the database, and returning a
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
                'Tenant' => 'required|string',
                'Category' => 'required|in:call,sms,data,custom',
                'Subject' => 'required|string',
                'ActivationTime' => 'nullable|date_format:Y-m-d H:i:s',
                'RatingPlanId' => 'required|exists:rating_plans,id',
                'RatesFallbackSubject' => 'string|nullable',
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

        // Create a new RatingProfile record with validated data
        $data = RatingProfile::create($validated);

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
     * Update the specified RatingProfile resource in storage.
     *
     * This method retrieves the RatingProfile with the given ID, checks if it exists,
     * validates the incoming request data, and updates the RatingProfile record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the RatingProfile to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Find the RatingProfile with the given ID
        $ratingProfile = RatingProfile::find($id);

        // Check if the RatingProfile exists
        if (!$ratingProfile) {
            // If the RatingProfile is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Rating profile not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'Tenant' => 'string',
                'Category' => 'in:call,sms,data,custom',
                'Subject' => 'string',
                'ActivationTime' => 'nullable|date_format:Y-m-d H:i:s',
                'RatingPlanId' => 'exists:rating_plans,id',
                'RatesFallbackSubject' => 'string|nullable',
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

        // Update the RatingProfile record with validated data
        $ratingProfile->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ratingProfile,
            'message' => 'Successfully updated rating profile',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function show($id)
    {
        // Find the RatingProfile with the given ID
        $ratingProfile = RatingProfile::find($id);

        // Check if the RatingProfile exists
        if (!$ratingProfile) {
            // If RatingProfile is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Rating profile not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with RatingProfile details
        $response = [
            'status' => true,
            'data' => ($ratingProfile) ? $ratingProfile : '', // Include RatingProfile details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the RatingProfile details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the RatingProfile with the given ID from the database.
     *
     * @param  int $id The ID of the RatingProfile to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the RatingProfile by ID
        $ratingProfile = RatingProfile::find($id);

        // Check if the RatingProfile exists
        if (!$ratingProfile) {
            // If the RatingProfile is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Rating profile not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the RatingProfile
        $ratingProfile->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
