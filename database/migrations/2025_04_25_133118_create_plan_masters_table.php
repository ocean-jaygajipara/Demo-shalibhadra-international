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
        Schema::create('plan_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('plan_valid_day');
            $table->integer('max_team_user_count');
            $table->integer('max_customer_count');
            $table->integer('max_inquiry_count');
            $table->string('plan_type')->default('General')->index();
            $table->string('app_right')->nullable();
            $table->string('panel_right')->nullable();
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
        Schema::dropIfExists('plan_masters');
    }
};
