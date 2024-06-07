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
            'usertype' => 'SupreAdmin'
        ]);

        // User::create([
        //     'name' => 'Azhar',
        //     'email' => 'peter@webvio.com',
        //     'password' => bcrypt('123456'),
        //     'username' => 'Azhar'
        // ]);

        // User::create([
        //     'name' => 'Tushar',
        //     'email' => 'john@webvio.com',
        //     'password' => bcrypt('123456'),
        //     'username' => 'Tushar'
        // ]);       

        // User::create([
        //     'name' => 'Ravi',
        //     'email' => 'ravi@webvio.com',
        //     'password' => bcrypt('123456'),
        //     'username' => 'Ravi'
        // ]);

        // User::create([
        //     'name' => 'solman',
        //     'email' => 'solman@webvio.com',
        //     'password' => bcrypt('123456'),
        //     'username' => 'solman'
        // ]);

        // User::create([
        //     'name' => 'Bikash',
        //     'email' => 'bikash@webvio.com',
        //     'password' => bcrypt('123456'),
        //     'username' => 'Bikash'
        // ]);
       
    }
}
