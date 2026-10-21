<?php

namespace Database\Factories;

use App\Models\SalesStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesStatusFactory extends Factory
{
    protected $model = SalesStatus::class;

    public function definition()
    {
        // Define the possible sales types as per the enum
        $salesTypes = ['Quotation', 'Order', 'Invoice'];

        return [
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1, // Randomly select company_id
            'salestype' => $this->faker->randomElement($salesTypes), // Select random sales type from the predefined list
            'statusname' => $this->faker->randomElement(['New', 'In Progress', 'Completed', 'On Hold', 'Cancelled', 'Pending']), // Random status name
            'status_color' => $this->faker->hexColor(), // Random color code
            'display_order' => $this->faker->numberBetween(1, 10), // Random display order between 1 and 10
            'status' => 'active', // Random status
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => null,
        ];
    }
}
