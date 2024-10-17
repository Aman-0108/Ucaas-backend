<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutboundRouting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OutboundRoutingController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Outbound routing'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Outbound_Routing';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all inbound routings from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched inbound routings.
     */
    public function index(Request $request)
    {
        // Retrieve all outbound routings from the database
        $outboundRoutings = OutboundRouting::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter outbound routings by account ID
            $outboundRoutings->where('account_id', $request->account);
        }

        // Execute the query to fetch outbound routings
        $outboundRoutings = $outboundRoutings->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $outboundRoutings,
            'message' => 'Successfully fetched all outbound routings'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the outbound routings with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the outbound routing to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched outbound routing.
     */
    public function show($id)
    {
        // Find the outbound routing by ID
        $outboundRouting = OutboundRouting::find($id);

        // Check if the outbound routing exists
        if (!$outboundRouting) {
            // If the outbound routing is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Outbound routing not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($outboundRouting) ? $outboundRouting : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created Outbound Routing resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new Outbound Routing record in the database, and returning a
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
                'primary_gateway' => 'required|string|unique:outbound_routings,primary_gateway',
                'aletrnate1_gateway' => 'string|nullable',
                'alternate2_gateway' => 'string|nullable',
                'prefix' => 'string|nullable'
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

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Create a new Outbound Routing record with validated data
        $data = OutboundRouting::create($validated);

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
     * Update the specified outbound routing resource in storage.
     *
     * This method retrieves the outbound routing with the given ID, checks if it exists,
     * validates the incoming request data, and updates the outbound routing record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the outbound routing to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the outbound Routing with the given ID
        $outboundRouting = OutboundRouting::find($id);

        // Check if the outbound Routing exists
        if (!$outboundRouting) {
            // If the outbound Routing is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Outbound routing not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'primary_gateway' => 'string|unique:outbound_routings,primary_gateway,' . $id,
                'aletrnate1_gateway' => 'string|nullable',
                'alternate2_gateway' => 'string|nullable',
                'prefix' => 'string|nullable'
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

        // Call the compareValues function to generate a formatted description based on the outbound routing and validated data
        $formattedDescription = compareValues($outboundRouting, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Update the outbound routing record with validated data
        $outboundRouting->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $outboundRouting,
            'message' => 'Successfully updated outbound routing.',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified outbound routing resource from storage.
     *
     * This method retrieves the outbound routing with the given ID, checks if it exists,
     * and deletes the outbound routing record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the outbound routing to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the outbound routing with the given ID
        $outboundRouting = OutboundRouting::find($id);

        // Check if the outbound routing exists
        if (!$outboundRouting) {
            // If the outbound routing is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Outbound routing not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the outbound routing record
        $outboundRouting->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted outbound routing'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
