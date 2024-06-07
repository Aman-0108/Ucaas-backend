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
            'name' => 'General',
            'created_by' => 1
        ]);

        Role::create([
            'name' => 'Company',
            'created_by' => 1
        ]);
    }
}
