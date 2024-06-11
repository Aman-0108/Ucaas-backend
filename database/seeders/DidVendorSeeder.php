<?php

namespace Database\Seeders;

use App\Models\DidVendor;
use Illuminate\Database\Seeder;

class DidVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DidVendor::create([
            "vendor_name" => "Commio",
            "username" => "Natty",
            "token" => "b0297d1b8199f7516500a3544bfde67bf13748a4",
            "status" => "active",
        ]);
    }
}
