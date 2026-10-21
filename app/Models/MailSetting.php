<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MailSetting extends Model
{
    use SoftDeletes;

    public $table = "mail_settings";

    protected $fillable = [
        'company_id',
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'bcc',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
