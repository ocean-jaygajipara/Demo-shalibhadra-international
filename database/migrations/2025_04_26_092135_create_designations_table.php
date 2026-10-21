<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name')->index();
            $table->string('status')->default('active')->comment('active, inactive')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('designations');
    }
};