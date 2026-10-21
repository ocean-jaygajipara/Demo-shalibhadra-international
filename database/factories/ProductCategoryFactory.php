<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        // Get random existing company ID between 1 and 5
        $companyId = Company::whereBetween('id', [1, 5])->inRandomOrder()->value('id');

        return [
            'company_id' => $companyId,
            'name' => $this->faker->randomElement([
                'Electronics',
                'Furniture',
                'Clothing',
                'Automotive',
                'Healthcare',
                'Food & Beverage',
                'Beauty & Personal Care',
                'Toys',
                'Home Appliances',
                'Books',
            ]),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
