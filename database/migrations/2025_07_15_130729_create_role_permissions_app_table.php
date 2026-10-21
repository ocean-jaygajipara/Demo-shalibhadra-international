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
        Schema::create('role_permissions_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('company_id');
            $table->string('team_id');
            $table->string('team_role_id');
            $table->string('main_menu_id');
            $table->string('sub_menu_id');
            $table->boolean('view_flag')->default(false);
            $table->boolean('add_flag')->default(false);
            $table->boolean('update_flag')->default(false);
            $table->boolean('delete_flag')->default(false);
            $table->boolean('restore_flag')->default(false);
            $table->boolean('print_flag')->default(false);
            $table->boolean('excel_flag')->default(false);
            $table->boolean('approval_flag')->default(false);
            $table->boolean('all_data_flag')->default(false);
            $table->boolean('personal_data_flag')->default(false);
            $table->string('status')->default('active');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions_app');
    }
};
