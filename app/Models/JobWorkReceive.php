<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkReceive extends Model
{
    use SoftDeletes;
    public $table = "job_work_receive";

    protected $fillable = [
        'company_id',
        'vendor_id',
        'product_category_id',
        'warehouse_id',
        'mobile_no',
        'phone',
        'state',
        'address',
        'job_work_issue_id',
        'job_work_receive_date',
        'job_work_receive_no',
        'process_receive_id',
        'remarks',
        'master_total_pending',
        'master_total_net_weight',
        'master_total_gross_weight',
        'master_total_current_pending',
        'term_and_condition',
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

    public function job_work_receive_items()
    {
        return $this->hasMany(JobWorkReceiveItems::class);
    }
    public function issueForProcess()
    {
        return $this->belongsTo(IssueForProcess::class, 'issue_for_process', 'id');
    }

    public function job_work_issue()
    {
        return $this->belongsTo(JobWorkIssue::class, 'job_work_issue_id', 'id');
    }
     public function job_work_type()
    {
        return $this->belongsTo(JobWorkType::class, 'job_work_type_id', 'id');
    }

    public function job_work_issue_items()
    {
        return $this->hasManyThrough(
            JobWorkIssueItems::class,
            JobWorkIssue::class,
            'id',                 // Foreign key on JobWorkIssue table...
            'job_work_issue_id',  // Foreign key on JobWorkIssueItems table...
            'job_work_issue_id',  // Local key on JobWorkReceive
            'id'                  // Local key on JobWorkIssue
        );
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function process_received()
    {
        return $this->belongsTo(ProcessReceived::class, 'process_receive_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(MasterWarehouse::class, 'warehouse_id', 'id');
    }
}
