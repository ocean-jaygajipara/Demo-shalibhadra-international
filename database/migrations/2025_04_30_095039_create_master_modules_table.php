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
        Schema::create('master_modules', function (Blueprint $table) {
            $table->id();
            $table->string('main_menu_id')->default('0');
            $table->string('sub_menu_id')->default('0');
            $table->string('customer_type_id')->default('0');
            $table->string('name');
            $table->string('platform')->default('panel')->comment('panel, app');
            $table->integer('order_by')->default(1);
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
        Schema::dropIfExists('master_modules');
    }
};
