<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->string('display_unit_id', 255)->nullable()->after('unit_id');
        });
    }

    public function down(): void {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('display_unit_id');
        });
    }
};