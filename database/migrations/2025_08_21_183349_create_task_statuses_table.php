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
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('status_type');
            $table->string('status_name');
            $table->string('display_order')->nullable();
            $table->string('background_color')->nullable();
            $table->string('gradient_color')->nullable();
            $table->string('font_color')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
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
        Schema::dropIfExists('task_statuses');
    }
};
