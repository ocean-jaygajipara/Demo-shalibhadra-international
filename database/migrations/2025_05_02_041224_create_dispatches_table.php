<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('warehouse_id');
            $table->string('customer_id');
            $table->string('customer_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('address')->nullable();
            $table->string('price_list_id')->nullable();
            $table->date('dispatch_date');
            $table->date('expected_dispatch_date')->nullable();
            $table->string('dispatch_no');
            $table->string('transport_by_id')->nullable();
            $table->string('transport_details_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('dispatches');
    }
};
