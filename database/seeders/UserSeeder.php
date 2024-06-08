<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'developer',
            'email' => 'developer@webvio.com',
            'password' => bcrypt('123456'),
            'username' => 'developer',
            'usertype' => 'SuperAdmin'
        ]);       
    }
}
