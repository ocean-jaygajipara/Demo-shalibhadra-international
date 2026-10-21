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
        Schema::create('process_wise_production_by_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_wise_production_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('stock', 15, 3)->nullable();
            $table->integer('produced_qty')->nullable();
            $table->decimal('produced_wt', 15, 3)->nullable();
            $table->string('batch_no')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_wise_production_by_products');
    }
};
