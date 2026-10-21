<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class QuotationLogs extends Model
{
    use SoftDeletes;
    public $table = "quotation_logs";

    protected $fillable = [
        'company_id',
        'quotation_id',
        'followup_id',
        'action',
        'status_id',
        'status_value',
        'json_data',
        'remarks',
        'created_by',
        'created_type',
    ];
}
