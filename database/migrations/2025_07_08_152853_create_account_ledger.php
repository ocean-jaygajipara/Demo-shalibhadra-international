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

        Schema::create('account_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_type_id');
            $table->unsignedBigInteger('customer_id');
            $table->date('entry_date')->nullable();

            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->string('receipt_type')->nullable();
            $table->string('quotation_id')->nullable();
            $table->string('sales_order_id')->nullable();

            $table->enum('payment_mode', ['debit', 'credit'])->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('debit_amount', 10, 2)->default(0);
            $table->decimal('credit_amount', 10, 2)->default(0);
            $table->decimal('closing_balance', 12, 2)->default(0);

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_ledgers');
    }
};


