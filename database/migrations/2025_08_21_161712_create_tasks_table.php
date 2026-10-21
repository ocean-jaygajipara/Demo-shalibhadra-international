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
        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('department_id')->nullable();
            $table->string('task_status_id')->nullable();
            $table->string('task_created_by')->nullable();
            $table->string('task_assigned_to')->nullable();
            $table->string('priority')->nullable();
            $table->dateTime('task_start_date_time')->nullable();
            $table->dateTime('arrival_date_time')->nullable();
            $table->dateTime('due_date_time')->nullable();
            $table->string('status')->default('Active');
            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task');
    }
};
