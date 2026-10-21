<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanySocial extends Model {
    use SoftDeletes;
    protected $table = 'company_socials';

    protected $fillable = [
        'company_id',
        'platform',
        'api_key',
        'user_id',
        'profile_id',
        'minutes_time',
        'page_id',
        'api_code',
        'json_response',
        'gstin',
        'username',
        'email',
        'password',
        'api_client_id',
        'api_client_secret',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
