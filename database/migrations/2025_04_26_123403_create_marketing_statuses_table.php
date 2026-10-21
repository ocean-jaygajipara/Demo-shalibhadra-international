<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('marketing_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('type')->default('inquiry')->comment('inquiry, followup')->index();
            $table->string('name')->index();
            $table->string('display_order')->index();
            $table->string('status_color')->index();
            $table->string('status_color_2')->nullable();
            $table->string('text_color')->nullable();
            $table->boolean('is_default')->default(0);
            $table->string('status')->default('active')->comment('active, inactive')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('marketing_statuses');
    }
};
