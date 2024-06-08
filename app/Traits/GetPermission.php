<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\UserPermission;
use App\Models\UserRole;

trait GetPermission
{
    public function getDefaultCompaniesPermissions()
    {
        $types = ['Account', 'User'];

        $permissions = Permission::whereIn('type', $types)->get();

        $permissions = $permissions->filter(function ($perm) {
            // Filter out permissions of type 'Account' where the action is 'browse'
            return !(
                ($perm->type === 'Account' && ($perm->action === 'browse' || $perm->action === 'add' || $perm->action === 'delete')) ||
                ($perm->type === 'User' && $perm->action === 'delete')
            );
        })->values()->all();

        $permission_ids = [];

        foreach ($permissions as $permission) {
            $permission_ids[] = $permission->id;
        }

        return $permission_ids;
    }

    public function getDefaultUserPermissions()
    {
        $types = ['User'];

        $permissions = Permission::whereIn('type', $types)->get();

        $permissions = $permissions->filter(function ($perm) {
            return !(
                ($perm->action === 'browse' || $perm->action === 'delete')
            );
        })->values()->all();

        $permission_ids = [];

        foreach ($permissions as $permission) {
            $permission_ids[] = $permission->id;
        }

        return $permission_ids;
    }

    public function getPermission($userId)
    {
        $role = UserRole::where('user_id', $userId)->first();

        if(!$role) {
            return [];
        }

        $formattedData = [];

        $permissionId = RolePermission::where('role_id', $role->role_id)->pluck('permission_id')->toArray();

        $permissionId = array_merge($permissionId, UserPermission::where('user_id', $userId)->pluck('permission_id')->toArray());

        $roleData = Role::select('name','id')->where('id',$role->role_id)->first();

        $formattedData = [
            'role' => $roleData,
            'permissions' => $permissionId
        ];

        return $formattedData;       
    }

    public function getRole($userId)
    {

    }
}
