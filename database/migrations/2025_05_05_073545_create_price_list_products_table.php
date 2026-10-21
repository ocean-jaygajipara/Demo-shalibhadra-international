<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('price_list_products', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('category_id');
            $table->string('subcategory_id');
            $table->string('product_id');
            $table->string('product_variant_price_id');
            $table->string('price_list_id');
            $table->decimal('price', 10, 2);
            $table->decimal('discounted_selling_price', 10, 2);
            $table->decimal('discounted_amount', 10, 2);
            $table->string('discount_type')->default('Percentage');
            $table->decimal('discount_percentage_value', 10, 2);

            $table->string('status')->default('active');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('price_list_products');
    }
};