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
        Schema::create('team_person_assign_areas', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('team_person_id');
            $table->string('country_id');
            $table->string('state_id');
            $table->string('city_id');
            $table->string('area_id');
            $table->string('created_type')->nullable();
            $table->string('created_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_person_assign_areas');
    }
};
