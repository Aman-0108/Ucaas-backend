<?php

namespace Database\Seeders;

use App\Models\DidRateChart;
use Illuminate\Database\Seeder;

class DidRateChartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DidRateChart::create([
            "vendor_id" => 1,
            "rate_type" => "random",
            "rate" => 120,
        ]);
    }
}
