<?php

namespace Database\Factories;

use App\Models\MasterArea;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterAreaFactory extends Factory
{
    protected $model = MasterArea::class;

    public function definition()
    {
        return [
            'country_id' => 0, // Set dynamically
            'state_id' => 0,   // Set dynamically
            'city_id' => 0,    // Set dynamically
            'area_name' => $this->faker->unique()->streetName,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
