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
        Schema::create('sale_executive_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('type_reference_id')->default('0');
            $table->string('type')->default(0)->comment('
            1 - Login,
            2 - Logout,
            3 - Attendance In,
            4 - Attendance Out,
            5 - Tracking Sync,
            6 - Create Order,
            7 - Create Customer,
            8 - Add Expense,
            9 - Add Visit,
            10 - Add Inquiry,
            11 - Edit Inquiry,
            12 - Delete Inquiry,
            13 - Add Follow-Up,
            14 - Add Leave,
            15 - Attendance AutoOut,
            16 - Add Quotation');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address')->nullable();
            $table->dateTime('current_datetime')->nullable();
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
        Schema::dropIfExists('sale_executive_tracking');
    }
};
