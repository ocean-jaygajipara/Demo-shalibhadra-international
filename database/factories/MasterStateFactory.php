<?php

namespace Database\Factories;

use App\Models\MasterState;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterStateFactory extends Factory
{
    protected $model = MasterState::class;

    public function definition()
    {
        return [
            'country_id' => 0, // This will be set dynamically from the command
            'name' => $this->faker->unique()->state, // Generates a unique state name
            'status' => $this->faker->randomElement(['active', 'inactive']), // Random status
            'created_by' => 1, // Static creator ID
            'updated_by' => 1, // Static updater ID
            'deleted_by' => null, // Static deleted_by field
        ];
    }
}
