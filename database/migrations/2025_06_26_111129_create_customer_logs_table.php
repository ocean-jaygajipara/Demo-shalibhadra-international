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
        Schema::create('customer_logs', function (Blueprint $table) {
           $table->id();
            $table->string('company_id');
            $table->string('customer_id');
            $table->string('followup_id')->nullable();
            $table->string('action');

            $table->string('status_id')->nullable();
            $table->string('status_value')->nullable();

            $table->longtext('json_data')->nullable();
            $table->text('remarks')->nullable();

            $table->string('created_by');
            $table->string('created_type')->default('Team');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_logs');
    }
};
