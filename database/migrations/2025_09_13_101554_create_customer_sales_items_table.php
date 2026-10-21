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
        Schema::create('customer_sales_items', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->string('item_code')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('rate', 15, 2)->nullable();
            $table->string('type')->nullable();
            $table->date('date')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('platform')->default('web')->comment('web, android, ios');
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
        Schema::dropIfExists('customer_sales_items');
    }
};
