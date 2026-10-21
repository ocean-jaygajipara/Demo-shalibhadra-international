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
        Schema::table('product_stock_history', function (Blueprint $table) {
            $table->unsignedBigInteger('process_wise_production_id')->nullable()->after('packing_slip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stock_history', function (Blueprint $table) {
            $table->dropColumn('process_wise_production_id');
        });
    }
};
