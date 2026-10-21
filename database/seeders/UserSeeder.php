<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        $users = [[
            'username' => 'Nimit-Work',
            'name' => 'Nimit Work',
            'email' => 'nimit.work.1922@gmail.com',
            'phone' => '9978911174',
            'sp' => 'nimit@123',
            'password' => bcrypt('nimit@123'),
            'created_by' => '0'
        ], [
            'username' => 'Ocean-Office',
            'name' => 'Ocean Office',
            'email' => 'nimit.ocean@gmail.com',
            'phone' => '123456789',
            'sp' => 'Ocean@2025',
            'password' => bcrypt('Ocean@2025'),
            'created_by' => '0'
        ]];

        foreach($users AS $user_data){
            $user = AdminSoftware::create($user_data);
        }
            */

            User::create([
                'name' => 'Nimit Work',
                'email' => 'nimit.work.1922@gmail.com',
                'password' => bcrypt('nimit@123'),
            ]);
    }
}
