<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidVendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DidVendorController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Vendor'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Vendor';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all DID Vendors from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched DID Vendors.
     */
    public function index(Request $request)
    {
        // Retrieve all DID Vendors from the database
        $vendors = DidVendor::with(['rates']);

        // Execute the query to fetch DID Vendors
        $vendors = $vendors->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $vendors,
            'message' => 'Successfully fetched all DID Vendors'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Vendor resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new vendor record in the database, and returning a
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
                'vendor_name' => 'required|unique:did_vendors,vendor_name',
                'username' => 'required|string',
                'token' => 'required|string',
                // 'status' => 'required|in:active,inactive',
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
        $validated['status'] = 'inactive';

        // Begin a database transaction
        DB::beginTransaction();

        // Defining action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Create a new group record with validated data
        $data = DidVendor::create($validated);

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
     * Update the specified vendor resource in storage.
     *
     * This method retrieves the vendor with the given ID, checks if it exists,
     * validates the incoming request data, and updates the vendor record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the vendor to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the vendor with the given ID
        $vendor = DidVendor::find($id);

        // Check if the vendor exists
        if (!$vendor) {
            // If the vendor is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'vendor not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'vendor_name' => 'unique:did_vendors,vendor_name,' . $id,
                'username' => 'string',
                'token' => 'string',
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
            $vendors = DidVendor::where('status', 'active')->get();

            // $vendors is not empty
            if (!$vendors->isEmpty()) {
                // Update status of all active vendors to inactive directly using the query
                DidVendor::where('status', 'active')->update(['status' => 'inactive']);
            }
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Call the compareValues function to generate a formatted description based on the vendor and validated data
        $formattedDescription = compareValues($vendor, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Update the vendor record with validated data
        $vendor->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $vendor,
            'message' => 'Successfully updated vendor',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }


    public function show($id)
    {
        // Find the vendor with the given ID
        $vendor = DidVendor::find($id);

        // Check if the vendor exists
        if (!$vendor) {
            // If vendor is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'Vendor not found'
            ];
            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with vendor details
        $response = [
            'status' => true,
            'data' => ($vendor) ? $vendor : '', // Include vendor details if found
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response containing the vendor details
        return response()->json($response, Response::HTTP_OK);
    }




    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the vendor with the given ID from the database.
     *
     * @param  int $id The ID of the vendor to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the vendor by ID
        $vendor = DidVendor::find($id);

        // Check if the vendor exists
        if (!$vendor) {
            // If the vendor is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Did vendor not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the vendor
        $vendor->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
