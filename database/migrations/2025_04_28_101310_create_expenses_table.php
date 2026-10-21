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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('team_person_id');
            $table->string('expense_category_id');
            $table->string('expense_subcategory_id');
            $table->date('date')->nullable();
            $table->decimal('req_amount', 10, 2)->nullable();
            $table->decimal('pass_amount', 10, 2)->nullable();
            $table->text('remark')->nullable();
            $table->text('reason')->nullable();
            $table->text('attachment')->nullable();
            // $table->boolean('is_image_required')->comment("image required or not")->nullable();
            $table->string('status')->default('pending')->comment('reject, pass');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->string('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
