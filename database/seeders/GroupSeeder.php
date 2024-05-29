<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $uuid = Str::uuid()->toString();

        Group::create([
            'account_id' => 1,
            'group_name' => 'Gr1',
            'created_by' => 1,
            'uid_no' => '',
        ]);

        Group::create([
            'account_id' => 1,
            'group_name' => 'Gr2',
            'created_by' => 1,
            'uid_no' => '',            
        ]);
    }
}
