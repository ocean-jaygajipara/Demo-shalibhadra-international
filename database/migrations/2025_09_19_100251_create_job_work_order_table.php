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
        Schema::create('job_work_order', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('work_type')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('job_work_order_date')->nullable();
            $table->string('job_work_order_no')->nullable();
            $table->text('term_and_condition')->nullable();
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
        Schema::dropIfExists('job_work_order');
    }
};
