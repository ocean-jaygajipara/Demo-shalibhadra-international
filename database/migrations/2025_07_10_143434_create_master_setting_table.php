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
        Schema::create('master_setting', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('platform')->nullable();
            $table->text('app_id')->nullable();
            $table->text('app_secret')->nullable();
            $table->text('redirect_url')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes(); // adds deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_setting');
    }
};
