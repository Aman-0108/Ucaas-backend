<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
use App\Traits\GetPermission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

            $userId = $request->user()->id;

            // Validate the request data
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required',
                    'username' => 'required|unique:users,username',
                    'domain_id' => 'required|exists:domains,id',
                    'account_id' => 'required|exists:accounts,id',
                    'timezone_id' => 'required|exists:timezones,id',
                    'status' => 'required|in:E,D',
                    'role_id' => 'required|integer|exists:roles,id',
                    'permissions' => [
                        'required',
                        'array'
                    ],
                    'permissions.*' => 'required|integer',
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

            DB::beginTransaction();
            // Create a new user record in the database
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'domain_id' => $request->domain_id,
                'account_id' => $request->account_id,
                'timezone_id' => $request->timezone_id,
                'status' => $request->status,
                'created_by' => $userId
            ]);

            $this->insertUserRole($user->id, $request->role_id);

            $checkPermissions = RolePermission::where('role_id', $request->role_id)->get();

            if ($checkPermissions->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Rules not set for this Role',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->setUserPermission($user->id, $request->permissions);

            DB::commit();

            // $this->setDefaultPermission($user);

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
                ], Response::HTTP_NOT_ACCEPTABLE);
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
        $userId = $request->user()->id;

        // Retrieve all users from the database
        $users = User::with(['domain', 'extension', 'userRole.roles']);

        $users->where('id', '!=', $userId);

        if ($request->user()->usertype == 'Company') {
            // If 'account' parameter is provided, filter extensions by account ID
            $users->where('account_id', $request->user()->account_id);
        } else {
            $users->where('created_by', $userId);
        }

        // COMING FROM GLOBAL CONFIG
        $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query to fetch users
        $users = $users->orderBy('id', 'asc')->paginate($ROW_PER_PAGE);

        $users->through(function ($user) {
            $user->permissions = UserPermission::where('user_id', $user->id)->pluck('permission_id')->toArray();
            return $user;
        });

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
                'username' => 'unique:users,username',
                'domain_id' => 'exists:domains,id',
                'account_id' => 'exists:accounts,id',
                'timezone_id' => 'exists:timezones,id',
                'status' => 'in:E,D',
                'firebase_token' => 'string',
                'permissions' => [
                    'required',
                    'array'
                ],
                'permissions.*' => 'required|integer',
                'role_id' => 'integer|exists:roles,id'
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

        DB::beginTransaction();

        unset($validated['permissions'], $validated['role_id']);
        // Update the user record in the database with the validated input
        $user->update($validated);

        if ($request->has('permissions') && $request->has('role_id')) {

            $this->insertUserRole($user->id, $request->role_id);

            $checkPermissions = RolePermission::where('role_id', $request->role_id)->get();

            if ($checkPermissions->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Rules not set for this Role',
                ], Response::HTTP_NOT_FOUND);
            }

            $this->setUserPermission($user->id, $request->permissions);
        }

        DB::commit();

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

        if ($user->usertype == 'General') {
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

    public function insertUserRole($userId, $roleId)
    {
        $userRole = UserRole::where('user_id', $userId)->first();

        // set user role
        if (!$userRole) {
            UserRole::create([
                'user_id' => $userId,
                'role_id' => $roleId
            ]);
        } else {
            $userRole->role_id = $roleId;
            $userRole->save();
        }
    }

    public function setUserPermission($userId, $permissions)
    {
        // insert into user permission
        $formattedData = [];
        foreach ($permissions as $permission) {
            $formattedData[] = [
                'user_id' => $userId,
                'permission_id' => $permission,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];
        }

        $checkExist = UserPermission::where('user_id', $userId)->first();

        if ($checkExist) {
            UserPermission::where('user_id', $userId)->delete();
        }

        UserPermission::insert($formattedData);
    }

    /**
     * Creates a new user associated with the provided account.
     *
     * This function extracts the name and email from the account's email address,
     * generates user credentials based on the account information, and then creates a new user.
     * The user's password is hashed using the company name of the account.
     *
     * @param $account object The account object to associate the new user with.
     * @return void
     */
    public function createUser($account)
    {
        // Extract name from email address
        $parts = explode('@', $account->email);
        $name = $parts[0]; // 'test'

        // Generate user credentials
        $userCredentials = [
            'name' => $name,
            'email' => $account->email,
            'username' => $account->company_name,
            'password' => Hash::make($account->company_name),
            'timezone_id' => $account->timezone_id,
            'status' => 'E',
            'usertype' => 'Company',
            'socket_status' => 'offline',
            'account_id' => $account->id
        ];

        // Create a new user with the generated credentials
        $user = User::create($userCredentials);

        return $user;
    }
}
