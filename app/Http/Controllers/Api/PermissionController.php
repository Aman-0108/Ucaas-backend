<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Response;

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
        $permissions = Permission::all();

        // Prepare the response data
        $response = [
            'status' => true,
            'data' => $permissions,
            'message' => 'Successfully fetched all permissions'
        ];

        // Return the response as JSON with HTTP status code 200 (OK)
        return response()->json($response, Response::HTTP_OK);
    }
}
