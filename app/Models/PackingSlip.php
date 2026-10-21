<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingSlip extends Model
{
    use SoftDeletes;
    protected $table = 'packing_slips';
    protected $fillable = [
        'company_id',
        'sales_order_id',
        'warehouse_id',
        'customer_id',
        'customer_name',
        'contact_person_name',
        'phone',
        'state',
        'address',
        'shipping_address',
        'billing_address',
        'price_list_id',
        'packing_slip_date',
        'packing_slip_no',
        'sales_order_no',
        'sales_order_date',
        'total_no_of_carton',
        'sales_status_id',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales_order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function sales_status()
    {
        return $this->belongsTo(SalesStatus::class, 'sales_status_id', 'id');
    }

    public function packing_slip_item()
    {
        return $this->hasMany(PackingSlipItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(MasterWarehouse::class, 'warehouse_id', 'id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'packing_slip_id', 'id')->whereNull('deleted_at');
    }

}
