<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
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
                'package_id' => 1,
                'name' => 'Unlimited Domestic Calling'
            ],
            [
                'package_id' => 1,
                'name' => 'SMS and MMS'
            ],
            [
                'package_id' => 1,
                'name' => 'IVR'
            ],
            [
                'package_id' => 1,
                'name' => 'Visual Voicemail'
            ],
            [
                'package_id' => 1,
                'name' => 'Single Signin'
            ],
            [
                'package_id' => 1,
                'name' => 'Realtime Quality of service'
            ],
            [
                'package_id' => 2,
                'name' => 'Auto call recording'
            ],
            [
                'package_id' => 2,
                'name' => 'Advanced call monitoring and handling'
            ],
            [
                'package_id' => 2,
                'name' => 'Unlimited internet fax'
            ],
            [
                'package_id' => 2,
                'name' => 'Adoption and usage analytics with 6 months of storage'
            ],
            [
                'package_id' => 2,
                'name' => 'Archiver for cloud storage back-up'
            ],
            [
                'package_id' => 2,
                'name' => 'Custom roles and permissions'
            ],
            [
                'package_id' => 3,
                'name' => 'Customizable business analytics with 12 months of storage'
            ],
            [
                'package_id' => 3,
                'name' => 'Unlimited AI-powered video meetings, up to 200 participants'
            ],
            [
                'package_id' => 3,
                'name' => 'Device analytics and alerts'
            ],
            [
                'package_id' => 3,
                'name' => 'Unlimited storage for files and recordings'
            ]
        ];

        // Map a new field to each subarray
        foreach ($data as &$row) {
            $row['created_at'] = date("Y-m-d H:i:s");
            $row['updated_at'] = date("Y-m-d H:i:s");
        }

        Feature::insert($data);
    }
}
