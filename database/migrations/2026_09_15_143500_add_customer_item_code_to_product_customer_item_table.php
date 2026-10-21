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
        Schema::table('product_customer_item', function (Blueprint $table) {
            $table->string('customer_item_code')->nullable()->after('customer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_customer_item', function (Blueprint $table) {
            $table->dropColumn('customer_item_code');
        });
    }
};
