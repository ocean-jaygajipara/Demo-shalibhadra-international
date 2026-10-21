<?php

namespace Database\Factories;

use App\Models\MasterTransportDetail;
use App\Models\MasterTransportBy;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterTransportDetailFactory extends Factory
{
    protected $model = MasterTransportDetail::class;

    public function definition()
    {
        $transportNames = [
            'Gati Logistics',
            'VRL Transport',
            'TCI Freight',
            'Blue Dart Express',
            'DTDC Cargo',
            'Safe Express',
            'Delhivery',
            'FedEx Logistics',
            'Agarwal Packers',
            'Mahindra Logistics'
        ];

        return [
            'company_id' => Company::inRandomOrder()->first()->id,
            'transport_by_id' => MasterTransportBy::inRandomOrder()->first()->id,
            'name' => $this->faker->randomElement($transportNames),
            'status' => 'active',
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => null,
        ];
    }
}
