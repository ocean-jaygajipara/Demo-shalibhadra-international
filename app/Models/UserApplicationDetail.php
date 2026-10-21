<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserApplicationDetail extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'user_application_details';


    protected $fillable = [
        'tbl_model', 'tbl_model_id',

        'device_brand', 'device_model', 'device_id', 'device_sdk', 'device_version_code', 'device_host', 'device_serial',

        'created_by', 'updated_by', 'deleted_by',
    ];
}
