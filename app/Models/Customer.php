<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Customer extends Authenticatable
{
    use SoftDeletes;
    public $table = "customers";
    protected $fillable = [
        'company_id',
        'gst_number',
        'pan_number',
        'opening_balance',
        'balance_type',
        'is_active',
        'customer_name',
        'password',
        'sp',
        'is_prospect',
        'customer_type_id',
        'parent_id',
        'contact_person_name',
        'mobile_number',
        'whatsapp_number',
        'client_code',
        'payment_term_id',
        'credit_days',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'pincode',
        'address',
        'shipping_address',
        'billing_address',
        'email',
        'longitude',
        'latitude',
        'status',

        'platform',

        'created_type',
        'created_by',

        'updated_type',
        'updated_by',

        'deleted_type',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id');
    }

    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'area_id');
    }

    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }

    public function quotation()
    {
        return $this->hasMany(Quotation::class, 'customer_id');
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function dispatch()
    {
        return $this->hasMany(Dispatch::class, 'customer_id');
    }
    public function contacts_details()
    {
        return $this->hasMany(CustomerContactDetail::class, 'customer_id', 'id');
    }

    public function bank_details()
    {
        return $this->hasMany(CustomerBankDetail::class, 'customer_id', 'id');
    }
    public function sales_items()
    {
        return $this->hasMany(CustomerSalesItem::class, 'customer_id', 'id');
    }
}
