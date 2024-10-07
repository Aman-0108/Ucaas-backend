<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IvrOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IvroptionsController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'IVR'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'IVR';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all ivrOptions from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched ivrOptions.
     */
    public function index(Request $request)
    {
        // Retrieve all ivrOptions from the database
        $ivrOptions = IvrOptions::query();

        // Apply filters if provided
        if ($request->has('ivr_id')) {
            $ivrOptions->where('ivr_id', $request->input('ivr_id'));
        }

        // Execute the query to fetch domains
        $ivrOptions = $ivrOptions->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ivrOptions,
            'message' => 'Successfully fetched all ivrOptions'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the IvrOptions with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the IvrOptions to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched IvrOptions.
     */
    public function show($id)
    {
        // Find the IvrOptions by ID
        $ivrOptions = IvrOptions::find($id);

        // Check if the IvrOptions exists
        if (!$ivrOptions) {
            // If the IvrOptions is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'IvrOptions not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($ivrOptions) ? $ivrOptions : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created IvrOptions resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new IvrOptions record in the database, and returning a
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
                'ivr_id' => 'required|exists:ivr_masters,id',
                'option_key' => 'required|string',
                'action_name' => 'required|string',
                'action_id' => 'required|string',
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

        // Create a new IvrOptions record with validated data
        $data = IvrOptions::create($validated);

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
     * Update the specified IvrOptions resource in storage.
     *
     * This method retrieves the IvrOptions with the given ID, checks if it exists,
     * validates the incoming request data, and updates the IvrOptions record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the IvrOptions to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the IvrOptions with the given ID
        $ivrOptions = IvrOptions::find($id);

        // Check if the IvrOptions exists
        if (!$ivrOptions) {
            // If the IvrOptions is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'IvrOptions not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'option_key' => 'string',
                'action_name' => 'string',
                'action_id' => 'string',
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

        // Call the compareValues function to generate a formatted description based on the gateway and validated data
        $formattedDescription = compareValues($ivrOptions, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Update the IvrOptions record with validated data
        $ivrOptions->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ivrOptions,
            'message' => 'Successfully updated IvrOptions',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified IvrOptions resource from storage.
     *
     * This method retrieves the IvrOptions with the given ID, checks if it exists,
     * and deletes the IvrOptions record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the IvrOptions to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the IvrOptions with the given ID
        $ivrOptions = IvrOptions::find($id);

        // Check if the IvrOptions exists
        if (!$ivrOptions) {
            // If the IvrOptions is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'IvrOptions not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the IvrOptions record
        $ivrOptions->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted IvrOptions'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
