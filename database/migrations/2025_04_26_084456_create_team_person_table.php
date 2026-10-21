<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('team_persons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->unsignedBigInteger('parent_type_id')->default(0);
            $table->string('mobile_no');
            $table->string('employee_code');
            $table->string('username');
            $table->string('password');
            $table->string('sp');
            $table->time('min_working_start_time')->nullable();
            $table->time('max_working_start_time')->nullable();
            $table->time('working_end_time')->nullable();
            $table->text('address')->nullable();

            $table->string('country_id');
            $table->string('state_id');
            $table->string('city_id');

            $table->string('area_id')->nullable();

            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('designation_id')->nullable();
            $table->string('team_role_id');
            $table->string('app_version')->nullable();
            $table->text('device_token')->nullable();
            $table->text('device_token_app')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');

            $table->string('created_by')->nullable();
            $table->string('created_type')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('team_persons');
    }
};
