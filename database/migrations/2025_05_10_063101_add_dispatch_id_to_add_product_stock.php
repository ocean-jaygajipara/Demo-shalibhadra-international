<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('add_product_stocks', function (Blueprint $table) {
            // $table->string('dispatch_id')->nullable()->after('product_variant_price_id');
        });
    }

    public function down(): void {
        Schema::table('add_product_stocks', function (Blueprint $table) {
            // $table->dropColumn('dispatch_id');
        });
    }
};