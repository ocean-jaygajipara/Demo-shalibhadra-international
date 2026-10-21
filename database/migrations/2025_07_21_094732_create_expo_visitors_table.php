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
        Schema::create('expo_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('expo_id');
            $table->string('company_name');
            $table->string('person_name');
            $table->string('mobile_no');
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->longText('requirement')->nullable();
            $table->string('inquiry_id')->nullable()->comment('Store when convert to inquiry');
            $table->string('platform')->default('web');
            $table->string('status')->default('active')->comment('active, inactive');
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
        Schema::dropIfExists('expo_visitors');
    }
};
