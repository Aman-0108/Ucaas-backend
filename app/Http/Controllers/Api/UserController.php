<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Extension;
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
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use GetPermission;

    protected $type;

    /**
     * Constructor function initializes the 'type' property to 'User'.
     */
    public function __construct()
    {
        // Perform initialization 
        $this->type = 'User';
    }

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

            $action = 'create';

            $userId = $request->user()->id;
            $userType = $request->user()->usertype;
            $account_id = $request->user()->account_id;

            // Validate the request data
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:6',
                    // 'username' => 'required|unique:users,username',
                    'username' => [
                        'required',
                        'string',
                        Rule::unique('users')->where(function ($query) use ($request) {
                            return $query->where('account_id', $request->input('account_id'));
                        }),
                    ],
                    'timezone_id' => 'required|exists:timezones,id',
                    'status' => 'required|in:E,D',
                    'role_id' => 'required|integer|exists:roles,id',
                    'permissions' => [
                        'required',
                        'array'
                    ],
                    'account_id' => 'required|integer|exists:accounts,id',
                    'permissions.*' => 'required|integer',
                    'extension_id' => [
                        'string',
                        'nullable',
                        function ($attribute, $value, $fail) use ($request) {
                            $accountId = $request->input('account_id');
                            $exists = DB::table('extensions')
                                ->where('id', $value)
                                ->where('account_id', $accountId)
                                ->exists();

                            if (!$exists) {
                                $fail('The selected extension_id does not belong to the specified account_id.');
                            }
                        },
                    ]
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

            if ($userType != 'SuperAdmin') {

                $domainId = Domain::where('account_id', $account_id)->first()->id;

                // Create a new user record in the database
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'username' => $request->username,
                    'password' => Hash::make($request->password),
                    'domain_id' => $domainId,
                    'account_id' => $account_id,
                    'timezone_id' => $request->timezone_id,
                    'status' => $request->status,
                    'created_by' => $userId
                ]);

                if ($request->has('extension_id') && $request->extension_id != null) {
                    $extension = Extension::where([
                        'account_id' => $request->account_id,
                        'id' => $request->extension_id
                    ])->first();

                    $checkAssigned = User::where('extension_id', $request->extension_id)->first();

                    if ($checkAssigned) {
                        $checkAssigned->extension_id = null;
                        $checkAssigned->save();
                    }

                    $user->extension_id = $request->extension_id;
                    $user->save();

                    $extension->user = $user->id;
                    $extension->save();
                }
            } else {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'username' => $request->username,
                    'password' => Hash::make($request->password),
                    'domain_id' => 'null',
                    'account_id' => 'null',
                    'timezone_id' => $request->timezone_id,
                    'status' => $request->status,
                    'created_by' => $userId
                ]);
            }

            // Log the action
            accessLog($action, $this->type, $user, $userId);

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

            $account_id = $request->user() ? $request->user()->account_id : null;

            if (!$account_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You dont have permission to perform this action',
                ], Response::HTTP_CONFLICT);
            }

            $request->merge([
                'account_id' => $account_id
            ]);

            // Validate the request data
            $validator = Validator::make(
                $request->all(),
                [
                    'username' => [
                        'required',
                        'string',
                        Rule::unique('users')->where(function ($query) use ($request) {
                            return $query->where('account_id', $request->input('account_id'));
                        }),
                    ],
                    'account_id' => 'required|integer|exists:accounts,id', // Ensure account_id exists
                ]
            );

            // Check if validation fails
            if ($validator->fails()) {
                // If validation fails, prepare error response with validation errors
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
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

        // $users->where('id', '!=', $userId);

        if ($request->user()->usertype == 'Company') {
            // If 'account' parameter is provided, filter extensions by account ID
            $users->where('account_id', $request->user()->account_id);
        } else {
            $users->where('created_by', $userId);
        }

        // Check if the request contains a 'search' parameter
        if ($request->has('search')) {
            // If 'search' parameter is provided, filter extensions by extension name
            $searchTerm = $request->search;

            if ($searchTerm) {
                $users->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%$searchTerm%")
                        ->orWhere('email', 'like', "%$searchTerm%")
                        ->orWhere('username', 'like', "%$searchTerm%")
                        ->orWhere('contact', 'like', "%$searchTerm%");
                });
            }
        }

        if ($request->has('row_per_page')) {
            $ROW_PER_PAGE = $request->row_per_page;

            if (!is_numeric($ROW_PER_PAGE) || $ROW_PER_PAGE < 1) {
                // Fallback to a default value if invalid
                $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');
            }
        } else {
            $ROW_PER_PAGE = config('globals.PAGINATION.ROW_PER_PAGE');
        }

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
                'password' => 'min:6',
                'domain_id' => 'exists:domains,id',
                'account_id' => 'exists:accounts,id',
                'extension_id' => 'integer|nullable',
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

        if ($request->has('password')) {
            $validated['password'] = Hash::make($validated['password']);
        }

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

        $users->whereNotIn('usertype', ['SuperAdmin', 'Company']);

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
            'password' => Hash::make(str_replace(' ', '', strtolower($account->company_name))),
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

    /**
     * Retrieves a list of all voicemail recordings associated with the authenticated account.
     *
     * This method returns a JSON response containing a list of all voicemail recordings
     * associated with the authenticated account. The list is paginated with a page size
     * of 10 records. The response includes the total number of records in the 'meta' key.
     *
     * @param Request $request The HTTP request object.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response containing the list of voicemail recordings.
     */
    public function getVoicemails(Request $request)
    {
        // Get the account ID from the authenticated user
        $account_id = $request->user() ? $request->user()->account_id : null;

        $userType = $request->user() ? $request->user()->usertype : null;

        // Get all voicemail recordings associated with the account
        $voicemailsQuery = DB::table('voicemail_recordings');

        if ($userType == 'Company') {
            // Apply the account_id filter if present
            $voicemailsQuery->where('account_id', $account_id);
        }

        if (!$userType) {
            $extension_id = $request->user() ? $request->user()->extension_id : null;

            $extension = Extension::where('id', $extension_id)->first();

            if (!empty($extension)) {
                // Apply the extension_id filter if present
                $voicemailsQuery->where(function ($query) use ($extension) {
                    $query->where('src', $extension->extension)
                        ->orWhere('dest', $extension->extension);
                });
            }
        }

        // COMING FROM GLOBAL CONFIG
        $rowsPerPage = config('globals.PAGINATION.ROW_PER_PAGE');

        // Execute the query, order by 'id' in descending order, and paginate
        $voicemails = $voicemailsQuery->orderBy('id', 'desc')->paginate($rowsPerPage);

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $voicemails,
            'message' => 'Successfully fetched all voicemails'
        ];

        // Return the response as JSON
        return response()->json($response, Response::HTTP_OK);
    }

    public function allUsersForMessageservice(Request $request)
    {
        $account_id = $request->user()->account_id;

        // Retrieve all users from the database
        $users = User::with(['extension']);

        $users->where('account_id', $account_id);

        // Check if the request contains a 'search' parameter
        if ($request->has('search')) {
            // If 'search' parameter is provided, filter extensions by extension name
            $searchTerm = $request->search;

            if ($searchTerm) {
                $users->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%$searchTerm%")
                        ->orWhere('email', 'like', "%$searchTerm%")
                        ->orWhere('username', 'like', "%$searchTerm%")
                        ->orWhere('contact', 'like', "%$searchTerm%");
                });
            }
        }

        // Execute the query to fetch users
        $users = $users->orderBy('id', 'asc')->get();

        // Prepare success response with user data
        $response = [
            'status' => true,
            'data' => $users,
            'message' => 'Successfully fetched all users'
        ];

        // Return a JSON response with user data and success message
        return response()->json($response, Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $action = 'delete';
        $type = $this->type;

        $account_id = auth()->user()->account_id;
        $usertype = auth()->user()->usertype;
        $userId = auth()->user()->id;

        // Find the Port by ID
        $user = User::find($id);

        // Check if the Port exists
        if (!$user) {
            // If the Port is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'User not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        if ($user->account_id != $account_id && $usertype != 'Company') {
            // If the Port is not found, return a 404 Not Found response
            $response = [
                'status' => false,
                'error' => 'User not found'
            ];

            return response()->json($response, Response::HTTP_NOT_FOUND);
        }

        // Update the extension 
        Extension::where('user', $id)->update(['extension' => '']);

        // Generate log
        accessLog($action, $type, $user, $userId);

        // Delete the user
        // $user->delete();
        $user->forceDelete();

        // Prepare the response data
        $response = [
            'status' => true,
            'message' => 'Successfully deleted user.'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
