<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dispatch_items', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_id')->nullable();
            $table->string('sales_order_item_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_variant_price_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('unit')->nullable();
            $table->string('product_description')->nullable();
            $table->string('remaining_quantity')->nullable();
            $table->string('available_quantity')->nullable();
            $table->string('dispatch_quantity')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('dispatch_items');
    }
};