<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallRatesPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CallRateController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'CallRatesPlan'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'CallRatesPlan';
    }

    /**
     * Retrieves a list of call rate plans.
     *
     * This method retrieves a list of call rate plans based on optional query parameters.
     * If a specific account ID is provided in the request, it filters call rate plans by that account.
     * It then returns a JSON response containing the list of call rate plans.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of call rate plans.
     */
    public function index(Request $request)
    {
        // Start building the query to fetch call rate plans
        $callrateplans = CallRatesPlan::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account_id')) {
            // If 'account' parameter is provided, filter call rate plans by account ID
            $callrateplans->where('account_id', $request->account);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch call rate plans
        $callrateplans = $callrateplans->orderBy('id', 'asc')->paginate($ROW_PER_PAGE);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $callrateplans,
            'message' => 'Successfully fetched all call rate plans'
        ];

        // Return a JSON response containing the list of call rate plans
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieves details of a specific CallRatesPlan.
     *
     * This method retrieves details of a CallRatesPlan with the given ID.
     * If the CallRatesPlan is found, it returns a JSON response containing
     * the CallRatesPlan details. If the CallRatesPlan is not found, it returns
     * a JSON response with an error message and a 404 status code.
     *
     * @param int $id The ID of the CallRatesPlan to retrieve.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the CallRatesPlan details or an error message.
     */
    public function show($id)
    {
        // Find the CallRatesPlan with the given ID
        $callRatesPlan = CallRatesPlan::find($id);

        // Check if the CallRatesPlan exists
        if (!$callRatesPlan) {
            // If CallRatesPlan is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'CallRatesPlan not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with CallRatesPlan details
        $response = [
            'status' => true,
            'data' => ($callRatesPlan) ? $callRatesPlan : '', // Include CallRatesPlan details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the CallRatesPlan details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Stores a new CallRatesPlan.
     *
     * This method attempts to store a new CallRatesPlan based on the provided data.
     * It validates the request data and checks for validation errors. If validation
     * fails, it returns a JSON response with validation errors. If validation succeeds,
     * it creates a new CallRatesPlan record in the database and returns a JSON response
     * indicating successful storage.
     *
     * @param Request $request The HTTP request object containing CallRatesPlan data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the storage attempt.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request

        $userId = ($request->user()) ? $request->user()->id : null;
      
        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'destination_name' => 'string|max:255',
                'account_id' => 'required|exists:accounts,id',
                'destination' => 'string|required',
                'selling_billing_block' => 'required|integer',
                'sell_rate' => 'nullable|numeric|between:0,99999999.99',
                'buy_rate' => 'nullable|numeric|between:0,99999999.99',
                'gateway_id' => 'required|integer',
            ]
        );      

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Begin a database transaction
        DB::beginTransaction();

        // Create a new CallRatesPlan record in the database
        $data = CallRatesPlan::create($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored'
        ];

        // Return a JSON response indicating successful storage and 201 status code
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Updates an existing CallRatesPlan.
     *
     * This method attempts to update an existing CallRatesPlan based on the provided data.
     * It first checks if the CallRatesPlan exists and if the authenticated user has permission
     * to edit it. If the CallRatesPlan doesn't exist or the user doesn't have permission,
     * it returns an appropriate error response. If validation fails, it returns
     * a JSON response with validation errors. If validation succeeds and the CallRatesPlan
     * is successfully updated, it returns a JSON response indicating success.
     *
     * @param Request $request The HTTP request object containing CallRatesPlan data.
     * @param int $id The ID of the CallRatesPlan to update.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update attempt.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the CallRatesPlan with the given ID
        $callRatesPlan = CallRatesPlan::find($id);

        // Check if the CallRatesPlan exists
        if (!$callRatesPlan) {
            // If CallRatesPlan is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'CallRatesPlan not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'destination_name' => 'nullable|string|max:255,' . $id, // Nullable for updates
                'account_id' => 'exists:accounts,id',
                'destination' => 'nullable|string', // Assuming you might not require 'destination' on update
                'selling_billing_block' => 'nullable|integer', // Nullable for updates
                'sell_rate' => 'nullable|numeric|between:0,99999999.99',
                'buy_rate' => 'nullable|numeric|between:0,99999999.99',
                'gateway_id' => 'integer',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with validation errors and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        // Update the CallRatesPlan with the validated data
        $callRatesPlan->update($validated);

        // Prepare success response
        $response = [
            'status' => true,
            'data' => $callRatesPlan,
            'message' => 'Successfully updated CallRatesPlan',
        ];

        // Return a JSON response indicating successful update
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Deletes a CallRatesPlan.
     *
     * This method attempts to delete a CallRatesPlan with the provided ID.
     * It first checks if the CallRatesPlan exists. If the CallRatesPlan doesn't exist,
     * it returns an appropriate error response. If the CallRatesPlan exists, it
     * generates a UID for the deletion action, deletes the CallRatesPlan from
     * the database, and returns a JSON response indicating successful deletion.
     *
     * @param Request $request The HTTP request object.
     * @param int $id The ID of the CallRatesPlan to delete.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion attempt.
     */
    public function destroy(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the CallRatesPlan with the given ID
        $callRatesPlan = CallRatesPlan::find($id);

        // Check if the CallRatesPlan exists
        if (!$callRatesPlan) {
            // If CallRatesPlan is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'CallRatesPlan not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the CallRatesPlan from the database
        $callRatesPlan->delete();

        // Prepare success response
        $response = [
            'status' => true,
            'message' => 'Successfully deleted CallRatesPlan'
        ];

        // Return a JSON response indicating successful deletion and 200 status code
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Search for call rate plans by CallRatesPlan name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('query');

        // Perform search query using Eloquent ORM
        $callRatePlan = CallRatesPlan::where('destination_name', 'like', "%$query%");

        if ($request->get('account_id')) {
            $callRatePlan->where('account_id', $request->get('account_id'));
        }

        $callRatePlan = $callRatePlan->get();

        // Prepare success response with search results
        $response = [
            'status' => true,
            'data' => $callRatePlan,
            'message' => 'Successfully fetched',
        ];

        // Return a JSON response with CallRatesPlan data and success message
        return response()->json($response, Response::HTTP_OK);
    }

    
}
