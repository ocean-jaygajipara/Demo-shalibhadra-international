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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('team_person_ids');
            $table->string('leave_type_id');
            $table->dateTime('fromdate_time');
            // $table->string('todate_time');
            $table->dateTime('todate_time')->nullable();

            $table->string('halfday_fullday');
            $table->string('singleday_multipleday')->nullable();
            $table->string('firsthalf_secondhalf')->nullable();
            $table->string('leave_reason');
            $table->string('rejection_reason')->nullable();
            $table->text('attachment')->nullable();

            $table->string('status')->default('active')->comment('active, inactive');
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
        Schema::dropIfExists('leave_applications');
    }
};
