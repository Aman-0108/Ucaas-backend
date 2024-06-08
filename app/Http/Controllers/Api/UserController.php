<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\User;
use App\Traits\GetPermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use WebSocket\Client;

class UserController extends Controller
{
    use GetPermission;
    /**
     * Creates a new user.
     *
     * This method attempts to create a new user based on the provided data.
     * It validates the request data and checks for validation errors. If validation
     * fails, it returns a JSON response with validation errors. If validation succeeds,
     * it creates a new user record in the database and returns a JSON response
     * indicating successful creation.
     *
     * @param Request $request The HTTP request object containing user data.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the creation attempt.
     */
    public function create(Request $request)
    {
        try {
            // Validate the request data
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required',
                    'username' => 'required|unique:users,username',
                    // 'group_id' => 'required|exists:groups,id',
                    // 'domain_id' => 'required|exists:domains,id',
                    // 'account_id' => 'required|exists:accounts,id',
                    'timezone_id' => 'required|exists:timezones,id',
                    'status' => 'required|in:E,D',
                    'usertype' => 'required|in:Primary,General',
                ]
            );

            // Check if validation fails
            if ($validateUser->fails()) {
                // If validation fails, prepare error response with validation errors
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], Response::HTTP_FORBIDDEN);
            }

            // Create a new user record in the database
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                // 'group_id' => $request->group_id,
                // 'domain_id' => $request->domain_id,
                // 'account_id' => $request->account_id,
                'timezone_id' => $request->timezone_id,
                'status' => $request->status,
                'usertype' => $request->usertype,
            ]);

            $this->setDefaultPermission($user);

            // Prepare success response
            return response()->json([
                'status' => true,
                'data' => $user,
                'message' => 'User Created Successfully',
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            // If an exception occurs, return an error response with the exception message
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Checks if a username is available to use.
     *
     * This method attempts to validate if the provided username is unique among users.
     * It validates the request data and checks for validation errors. If validation
     * fails, it returns a JSON response with validation errors. If validation succeeds,
     * it returns a JSON response indicating that the username is available.
     *
     * @param Request $request The HTTP request object containing the username to check.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the availability of the username.
     */
    public function checkUserName(Request $request)
    {
        try {
            // Validate the request data
            $validateUser = Validator::make(
                $request->all(),
                [
                    'username' => 'required|unique:users,username,'
                ]
            );

            // Check if validation fails
            if ($validateUser->fails()) {
                // If validation fails, prepare error response with validation errors
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            // If validation succeeds, return a JSON response indicating that the username is available
            return response()->json([
                'status' => true,
                'message' => 'username available to use',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // If an exception occurs, return an error response with the exception message
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieves all users.
     *
     * This method retrieves all users from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing all users.
     */
    public function users(Request $request)
    {
        // Retrieve all users from the database
        $users = User::with(['domain', 'extension', 'role', 'rolepermission.permission']);

        // Check if the request contains an 'account' parameter
        if ($request->has('account')) {
            // If 'account' parameter is provided, filter extensions by account ID
            $users->where('account_id', $request->account);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch users
        $users = $users->orderBy('id', 'asc')->paginate($ROW_PER_PAGE);

        // Prepare success response with user data
        $response = [
            'status' => true,
            'data' => $users,
            'message' => 'Successfully fetched all users'
        ];

        // Return a JSON response with user data and success message
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Retrieves a specific user by ID.
     *
     * This method retrieves a user with the provided ID from the database and returns it as a JSON response.
     * If no user is found with the given ID, it returns an appropriate error response.
     *
     * @param int $id The ID of the user to retrieve.
     * @return \Illuminate\Http\JsonResponse A JSON response containing the user data.
     */
    public function show($id)
    {
        // Find the user with the given ID
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            // If user is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'User not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Prepare success response with user data
        $response = [
            'status' => true,
            'data' => ($user) ? $user : '',
            'message' => 'Successfully fetched'
        ];

        // Return a JSON response with user data and success message
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Updates an existing user.
     *
     * This method updates the user with the provided ID based on the request data.
     * It first retrieves the user by ID. If no user is found, it returns an appropriate error response.
     * It then validates the request data and checks for validation errors. If validation fails, it returns
     * a JSON response with validation errors. If validation succeeds, it updates the user record in the database
     * with the validated input and returns a JSON response indicating successful update.
     *
     * @param Request $request The HTTP request object containing user data to update.
     * @param int $id The ID of the user to update.
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the update operation.
     */
    public function update(Request $request, $id)
    {
        // Find the user with the given ID
        $user = User::find($id);

        // Check if user exists
        if (!$user) {
            // If user is not found, prepare error response
            $response = [
                'status' => false,
                'error' => 'User not found'
            ];

            // Return a JSON response with error message and 404 status code
            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Validate the request data
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'string|min:2',
                'email' => 'email|unique:users,email,' . $id,
                // 'password' => '',
                'username' => 'unique:users,username',
                'group_id' => 'exists:groups,id',
                'domain_id' => 'exists:domains,id',
                'account_id' => 'exists:accounts,id',
                'timezone_id' => 'exists:timezones,id',
                'status' => 'in:E,D',
                'usertype' => 'in:Primary,General',
                'firebase_token' => 'string',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            $response = [
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ];

            // Return a JSON response with error message and 403 status code
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        // Retrieve the validated input...
        $validated = $validator->validated();

        // Update the user record in the database with the validated input
        $user->update($validated);

        // Prepare success response with updated user data
        $response = [
            'status' => true,
            'data' => $user,
            'message' => 'Successfully updated user',
        ];

        // Return a JSON response with updated user data and success message
        return response()->json($response);
    }

    /**
     * Search for users by name or email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('query');

        $users = User::query();

        if ($query) {
            $users->where(function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                    ->orWhere('email', 'like', "%$query%");
            });
        }

        if ($request->get('account')) {
            $users->where('account_id', $request->account);
        }

        $users = $users->get();

        // Prepare success response with updated user data
        $response = [
            'status' => true,
            'data' => $users,
            'message' => 'Successfully fetched',
        ];

        // Return a JSON response with updated user data and success message
        return response()->json($response, Response::HTTP_OK);
    }

    public function setDefaultPermission($user)
    {
        $formattedData = [];

        if($user->usertype == 'General') {
            $userPermission = $this->getDefaultUserPermissions();

            foreach ($userPermission as $permision) {
                $formattedData[] = [
                    'role_id' => 1,
                    'permission_id' => $permision->id,
                    'created_at' =>  date("Y-m-d H:i:s"),
                    'updated_at' =>  date("Y-m-d H:i:s")
                ];
            }

            RolePermission::insert($formattedData);

            $user->role_id = 1;
            $user->save();
        }

        if ($user->usertype == 'Company') {

            $permissions = $this->getDefaultCompaniesPermissions();

            foreach ($permissions as $permision) {
                $formattedData[] = [
                    'role_id' => 2,
                    'permission_id' => $permision->id,
                    'created_at' =>  date("Y-m-d H:i:s"),
                    'updated_at' =>  date("Y-m-d H:i:s")
                ];
            }

            RolePermission::insert($formattedData);

            $user->role_id = 2;
            $user->save();
        }
    }
}
