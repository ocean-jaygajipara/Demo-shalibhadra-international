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
    { {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('company_id');
                $table->string('gst_number')->nullable();
                $table->string('pan_number', 10)->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->enum('balance_type', ['Cr'])->default('Cr');
                $table->boolean('is_active')->default(true);
                $table->string('customer_name');
                $table->string('password')->nullable();
                $table->string('sp')->nullable();
                $table->string('customer_type_id')->nullable();
                $table->boolean('is_prospect')->default(0)->comment('0 = Customer, 1 = Prospective Customer');
                $table->string('payment_term_id');
                $table->string('parent_id')->nullable();
                $table->string('contact_person_name')->nullable();
                $table->string('mobile_number')->nullable();
                $table->string('whatsapp_number')->nullable();
                $table->string('client_code')->unique();
                $table->string('country_id')->nullable();
                $table->string('state_id')->nullable();
                $table->string('city_id')->nullable();
                $table->string('area_id')->nullable();
                $table->string('pincode')->nullable();
                $table->text('address')->nullable();
                $table->text('latitude')->nullable();
                $table->text('longitude')->nullable();
                $table->text('shipping_address')->nullable();
                $table->text('billing_address')->nullable();
                $table->string('email')->nullable();
                $table->string('status')->default('active')->comment('active, inactive');

                $table->string('platform')->default('Web');

                $table->string('created_type')->nullable();
                $table->string('created_by')->nullable();

                $table->string('updated_type')->nullable();
                $table->string('updated_by')->nullable();

                $table->string('deleted_type')->nullable();
                $table->string('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
