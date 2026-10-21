<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $table = 'email_log';

    protected $fillable = [
        'company_id',
        'user_id',
        'user_type',
        'user_email',
        'org_subject',
        'org_body_template',
        'subject',
        'body_template',
        'send_status',
        'send_date',
        'created_at',
        'api_name',
    ];

    public $timestamps = false; // Since you have only `created_at` and not `updated_at`
}
