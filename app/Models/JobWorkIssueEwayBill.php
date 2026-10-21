<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkIssueEwayBill extends Model
{
    use SoftDeletes;
    protected $table = 'job_work_issue_eway_bills';

    protected $fillable = [
        'company_id',
        'job_work_issue_id',
        'eway_bill_no',
        'eway_bill_date',
        'valid_upto',
        'vehicle_no',
        'vehicle_type',
        'transporter_id',
        'transporter_name',
        'transporter_gstin',
        'status',
        'generated_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'cancel_remarks',
        'cancel_response',
        'vehicle_update_response',
        'lr_no',
        'lr_date',
        'api_response',
        'error_message',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'api_response' => 'array',
        'vehicle_update_response' => 'array',
        'eway_bill_date' => 'datetime',
        'valid_upto' => 'datetime',
        'generated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'lr_date' => 'date',
    ];

    public function jobWorkIssue()
    {
        return $this->belongsTo(JobWorkIssue::class, 'job_work_issue_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // If implementing vehicle history later
    /*
    public function vehicleHistory()
    {
        return $this->hasMany(JobWorkIssueEwayBillVehicleHistory::class)->orderBy('updated_at', 'desc');
    }

    public function currentVehicle()
    {
        return $this->hasOne(JobWorkIssueEwayBillVehicleHistory::class)->where('is_current', true)->latest('updated_at');
    }
    */
}
