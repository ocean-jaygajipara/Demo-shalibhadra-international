<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->string('sales_order_id')->nullable()->after('warehouse_id');
        });
    }

    public function down(): void {
        Schema::table('dispatches', function (Blueprint $table) {
            $table->dropColumn('sales_order_id');
        });
    }
};