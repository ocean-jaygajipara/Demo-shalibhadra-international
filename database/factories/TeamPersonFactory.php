<?php

namespace Database\Factories;

use App\Models\TeamPerson;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TeamPersonFactory extends Factory
{
    protected $model = TeamPerson::class;

    public function definition(): array
    {
        $companyIds = Company::pluck('id')->toArray();
        return [
            'company_id' => $this->faker->randomElement($companyIds ?? [1]),
            'name' => $this->faker->name,
            'parent_type_id' => $this->faker->numberBetween(0, 3),
            'mobile_no' => $this->faker->numerify('98########'),
            'employee_code' => 'EMP' . $this->faker->unique()->numberBetween(100, 999),
            'username' => 'user' . $this->faker->unique()->numberBetween(1000, 9999),
            'password' => Hash::make('password123'),
            'sp' => 'Sales Person',
            'min_working_start_time' => '09:00:00',
            'max_working_start_time' => '10:00:00',
            'working_end_time' => '18:00:00',
            'address' => $this->faker->address,
            'country_id' => 1,
            'state_id' => 1,
            'city_id' => 1,
            'area_id' => 1,
            'email' => $this->faker->unique()->safeEmail,
            'birth_date' => $this->faker->date('Y-m-d', '2000-01-01'),
            'designation_id' => 1,
            'team_role_id' => 1,
            'app_version' => '1.0.0',
            'status' => 'active',
            'created_type' => 'admin',
            'created_by' => 1,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}
