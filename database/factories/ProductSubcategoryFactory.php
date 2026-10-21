<?php

namespace Database\Factories;

use App\Models\ProductSubcategory;
use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSubcategoryFactory extends Factory
{
    protected $model = ProductSubcategory::class;

    public function definition(): array
    {
        // Ensure the selected IDs exist in DB
        $companyId = Company::whereBetween('id', [1, 5])->inRandomOrder()->value('id');
        $categoryId = ProductCategory::inRandomOrder()->value('id');

        return [
            'company_id' => $companyId,
            'product_category_id' => $categoryId,
            'name' => $this->faker->randomElement([
                'Laptops',
                'Smartphones',
                'Office Chairs',
                'Men\'s Wear',
                'Vitamins',
                'Kitchen Appliances',
                'Hair Care',
                'Bookshelves',
                'Children\'s Toys',
                'Cookware'
            ]),
            'status' => $this->faker->randomElement(['active']),
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
