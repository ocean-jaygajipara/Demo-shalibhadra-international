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
        Schema::create('visitor_gatepasses', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('visitor_type_id')->nullable();
            $table->string('organization_name')->nullable();
            $table->dateTime('visiting_datetime')->nullable();
            $table->text('purpose_of_visit_id')->nullable();
            $table->string('team_person_id')->nullable();
            $table->string('department_id')->nullable();
            $table->enum('id_proof_type', ['Aadhar', 'DL', 'Passport', 'Other'])->nullable();
            $table->string('id_proof_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->boolean('items_carried_in')->default(false);
            $table->datetime('check_in_time')->nullable();
            $table->datetime('check_out_time')->nullable();
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            $table->string('gate_location_name')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('platform')->default('web')->comment('web, android, ios');
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
        Schema::dropIfExists('visitor_gatepasses');
    }
};
