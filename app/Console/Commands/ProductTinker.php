<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;

class ProductTinker extends Command
{
    protected $signature = 'producttinker';
    protected $description = 'Clear old ProductCategory and ProductSubcategory data, and insert 5 new records each using factory';

    public function handle()
    {
        // Step 1: Delete old data
        ProductCategory::truncate();
        ProductSubcategory::truncate();

        // Step 2: Insert 5 new ProductCategory records
        ProductCategory::factory()->count(5)->create();

        // Step 3: Insert 5 new ProductSubcategory records
        ProductSubcategory::factory()->count(5)->create();

        $this->info("🧹 Old ProductCategory and ProductSubcategory data cleared.");
        $this->info("✅ 5 new ProductCategory and ProductSubcategory records created successfully.");
    }
}
