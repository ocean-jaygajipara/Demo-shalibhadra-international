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
        Schema::create('team_person_map_cordinates', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('user_type');
            $table->string('user_id');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address')->nullable();
            $table->string('pin')->nullable();
            $table->string('platform', 100)->default('Web');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_person_map_cordinates');
    }
};
