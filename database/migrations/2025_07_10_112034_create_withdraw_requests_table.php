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
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable(); // Add if needed
            $table->string('customer_id')->nullable();
            $table->integer('request_points');
            $table->decimal('amount', 10, 2);
            $table->date('withdraw_request_date');
            $table->string('attachment')->nullable();
            $table->enum('status', ['Pending', 'Approve', 'Reject'])->default('Pending');
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
        Schema::dropIfExists('withdraw_requests');
    }
};
