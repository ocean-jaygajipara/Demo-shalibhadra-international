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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('module_name')->nullable();
            $table->string('module_id')->nullable();
            $table->string('module_action')->nullable();
            $table->boolean('notify_read')->default(0);
            $table->string('status')->nullable();
            $table->string('send_status')->nullable();
            $table->string('created_type')->nullable();
            $table->string('updated_type')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
