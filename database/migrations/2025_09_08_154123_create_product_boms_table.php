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
        Schema::create('product_bom', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('bom_product_category_id')->nullable();
            $table->string('bom_product_variant_price_id')->nullable();
            $table->string('material_name')->nullable();
            $table->string('last_process')->nullable();
            $table->decimal('required_material_qty', 10, 3)->default(0);
            $table->integer('for_product_qty')->nullable();
            $table->string('use_in_operation')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bom');
    }
};
