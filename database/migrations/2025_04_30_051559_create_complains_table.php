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
        Schema::create('complains', function (Blueprint $table) {
            $table->id();
            $table->string('company_id', 255);
            $table->string('source_complain_id', 255);
            $table->string('complain_type_id', 255);
            $table->string('customer_type_id', 255);
            $table->string('customer_id', 255);
            $table->string('complain_no', 255);
            $table->string('remark', 255)->nullable();
            $table->string('complain_created_by_id', 255);
            $table->string('complain_assign_to_id', 255);
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('image', 255)->nullable(); // Store image filename (not the actual file)
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
        Schema::dropIfExists('complains');
    }
};
