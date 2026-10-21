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
        Schema::create('job_work_receive', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('product_category_id')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('job_work_issue_id')->nullable();
            $table->string('job_work_receive_date')->nullable();
            $table->string('job_work_receive_no')->nullable();
            $table->string('process_receive_id')->nullable();
            $table->string('remarks')->nullable();
            $table->string('master_total_pending')->nullable();
            $table->string('master_total_net_weight')->nullable();
            $table->string('master_total_gross_weight')->nullable();
            $table->string('master_total_current_pending')->nullable();
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
        Schema::dropIfExists('job_work_receive');
    }
};
