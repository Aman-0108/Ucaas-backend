<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i < 15; $i++) {
            RolePermission::create([
                "role_id" => 1,
                'permission_id' => $i
            ]);
        }

        for ($i = 50; $i < 60; $i++) {
            RolePermission::create([
                "role_id" => 2,
                'permission_id' => $i
            ]);
        }
    }
}
