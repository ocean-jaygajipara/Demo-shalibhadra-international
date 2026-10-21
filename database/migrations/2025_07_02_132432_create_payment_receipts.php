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
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->date('date');
            $table->string('customer_type_id');
            $table->string('customer_id');
            $table->string('receipt_type');
            $table->string('quotation_id')->nullable();
            $table->string('sales_order_id')->nullable();
            $table->string('receipt_no');
            $table->string('team_person_ids');
            $table->string('payment_by');
            $table->string('cheque_no')->nullable();
            $table->string('upi_no')->nullable();
            $table->string('payment_reason')->nullable();
            $table->string('payment_mode');
            $table->string('amount');
            $table->string('remark')->nullable();
            $table->string('status')->default('pending')->comment('pending, approve');
            $table->string('deleted_by')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
