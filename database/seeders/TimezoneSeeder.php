<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Timezone;
use DateTimeZone;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $timezones = DateTimeZone::listIdentifiers();

        // Print out the time zones
        foreach ($timezones as $timezone) {
            Timezone::create([
                'name' => $timezone,
                'value' => $timezone,
                'created_by' => 1,
            ]);
        }
    }
}
