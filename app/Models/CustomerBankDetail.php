<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CustomerBankDetail extends Model
{
    use SoftDeletes;

    public $table = "customer_bank_details";

    protected $fillable = [
        'customer_id',
        'bank_name',
        'account_number',
        'branch',
        'ifsc',
        'swift_code',
        'status',
        'platform',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
