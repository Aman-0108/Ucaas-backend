<?php

namespace Database\Seeders;

use App\Models\DefaultPermission;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class DefaultPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filter = [
            'Account', 'User', 'Role', 'RolePermission', 'Extension', 'ChannelHangupComplete', 'WalletTransaction', 'BillingAddress', 'CardDetail', 'Domain', 'Timezone', 'Dialplan'
        ];

        $Permissions = Permission::whereIn('model', $filter)->get();

        $formatteddata = [];

        foreach ($Permissions as $permission) {
            $formatteddata[] = [
                'permission_id' => $permission->id,
                'setfor' => 'New Company',
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s")
            ];
        }

        DefaultPermission::insert($formatteddata);
    }
}
