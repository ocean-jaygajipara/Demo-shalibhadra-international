<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model {
    use SoftDeletes;
    public $table = "vendors";

    protected $fillable = [
        'company_id',
        'gst_number',
        'vendor_name',
        'mobile_number',
        'whatsapp_number',
        'vendor_code',
        'pan_number',
        'uan_number',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'pincode',
        'address',
        'shipping_address',
        'billing_address',
        'email_id',
        'birth_date',
        'status',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function country() {
        return $this->belongsTo(MasterCountry::class, 'country_id', 'id')->withDefault();
    }

    public function state() {
        return $this->belongsTo(MasterState ::class, 'state_id', 'id')->withDefault();
    }

    public function city() {
        return $this->belongsTo(MasterCity::class, 'city_id', 'id');
    }

    public function area() {
        return $this->belongsTo(MasterArea::class, 'area_id', 'id');
    }

    public function team_person() {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person() {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }
}
