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
        // \App\Models\User::factory(10)->create();
        $this->call(UidSeeder::class);
        $this->call(AccountSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(TimezoneSeeder::class);
        $this->call(DomainSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(SofiaGlobalsettingsSeeder::class);
    }
}
