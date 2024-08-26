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
                "id": "1",
                "name": "internal",
                "description": "default internal profile",
                "hostname": "debian",
                "enabled": "1",
                "created_by": "1",
                "deleted_at": null,
                "created_at": "2024-08-26 12:48:39",
                "updated_at": "2024-08-26 12:48:39"
              },
              {
                "id": "2",
                "name": "external",
                "description": "default external profile",
                "hostname": "debian",
                "enabled": "1",
                "created_by": "1",
                "deleted_at": null,
                "created_at": "2024-08-26 12:48:39",
                "updated_at": "2024-08-26 12:48:39"
              },
              {
                "id": "3",
                "name": "internal-ipv6",
                "description": "default internal-ipv6 profile",
                "hostname": "debian",
                "enabled": "1",
                "created_by": "1",
                "deleted_at": null,
                "created_at": "2024-08-26 12:48:39",
                "updated_at": "2024-08-26 12:48:39"
              },
              {
                "id": "4",
                "name": "external-ipv6",
                "description": "default external-ipv6 profile",
                "hostname": "debian",
                "enabled": "1",
                "created_by": "1",
                "deleted_at": null,
                "created_at": "2024-08-26 12:48:39",
                "updated_at": "2024-08-26 12:48:39"
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
