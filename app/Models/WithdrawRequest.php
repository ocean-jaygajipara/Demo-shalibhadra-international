<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WithdrawRequest extends Model
{
    use SoftDeletes;

    protected $table = 'withdraw_requests';


    protected $fillable = [
        'company_id',
        'customer_id',
        'request_points',
        'amount',
        'withdraw_request_date',
        'attachment',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }




}
