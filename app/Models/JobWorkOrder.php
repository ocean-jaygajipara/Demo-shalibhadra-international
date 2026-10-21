<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkOrder extends Model
{
    use SoftDeletes;
    public $table = "job_work_order";

    protected $fillable = [
        'company_id',
        'vendor_id',
        'product_category_id',
        'work_type',
        'mobile_no',
        'phone',
        'state',
        'address',
        'shipping_address',
        'billing_address',
        'job_work_order_date',
        'job_work_order_no',
        'term_and_condition',
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

    public function job_work_order_items()
    {
        return $this->hasMany(JobWorkOrderItems::class);
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function term_condition()
    {
        return $this->belongsTo(TermsAndCondition::class, 'term_condition_id', 'id');
    }
}
