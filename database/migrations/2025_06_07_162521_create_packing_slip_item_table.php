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
        Schema::create('packing_slip_item', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('is_combo')->default(0);
            $table->unsignedBigInteger('packing_slip_id');
            $table->string('sales_order_item_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_variant_price_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_description')->nullable();
            $table->string('box_no')->nullable();
            $table->string('hsn_code')->nullable();
            $table->string('unit')->nullable();
            // $table->string('no_of_carton')->nullable();
            $table->string('sales_order_no')->nullable();
            $table->string('sales_order_date')->nullable();
            $table->decimal('wt_pc', 8, 3)->nullable();
            $table->decimal('qty_pc', 8, 3)->nullable();
            $table->decimal('org_qty_pc', 10, 3)->nullable();
            $table->decimal('total_packet', 8, 3)->nullable();
            $table->decimal('qty_ctn', 8, 3)->nullable();
            $table->decimal('actual_wt_ctn', 8, 3)->nullable();
            $table->decimal('total_qty', 8, 3)->nullable();
            $table->decimal('total_weight', 8, 3)->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_slip_item');
    }
};
