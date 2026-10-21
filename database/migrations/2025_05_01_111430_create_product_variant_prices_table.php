<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_variant_prices', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            $table->string('variant_id');

            $table->boolean('including_gst')->default(false);
            $table->text('description')->nullable();
            $table->string('product_weight')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('inner_pack_qty');
            $table->integer('outer_pack_qty');
            $table->integer('min_stock_qty')->nullable();
            $table->integer('max_stock_qty')->nullable();

            // Audit Fields
            $table->string('status')->default('active');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('product_variant_prices');
    }
};
