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
        Schema::create('customer_contact_details', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id');
            $table->string('department')->nullable();
            $table->string('name')->nullable();
            $table->string('designation')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('platform')->default('web')->comment('web, android, ios');
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
        Schema::dropIfExists('customer_contact_details');
    }
};
