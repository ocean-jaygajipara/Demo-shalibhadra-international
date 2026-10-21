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
        Schema::create('material_detail', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('qc_inspection_id')->nullable();
            $table->string('dimension')->nullable();
            $table->decimal('size', 8, 2)->nullable();
            $table->decimal('ltl', 8, 2)->nullable();
            $table->decimal('utl', 8, 2)->nullable();
            $table->string('instrument')->nullable();

            for ($i = 1; $i <= 10; $i++) {
                $table->decimal("size$i", 8, 2)->nullable();
            }
            $table->string('status')->default('active')->comment('active, inactive');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_detail');
    }
};
