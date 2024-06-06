<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TimezoneSeeder::class);
        $this->call(PackageSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(UserSeeder::class);
        $this->call(SofiaGlobalsettingsSeeder::class);
        $this->call(SipProfileSeeder::class);
        $this->call(PermissionSeeder::class);
    }
}
