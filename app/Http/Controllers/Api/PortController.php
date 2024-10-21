<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use App\Models\Port;
use App\Rules\GlobalMobileNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PortController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Port'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Port';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all ports from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched ports.
     */
    public function index(Request $request)
    {        
        // Retrieve all ports from the database
        $ports = Port::query();

        $account_id = $request->user()->account_id;

        if($account_id) {
            $ports->where('account_id', $account_id);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $ports,
            'message' => 'Successfully fetched all ports'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Port resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new Port record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $userId = $request->user() ? $request->user()->id : null;
        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'fullname' => 'required|string|max:255',
                'company_name' => 'required|string|max:255',
                'billing_address' => 'required|string|max:255',
                'pin' => 'required|string|max:10',
                'carrier' => 'required|string|max:100',
                'account_number' => 'required|string',
                'phone_number' => ['required',  new GlobalMobileNumber],
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

        // Create a new Port record with validated data
        $data = Port::create($validated);

        $action = 'store';
        $type = $this->type;

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
     * Update the specified Port resource in storage.
     *
     * This method retrieves the Port with the given ID, checks if it exists,
     * validates the incoming request data, and updates the Port record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the Port to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        $userId = $request->user() ? $request->user()->id : null;

        // Find the Port with the given ID
        $port = Port::find($id);

        // Check if the Port exists
        if (!$port) {
            // If the Port is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Port not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'exists:accounts,id',
                'fullname' => 'string|max:255|nullable',
                'company_name' => 'string|max:255|nullable',
                'billing_address' => 'string|max:255|nullable',
                'pin' => 'string|max:10|nullable',
                'carrier' => 'string|max:100|nullable',
                'account_number' => 'string|nullable',
                'phone_number' => ['nullable',  new GlobalMobileNumber],
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

        // Update the Port record with validated data
        $port->update($validated);

        $action = 'update';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $port,
            'message' => 'Successfully updated Port',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    public function show($id)
    {
        // Find the Port with the given ID
        $port = Port::find($id);

        // Check if the Port exists
        if (!$port) {
            // If Port is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Port not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with Port details
        $response = [
            'status' => true,
            'data' => ($port) ? $port : '', // Include Port details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the Port details
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the Port with the given ID from the database.
     *
     * @param  int $id The ID of the Port to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the Port by ID
        $port = Port::find($id);

        // Check if the Port exists
        if (!$port) {
            // If the Port is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Port not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the Port
        $port->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
