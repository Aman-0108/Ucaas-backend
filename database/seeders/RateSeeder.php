<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'RT_22c_PM',
                'ConnectFee' => 0,
                'Rate' => 22,
                'RateUnit' => '60s',
                'RateIncrement' => '60s',
                'GroupIntervalStart' => '0s',
            ],
            [
                'name' => 'RT_20c_Untimed',
                'ConnectFee' => 20,
                'Rate' => 0,
                'RateUnit' => '60s',
                'RateIncrement' => '60s',
                'GroupIntervalStart' => '0s',
            ],
            [
                'name' => 'RT_25c_Flat',
                'ConnectFee' => 25,
                'Rate' => 0,
                'RateUnit' => '60s',
                'RateIncrement' => '60s',
                'GroupIntervalStart' => '0s',
            ],
            [
                'name' => 'RT_25c_PM_PerMinute_Billing',
                'ConnectFee' => 0,
                'Rate' => 25,
                'RateUnit' => '60s',
                'RateIncrement' => '60s',
                'GroupIntervalStart' => '0s',
            ],
            [
                'name' => 'RT_25c_PM_PerSecond_Billing',
                'ConnectFee' => 0,
                'Rate' => 25,
                'RateUnit' => '60s',
                'RateIncrement' => '1s',
                'GroupIntervalStart' => '0s',
            ],
        ];

        foreach ($data as $item) {
            Rate::create($item);
        }
    }
}
