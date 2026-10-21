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
        Schema::create('sales_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->enum('salestype', ['Quotation','Sales Orders','Dispatch Orders','Packing Slip','Invoice','Purchase Order']);
            $table->string('statusname');
            $table->string('statusslug');
            $table->string('display_order')->nullable();
            $table->string('status_color')->nullable(); // For color code picker
            $table->string('parent_status_id')->nullable();

            $table->tinyInteger('is_redirect')->nullable();
            $table->tinyInteger('is_editable')->nullable();
            $table->tinyInteger('is_revise')->nullable();
            $table->tinyInteger('is_repeat')->nullable();
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
        Schema::dropIfExists('sales_statuses');
    }
};
