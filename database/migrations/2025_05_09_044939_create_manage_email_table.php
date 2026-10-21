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
        Schema::create('manage_email', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('module');
            $table->string('ntype');
            $table->string('name');
            $table->text('subject');
            $table->text('body');
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
            $table->unique(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manage_email');
    }
};
