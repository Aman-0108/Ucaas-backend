<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InboundRouting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InboundRoutingController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Inbound routing'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Inbound_Routing';
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
        // Retrieve all inbound routings from the database
        $inboundRoutings = InboundRouting::query();

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter inbound routings by account ID
            $inboundRoutings->where('account_id', $request->account);
        }

        // Execute the query to fetch inbound routings
        $inboundRoutings = $inboundRoutings->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $inboundRoutings,
            'message' => 'Successfully fetched all inbound routings'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the inbound routing with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the inbound routing to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched inbound routing.
     */
    public function show($id)
    {
        // Find the Inbound routing by ID
        $inboundRouting = InboundRouting::find($id);

        // Check if the inbound routing exists
        if (!$inboundRouting) {
            // If the inbound routing is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Inbound Routing not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($inboundRouting) ? $inboundRouting : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created inbound routing resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new inbound routing record in the database, and returning a
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
                'name' => 'required|string|unique:inbound_routings,name',
                'destination_number' => 'required|string',
                'action' => 'string|nullable',
                'type' => 'in:ring group,individual,queue',
                'other' => 'in:voice Mail,hangup recording',
                'caller_id_number_prefix' => 'string|nullable',
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

        // Create a new inbound routing record with validated data
        $data = InboundRouting::create($validated);

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
     * Update the specified inbound routing resource in storage.
     *
     * This method retrieves the inbound routing with the given ID, checks if it exists,
     * validates the incoming request data, and updates the inbound routing record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the inbound routing to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the inbound routing with the given ID
        $inboundRouting = InboundRouting::find($id);

        // Check if the inbound routing exists
        if (!$inboundRouting) {
            // If the inbound routing is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Inbound routing not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|unique:inbound_routings,name,' . $id,
                'destination_number' => 'string',
                'action' => 'string|nullable',
                'type' => 'in:ring group,individual,queue',
                'other' => 'in:voice Mail,hangup recording',
                'caller_id_number_prefix' => 'string|nullable',
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

        // Call the compareValues function to generate a formatted description based on the inbound routing and validated data
        $formattedDescription = compareValues($inboundRouting, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Update the inbound routing record with validated data
        $inboundRouting->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $inboundRouting,
            'message' => 'Successfully updated inbound routing',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified inbound routing resource from storage.
     *
     * This method retrieves the inbound routing with the given ID, checks if it exists,
     * and deletes the inbound routing record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the inbound routing to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the inbound routing with the given ID
        $inboundRouting = InboundRouting::find($id);

        // Check if the inbound routing exists
        if (!$inboundRouting) {
            // If the inbound routing is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Inbound routing not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the inbound routing record
        $inboundRouting->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted inbound routing'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
