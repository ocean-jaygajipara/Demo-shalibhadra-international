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
        Schema::table('invoice', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_customer_id')->nullable()->after('shipping_address');
            $table->string('shipping_gst_no')->nullable()->after('shipping_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice', function (Blueprint $table) {
            $table->dropColumn(['shipping_customer_id', 'shipping_gst_no']);
        });
    }
};
