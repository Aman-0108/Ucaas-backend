<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Uid;
use Illuminate\Support\Str;

class UidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $uuid = Str::uuid()->toString();

        Uid::create([
            'uid_no' => $uuid,
        ]);
    }
}
