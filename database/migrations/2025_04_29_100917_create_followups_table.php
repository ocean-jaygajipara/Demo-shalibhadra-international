<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('inquiry_id');
            $table->string('assign_to_id')->nullable();
            $table->dateTime('date_time');
            $table->string('reason_id');
            $table->text('details');
            $table->string('through')->nullable();
            $table->string('status_id');
            $table->text('response_details')->nullable();
            $table->dateTime('response_date_time')->nullable();
            $table->string('followup_action')->nullable();
            $table->string('platform')->default('Web');
            $table->boolean('notified_at_15')->default(false);
            $table->boolean('notified_at_10')->default(false);
            $table->boolean('notified_at_5')->default(false);
            $table->string('created_type')->default('Team');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('followups');
    }
};
