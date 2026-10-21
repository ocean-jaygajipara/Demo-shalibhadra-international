<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('company_socials', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('platform')->nullable();
            $table->string('api_key')->nullable();
            $table->text('user_id')->nullable();
            $table->string('profile_id')->nullable();
            $table->string('minutes_time')->nullable();
            $table->text('page_id')->nullable();
            $table->text('api_code')->nullable();
            $table->text('json_response')->nullable();

            $table->string('gstin')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('api_client_id')->nullable();
            $table->string('api_client_secret')->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('company_socials');
    }
};
