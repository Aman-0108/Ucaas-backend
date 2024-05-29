<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dialplan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DialplanController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Dialplan'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Dialplan';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all dialplans from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched gateways.
     */
    public function index()
    {
        // Retrieve all dialplans from the database
        $dialplans = Dialplan::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $dialplans,
            'message' => 'Successfully fetched all dialplans'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the dialplan with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the dialplan to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched dialplan.
     */
    public function show($id)
    {
        // Find the dialplan by ID
        $dialplan = Dialplan::find($id);

        // Check if the gateway exists
        if (!$dialplan) {
            // If the dialplan is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Dialplan not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($dialplan) ? $dialplan : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * This method validates the incoming request data, creates a new dialplan record in the database,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing the dialplan data.
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
                'type' => 'in:Local,Inbound,Outbound|nullable',
                'country_code' => 'required|string',
                'destination' => 'required|string',
                'context' => 'string|nullable',
                'dial_action' => 'required|string',
                'caller_Id_name' => 'string|nullable',
                'caller_Id_number' => 'string|nullable',
                'caller_Id_name_prefix' => 'string|nullable',
                'usage' => 'in:voice,fax,text,emergency',
                'domain' => 'required|string',
                'order' => 'string|nullable',
                'destination_status' => 'required|boolean',
                'description' => 'string|nullable',

                'account_id' => 'required|numeric|exists:accounts,id',
                'user' => 'numeric|exists:users,id|nullable',
                'group' => 'numeric|nullable',
                'record' => 'string|nullable',
                'holdMusic' => 'string|nullable',
                'action' => 'string|nullable',
                'created_by' => 'string|exists:users,id|nullable'
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

        // Create a new dialplan record with the validated data
        $data = Dialplan::create($validated);

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
     * Update the specified dialplan resource in storage.
     *
     * This method retrieves the dialplan with the given ID, checks if it exists,
     * validates the incoming request data, and updates the dialplan record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the dialplan to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the dialplan with the given ID
        $dialplan = Dialplan::find($id);

        // Check if the dialplan exists
        if (!$dialplan) {
            // If the dialplan is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Dialplan not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'type' => 'in:Local,Inbound,Outbound|nullable',
                'country_code' => 'string',
                'destination' => 'string',
                'context' => 'string|nullable',
                'dial_action' => 'string',
                'caller_Id_name' => 'string|nullable',
                'caller_Id_number' => 'string|nullable',
                'caller_Id_name_prefix' => 'string|nullable',
                'usage' => 'in:voice,fax,text,emergency',
                'domain' => 'string',
                'order' => 'string|nullable',
                'destination_status' => 'boolean',
                'description' => 'string|nullable',
                'account_id' => 'numeric|exists:accounts,id',
                'user' => 'numeric|exists:users,id|nullable',
                'group' => 'numeric|nullable',
                'record' => 'string|nullable',
                'holdMusic' => 'string|nullable',
                'dialplan_xml' => 'string',
                'action' => 'string|nullable',
                'created_by' => 'string|exists:users,id|nullable'
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

        if(isset($validated['dialplan_xml'])) {
            $validated['dialplan_xml'] = trim($validated['dialplan_xml']);
        }

        // Call the compareValues function to generate a formatted description based on the dialplan and validated data
        $formattedDescription = compareValues($dialplan, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        $validated['uid_no'] = createUid($action, $type, $formattedDescription, $userId);

        // Update the dialplan record with validated data
        $dialplan->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $dialplan,
            'message' => 'Successfully updated dialplan',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * This method deletes the dialplan with the given ID from the database.
     *
     * @param  int $id The ID of the dialplan to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the dialplan by ID
        $dialplan = Dialplan::find($id);

        // Check if the dialplan exists
        if (!$dialplan) {
            // If the dialplan is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Dialplan not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the dialplan
        $dialplan->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted dialplan'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
