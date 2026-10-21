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
        Schema::create('invoice', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('product_category_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('term_condition_id')->nullable();
            $table->string('packing_slip_id')->nullable();
            $table->string('sales_order_id')->nullable();
            $table->string('po_number')->nullable();
            $table->date('po_date')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable();
            $table->string('gst_no')->nullable();
            $table->string('price_list_id')->nullable();
            $table->string('sales_status_id')->default('6');
            $table->string('address')->nullable();
            $table->string('expected_dispatch_date')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('invoice_order_date')->nullable();
            $table->string('invoice_order_no')->nullable();
            $table->string('transport_by_id')->nullable();
            $table->string('transport_details_id')->nullable();
            $table->decimal('cash_discount_percentage', 10, 2)->nullable();
            $table->decimal('cash_discount_amount', 10, 2)->nullable();
            $table->decimal('additional_discount_percentage', 10, 2)->nullable();
            $table->decimal('additional_discount_amount', 10, 2)->nullable();
            $table->decimal('packing_forwarding_charge', 10, 2)->nullable();
            $table->decimal('transport_charge', 10, 2)->nullable();
            $table->decimal('freight_charge', 10, 2)->nullable();
            $table->decimal('total_taxable', 10, 2)->nullable();
            $table->string('is_gst_applicable')->default('yes');
            $table->decimal('gst_amount', 10, 2)->nullable();
            $table->string('is_tcs_applicable')->default('no');
            $table->decimal('tcs_amount', 10, 2)->nullable();
            $table->decimal('round_off', 10, 2)->nullable();
            $table->decimal('grand_total', 10, 2)->nullable();
            $table->decimal('master_quantity', 10, 2)->nullable();
            $table->decimal('master_amount', 10, 2)->nullable();
            $table->decimal('master_taxable_amount', 10, 2)->nullable();
            $table->decimal('master_gst_amount', 10, 2)->nullable();
            $table->decimal('master_sub_total', 10, 2)->nullable();
            $table->text('term_and_condition')->nullable();
            $table->text('regards')->nullable();
            $table->text('notes')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('status')->default('active');
            $table->string('platform')->default('web');
            $table->string('parent_invoice_id', 100)->nullable();
            $table->string('invoice_repeat', 100)->nullable();

            $table->tinyInteger('generate_eway')->default(0);
            $table->string('eway_bill_id')->nullable();
            $table->string('eway_bill_no')->nullable();
            $table->enum('eway_bill_status', [
                'PENDING','GENERATED','CANCELLED'
            ])->nullable();
            $table->string('transporter_id')->nullable();
            $table->string('transporter_name')->nullable();
            $table->string('transporter_gstin')->nullable();

            $table->string('irn')->nullable();
            $table->string('ack_no')->nullable();
            $table->dateTime('ack_date')->nullable();
            $table->longText('signed_invoice')->nullable();
            $table->longText('signed_qr_code')->nullable();
            $table->string('irn_status')->nullable();
            $table->string('e_invoice_status')->nullable();
            $table->longText('e_invoice_response')->nullable();
            $table->dateTime('irn_generated_at')->nullable();

            $table->timestamp('cancel_date')->nullable();
            $table->string('cancel_reason', 1)->nullable();
            $table->string('cancel_remarks', 100)->nullable();

            $table->string('created_type')->default('team')->comment('team, admin');
            $table->string('updated_type')->default('team')->comment('team, admin');
            $table->string('created_by')->nullable()->index();
            $table->string('updated_by')->nullable()->index();
            $table->timestamps();
            $table->string('deleted_by')->nullable()->index();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
