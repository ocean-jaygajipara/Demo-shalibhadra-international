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
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('product_combo_name');
            $table->string('display_unit_id');
            $table->string('product_category_id')->nullable();
            $table->string('price');
            $table->string('part_no')->nullable();
            $table->string('gross_wt')->nullable();
            $table->string('net_wt')->nullable();
            $table->string('grosse_per_qty')->nullable();
            $table->string('hsn_code')->nullable();
            $table->string('product_tax_id')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_combos');
    }
};
