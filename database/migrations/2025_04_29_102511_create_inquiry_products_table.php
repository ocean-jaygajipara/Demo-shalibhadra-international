<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inquiry_products', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('inquiry_id');
            $table->string('product_id');
            $table->integer('quantity')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inquiry_products');
    }
};