<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * This method fetches all dialplans from the database and returns them as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse Returns a JSON response containing all fetched permissions.
     */
    public function index()
    {
        // Retrieve all permissions from the database
        $permissions = Permission::all()->groupBy('type');

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $permissions,
            'message' => 'Successfully fetched all permissions'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }

    public function assignPermissionToRole(Request $request)
    {
        $rules = [
            'role_id' => 'required|integer|exists:roles,id',
            'permissions' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    // Validate uniqueness of role_id and permission_id combination
                    $existingPermissions = RolePermission::where('role_id', request()->input('role_id'))
                        ->whereIn('permission_id', $value)
                        ->count();

                    if ($existingPermissions > 0) {
                        $fail('The combination of role_id and permission_id already exists.');
                    }
                },
            ],
            'permissions.*' => 'required|integer'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            // If validation fails, prepare error response with validation errors
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ], Response::HTTP_FORBIDDEN);
        }

        $formattedData = [];

        $permissions = $request->permissions;

        foreach ($permissions as $permission_id) {
            $inputData = [
                'role_id' => $request->role_id,
                'permission_id' => $permission_id,
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];

            $formattedData[] = RolePermission::create($inputData);
        }

        // Prepare success response with user data
        $response = [
            'status' => true,
            'data' => $formattedData,
            'message' => 'Successfully fetched..'
        ];

        // Return a JSON response with user data and success message
        return response()->json($response, Response::HTTP_OK);
    }
}
