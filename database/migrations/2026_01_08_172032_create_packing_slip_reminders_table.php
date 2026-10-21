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
        Schema::create('packing_slip_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('packing_slip_id');
            $table->unsignedBigInteger('user_id');
            $table->string('user_type', 20)->default('Team');
            $table->dateTime('last_sent_at')->nullable();
            $table->boolean('is_completed')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_slip_reminders');
    }
};
