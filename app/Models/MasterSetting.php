<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterSetting extends Model
{
    use SoftDeletes;

    protected $table = 'master_setting';

    protected $fillable = [
        'company_id',
        'platform',
        'app_id',
        'app_secret',
        'redirect_url',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
