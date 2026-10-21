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
        Schema::create('expo_marketing_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('name');
            $table->string('attachment');
            $table->string('redirect_url');
            $table->string('sequence')->nullable();

            $table->string('status')->default('active');

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
        Schema::dropIfExists('expo_marketing_sliders');
    }
};
