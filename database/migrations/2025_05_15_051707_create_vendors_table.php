<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('vendor_code')->nullable();
            $table->string('country_id')->nullable();
            $table->string('state_id')->nullable();
            $table->string('city_id')->nullable();
            $table->string('area_id')->nullable();
            $table->string('pincode')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('uan_number')->nullable();
            $table->string('address')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('email_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('vendors');
    }
};
