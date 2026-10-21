<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('product_category_id')->nullable();
            $table->string('term_condition_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('gst_no')->nullable();
            $table->string('sales_status_id')->nullable();
            $table->string('address')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('purchase_order_date')->nullable();
            $table->string('purchase_order_no')->nullable();
            $table->string('transport_by_id')->nullable();
            $table->string('transport_details_id')->nullable();
            $table->decimal('cash_discount_percentage', 10, 2)->nullable();
            $table->decimal('cash_discount_amount', 10, 2)->nullable();
            $table->decimal('additional_discount_percentage', 10, 2)->nullable();
            $table->decimal('additional_discount_amount', 10, 2)->nullable();
            $table->decimal('packing_forwarding_charge', 10, 2)->nullable();
            $table->decimal('transport_charge', 10, 2)->nullable();
            $table->decimal('total_taxable', 10, 2)->nullable();
            $table->string('is_gst_applicable')->default('yes');
            $table->decimal('gst_amount', 10, 2)->nullable();
            $table->string('is_tcs_applicable')->default('no');
            $table->decimal('tcs_amount', 10, 2)->nullable();
            $table->decimal('round_off', 10, 2)->nullable();
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->decimal('master_quantity', 10, 2)->nullable();
            $table->decimal('master_amount', 10, 2)->nullable();
            $table->decimal('master_taxable_amount', 10, 2)->nullable();
            $table->decimal('master_gst_amount', 10, 2)->nullable();
            $table->decimal('master_sub_total', 10, 2)->nullable();
            $table->text('term_and_condition')->nullable();
            $table->text('regards')->nullable();
            $table->text('notes')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('status')->default('active');
            $table->string('platform')->default('web');
            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
            $table->string('created_by')->nullable()->index();
            $table->string('updated_by')->nullable()->index();
            $table->timestamps();
            $table->string('deleted_by')->nullable()->index();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('purchase_orders');
    }
};
