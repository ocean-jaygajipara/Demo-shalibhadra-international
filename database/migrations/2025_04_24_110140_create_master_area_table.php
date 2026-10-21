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
        Schema::create('master_area', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('country_id');
            $table->string('state_id');
            $table->string('city_id');
            $table->string('area_name');
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
        Schema::dropIfExists('master_area');
    }
};
