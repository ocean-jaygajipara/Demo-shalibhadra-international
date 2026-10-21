<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkIssue extends Model
{
    use SoftDeletes;
    public $table = "job_work_issue";

    protected $fillable = [
        'company_id',
        'vendor_id',
        'job_work_type_id',
        'product_category_id',
        'warehouse_id',
        'mobile_no',
        'phone',
        'state',
        'address',
        'sales_status_id',
        'shipping_address',
        'billing_address',
        'issue_for_process',
        'job_work_issue_date',
        'job_work_issue_no',
        'vehicle_no',
        'remarks',
        'master_total_pending',
        'total_packing_weight',
        'total_item_weight',
        'total_gross_weight',
        'total_cgst_percentage',
        'total_cgst_amount',
        'total_sgst_percentage',
        'total_sgst_amount',
        'master_total_rate',
        'master_total_amount',
        'master_rate',
        'master_taxable',
        'sub_total',
        'round_off',
        'grand_total',
        'term_and_condition',
        'status',
        'platform',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
        'generate_eway',
        'eway_bill_id',
        'eway_bill_no',
        'eway_bill_status',
        'irn',
        'irn_status',
        'irn_generated_at',
        'e_invoice_status',
        'ack_no',
        'ack_date',
        'transport_by_id',
        'transport_details_id',
        'gst_no',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function job_work_issue_items()
    {
        return $this->hasMany(JobWorkIssueItems::class);
    }

    public function job_work_type()
    {
        return $this->belongsTo(JobWorkType::class, 'job_work_type_id', 'id');
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

    public function issueForProcess()
    {
        return $this->belongsTo(IssueForProcess::class, 'issue_for_process', 'id');
    }

    public function sales_status()
    {
        return $this->belongsTo(SalesStatus::class, 'sales_status_id', 'id');
    }
    public function job_work_receive_items()
    {
        return $this->belongsTo(JobWorkReceiveItems::class, 'job_work_receive_items_id', 'id');
    }
    public function job_work_receives()
    {
        return $this->hasMany(
            JobWorkReceive::class,
            'job_work_issue_id', // FK in job_work_receives table
            'id'
        );
    }
    public function receives()
    {
        return $this->hasMany(JobWorkReceive::class, 'job_work_issue_id');
    }

    public function ewayBill()
    {
        return $this->hasOne(JobWorkIssueEwayBill::class, 'job_work_issue_id');
    }

    public function jobWorkIssueEwayBill()
    {
        return $this->hasOne(JobWorkIssueEwayBill::class, 'job_work_issue_id');
    }

    public function transport_by()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }

    public function transport_details()
    {
        return $this->belongsTo(MasterTransportDetail::class, 'transport_details_id', 'id');
    }
}
