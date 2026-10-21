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
        Schema::create('inquiry_import_file', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('filename')->nullable();
            $table->integer('total_inquiries')->default(0);
            $table->integer('total_success_inquiries')->default(0);
            $table->integer('total_failed_inquiries')->default(0);
            $table->longText('errors')->nullable();
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
        Schema::dropIfExists('inquiry_import_file');
    }
};
