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
        Schema::create('expense_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('team_person_ids');
            $table->string('expense_category_id');
            $table->string('name');
            $table->string('expense_type')->comment('General , KM , Food');
            $table->boolean('is_image_required')->comment("image required or not")->nullable();

            // General
            $table->decimal('min_amount', 10, 2)->nullable();
            $table->decimal('max_amount', 10, 2)->nullable();
            // Kilometer
            $table->decimal('per_km_rate',10,2)->nullable();
            // Food
            $table->decimal('fix_amount',10,2)->nullable();
            $table->time('from_time')->nullable();
            $table->time('to_time')->nullable();
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
        Schema::dropIfExists('expense_sub_categories');
    }
};
