<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->tinyInteger('is_combo')->default(0);
            $table->string('quotation_item_id')->nullable();
            $table->string('sales_order_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_variant_price_id')->nullable();
            $table->string('price_list_product_id')->nullable();
            $table->integer('display_order')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_name')->nullable();
            $table->text('product_description')->nullable();
            $table->string('hsn_code')->nullable();
            $table->integer('outer_quantity')->nullable();
            $table->integer('inner_quantity')->nullable();
            $table->integer('loose_quantity')->nullable();
            $table->integer('kg_quantity')->nullable();
            $table->string('unit')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('remaining_quantity')->nullable();
            $table->integer('completation_time_adjustment_quantity')->nullable();
            $table->decimal('price_in_amount', 10, 2)->nullable();
            $table->decimal('discount_flat', 10, 2)->nullable();
            $table->decimal('discount_percentage', 10, 2)->nullable();
            $table->decimal('rate_in_amount', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('taxable_amount', 10, 2)->nullable();
            $table->decimal('gst_in_percentage', 10, 2)->nullable();
            $table->decimal('gst_amount', 10, 2)->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sales_order_items');
    }
};
