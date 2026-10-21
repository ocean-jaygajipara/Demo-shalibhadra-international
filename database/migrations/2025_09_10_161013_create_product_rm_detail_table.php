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
        Schema::create('product_rm_detail', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('size_premium')->nullable();
            $table->decimal('od_mm', 10, 2)->nullable();
            $table->decimal('id_mm', 10, 2)->nullable();
            $table->string('shape')->nullable();
            $table->string('alloy')->nullable();
            $table->decimal('density', 10, 2)->nullable();
            $table->decimal('tol_plus', 10, 2)->nullable();
            $table->decimal('tol_minus', 10, 2)->nullable();
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
        Schema::dropIfExists('product_rm_detail');
    }
};
