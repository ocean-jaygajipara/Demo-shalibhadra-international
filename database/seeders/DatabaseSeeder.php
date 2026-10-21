<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(CreateAdminSoftwreSeeder::class);
        $this->call(MainMenuSeeder::class);
        $this->call(PlanMasterSeeder::class);
        $this->call(CompanySeeder::class);
        // $this->call(CustomerTypeSeeder::class);
        // $this->call(LeaveTypeSeeder::class);

        /** Passport client create */
        Artisan::call("passport:client --personal");
        echo 'Passport client create successfully.';
    }
}
