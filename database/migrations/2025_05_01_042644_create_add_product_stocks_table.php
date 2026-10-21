<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_stock_history', function (Blueprint $table) {
            $table->id();
            $table->string('company_id');
            $table->string('warehouse_id');
            $table->string('product_category_id');
            $table->string('product_subcategory_id')->nullable();
            $table->string('product_variant_price_id')->nullable();
            $table->string('dispatch_id')->nullable();
            $table->string('inward_id')->nullable();
            $table->string('job_work_issue_id')->nullable();
            $table->string('job_work_receive_id')->nullable();
            $table->string('qc_inspection_id')->nullable();
            $table->string('packing_slip_id')->nullable();
            $table->string('total_quantity');
            $table->string('total_kg');
            $table->string('credit')->nullable();
            $table->string('debit')->nullable();
            $table->string('stock_type')->default('qty');
            $table->date('date');
            $table->text('remark')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('add_product_stocks');
    }
};
