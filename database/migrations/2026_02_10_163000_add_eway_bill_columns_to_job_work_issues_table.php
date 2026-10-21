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
            $table->tinyInteger('generate_eway')->default(0)->after('status');
            $table->string('eway_bill_id')->nullable()->after('generate_eway');
            $table->string('eway_bill_no')->nullable()->after('eway_bill_id');
            $table->enum('eway_bill_status', [
                'PENDING',
                'GENERATED',
                'CANCELLED'
            ])->nullable()->after('eway_bill_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_work_issue', function (Blueprint $table) {
            $table->dropColumn(['generate_eway', 'eway_bill_id', 'eway_bill_no', 'eway_bill_status']);
        });
    }
};
