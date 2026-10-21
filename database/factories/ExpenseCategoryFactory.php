<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition()
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 5), // Company ID between 1 and 5
            'name' => $this->faker->randomElement(['Food', 'Transport', 'Office Supplies', 'Travel', 'Entertainment', 'Healthcare']), // Predefined list of names
            'status' => $this->faker->randomElement(['active']), // Only "active" status
            'created_by' => $this->faker->numberBetween(1, 5), // Replace with a valid user ID range
            'updated_by' => $this->faker->numberBetween(1, 5), // Replace with a valid user ID range
        ];
    }
}
