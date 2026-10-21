<?php

namespace Database\Factories;

use App\Models\MasterCity;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterCityFactory extends Factory
{
    protected $model = MasterCity::class;

    public function definition()
    {
        return [
            'country_id' => 0, // Will be set dynamically in the seeder/command
            'state_id' => 0,   // Will be set dynamically in the seeder/command
            'name' => $this->faker->unique()->city,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
