<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_work_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('job_work_order_id')->nullable();
            $table->string('product_id')->nullable();
            $table->integer('display_order')->nullable();
            $table->string('product_name')->nullable();
            $table->text('product_code')->nullable();
            $table->decimal('product_stock', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('price_in_amount', 10, 2)->nullable();
            $table->decimal('discount_flat', 10, 2)->nullable();
            $table->decimal('discount_percentage', 10, 2)->nullable();
            $table->decimal('rate_in_amount', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('taxable_amount', 10, 2)->nullable();
            $table->decimal('gst_in_percentage', 10, 2)->nullable();
            $table->decimal('gst_amount', 10, 2)->nullable();
            $table->decimal('cgst_in_percentage', 10, 2)->nullable();
            $table->decimal('cgst_amount', 10, 2)->nullable();
            $table->decimal('sgst_in_percentage', 10, 2)->nullable();
            $table->decimal('sgst_amount', 10, 2)->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_work_order_items');
    }
};
