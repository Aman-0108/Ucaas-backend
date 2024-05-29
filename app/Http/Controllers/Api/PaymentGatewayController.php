<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentGatewayController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Payment Gateway'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Payment_Gateway';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all paymentGateways from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched gateways.
     */
    public function index()
    {
        // Retrieve all paymentGateways from the database
        $paymentGateways = PaymentGateway::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $paymentGateways,
            'message' => 'Successfully fetched all paymentGateways'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the Payment Gateway with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the Payment Gateway to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched Payment Gateway.
     */
    public function show($id)
    {
        // Find the Payment Gateway by ID
        $paymentGateway = PaymentGateway::find($id);

        // Check if the gateway exists
        if (!$paymentGateway) {
            // If the Payment Gateway is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Payment Gateway not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($paymentGateway) ? $paymentGateway : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * This method validates the incoming request data, creates a new Payment Gateway record in the database,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing the Payment Gateway data.
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
                'name' => 'required|string|unique:payment_gateways,name',
                'username' => 'string|nullable',
                'password' => 'string|nullable',
                'api_key' => 'string|nullable',
                'api_secret' => 'string|nullable',
                // 'status' => 'in:active,inactive',
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
        $validated['status'] = 'inactive';

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        createUid($action, $type, $validated, $userId);

        // Create a new Payment Gateway record with the validated data
        $data = PaymentGateway::create($validated);

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
     * Update the specified Payment Gateway resource in storage.
     *
     * This method retrieves the Payment Gateway with the given ID, checks if it exists,
     * validates the incoming request data, and updates the Payment Gateway record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the Payment Gateway to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the Payment Gateway with the given ID
        $paymentGateway = PaymentGateway::find($id);

        // Check if the Payment Gateway exists
        if (!$paymentGateway) {
            // If the Payment Gateway is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Payment Gateway not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'name' => 'string|unique:payment_gateways,name,' . $id,
                'username' => 'string|nullable',
                'password' => 'string|nullable',
                'api_key' => 'string|nullable',
                'api_secret' => 'string|nullable',
                'status' => 'in:active,inactive',
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

        if ($request->has('status') && $request->status == 'active') {
            // check either any other vendor is active or not
            $gateways = PaymentGateway::where('status', 'active')->get();

            // $vendors is not empty
            if (!$gateways->isEmpty()) {                
                // Update status of all active vendors to inactive directly using the query
                PaymentGateway::where('status', 'active')->update(['status' => 'inactive']);
            }
        }


        // Retrieve the validated input
        $validated = $validator->validated();

        // Call the compareValues function to generate a formatted description based on the Payment Gateway and validated data
        $formattedDescription = compareValues($paymentGateway, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        createUid($action, $type, $formattedDescription, $userId);

        // Update the Payment Gateway record with validated data
        $paymentGateway->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $paymentGateway,
            'message' => 'Successfully updated Payment Gateway',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the Payment Gateway with the given ID from the database.
     *
     * @param  int $id The ID of the Payment Gateway to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the Payment Gateway by ID
        $paymentGateway = PaymentGateway::find($id);

        // Check if the Payment Gateway exists
        if (!$paymentGateway) {
            // If the Payment Gateway is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Payment Gateway not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the Payment Gateway
        $paymentGateway->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted Payment Gateway'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
