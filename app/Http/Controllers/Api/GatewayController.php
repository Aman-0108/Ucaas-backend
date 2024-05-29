<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GatewayController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Gateway'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Gateway';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all gateways from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched gateways.
     */
    public function index(Request $request)
    {
        // Retrieve all gateways from the database
        $gateways = Gateway::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter gateways by account ID
            $gateways->where('account_id', $request->account);
        }

        // Execute the query to fetch domains
        $gateways = $gateways->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $gateways,
            'message' => 'Successfully fetched all gateways'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the gateway with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the gateway to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched gateway.
     */
    public function show($id)
    {
        // Find the gateway by ID
        $gateway = Gateway::find($id);

        // Check if the gateway exists
        if (!$gateway) {
            // If the gateway is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Gateway not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($gateway) ? $gateway : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * This method validates the incoming request data, creates a new gateway record in the database,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing the gateway data.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the result of the store operation.
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'account_id' => 'required|exists:accounts,id',
                'uid_no' => 'exists:uids,uid_no',
                'name' => 'required|unique:gateways,name',
                'username' => 'required|string',
                'password' => 'required|string',
                'proxy' => 'required|ip',
                'expireseconds' => 'required|digits_between:1,20',
                'register' => 'required|string',
                'profile' => 'required|string',
                'status' => 'required|in:E,D',
                'description' => 'required|string',
                'retry' => 'numeric',
                'fromUser' => 'required|string|min:3',
                'fromDomain' => 'required|string|min:5',
                'realm' => 'required|string|min:2',
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

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $validated, $userId);

        // Create a new gateway record with the validated data
        $data = Gateway::create($validated);

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
     * Update the specified resource in storage.
     *
     * This method updates the gateway with the given ID using the provided request data,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing the update data.
     * @param  int $id The ID of the gateway to be updated.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the result of the update operation.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the gateway by ID
        $gateway = Gateway::find($id);

        // Check if the gateway exists
        if (!$gateway) {
            // If the gateway is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Gateway not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'status' => 'in:E,D',
                'account_id' => 'exists:accounts,id',
                'name' => 'unique:gateways,name,' . $id,
                'username' => 'string|min:5',
                'password' => 'string|min:5',
                'proxy' => 'ip',
                'expireseconds' => 'digits_between:1,20',
                'register' => 'string',
                'profile' => 'string',
                'status' => 'in:E,D',
                'description' => 'string',
                'retry' => 'numeric',
                'fromUser' => 'string|min:3',
                'fromDomain' => 'string',
                'realm' => 'string',
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
        $formattedDescription = compareValues($gateway, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $formattedDescription, $userId);

        // Update the gateway with the validated data
        $gateway->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $gateway,
            'message' => 'Successfully updated Gateway',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the gateway with the given ID from the database.
     *
     * @param  int $id The ID of the gateway to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the gateway by ID
        $gateway = Gateway::find($id);

        // Check if the gateway exists
        if (!$gateway) {
            // If the gateway is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Gateway not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the gateway
        $gateway->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted gateway'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
