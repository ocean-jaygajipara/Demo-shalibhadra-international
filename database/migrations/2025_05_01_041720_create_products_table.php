<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->string('company_id');
            $table->string('product_category_id');
            $table->string('product_name');
            $table->string('part_no')->nullable();
            $table->string('product_grade_id')->nullable();
            $table->string('unit_id')->nullable();
            $table->string('hsn_code')->nullable();
            $table->string('product_tax_id')->nullable();
            //$table->decimal('product_mrp', 15, 2);
            $table->integer('grosse_per_qty')->nullable();
            $table->decimal('gross_wt', 15, 3)->nullable();
            $table->decimal('net_wt', 15, 3)->nullable();
            $table->decimal('min_stock', 15, 2)->nullable();
            $table->decimal('opening_kg', 15, 3)->nullable();
            $table->integer('opening_pcs')->nullable();
            $table->string('std_packing', 100)->nullable();
            $table->string('status')->default('active');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('products');
    }
};
