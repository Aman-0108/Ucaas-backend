<?php

namespace App\Traits;

use App\Models\Permission;

trait GetPermission
{
    public function getDefaultCompaniesPermissions()
    {
        $types = ['Account', 'User'];
        $permissions = Permission::whereIn('type', $types)->get();

        $filteredPermissions = $permissions->filter(function ($perm) {
            // Filter out permissions of type 'Account' where the action is 'browse'
            return !(
                ($perm->type === 'Account' && ($perm->action === 'browse' || $perm->action === 'add' || $perm->action === 'delete')) ||
                ($perm->type === 'User' && $perm->action === 'delete')
            );
        })->values()->all();

        return $filteredPermissions;
    }

    public function getDefaultUserPermissions()
    {
        $types = ['User'];

        $permissions = Permission::whereIn('type', $types)->get();

        $filteredPermissions = $permissions->filter(function ($perm) {
            return !(
                ($perm->action === 'browse' || $perm->action === 'delete')
            );
        })->values()->all();

        return $filteredPermissions;
    }
}
