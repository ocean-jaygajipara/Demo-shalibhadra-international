<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TeamPerson;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

class CreateTeamPerson extends Command
{
    protected $signature = 'tinker:create-teamperson';
    protected $description = 'Create one TeamPerson for each core company';

    public function handle(): void
    {
        $this->info("🚀 Creating 3 TeamPersons for specific companies...");

        // Define companies and their team members
        $employees = [
            'RM Engineering'     => ['name' => 'Kiran Bhai',      'username' => 'kiran.rm'],
            'Jeel Flow Pvt Ltd'  => ['name' => 'Bhavesh Bhai',    'username' => 'bhavesh.jeel'],
            'Ocean Infotech'     => ['name' => 'Sandip Gajera',   'username' => 'sandip.ocean'],
        ];

        foreach ($employees as $companyName => $employeeData) {
            $company = Company::where('company_name', $companyName)->first();

            if (!$company) {
                $this->error("❌ Company '$companyName' not found.");
                continue;
            }

            $person = TeamPerson::create([
                'company_id' => $company->id,
                'name' => $employeeData['name'],
                'username' => $employeeData['username'],
                'password' => Hash::make('password123'),
                'mobile_no' => fake()->numerify('98########'),
                'employee_code' => 'EMP' . fake()->unique()->numberBetween(100, 999),
                'sp' => 'Sales Person',
                'min_working_start_time' => '09:00:00',
                'max_working_start_time' => '10:00:00',
                'working_end_time' => '18:00:00',
                'address' => fake()->address,
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'area_id' => 1,
                'email' => fake()->unique()->safeEmail,
                'birth_date' => fake()->date('Y-m-d', '2000-01-01'),
                'designation_id' => 1,
                'team_role_id' => 1,
                'app_version' => '1.0.0',
                'status' => 'active',
                'created_type' => 'admin',
                'created_by' => 1,
                'updated_by' => null,
                'deleted_by' => null,
            ]);

            $this->info("✅ Created TeamPerson '{$person->name}' for '{$companyName}' (ID: {$person->id})");
        }

        $this->info("🎉 Done! 3 TeamPersons added successfully.");
    }
}
