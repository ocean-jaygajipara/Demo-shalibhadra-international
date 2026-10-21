<?php

namespace Database\Factories;

use App\Models\MasterCountry;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterCountryFactory extends Factory
{
    protected $model = MasterCountry::class;

    protected $countries = [
        ['name' => 'India', 'short_name' => 'IND', 'code' => '91'],
        ['name' => 'United States', 'short_name' => 'USA', 'code' => '1'],
        ['name' => 'United Kingdom', 'short_name' => 'GBR', 'code' => '44'],
        ['name' => 'Australia', 'short_name' => 'AUS', 'code' => '61'],
        ['name' => 'Canada', 'short_name' => 'CAN', 'code' => '1'],
        ['name' => 'Germany', 'short_name' => 'DEU', 'code' => '49'],
        ['name' => 'France', 'short_name' => 'FRA', 'code' => '33'],
        ['name' => 'Japan', 'short_name' => 'JPN', 'code' => '81'],
        ['name' => 'China', 'short_name' => 'CHN', 'code' => '86'],
        ['name' => 'Brazil', 'short_name' => 'BRA', 'code' => '55'],
    ];

    public function definition()
    {
        $country = $this->faker->unique()->randomElement($this->countries);

        return [
            'name' => $country['name'],
            'code' => $country['code'],
            'short_name' => $country['short_name'],
            'flag' => null,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
