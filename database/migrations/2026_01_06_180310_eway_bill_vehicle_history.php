<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('eway_bill_vehicle_history', function (Blueprint $table) {
            $table->id();
            $table->string('eway_bill_id');
            $table->string('eway_bill_no');
            $table->string('vehicle_no');
            $table->string('vehicle_type', 1)->default('R'); // R=Regular, O=Over Dimensional
            $table->string('transport_mode', 1)->default('1'); // 1=Road, 2=Rail, 3=Air, 4=Ship
            $table->string('transporter_name')->nullable();
            $table->string('lr_no')->nullable();
            $table->date('lr_date')->nullable();
            $table->string('from_place', 50)->nullable();
            $table->string('from_state', 2)->nullable();
            $table->string('reason_code', 1); // 1=Breakdown, 2=Transhipment, 3=Others, 4=First Time
            $table->string('reason_remark')->nullable();
            $table->json('update_response')->nullable(); // API response store karva
            $table->boolean('is_current')->default(true); // Current vehicle ke nahi

            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
            $table->string('created_by')->nullable()->index();
            $table->string('updated_by')->nullable()->index();
            $table->timestamps();
            $table->string('deleted_by')->nullable()->index();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eway_bill_vehicle_history');
    }
};
