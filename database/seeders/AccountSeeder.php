<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\User;

class AccountSeeder extends Seeder
{
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

        User::create([
            'name' => 'rdx',
            'email' => 'rdx@webvio.com',
            'password' => bcrypt('123456'),
            'username' => 'RDX',
            'account_id' => 1,
            'usertype' => 'Company'
        ]);
    }
}
