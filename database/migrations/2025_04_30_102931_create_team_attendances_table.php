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
        Schema::create('team_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('team_person_id');

            $table->dateTime('punch_in_time')->nullable();
            $table->string('punch_in_image_path')->nullable();
            $table->string('punch_in_latitude')->nullable();
            $table->string('punch_in_longitude')->nullable();
            $table->text('punch_in_address')->nullable();

            $table->dateTime('punch_out_time')->nullable();
            $table->string('punch_out_image_path')->nullable();
            $table->string('punch_out_latitude')->nullable();
            $table->string('punch_out_longitude')->nullable();
            $table->text('punch_out_address')->nullable();

            $table->string('punch_in_flag')->nullable();
            $table->string('punch_out_flag')->nullable();

            $table->string('working_time')->nullable();

            $table->string('status')->nullable();
			$table->string('verify_by')->nullable();
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
        Schema::dropIfExists('team_attendances');
    }
};
