<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Role'.
     */
    public function __construct()
    {
        // Perform initialization
        $this->type = 'Role';
    }

    /**
     * Display a listing of the resource.
     *
     * This method fetches all roles from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched roles.
     */
    public function index(Request $request)
    {
        // Retrieve all roles from the database
        $roles = Role::query();

        if ($request->has('account_id')) {
            $roles->where('created_by', $request->account_id);
        }

        // Execute the query to fetch domains
        $roles = $roles->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $roles,
            'message' => 'Successfully fetched all roles',
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     *
     * This method fetches the group with the given ID from the database and returns it as a JSON response.
     *
     * @param  int $id The ID of the role to be fetched.
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing the fetched role.
     */
    public function show($id)
    {
        // Find the role by ID
        $role = Role::find($id);

        // Check if the role exists
        if (!$role) {
            // If the role is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Role not found',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => ($role) ? $role : '', // Ensure data is not null
            'message' => 'Successfully fetched',
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a newly created role resource in storage.
     *
     * This method is responsible for validating incoming request data,
     * creating a new role record in the database, and returning a
     * JSON response indicating success or failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;
        $request->merge(['created_by' => $userId]);

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|unique:roles,name,NULL,id,created_by,' . $request->created_by,
                'created_by' => 'required|exists:users,id',
            ]
        );

        // If validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors(),
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

        // Generate UID and attach it to the validated data
        createUid($action, $type, $validated, $userId);

        // Create a new role record with validated data
        $data = Role::create($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $data,
            'message' => 'Successfully stored',
        ];

        // Return a JSON response with HTTP status code 201 (Created)
        return response()->json($response, Response::HTTP_CREATED);
    }

    /**
     * Update the specified role resource in storage.
     *
     * This method retrieves the role with the given ID, checks if it exists,
     * validates the incoming request data, and updates the role record in the
     * database if validation passes. It returns a JSON response indicating success
     * or failure along with any relevant data or error messages.
     *
     * @param  \Illuminate\Http\Request  $request The incoming HTTP request
     * @param  int  $id The ID of the role to update
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function update(Request $request, $id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = $request->user()->id;

        // Find the role with the given ID
        $role = Role::find($id);

        // Check if the role exists
        if (!$role) {
            // If the role is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'role not found',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'created_by' => 'exists:users,id',
                'name' => 'unique:roles,name,' . $id . ',id,created_by,' . $request->input('created_by'),
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, return a JSON response with error messages
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors(),
            ];

            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input
        $validated = $validator->validated();

        // Call the compareValues function to generate a formatted description based on the role and validated data
        $formattedDescription = compareValues($role, $validated);

        // Defining action and type for creating UID
        $action = 'update';
        $type = $this->type;

        // Generate UID and attach it to the validated data
        createUid($action, $type, $formattedDescription, $userId);

        // Update the role record with validated data
        $role->update($validated);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $role,
            'message' => 'Successfully updated role',
        ];

        // Return a JSON response indicating successful update with response code 200(ok)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified role resource from storage.
     *
     * This method retrieves the role with the given ID, checks if it exists,
     * and deletes the role record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the role to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Find the role with the given ID
        $role = Role::find($id);

        // Check if the role exists
        if (!$role) {
            // If the role is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Role not found',
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Delete the  record
        $role->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted.',
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
