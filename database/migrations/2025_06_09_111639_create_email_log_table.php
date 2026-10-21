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
        Schema::create('email_log', function (Blueprint $table) {
            $table->id(); // id (auto increment)
            $table->string('company_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('user_type');
            $table->string('user_email')->nullable();
            $table->string('org_subject')->nullable();
            $table->text('org_body_template')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_template')->nullable();
            $table->string('send_status')->default('pending');
            $table->timestamp('send_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('api_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_log');
    }
};
