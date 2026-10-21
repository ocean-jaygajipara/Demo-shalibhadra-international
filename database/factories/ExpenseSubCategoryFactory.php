<?php

namespace Database\Factories;

use App\Models\ExpenseSubCategory;
use App\Models\ExpenseCategory;
use App\Models\Company;
use App\Models\TeamPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseSubCategoryFactory extends Factory
{
    protected $model = ExpenseSubCategory::class;

    public function definition()
    {
        // Generate the expense category first
        $expenseCategory = ExpenseCategory::factory()->create();

        // Define subcategory names based on the expense category
        $subcategoryNames = [];
        switch ($expenseCategory->name) {
            case 'Travel':
                $subcategoryNames = ['Air Travel', 'Train Travel', 'Hotel Accommodation', 'Taxi', 'Car Rental'];
                break;
            case 'Food':
                $subcategoryNames = ['Meals', 'Snacks', 'Catering', 'Buffet', 'Lunch'];
                break;
            case 'Office Supplies':
                $subcategoryNames = ['Stationery', 'Printer Supplies', 'Office Furniture', 'Whiteboards', 'Software Licenses'];
                break;
            case 'Utilities':
                $subcategoryNames = ['Electricity', 'Water', 'Gas', 'Internet', 'Phone Bills'];
                break;
            case 'Equipment':
                $subcategoryNames = ['Computers', 'Projectors', 'Office Chairs', 'Printers', 'Telephones'];
                break;
            case 'Advertising':
                $subcategoryNames = ['Online Ads', 'Print Ads', 'TV Ads', 'Radio Ads', 'Billboards'];
                break;
            case 'Employee Benefits':
                $subcategoryNames = ['Health Insurance', 'Bonuses', 'Retirement Plans', 'Paid Time Off', 'Gym Membership'];
                break;
            case 'Transportation':
                $subcategoryNames = ['Fuel', 'Parking Fees', 'Tolls', 'Bus Passes', 'Car Maintenance'];
                break;
            case 'Rent':
                $subcategoryNames = ['Office Rent', 'Storage Rent', 'Parking Space Rent', 'Building Maintenance'];
                break;
            case 'Software Licenses':
                $subcategoryNames = ['Operating System', 'Productivity Software', 'Design Tools', 'Accounting Software', 'CRM Software'];
                break;
            default:
                $subcategoryNames = ['General'];
                break;
        }

        // Randomly assign the expense type (General, KM, Food)
        $expenseType = $this->faker->randomElement(['General', 'KM', 'Food']);

        // Initialize values based on the expense type
        $minAmount = $maxAmount = $perKmRate = $fixAmount = $fromTime = $toTime = null;

        switch ($expenseType) {
            case 'General':
                $minAmount = $this->faker->randomFloat(2, 1, 100);
                $maxAmount = $this->faker->randomFloat(2, 101, 1000);
                break;
            case 'KM':
                $perKmRate = $this->faker->randomFloat(2, 0.5, 5);
                break;
            case 'Food':
                $fixAmount = $this->faker->randomFloat(2, 50, 500);
                $fromTime = $this->faker->time();
                $toTime = $this->faker->time();
                break;
        }

        return [
            'company_id' => $this->faker->numberBetween(1, 5), // Company ID between 1 and 5
            'expense_category_id' => $expenseCategory->id, // Use the created ExpenseCategory ID
            'team_person_ids' => TeamPerson::factory(), // Assuming you want a team person related to each subcategory
            'name' => $this->faker->randomElement($subcategoryNames), // Dynamic subcategory names based on the ExpenseCategory
            'expense_type' => $expenseType, // Randomly assigned expense type
            'is_image_required' => $this->faker->boolean(), // Randomly decide if image is required
            // General
            'min_amount' => $minAmount, // Only filled for "General"
            'max_amount' => $maxAmount, // Only filled for "General"
            // Kilometer
            'per_km_rate' => $perKmRate, // Only filled for "KM"
            // Food
            'fix_amount' => $fixAmount, // Only filled for "Food"
            'from_time' => $fromTime, // Only filled for "Food"
            'to_time' => $toTime, // Only filled for "Food"
            'status' => $this->faker->randomElement(['active']), // Random status
            'created_by' => $this->faker->numberBetween(1, 5), // Assuming a valid user ID range
            'updated_by' => $this->faker->numberBetween(1, 5), // Assuming a valid user ID range
            'deleted_by' => $this->faker->numberBetween(1, 5), // Assuming a valid user ID range
        ];
    }
}
