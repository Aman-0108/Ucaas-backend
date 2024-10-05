<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IvrMaster;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IvrmasterController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'IVR_MASTER'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'IVR_MASTER';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all ivrMaster from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched ivrMaster.
     */
    public function index(Request $request)
    {
        $account_id = $request->user()->account_id;

        // Retrieve all ivrMaster from the database
        $ivrMaster = IvrMaster::with(['options']);

        // Filter by account_id if available
        if($account_id) {
            $ivrMaster->where('account_id', $account_id);
        }

        // Execute the query to fetch domains
        $ivrMaster = $ivrMaster->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ivrMaster,
            'message' => 'Successfully fetched all ivrMaster'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the ivrMaster with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the ivrMaster to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched ivrMaster.
     */
    public function show($id)
    {
        // Find the ivrMaster by ID
        $ivrMaster = IvrMaster::with(['options'])->find($id);

        // Check if the ivrMaster exists
        if (!$ivrMaster) {
            // If the ivrMaster is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'ivrMaster not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($ivrMaster) ? $ivrMaster : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created ivrMaster resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new ivrMaster record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $accountId = $request->user()->account_id;

        if($accountId == null) {
            $response = [
                'status' => false,
                'message' => 'Account not found'
            ];
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        $request->merge(['account_id' => $accountId]);

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'ivr_name' => 'required|string|max:255',
                'account_id' => 'required|exists:accounts,id',
                'ivr_type' => 'required|in:1,0',
                'greet_long' => 'nullable|string|max:255',
                'greet_short' => 'nullable|string|max:255',
                'invalid_sound' => 'nullable|string|max:255',
                'exit_sound' => 'nullable|string|max:255',
                'confirm_macro' => 'nullable|string|max:255',
                'confirm_key' => 'nullable|string|max:255',
                'tts_engine' => 'nullable|string|max:255',
                'tts_voice' => 'nullable|string|max:255',
                'confirm_attempts' => 'nullable|integer',
                'timeout' => 'nullable|integer',
                'inter_digit_timeout' => 'nullable|integer',
                'max_failures' => 'nullable|integer',
                'max_timeouts' => 'nullable|integer',
                'digit_len' => 'nullable|integer',
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

        // Create a new ivrMaster record with validated data
        $data = IvrMaster::create($validated);

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
     * Update the specified ivrMaster resource in storage.
     *
     * This method retrieves the ivrMaster with the given ID, checks if it exists,
     * validates the incoming request data, and updates the ivrMaster record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the ivrMaster to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the ivrMaster with the given ID
        $ivrMaster = IvrMaster::find($id);

        // Check if the ivrMaster exists
        if (!$ivrMaster) {
            // If the ivrMaster is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'ivrMaster not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'ivr_name' => 'string|max:255',
                'ivr_type' => 'required|in:1,0',
                'greet_long' => 'nullable|string|max:255',
                'greet_short' => 'nullable|string|max:255',
                'invalid_sound' => 'nullable|string|max:255',
                'exit_sound' => 'nullable|string|max:255',
                'confirm_macro' => 'nullable|string|max:255',
                'confirm_key' => 'nullable|string|max:255',
                'tts_engine' => 'nullable|string|max:255',
                'tts_voice' => 'nullable|string|max:255',
                'confirm_attempts' => 'nullable|integer',
                'timeout' => 'nullable|integer',
                'inter_digit_timeout' => 'nullable|integer',
                'max_failures' => 'nullable|integer',
                'max_timeouts' => 'nullable|integer',
                'digit_len' => 'nullable|integer',
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
        $formattedDescription = compareValues($ivrMaster, $validated);

        // Defining action and type for access log
        $action = 'update';
        $type = $this->type;

        // Update the ivrMaster record with validated data
        $ivrMaster->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ivrMaster,
            'message' => 'Successfully updated ivrMaster',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified ivrMaster resource from storage.
     *
     * This method retrieves the ivrMaster with the given ID, checks if it exists,
     * and deletes the ivrMaster record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the ivrMaster to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the ivrMaster with the given ID
        $ivrMaster = IvrMaster::find($id);

        // Check if the ivrMaster exists
        if (!$ivrMaster) {
            // If the ivrMaster is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'ivrMaster not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the ivrMaster record
        $ivrMaster->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted ivrMaster'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
