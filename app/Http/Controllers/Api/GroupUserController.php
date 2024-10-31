<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GroupUserController extends Controller
{
    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'Group_user'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'Group_user';
    }

    /**
     * Retrieve a list of GroupUser records for the authenticated user's account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Create a new query of the GroupUser model
        $group_users = GroupUser::query();

        $account_id = $request->user()->account_id;

        if ($account_id) {
            $group_users->where('account_id', $account_id);
        }

        // Execute the query
        $group_users = $group_users->get();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $group_users,
            'message' => 'Successfully fetched all group users'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Store a new GroupUser record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $account_id = $request->user()->account_id;

        $request->merge(['account_id' => $account_id]);

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'account_id' => 'required|exists:accounts,id',
                'group_id' => 'required|exists:groups,id',
                'user_id' => 'required|exists:users,id',
                'status' => 'in:1,0',
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

        // Create a new group user record with validated data
        $data = GroupUser::create($validated);

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
     * Update a GroupUser record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;

        $account_id = $request->user()->account_id;

        $group_user = GroupUser::find($id);

        // Check if the group_user exists
        if (!$group_user) {
            // If the group_user is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Group user not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the user has permission to access the group_user
        if ($account_id !== $group_user->account_id) {
            // If user doesn't have permission, prepare error response
            $response = [
                'status' => false,
                'error' => 'You do not have permission to access this resource.',
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'group_id' => 'exists:groups,id',
                'user_id' => 'exists:users,id',
                'status' => 'in:1,0',
            ]
        );

        // If validation fails
        if ($validator->fails()) {
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

        // Defining action and type 
        $action = 'update';
        $type = $this->type;

        // Log the action
        accessLog($action, $type, $validated, $userId);

        // Update the GroupUser record with validated data
        $group_user->update($validated);

        // Commit the database transaction
        DB::commit();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $group_user,
            'message' => 'Successfully updated',
        ];

        // Return a JSON response with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Remove the specified GroupUser resource from storage.
     *
     * This method retrieves the GroupUser with the given ID, checks if it exists,
     * and deletes the GroupUser record from the database. It returns a JSON response
     * indicating success or failure.
     *
     * @param  int  $id The ID of the GroupUser to delete
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure
     */
    public function destroy($id)
    {
        // Retrieve the ID of the authenticated user making the request
        $userId = auth()->user()->id;

        // Retrieve the account ID of the authenticated user
        $account_id = auth()->user()->account_id;

        // Find the groupUser by ID
        $groupUser = GroupUser::find($id);

        // Check if the groupUser exists
        if (!$groupUser) {
            // If the groupUser is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'Group User not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Check if the user has permission to access the group_user
        if ($account_id !== $groupUser->account_id) {
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
        accessLog($action, $type, $groupUser, $userId);

        // Delete the groupUser
        $groupUser->delete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted user'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
