<?php

namespace Database\Seeders;

use App\Models\SofiaGlobalSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SofiaGlobalsettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $jsonData = '{
            "data": [
                {
                    "name": "log-level",
                    "value": "0",
                    "description": "",
                    "enabled": "1",
                    "created_by": "1",
                    "isEditable": false
                },
                {
                    "name": "abort-on-empty-external-ip",
                    "value": "true",
                    "description": "",
                    "enabled": "0",
                    "created_by": "1",
                    "isEditable": false
                },
                {
                    "name": "auto-restart",
                    "value": "false",
                    "description": "",
                    "enabled": "0",
                    "created_by": "1",
                    "isEditable": false
                },
                {
                    "name": "debug-presence",
                    "value": "0",
                    "description": "",
                    "enabled": "1",
                    "created_by": "1",
                    "isEditable": false
                },
                {
                    "name": "capture-server",
                    "value": "udp:homer.domain.com:5060",
                    "description": "",
                    "enabled": "0",
                    "created_by": "1",
                    "isEditable": false
                }
            ]
        }';
        
        // Convert JSON data to PHP associative array
        $arrayData = json_decode($jsonData, true);
        
        // Extract the 'data' array from the associative array
        $data = $arrayData['data'];
    
        foreach($data as $row) {
            SofiaGlobalSetting::create($row);
        }
        
    }
}
