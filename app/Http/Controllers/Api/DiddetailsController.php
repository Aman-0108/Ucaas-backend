<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidDetail;
use App\Models\DidOrderStatus;
use App\Models\DidVendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DiddetailsController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'did details'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'did_details';
    }

    public function index(Request $request)
    {
        // Define a base query for did's
        $query = DidDetail::with(['dialplan','configuration']);

        // Apply filtering based on request parameters
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Fetch the did'sbased on the query
        $dialplans = $query->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $dialplans,
            'message' => 'Successfully fetched all dids'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created did details resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new did details record in the database, and returning a
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
                'account_id' => 'required|exists:accounts,id',
                'transaction_id' => 'required|exists:payments,transaction_id',
                'domain' => 'required|string',
                'did' => 'required|string',
                'didSummary' => 'required|string',
                'tollfreePrefix' => 'required|string',
                'npanxx' => 'required|string',
                'ratecenter' => 'required|string',
                'thinqTier' => 'required|string',
                'currency' => 'required|string',
                'price' => 'required|numeric|between:0,9999999.99',
                'created_by' => 'required'
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

        // Create a new did details record with validated data
        $data = DidDetail::create($validated);

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
     * Update the specified did details resource in storage.
     *
     * This method retrieves the did details with the given ID, checks if it exists,
     * validates the incoming request data, and updates the did details record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the did details to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the did details with the given ID
        $didDetail = DidDetail::find($id);

        // Check if the did details exists
        if (!$didDetail) {
            // If the did details is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Did details not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'domain' => 'string',
                'did' => 'string',
                'didSummary' => 'string',
                'tollfreePrefix' => 'string',
                'npanxx' => 'string',
                'ratecenter' => 'string',
                'thinqTier' => 'string',
                'currency' => 'string',
                'price' => 'numeric|between:0,9999999.99',
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

        // Call the compareValues function to generate a formatted description based on the did details and validated data
        $formattedDescription = compareValues($didDetail, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Update the did details record with validated data
        $didDetail->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $didDetail,
            'message' => 'Successfully updated did details',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the did details with the given ID from the database.
     *
     * @param  int $id The ID of the did details to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the did details by ID
        $didDetail = DidDetail::find($id);

        // Check if the did details exists
        if (!$didDetail) {
            // If the did details is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'did details not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Get the user type and account holder ID
        $usertype = Auth::user()->usertype;

        // Get the user type and account holder ID
        $accountHolderId = Auth::user()->account_id;

        // Check if the user has permission to disconnect the did details
        if($usertype !== 'Company' || $accountHolderId !== $didDetail->account_id) {
            return response()->json(['success' => false, 'message' => `You don't have permission to perform this action.`], 403);
        }

        // Retrieve the vendor ID based on the order ID
        $OrderStatus = DidOrderStatus::where('order_id', $didDetail->orderid)->first();

        if (!$OrderStatus) {
            return response()->json(['success' => false, 'message' => 'Vendor details not found.'], 404);
        }

        $vendorId = $OrderStatus->vendor_id;

        // Find the vendor based on the vendor ID
        $didVendor = DidVendor::find($vendorId);

        $authToken = $didVendor->token;
        $username = $didVendor->username;

        if(!$authToken || !$username) {
            return response()->json(['success' => false, 'message' => 'Vendor details not found.'], 404);
        }

        $srcData = [
            'account_id' => 14642,
            // 'dids' => $didDetail->did,
            'dids' => [
                '8885156451'
            ],
            'authToken' => $authToken,
            'username' => $username
        ];

        $requestObj = new Request($srcData);

        $vendorResponse = $this->didDisconnect($requestObj);

        // Decode the JSON response
        $decodedResponse = json_decode($vendorResponse, true);

        // Check the response code and message
        if (isset($decodedResponse['code']) && $decodedResponse['code'] == 403) {
            // Forbidden: User does not have permission
            $message = $decodedResponse['message'] ?: 'Access denied.';
            return response()->json(['success' => false, 'message' => $message], 403);
        } else if (isset($decodedResponse['success']) && $decodedResponse['success'] === true) {
            // Delete the did details
            // $didDetail->delete();
            // API call was successful
            return response()->json(['success' => true, 'message' => 'DIDs successfully disconnected.'], 200);
        } else {
            // General failure
            $message = $decodedResponse['message'] ?: 'Failed to disconnect DIDs.';
            return response()->json(['success' => false, 'message' => $message, 'data' => $decodedResponse], 402);
        }
    }

    public function didDisconnect(Request $request)
    {
        // Define the account ID
        $account_id = $request->account_id;

        // Retrieve the DIDs from the request
        $dids = $request->dids;

        // Define the URL for the API call
        $url = 'https://api.thinq.com/account/' . $account_id . '/origination/disconnect';

        // Retrieve the authentication token and username from the request
        $authToken = $request->authToken;

        // Convert the array to JSON format
        $username = $request->username;

        // Convert the array to JSON format
        $postData = json_encode([
            'dids' => $dids
        ]);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . base64_encode($username . ':' . $authToken),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }
}
