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
        Schema::create('team_role', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->string('name');
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('created_type')->nullable()->comment('login user guard');

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
        Schema::dropIfExists('team_role');
    }
};
