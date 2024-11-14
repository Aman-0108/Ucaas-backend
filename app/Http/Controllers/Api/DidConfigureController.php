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
                'did_id' => 'required|exists:did_details,id',
                'usages' => 'required|string', // Validate 'usages' as an array
                // 'usages.*' => 'string', 
                'action' => 'required|string',
                'forward' => 'required|in:disabled,pstn,direct',
                'forward_to' => 'string|nullable',
                'record' => 'required|boolean',
                'hold_music' => 'required|string',
                'stick_agent_type' => 'nullable|in:last_spoken,longest_time',
                'stick_agent_expires' => 'numeric|between:1,99',
                'sticky_agent_enable' => 'boolean|nullable',
                'status' => 'boolean',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Convert the usages array to a comma-separated string
        // $validated['usages'] = implode(', ', $validated['usages']);

        // Begin a database transaction
        DB::beginTransaction();

        try {
            // Define the action and type
            $action = 'create';
            $type = $this->type;

            // Use updateOrCreate to either update an existing record or create a new one
            $data = DidConfigure::updateOrCreate(
                [
                    'did_id' => $validated['did_id'] // Use this as the criteria to find the existing record
                ],
                $validated // Attributes to update or create
            );

            // Log the action and type
            accessLog($action, $type, $validated, $userId);

            // Commit the database transaction
            DB::commit();

            // Prepare the response data
            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => 'Successfully stored'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();

            // Return a JSON response with error details
            return response()->json([
                'status' => false,
                'message' => 'Failed to store data',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            'data' => $didConfigure, // Ensure data is not null
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

        // Find the didConfigure with the given ID
        $didConfigure = DidConfigure::find($id);

        if (!$didConfigure) {
            // Handle case where the didConfigure record is not found
            return response()->json([
                'status' => false,
                'message' => 'Did configuration not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'usages' => 'string|nullable', // Validate 'usages' as an array and allow it to be null
                // 'usages.*' => 'string', // Optionally, validate each item in the 'usages' array as a string
                'action' => 'string|nullable',
                'forward' => 'in:disabled,pstn,direct|nullable',
                'forward_to' => 'string|nullable',
                'record' => 'boolean|nullable',
                'hold_music' => 'string|nullable',
                'stick_agent_type' => 'nullable|in:last_spoken,longest_time',
                'stick_agent_expires' => 'numeric|between:1,99',
                'sticky_agent_enable' => 'boolean|nullable',
                'status' => 'boolean|nullable',
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

        // Convert the usages array to a comma-separated string if it's present
        // if (isset($validated['usages'])) {
        //     $validated['usages'] = implode(', ', $validated['usages']);
        // }

        // Call the compareValues function to generate a formatted description based on the didConfigure and validated data
        $formattedDescription = compareValues($didConfigure, $validated);

        // Define the action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Update the didConfigure record with validated data
        $didConfigure->update($validated);

        // Call the accessLog function to log the action and type
        accessLog($action, $type, $formattedDescription, $userId);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $didConfigure,
            'message' => 'Successfully updated did configuration',
        ];

        // Return a JSON response indicating successful update with response code 200 (OK)
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
