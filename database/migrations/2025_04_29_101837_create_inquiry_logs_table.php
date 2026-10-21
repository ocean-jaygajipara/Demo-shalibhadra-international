<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inquiry_logs', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('inquiry_id');
            $table->string('followup_id')->nullable();
            $table->string('action');

            $table->string('status_id')->nullable(); 
            $table->string('status_value')->nullable(); 

            $table->string('inquiry_assigned_to_id')->nullable(); 
            $table->string('inquiry_assigned_to')->nullable(); 

            $table->longtext('json_data')->nullable();
            $table->text('remarks')->nullable();

            $table->string('created_by'); 
            $table->string('created_type')->default('Team');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inquiry_logs');
    }
};