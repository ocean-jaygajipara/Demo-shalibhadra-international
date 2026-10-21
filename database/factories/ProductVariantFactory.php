<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition()
    {
        return [
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1, // Random company or fallback to 1
            'name' => 'WithOut Variant', // Single variant name for all companies
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
