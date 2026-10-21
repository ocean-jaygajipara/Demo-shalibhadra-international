<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('inquiry_no');
            $table->string('customer_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('mobile_no');
            $table->string('whatsapp_no')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('inquiry_status_id')->nullable();
            $table->string('followup_status_id')->nullable();
            $table->string('source_of_inquiry_id')->nullable();
            $table->text('requirement_details')->nullable();
            $table->string('address')->nullable();
            $table->date('inquiry_date');
            $table->string('country_id')->nullable();
            $table->string('state_id')->nullable();
            $table->string('city_id')->nullable();
            $table->string('area_id')->nullable();
            $table->string('pincode')->nullable();
            $table->string('inquiry_created_by')->nullable();
            $table->string('inquiry_assigned_to')->nullable();
            $table->string('attachment')->nullable();
            $table->dateTime('followup_date')->nullable();
            $table->text('followup_details')->nullable();
            $table->string('indiamart_unique_id')->default('Web');
            $table->text('indiamart_json')->nullable();
            $table->text('platform_unique_id');
            $table->text('platform_json')->nullable();
            $table->string('platform')->default('Web');
            $table->string('customer_id')->nullable();
            $table->string('status')->default('Active');
            $table->string('created_type')->default('Team');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inquiries');
    }
};
