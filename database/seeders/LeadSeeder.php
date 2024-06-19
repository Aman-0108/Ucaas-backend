<?php

namespace Database\Seeders;

use App\Models\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            "admin_name" => "webvio",
            "alternate_contact_no" => "1234567895",
            "building" => "44",
            "city" => "kolkata",
            "company_name" => "webvio",
            "contact_no" => "9874563254",
            "country" => "IN",
            "email" => "webvio@google.com",
            "package_id" => "2",
            "state" => "WB",
            // "status" => "active",
            "street" => "Robinson",
            "timezone_id" => "2",
            "unit" => "D",
            "zip" => "700135"
        ];

        Lead::create($data);
    }
}
