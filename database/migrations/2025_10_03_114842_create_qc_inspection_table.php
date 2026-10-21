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
        Schema::create('qc_inspection', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('job_work_receive_id')->nullable();
            $table->string('job_work_receive_item_id')->nullable();
            $table->string('qc_inspection_date')->nullable();
            $table->string('qc_inspection_no')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('product_code')->nullable();
            $table->string('product_godown_id')->nullable();
            $table->string('location_id')->nullable();
            $table->string('issue_for_process_id')->nullable();
            $table->decimal('received_weight', 10, 2)->nullable();
            $table->decimal('ok_weight', 10, 2)->nullable();
            $table->decimal('ok_qty', 10, 2)->nullable();
            $table->decimal('wt_pc', 10, 2)->nullable();
            $table->decimal('rejection_qty', 10, 2)->nullable();
            $table->string('available_qty')->nullable();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_inspection');
    }
};
