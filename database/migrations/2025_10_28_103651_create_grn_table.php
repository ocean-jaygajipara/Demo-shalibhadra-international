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
        Schema::create('grn', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->text('address')->nullable();
            $table->date('grn_date')->nullable();
            $table->string('grn_no')->nullable();
            $table->date('dos_date')->nullable();
            $table->unsignedBigInteger('sales_status_id')->nullable();
            $table->text('remark')->nullable();
            $table->string('created_type')->nullable();
            $table->string('updated_type')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes(); // For soft delete support
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grn');
    }
};
