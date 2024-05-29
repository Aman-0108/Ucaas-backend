<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Domain;
use App\Models\Uid;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $uid = Uid::find(1);

        Domain::create([
            'domain_name' => '192.168.1.150',
            'created_by' => 1,
            'account_id' => 1,
            'uid_no' => $uid->uid_no
        ]);

        Domain::create([
            'domain_name' => '192.168.1.21',
            'created_by' => 1,
            'account_id' => 1,
            'uid_no' => $uid->uid_no
        ]);

        Domain::create([
            'domain_name' => '192.168.1.22',
            'created_by' => 1,
            'account_id' => 1,
            'uid_no' => $uid->uid_no
        ]);
    }
}
