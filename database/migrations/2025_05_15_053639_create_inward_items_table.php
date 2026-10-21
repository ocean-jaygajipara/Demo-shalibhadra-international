<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inward_items', function (Blueprint $table) {
            $table->id();
            $table->string('inward_id')->nullable();
            $table->string('purchase_order_item_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_variant_price_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('unit')->nullable();
            $table->string('product_description')->nullable();
            $table->string('remaining_quantity')->nullable();
            $table->string('available_quantity')->nullable();
            $table->string('inward_quantity')->nullable();
            $table->string('product_weight')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inward_items');
    }
};