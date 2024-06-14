<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillingAddressController extends Controller
{
    /**
     * Retrieve all billing addresses.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the retrieved billing addresses or an error message.
     */
    public function index()
    {
        // Retrieve all billing addresses from the database
        $billingAddresses = BillingAddress::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $billingAddresses,
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new billing address.
     *
     * @param \Illuminate\Http\Request $request The incoming request.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the store operation.
     */
    public function store(Request $request)
    {
        // Perform validation on the request data     
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'fullname' => 'required|string',
                'contact_no' => 'required|string',
                'email' => 'required|string',
                'address' => 'required|string',
                'zip' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'country' => 'required|string'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a 403 Forbidden response with validation errors
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

        // Create a new Billing Address with the validated data
        $data = BillingAddress::create($validated);

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
     * Retrieves a billing address by ID.
     *
     * @param int $id The ID of the billing address to be retrieved.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the retrieved billing address or an error message.
     */
    public function show($id)
    {
        // Find the billing address by its ID
        $billingAddress = BillingAddress::find($id);

        // Check if the billing address exists
        if (!$billingAddress) {
            // If the billing address is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Billing address not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($billingAddress) ? $billingAddress : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Update a billing address by ID.
     *
     * @param \Illuminate\Http\Request $request The incoming request.
     * @param int $id The ID of the billing address to be updated.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update operation.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the billing address with the given ID
        $billingAddress = BillingAddress::find($id);

        // Check if the billing address exists
        if (!$billingAddress) {
            // If the billing address is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Billing address not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'fullname' => 'string|nullable',
                'contact_no' => 'string|nullable',
                'email' => 'string|nullable',
                'address' => 'string|nullable',
                'zip' => 'string|nullable',
                'city' => 'string|nullable',
                'state' => 'string|nullable',
                'country' => 'string|nullable'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Update the billing address record with validated data
        $billingAddress->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $billingAddress,
            'message' => 'Successfully updated billing address',
        ];

        // Return a JSON response indicating successful update with response code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Deletes a billing address by ID.
     *
     * @param int $id The ID of the billing address to be deleted.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the billing address by its ID
        $billingAddress = BillingAddress::find($id);

        // Check if the billing address exists
        if (!$billingAddress) {
            // If the billing address is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Billing address not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the billing address
        $billingAddress->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Adds data to the billing address table for a given account.
     *
     * @param int $accountId The ID of the account for which the data is being added.
     * @param array $inputs An array containing the data to be added. Should include fields like 'street', 'city', 'state', etc.
     * @return \Illuminate\Database\Eloquent\Model|mixed The response from the database operation.
     */
    public function addData($accountId, $inputs)
    {
        // Set the 'account_id' field in the inputs array to the provided account ID.
        $inputs['account_id'] = $accountId;

        // Create a new record in the BillingAddress model/table using the provided inputs.
        $response = BillingAddress::create($inputs);

        // Return the response from the database operation.
        return $response;
    }
}
