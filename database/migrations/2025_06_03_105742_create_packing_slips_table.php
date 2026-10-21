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
        Schema::create('packing_slips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->text('address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->unsignedBigInteger('price_list_id')->nullable();
            $table->date('packing_slip_date')->nullable();
            $table->string('packing_slip_no')->nullable();
            $table->string('sales_order_no')->nullable();
            $table->string('sales_order_date')->nullable();
            $table->unsignedBigInteger('sales_status_id')->nullable();
            $table->string('created_type')->nullable();
            $table->string('updated_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes(); // For soft delete support
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_slips');
    }
};
