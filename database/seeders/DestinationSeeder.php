<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['name' => 'DST_AUS_Mobile', 'prefix' => '614'],
            ['name' => 'DST_AUS_Fixed', 'prefix' => '612'],
            ['name' => 'DST_AUS_Fixed', 'prefix' => '613'],
            ['name' => 'DST_AUS_Fixed', 'prefix' => '617'],
            ['name' => 'DST_AUS_Fixed', 'prefix' => '618'],
            ['name' => 'DST_AUS_Toll_Free', 'prefix' => '611300'],
            ['name' => 'DST_AUS_Toll_Free', 'prefix' => '611800'],
        ];

        foreach ($data as $item) {
            Destination::create($item);
        }
    }
}
