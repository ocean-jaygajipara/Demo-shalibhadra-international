<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InquiryLogs extends Model {
    use SoftDeletes;
    public $table = "inquiry_logs";

    protected $fillable = [
        'company_id',
        'inquiry_id',
        'followup_id',
        'action',
        'status_id',
        'status_value',
        'inquiry_assigned_to_id',
        'inquiry_assigned_to',
        'json_data',
        'remarks',
        'created_by',
        'created_type',
    ];
}