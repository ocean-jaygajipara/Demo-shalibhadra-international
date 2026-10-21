<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingSlipDispatchItem extends Model
{
    use SoftDeletes;
    protected $table = 'packing_slip_dispatch_item';

    protected $fillable = [
        'packing_slip_id',
        'product_id',
        'variant_id',
        'product_name',
        'qty',
        'remaining_quantity',
        'available_quantity',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


}
