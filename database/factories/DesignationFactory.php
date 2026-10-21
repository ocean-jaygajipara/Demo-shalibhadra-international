<?php

namespace Database\Factories;

use App\Models\Designation;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    public function definition()
    {
        $designationNames = [
            'Software Engineer',
            'Admin',
            'Project Manager',
            'HR Manager',
            'Sales Executive',
            'Marketing Specialist',
            'Business Analyst',
            'Product Owner',
            'Team Lead',
            'UX Designer',
            'Data Scientist',
            'Database Administrator',
            'System Administrator',
            'Web Developer',
            'Mobile Developer',
            'Quality Analyst',
        ];

        return [
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1, // Randomly select company_id
            'name' => $this->faker->randomElement($designationNames), // Random designation name
            'status' => 'active',
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => null,
        ];
    }
}
