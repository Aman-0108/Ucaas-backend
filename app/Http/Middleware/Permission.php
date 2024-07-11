<?php

namespace App\Http\Middleware;

use App\Models\DefaultPermission;
use App\Models\UserPermission;
use App\Models\Permission as AllPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()->getName(); // Get current route name
        $userId = $request->user()->id; // Get logged-in user's ID

        $userType = $request->user()->usertype;

        if ($userType == 'SuperAdmin') {
            return $next($request);
        }

        if ($userType == 'Company') {
            if (!$this->companyPermission($routeName)) {
                $response = [
                    'status' => false,
                    'message' => 'You do not have privileges.',
                    'routeName' => $routeName,
                    'userId' => $userId
                ];

                // Return a 403 Forbidden response
                return response()->json($response, Response::HTTP_FORBIDDEN);
            }

            return $next($request);
        }

        // Check if the user has permissions for this route
        if (!$this->permissions($userId, $routeName)) {
            $response = [
                'status' => false,
                'message' => 'You do not have privileges.',
                'routeName' => $routeName,
                'userId' => $userId
            ];

            // Return a 403 Forbidden response
            return response()->json($response, Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /**
     * Check if the user has specific permissions for a given route.
     *
     * @param int $userId The ID of the user whose permissions need to be checked.
     * @param string $routeName The name of the route in the format 'model.action'.
     * @return bool True if the user has permissions for the route, false otherwise.
     */
    protected function permissions($userId, $routeName)
    {
        // Step 1: Retrieve permission IDs associated with the user
        $permissionIds = UserPermission::where('user_id', $userId)->pluck('permission_id');

        // Step 2: Parse the route name into model and action parts
        $parts = explode('.', $routeName);
        $model = strtolower($parts[0]);
        $action = $parts[1];

        // Step 3: Check if specific permissions exist for the user and route
        $permissions = AllPermissions::whereIn('id', $permissionIds)->where('model', $model)->where('action', $action)->exists();

        return $permissions;
    }

    /**
     * Check if a new company has specific permissions for a given route.
     *
     * @param string $routeName The name of the route in the format 'model.action'.
     * @return bool True if the new company has permissions for the route, false otherwise.
     */
    protected function companyPermission($routeName)
    {
        // Step 1: Retrieve permission IDs associated with the new company
        $permissionIds = DefaultPermission::where('setfor', 'New Company')->pluck('permission_id');

        // Step 2: Parse the route name into model and action parts
        $parts = explode('.', $routeName);
        $model = strtolower($parts[0]);
        $action = $parts[1];

        // Step 3: Check if specific permissions exist for the new company and route
        $permissions = AllPermissions::whereIn('id', $permissionIds)
            ->where('model', $model)
            ->where('action', $action)
            ->exists();
            
        return $permissions;
    }
}
