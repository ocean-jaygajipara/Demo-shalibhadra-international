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
            $table->string('irn', 500)->nullable();
            $table->string('irn_status')->nullable();
            $table->timestamp('irn_generated_at')->nullable();
            $table->string('e_invoice_status')->nullable();
            $table->string('ack_no')->nullable();
            $table->string('ack_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_work_issue', function (Blueprint $table) {
            $table->dropColumn(['irn', 'irn_status', 'irn_generated_at', 'e_invoice_status', 'ack_no', 'ack_date']);
        });
    }
};
