<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchItems extends Model {
    use SoftDeletes;
    protected $table = 'dispatch_items';

    protected $fillable = [
        'dispatch_id',
        'sales_order_item_id',
        'product_id',
        'product_variant_price_id',
        'product_name',
        'unit',
        'product_description',
        'remaining_quantity',
        'available_quantity',
        'dispatch_quantity',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItems::class, 'sales_order_item_id');
    }
}
