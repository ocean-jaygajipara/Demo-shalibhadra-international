<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inward extends Model
{
    use SoftDeletes;
    public $table = "inwards";

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'purchase_order_id',
        'vendor_id',
        'vendor_name',
        'term_condition_id',
        'phone',
        'state',
        'gst_no',
        'address',
        'shipping_address',
        'billing_address',
        'inward_date',
        'inward_no',
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
        'is_qc_required',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id', 'id');
    }

    public function inward_items()
    {
        return $this->hasMany(InwardItems::class, 'inward_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(MasterWarehouse::class, 'warehouse_id', 'id');
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
        return $this->belongsTo(TermsAndCondition::class, 'term_condition_id');
    }
}
