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
        Schema::create('user_application_details', function (Blueprint $table) {
            $table->id();
            $table->string('tbl_model');
            $table->string('tbl_model_id');

            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('device_id')->nullable();
            $table->string('device_sdk')->nullable();
            $table->string('device_version_code')->nullable();
            $table->string('device_host')->nullable();
            $table->string('device_serial')->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_application_details');
    }
};
