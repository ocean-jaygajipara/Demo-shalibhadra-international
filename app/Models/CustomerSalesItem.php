<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerSalesItem extends Model
{
    use SoftDeletes;

    public $table = "customer_sales_items";

    protected $fillable = [
        'customer_id',
        'item_code',
        'item_name',
        'rate',
        'type',
        'date',
        'status',
        'platform',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
