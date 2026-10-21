<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowupLogs extends Model {
    use SoftDeletes;
    public $table = "followup_logs";

    protected $fillable = [
        'company_id',
        'inquiry_id',
        'quotation_id',
        'followup_id',
        'action',
        'status_id',
        'status_value',
        'assigned_to_id',
        'assigned_to',
        'json_data',
        'remarks',
        'created_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
