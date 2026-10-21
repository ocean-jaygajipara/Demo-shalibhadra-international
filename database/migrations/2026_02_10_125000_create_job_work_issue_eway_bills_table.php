<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('job_work_issue_eway_bills');
        Schema::create('job_work_issue_eway_bills', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('job_work_issue_id')->nullable();

            $table->string('eway_bill_no')->nullable();
            $table->dateTime('eway_bill_date')->nullable();
            $table->dateTime('valid_upto')->nullable();

            $table->string('vehicle_no')->nullable();
            $table->enum('vehicle_type', ['R', 'RW', 'O'])->nullable();
            $table->string('transporter_id')->nullable();
            $table->string('transporter_name')->nullable();
            $table->string('transporter_gstin')->nullable();

            $table->string('status')->default('PENDING')->comment('PENDING, GENERATED, CANCELLED, UPDATED');

            $table->dateTime('generated_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->text('cancel_remarks')->nullable();
            $table->json('cancel_response')->nullable();

            // For storing vehicle update history/response if needed
            $table->json('vehicle_update_response')->nullable();

            $table->string('lr_no')->nullable();
            $table->date('lr_date')->nullable();

            $table->json('api_response')->nullable();
            $table->text('error_message')->nullable();

            $table->enum('created_type', ['admin', 'customer', 'user', 'employee'])->nullable();
            $table->enum('updated_type', ['admin', 'customer', 'user', 'employee'])->nullable();
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
        Schema::dropIfExists('job_work_issue_eway_bills');
    }
};
