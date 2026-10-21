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
            $table->renameColumn('customer_item_code', 'customer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_customer_item', function (Blueprint $table) {
            $table->renameColumn('customer_code', 'customer_item_code');
        });
    }
};
