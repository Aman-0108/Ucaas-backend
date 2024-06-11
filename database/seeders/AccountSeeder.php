<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Traits\GetPermission;

class AccountSeeder extends Seeder
{
    use GetPermission;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Account::create([
            'admin_name' => 'rdx',
            'company_name' => 'RDX',
            'timezone_id' => 1,
            'email' => 'rdx@webvio.com',
            'contact_no' => 9609090569,
            'state' => 'WB',
            'company_status' => 1,
            'package_id' => 2
        ]);

        $user = User::create([
            'name' => 'rdx',
            'email' => 'rdx@webvio.com',
            'password' => bcrypt('123456'),
            'username' => 'RDX',
            'account_id' => 1,
            'usertype' => 'Company'
        ]);

        // UserRole::create([
        //     'user_id' => $user->id,
        //     'role_id' => 2
        // ]);

        // $pids = $this->getDefaultCompaniesPermissions();

        // $formattedData = [];
        // foreach($pids as $pid) {
        //     $formattedData[] = [
        //         'role_id' => 2,
        //         'permission_id' => $pid,
        //         'created_at' => date("Y-m-d H:i:s"),
        //         'updated_at' => date("Y-m-d H:i:s")
        //     ];
        // }

        // RolePermission::insert($formattedData);
    }
}
