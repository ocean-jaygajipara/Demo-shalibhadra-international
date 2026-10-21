<?php

namespace Database\Factories;

use App\Models\MarketingStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketingStatusFactory extends Factory
{
    protected $model = MarketingStatus::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['Inquiry', 'Followup']);

        $namesByType = [
            'Inquiry' => ['Suspected', 'Prospected', 'In-Progress'],
            'Followup' => ['Open', 'Close', 'Connected'],
        ];

        $statusColors = ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6f42c1'];

        return [
            'company_id' => Company::inRandomOrder()->value('id'),
            'type' => $type,
            'name' => $this->faker->randomElement($namesByType[$type]),
            'status_color' => $this->faker->randomElement($statusColors),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
