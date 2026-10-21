<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('followup_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('inquiry_id')->index();
            $table->unsignedBigInteger('followup_id')->nullable()->index();
            $table->string('action')->index();

            $table->unsignedBigInteger('status_id')->nullable()->index(); 
            $table->string('status_value')->nullable()->index(); 

            $table->unsignedBigInteger('assigned_to_id')->nullable()->index(); 
            $table->string('assigned_to')->nullable()->index(); 

            $table->longtext('json_data')->nullable();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by'); 
            $table->string('created_type')->default('Team')->index();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('followup_logs');
    }
};