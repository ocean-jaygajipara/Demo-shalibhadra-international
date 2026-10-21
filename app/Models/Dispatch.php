<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispatch extends Model {

    use SoftDeletes;
    protected $table = 'dispatches';
    protected $fillable = [
        'company_id',
        'sales_order_id',
        'customer_name',
        'contact_person_name',
        'phone',
        'state',
        'address',
        'price_list_id',
        'dispatch_no',
        'dispatch_date',
        'expected_dispatch_date',
        'customer_id',
        'remarks',
        'sales_status_id',
        'warehouse_id',
        'transport_by_id',
        'transport_details_id',
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

    public function dispatch_items()
    {
        return $this->hasMany(DispatchItems::class, 'dispatch_id');
    }
    public function price_list()
    {
        return $this->belongsTo(PriceList::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transport_by()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }

    public function transport_detail()
    {
        return $this->belongsTo(MasterTransportDetail::class, 'transport_details_id', 'id');
    }

    public function sales_status()
    {
        return $this->belongsTo(SalesStatus::class, 'sales_status_id', 'id');
    }
    
}
