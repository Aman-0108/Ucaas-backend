<?php

namespace Database\Seeders;

use App\Models\DefaultPermission;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultPermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     *
     * @return void
     */
    public function run()
    {
        $filteredModels = ['Account'];

        $permissions = Permission::whereIn('model', $filteredModels)->get();

        $defaultPermissionsData = $this->formatDefaultPermissionsData($permissions);

        DefaultPermission::upsert($defaultPermissionsData,'permission_id');
    }


    /**
     * Formats the given permissions data into the default permissions data format.
     *
     * @param \Illuminate\Support\Collection $permissions The permissions data to format.
     * @return array The formatted default permissions data.
     */
    private function formatDefaultPermissionsData($permissions)
    {
        $formatteddata = [];

        foreach ($permissions as $permission) {
            if ($permission->type == 'account') {
                if ($permission->action == 'read') {
                    $formatteddata[] = [
                        'permission_id' => $permission->id,
                        'setfor' => 'New Company',
                        'created_at' => date("Y-m-d H:i:s"),
                        'updated_at' => date("Y-m-d H:i:s")
                    ];
                }
            } else {
                $formatteddata[] = [
                    'permission_id' => $permission->id,
                    'setfor' => 'New Company',
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s")
                ];
            }
        }

        return $formatteddata;
    }

    //     // $filter = [
    //     //     'Account', 'User', 'Role', 'RolePermission', 'Extension', 'ChannelHangupComplete', 'WalletTransaction', 'BillingAddress', 'CardDetail', 'Domain', 'Timezone', 'Dialplan', 'Sound', 'Gateway'
    //     // ];
}
