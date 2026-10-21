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
        Schema::create('process_wise_production_used_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_wise_production_id');
            $table->unsignedBigInteger('material_id')->nullable();
            $table->unsignedBigInteger('used_from_godown_id')->nullable();
            $table->unsignedBigInteger('used_from_location_id')->nullable();
            $table->string('last_process')->nullable();
            $table->string('batch_no')->nullable();
            $table->decimal('stock', 15, 3)->nullable();
            $table->decimal('machine_stock', 15, 3)->nullable();
            $table->integer('used_qty')->nullable();
            $table->decimal('used_wt', 15, 3)->nullable();
            $table->decimal('wt_pc', 15, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_wise_production_used_materials');
    }
};
