<?php

namespace Database\Seeders;

use App\Models\AdminSoftware;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CreateAdminSoftwreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [[
            'username' => 'Nimit-Work',
            'name' => 'Nimit Work',
            'email' => 'nimit.work.1922@gmail.com',
            'phone' => '9978911174',
            'sp' => 'nimit@123',
            'password' => bcrypt('nimit@123'),
            'created_by' => '0'
        ], [
            'username' => 'Demo-User',
            'name' => 'Ocean CRM',
            'email' => 'demo@oceancrm.com',
            'phone' => '123456789',
            'sp' => 'Demo@123',
            'password' => bcrypt('Demo@123'),
            'created_by' => '0'
        ]];

        foreach($users AS $user_data){
            $user = AdminSoftware::create($user_data);
        }
    }
}
