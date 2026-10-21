<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAssignArea extends Model
{

    public $timestamps = false;
    public $table = "customer_assign_areas";
    protected $fillable = [
        'company_id',
        'customer_id',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'created_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
}
