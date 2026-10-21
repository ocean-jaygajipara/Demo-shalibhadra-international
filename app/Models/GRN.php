<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GRN extends Model
{
    use SoftDeletes;
    protected $table = 'grn';
    protected $fillable = [
        'company_id',
        'customer_id',
        'customer_name',
        'contact_person_name',
        'phone',
        'state',
        'address',
        'grn_date',
        'grn_no',
        'dos_date',
        'sales_status_id',
        'remark',
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

    public function grn_item()
    {
        return $this->hasMany(GRNItem::class, 'grn_id', 'id');
    }

}
