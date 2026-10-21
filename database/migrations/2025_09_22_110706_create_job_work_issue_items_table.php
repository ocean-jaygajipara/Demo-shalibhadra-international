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
        Schema::create('job_work_issue_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('job_work_issue_id')->nullable();
            $table->string('product_id')->nullable();
            $table->integer('display_order')->nullable();
            $table->string('product_name')->nullable();
            $table->text('product_code')->nullable();
            $table->decimal('product_stock', 10, 2)->nullable();
            $table->decimal('gross_weight', 10, 3)->nullable();
            $table->decimal('issue_weight', 10, 3)->nullable();
            $table->decimal('remaining_weight', 10, 3)->nullable();
            $table->decimal('wt_pc', 10, 2)->nullable();
            $table->decimal('rate_in_amount', 10, 2)->nullable();
            $table->decimal('taxable_amount', 10, 2)->nullable();
            $table->decimal('cgst_percentage', 10, 2)->nullable();
            $table->decimal('cgst_amount', 10, 2)->nullable();
            $table->decimal('sgst_percentage', 10, 2)->nullable();
            $table->decimal('sgst_amount', 10, 2)->nullable();
            $table->decimal('total_rate_in_amount', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_work_issue_items');
    }
};
