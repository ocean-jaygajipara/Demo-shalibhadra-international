<?php

namespace Database\Factories;

use App\Models\MasterTransportBy;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterTransportByFactory extends Factory
{
    protected $model = MasterTransportBy::class;

    public function definition()
    {
        $transportOptions = ['By Road', 'By Air', 'By Sea', 'By Rail', 'Courier Service'];

        return [
            'company_id' => Company::inRandomOrder()->first()->id,
            'name' => $this->faker->unique()->randomElement($transportOptions),
            'status' => 'active',
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => null,
        ];
    }
}
