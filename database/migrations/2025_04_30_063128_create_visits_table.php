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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            $table->string('company_id');
            $table->string('team_person_id');

            $table->string('model')->nullable();
            $table->string('model_id')->nullable();

            $table->string('meet_person_name');
            $table->dateTime('visit_start_date')->nullable();
            $table->text('visit_start_remark')->nullable();
            $table->string('visit_start_latitude')->nullable();
            $table->string('visit_start_longitude')->nullable();
            $table->string('visit_start_address')->nullable();
            $table->string('start_image')->nullable();

            $table->dateTime('visit_stop_date')->nullable();
            $table->text('visit_stop_remark')->nullable();
            $table->string('visit_stop_latitude')->nullable();
            $table->string('visit_stop_longitude')->nullable();
            $table->string('visit_stop_address')->nullable();
            $table->string('stop_image')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('platform')->default('web')->comment('web, android, ios');
            $table->text('extra_detail')->nullable()->comment('extra detail store');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
