<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Package::create([
            'name' => 'Basic',
            'number_of_user' => '1000',
            'description' => 'Everything you need to create your website',
            'subscription_type' => 'annually',
            'regular_price' => '100.00',
            'offer_price' => '20000.00'
        ]);

        Package::create([
            'name' => 'Advanced',
            'number_of_user' => '10',
            'description' => 'Enjoy optimised performance & guaranteed',
            'subscription_type' => 'annually',
            'regular_price' => '30000.00',
            'offer_price' => '25000.00'
        ]);

        Package::create([
            'name' => 'Standard',
            'number_of_user' => '100',
            'description' => 'Level-up with more power and enhanced features',
            'subscription_type' => 'annually',
            'regular_price' => '3000.00',
            'offer_price' => '2000.00'
        ]);
    }
}
