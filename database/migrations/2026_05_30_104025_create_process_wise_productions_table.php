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
        Schema::create('process_wise_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('date')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('process')->nullable();
            $table->decimal('weight', 15, 3)->nullable();
            $table->decimal('wt_pc', 15, 3)->nullable();
            $table->integer('qty')->nullable();
            
            $table->string('prev_process')->nullable();
            $table->decimal('prev_weight', 15, 3)->nullable();
            $table->decimal('prev_wt_pc', 15, 3)->nullable();
            $table->integer('prev_qty')->nullable();
            
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            
            $table->string('status')->default('active');
            $table->string('created_type')->nullable();
            $table->string('updated_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_wise_productions');
    }
};
