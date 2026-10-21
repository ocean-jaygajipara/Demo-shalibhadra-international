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
        Schema::create('eway_bills', function (Blueprint $table) {
            $table->id();

            $table->string('company_id')->nullable();
            $table->string('invoice_id')->nullable();

            $table->string('eway_bill_no')->nullable();
            $table->date('eway_bill_date')->nullable();
            $table->date('valid_upto')->nullable();

            $table->string('vehicle_no')->nullable();
            $table->enum('vehicle_type', ['R','RW','O'])->nullable();

            $table->string('transporter_id')->nullable();
            $table->string('transporter_name')->nullable();
            $table->string('transporter_gstin')->nullable();

            $table->enum('status', [
                'PENDING','GENERATED','CANCELLED','FAILED'
            ])->default('PENDING');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->json('api_response')->nullable();
            $table->text('error_message')->nullable();

            $table->string('cancelled_by')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('vehicle_update_response')->nullable();
            $table->string('lr_no')->nullable();
            $table->date('lr_date')->nullable();

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
        Schema::dropIfExists('eway_bills');
    }
};
