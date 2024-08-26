<?php

namespace Database\Seeders;

use App\Models\SipProfileDomain;
use Illuminate\Database\Seeder;

class SipProfileDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            "sip_profile_id" => "1",
            "name" => "192.168.2.225",
            "alias" => "192.168.2.225"
        ];

        SipProfileDomain::create($data);
    }
}
