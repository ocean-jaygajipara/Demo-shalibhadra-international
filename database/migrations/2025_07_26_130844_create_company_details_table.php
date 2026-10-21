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
        Schema::create('company_details', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('how_assign_inquiry')->nullable();
            $table->string('duplication_check_type')->nullable();
            $table->string('color_bg_primary_1', 50)->nullable();
            $table->string('color_bg_primary_2', 50)->nullable();
            $table->string('color_bg_primary_3', 50)->nullable();
            $table->string('color_text_primary_1', 50)->nullable();
            $table->string('color_text_primary_2', 50)->nullable();
            $table->string('color_text_primary_3', 50)->nullable();
            $table->string('panel_sidebar_background_color', 50)->nullable();
            $table->string('panel_sidebar_text_color', 50)->nullable();
            $table->string('panel_text_color', 50)->nullable();
            $table->string('status_bar_color', 50)->nullable();
            $table->string('title_name_color', 50)->nullable();
            $table->string('all_icon_color', 50)->nullable();

            $table->string('edittext_title_color', 50)->nullable();
            $table->string('screen_background_light_color', 50)->nullable();
            $table->string('screen_background_dark_color', 50)->nullable();
            $table->string('all_screen_header_color', 50)->nullable();
            $table->string('all_screen_back_arrow_background_color', 50)->nullable();
            $table->string('all_screen_back_arrow_color', 50)->nullable();

            $table->string('data_list_border_color', 50)->nullable();
            $table->string('login_text_color_1', 50)->nullable();
            $table->string('login_text_color_2', 50)->nullable();

            $table->string('background_shape_1', 50)->nullable();
            $table->string('background_shape_2', 50)->nullable();
            $table->string('background_shape_3', 50)->nullable();

            $table->string('extra_color_1', 50)->nullable();
            $table->string('extra_color_2', 50)->nullable();
            $table->string('extra_color_3', 50)->nullable();
            $table->string('per_minutes_request_current_location', 50)->default(0)->nullable();
            $table->tinyInteger('is_quantity_decimal')->nullable()->default(0);

            $table->string('inward_prefix', 50)->nullable();
            $table->string('inward_title', 50)->nullable();
            $table->string('inward_color', 50)->nullable();

            $table->string('job_work_order_title', 50)->nullable();
            $table->string('job_work_order_prefix', 50)->nullable();
            $table->string('job_work_order_color', 50)->nullable();

            $table->string('job_work_issue_title', 50)->nullable();
            $table->string('job_work_issue_prefix', 50)->nullable();
            $table->string('job_work_issue_color', 50)->nullable();

            $table->string('job_work_receive_title', 50)->nullable();
            $table->string('job_work_receive_prefix', 50)->nullable();
            $table->string('job_work_receive_color', 50)->nullable();

            $table->string('invoice_title', 50)->nullable();
            $table->string('invoice_prefix', 50)->nullable();
            $table->string('invoice_color', 50)->nullable();

            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();
            $table->string('deleted_by', 50)->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_details');
    }
};
