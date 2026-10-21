<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_work_issue', function (Blueprint $table) {
            $table->string('transport_by_id')->nullable()->after('updated_by');
            $table->string('transport_details_id')->nullable()->after('transport_by_id');
            $table->string('gst_no')->nullable()->after('transport_details_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_work_issue', function (Blueprint $table) {
            $table->dropColumn(['transport_by_id', 'transport_details_id', 'gst_no']);
        });

    }
};
