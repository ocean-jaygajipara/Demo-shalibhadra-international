<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;
    protected $table = 'quotations';

    protected $fillable = [
        'company_id',
        'sales_order_id',
        'product_category_id',
        'customer_id',
        'term_condition_id',
        'sales_status_id',
        'contact_person_name',
        'customer_name',
        'phone',
        'state',
        'gst_no',
        'price_list_id',
        'address',
        'shipping_address',
        'billing_address',
        'quotation_date',
        'quotation_no',
        'transport_by_id',
        'transport_details_id',
        'cash_discount_percentage',
        'cash_discount_amount',
        'additional_discount_percentage',
        'additional_discount_amount',
        'packing_forwarding_charge',
        'transport_charge',
        'total_taxable',
        'freight_charge',
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
        'parent_quotation_id',
        'quotation_revise',
        'quotation_repeat',
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

    public function quotation_items()
    {
        return $this->hasMany(QuotationItems::class);
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
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function term_condition()
    {
        return $this->belongsTo(TermsAndCondition::class, 'term_condition_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function transport_by()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }

    public function transport_detail()
    {
        return $this->belongsTo(MasterTransportDetail::class, 'transport_details_id', 'id');
    }

    public function price_list()
    {
        return $this->belongsTo(PriceList::class);
    }
    public function paymentReceipts()
    {
        return $this->hasMany(PaymentReceipt::class, 'quotation_id', 'id');
    }
}
