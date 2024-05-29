<?php

namespace Database\Seeders;

use App\Models\SipProfile;
use Illuminate\Database\Seeder;

class SipProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jsonData = '{
            "data": [
                {
                    "name": "internal",
                    "description": "default internal profile",
                    "hostname": "",
                    "enabled": "1",
                    "created_by": "1"
                },
                {
                    "name": "external",
                    "description": "default external profile",
                    "hostname": "",
                    "enabled": "1",    
                    "created_by": "1"               
                },
                {
                    "name": "internal-ipv6",
                    "description": "default internal-ipv6 profile",
                    "hostname": "",
                    "enabled": "1",    
                    "created_by": "1"                
                },
                {
                    "name": "external",
                    "description": "default external-ipv6 profile",
                    "hostname": "",
                    "enabled": "1", 
                    "created_by": "1"                 
                }
            ]
        }';

        // Convert JSON data to PHP associative array
        $arrayData = json_decode($jsonData, true);

        // Extract the 'data' array from the associative array
        $data = $arrayData['data'];

        foreach ($data as $row) {
            SipProfile::create($row);
        }
    }
}
