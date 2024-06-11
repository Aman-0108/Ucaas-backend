<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create([
            'name' => 'Admin',
            'created_by' => 2
        ]);

        Role::create([
            'name' => 'Manager',
            'created_by' => 2
        ]);
    }
}
