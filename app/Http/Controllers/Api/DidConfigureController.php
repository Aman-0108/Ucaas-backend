<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DidConfigure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DidConfigureController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'DidConfigure'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'DidConfigure';
    }

    /**
     * Store a newly created resource in storage.
     *
     * This method validates the incoming request data, creates a new didConfigure record in the database,
     * and returns a JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request $request The incoming HTTP request containing the didConfigure data.
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
                'did_id' => 'required|exists:did_details,id',
                'usages' => 'required|string',
                'action' => 'required|string',
                'forward' => 'required|in:disabled,pstn,direct',
                'forward_to' => 'string|nullable',
                'record' => 'required|boolean',
                'hold_music' => 'required|string',
                'status' => 'boolean',
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

        // Define the action and type for creating UID
        $action = 'create';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        createUid($action, $type, $validated, $userId);

        // Create a new didConfigure record with the validated data
        $data = DidConfigure::create($validated);

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
     * Display the did configure record with the specified ID.
     *
     * @param int $id The ID of the did configure record to be displayed.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the did configure record data.
     */
    public function show($id)
    {
        // Find the did configure record by ID
        $didConfigure = DidConfigure::find($id);

        // Check if the did configure record exists
        if (!$didConfigure) {
            // If the did configure record is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Did configure record not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($didConfigure) ? $didConfigure : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Update a did configure record by ID.
     *
     * @param Request $request The HTTP request object containing the updated data.
     * @param int $id The ID of the did configure record to be updated.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the update operation.
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                // Validation rules for each field
                'usages' => 'string',
                'action' => 'string',
                'forward' => 'in:disabled,pstn,direct',
                'forward_to' => 'string|nullable',
                'record' => 'boolean',
                'hold_music' => 'string',
                'status' => 'boolean',
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

        // Find the didConfigure with the given ID
        $didConfigure = DidConfigure::find($id);

        // Call the compareValues function to generate a formatted description based on the didConfigure and validated data
        $formattedDescription = compareValues($didConfigure, $validated);

        // Define the action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        createUid($action, $type, $formattedDescription, $userId);

        // Update the didConfigure record with validated data
        $didConfigure->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $didConfigure,
            'message' => 'Successfully updated did configuration',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Destroy a did configure record by ID.
     *
     * @param int $id The ID of the did configure record to be deleted.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response indicating success or failure of the deletion operation.
     */
    public function destroy($id)
    {
        // Find the did configure record by ID
        $didConfigure = DidConfigure::find($id);

        // Check if the did configure record exists
        if (!$didConfigure) {
            // If the did configure record is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Did configuration not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the didConfigure record
        $didConfigure->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted did configuration'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
