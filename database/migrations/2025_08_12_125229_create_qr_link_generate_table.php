<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_link_generate', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('qr_type_id');
            $table->string('qr_code_uuid')->unique();
            $table->text('qr_code_image');
            $table->string('name')->nullable();
            $table->longText('qr_type_fields')->nullable();
            $table->integer('scan_count')->default(0);
            $table->string('status')->default('active')->comment('active, inactive');
            $table->string('created_by')->nullable()->index();
            $table->string('updated_by')->nullable()->index();
            $table->timestamps();
            $table->string('deleted_by')->nullable()->index();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_link_generate');
    }
};
