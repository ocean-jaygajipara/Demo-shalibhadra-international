<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    public function up(): void
    {
        Schema::create('master_state', function (Blueprint $table) {
            $table->id();
            $table->string('country_id');
            $table->string('name');
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('master_state');
    }
};
