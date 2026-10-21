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
        Schema::create('job_work_issue', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('job_work_type_id')->nullable();
            $table->string('product_category_id')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('sales_status_id')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('issue_for_process')->nullable();
            $table->string('job_work_issue_date')->nullable();
            $table->string('job_work_issue_no')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('remarks')->nullable();
            $table->decimal('master_total_pending', 10, 3)->nullable();
            $table->decimal('total_packing_weight', 10, 3)->nullable();
            $table->decimal('total_item_weight', 10, 3)->nullable();
            $table->decimal('total_gross_weight', 10, 3)->nullable();
            $table->decimal('total_cgst_percentage', 10, 2)->nullable();
            $table->decimal('total_cgst_amount', 10, 2)->nullable();
            $table->decimal('total_sgst_percentage', 10, 2)->nullable();
            $table->decimal('total_sgst_amount', 10, 2)->nullable();
            $table->decimal('master_total_rate', 10, 2)->nullable();
            $table->decimal('master_total_amount', 10, 2)->nullable();
            $table->decimal('master_rate', 10, 2)->nullable();
            $table->decimal('master_taxable', 10, 2)->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('round_off', 10, 2)->nullable();
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->text('term_and_condition')->nullable();
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
        Schema::dropIfExists('job_work_issue');
    }
};
