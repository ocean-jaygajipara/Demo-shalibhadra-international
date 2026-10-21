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
        Schema::create('product_customer_item', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_item_code')->nullable();
            $table->text('customer_item_description')->nullable();
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
        Schema::dropIfExists('product_customer_item');
    }
};
