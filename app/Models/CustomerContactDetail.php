<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CustomerContactDetail extends Model
{
    use SoftDeletes;

    public $table = "customer_contact_details";

    protected $fillable = [
        'customer_id',
        'department',
        'name',
        'designation',
        'mobile',
        'email',
        'status',
        'platform',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
