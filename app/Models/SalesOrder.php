<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;
    protected $table = 'sales_orders';
    protected $fillable = [
        'company_id',
        'quotation_id',
        'product_category_id',
        'customer_id',
        'term_condition_id',
        'po_number',
        'po_date',
        'contact_person_name',
        'customer_name',
        'phone',
        'state',
        'gst_no',
        'price_list_id',
        'sales_status_id',
        'is_full_packing',
        'address',
        'expected_dispatch_date',
        'shipping_address',
        'shipping_customer_id',
        'shipping_gst_no',
        'billing_address',
        'sales_order_date',
        'sales_order_no',
        'transport_by_id',
        'transport_details_id',
        'cash_discount_percentage',
        'cash_discount_amount',
        'additional_discount_percentage',
        'additional_discount_amount',
        'packing_forwarding_charge',
        'transport_charge',
        'freight_charge',
        'total_taxable',
        'is_gst_applicable',
        'gst_amount',
        'is_tcs_applicable',
        'tcs_amount',
        'round_off',
        'grand_total',
        'master_quantity',
        'master_amount',
        'master_taxable_amount',
        'master_gst_amount',
        'master_sub_total',
        'term_and_condition',
        'regards',
        'notes',
        'latitude',
        'longitude',
        'status',
        'platform',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function sales_status()
    {
        return $this->belongsTo(SalesStatus::class, 'sales_status_id', 'id');
    }

    public function sales_order_items()
    {
        return $this->hasMany(SalesOrderItems::class);
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipping_customer()
    {
        return $this->belongsTo(Customer::class, 'shipping_customer_id', 'id');
    }

    public function term_condition()
    {
        return $this->belongsTo(TermsAndCondition::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function price_list()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'id');
    }

    public function transport_by()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }

    public function transport_detail()
    {
        return $this->belongsTo(MasterTransportDetail::class, 'transport_details_id', 'id');
    }

    public function paymentReceipts()
    {
        return $this->hasMany(PaymentReceipt::class, 'sales_order_id', 'id');
    }
}
