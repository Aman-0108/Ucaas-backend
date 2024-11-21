<?php

namespace Database\Seeders;

use App\Models\DefaultPermission;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefaultPermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     *
     * @return void
     */
    public function run()
    {
        $filteredModels = [
            'Account', 'User', 'Role', 'RolePermission', 
            'Extension', 'ChannelHangupComplete', 
            'WalletTransaction', 'BillingAddress', 
            'CardDetail', 'Domain', 'Timezone', 
            'Dialplan', 'Sound', 'Gateway', 
            'CallCenterQueue', 'CallCenterAgent', 'Ringgroup', 
            'MailSetting', 'IvrMaster', 'IvrOptions',
            'Port', 'Autodialer', 'Sound', 
            'DidDetail', 'DidConfigure'
        ];

        $permissions = Permission::whereIn('model', $filteredModels)->get();

        $defaultPermissionsData = $this->formatDefaultPermissionsData($permissions);

        Log::info('Default permissions data: ' . json_encode($defaultPermissionsData));

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

}
