<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Group'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Group';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all groups from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched groups.
     */
    public function index(Request $request)
    {
        // Retrieve all groups from the database
        $groups = Group::query();

        $account_id = $request->user()->account_id;

        if ($account_id) {
            $groups->where('account_id', $account_id);
        }

        // Execute the query to fetch domains
        $groups = $groups->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $groups,
            'message' => 'Successfully fetched all groups'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the group with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the group to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched group.
     */
    public function show($id)
    {
        // Find the group by ID
        $group = Group::find($id);

        // Check if the group exists
        if (!$group) {
            // If the group is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Group not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Retrieve the account_id of the authenticated user
        $account_id = auth()->user()->account_id;

        // Check if the user has permission to access the group
        if ($account_id !== $group->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($group) ? $group : '', // Ensure data is not null
            'message' => 'Successfully fetched'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created group resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new group record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $account_id = $request->user()->account_id;

        $request->merge(['created_by' => $userId, 'account_id' => $account_id]);

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'group_name' => [
                    'required',
                    Rule::unique('groups')->where(function ($query) use ($account_id) {
                        return $query->where('account_id', $account_id);
                    }),
                ],
                'created_by' => 'required|exists:users,id',
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

        // Create a new group record with validated data
        $data = Group::create($validated);

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
     * Update the specified group resource in storage.
     *
     * This method retrieves the group with the given ID, checks if it exists,
     * validates the incoming request data, and updates the group record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the group to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Find the group with the given ID
        $group = Group::find($id);

        // Check if the group exists
        if (!$group) {
            // If the group is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Group not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to update the group
        if ($account_id !== $group->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'group_name' => 'unique:groups,group_name,' . $id . ',id,account_id,' . $request->input('account_id'),
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
        $formattedDescription = compareValues($group, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $formattedDescription, $userId);

        // Update the group record with validated data
        $group->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $group,
            'message' => 'Successfully updated Group',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified group resource from storage.
     *
     * This method retrieves the group with the given ID, checks if it exists,
     * and deletes the group record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the group to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the group with the given ID
        $group = Group::find($id);

        $userId = auth()->user()->id;
        $account_id = auth()->user()->account_id;

        // Check if the group exists
        if (!$group) {
            // If the group is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Group not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the authenticated user has permission to update the group
        if ($account_id !== $group->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        $action = 'delete';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $group, $userId);

        // Delete the group record
        $group->forceDelete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted group'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
