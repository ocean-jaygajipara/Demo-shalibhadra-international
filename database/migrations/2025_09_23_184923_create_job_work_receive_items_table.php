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
        Schema::create('job_work_receive_items', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('job_work_issue_item_id')->nullable();
            $table->string('job_work_receive_id')->nullable();
            $table->string('product_id')->nullable();
            $table->integer('display_order')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();
            $table->decimal('product_stock', 10, 2)->nullable();
            $table->string('pending_weight')->nullable();
            $table->string('received_weight')->nullable();
            $table->string('gross_weight')->nullable();
            $table->decimal('wt_pc', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('rate_in_amount', 10, 2)->nullable();
            $table->decimal('bl_percentage', 10, 2)->nullable();
            $table->decimal('incoming_scrap', 10, 3)->nullable();
            $table->string('product_used')->nullable();
            $table->decimal('our_stock', 10, 2)->nullable();
            $table->decimal('challan_stock', 10, 2)->nullable();
            $table->decimal('used_qty', 10, 2)->nullable();
            $table->decimal('used_weight', 10, 2)->nullable();
            $table->decimal('qc_check_weight', 10, 2)->nullable();
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
        Schema::dropIfExists('job_work_receive_items');
    }
};
