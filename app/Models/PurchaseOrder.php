<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;
    public $table = "purchase_orders";

    protected $fillable = [
        'company_id',
        'vendor_id',
        'vendor_name',
        'product_category_id',
        'term_condition_id',
        'phone',
        'state',
        'gst_no',
        'sales_status_id',
        'address',
        'shipping_address',
        'billing_address',
        'purchase_order_date',
        'purchase_order_no',
        'transport_by_id',
        'transport_details_id',
        'cash_discount_percentage',
        'cash_discount_amount',
        'additional_discount_percentage',
        'additional_discount_amount',
        'packing_forwarding_charge',
        'transport_charge',
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

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function purchase_order_items()
    {
        return $this->hasMany(PurchaseOrderItems::class);
    }

    public function sales_status()
    {
        return $this->belongsTo(SalesStatus::class, 'sales_status_id', 'id');
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function transport_by()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }

    public function transport_detail()
    {
        return $this->belongsTo(MasterTransportDetail::class, 'transport_details_id', 'id');
    }
    public function term_condition()
    {
        return $this->belongsTo(TermsAndCondition::class, 'term_condition_id', 'id');
    }
    // App\Models\PurchaseOrder.php

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
