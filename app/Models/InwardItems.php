<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InwardItems extends Model
{
    use SoftDeletes;
    public $table = "inward_items";

    protected $fillable = [
        'inward_id',
        'purchase_order_item_id',
        'product_id',
        'product_variant_price_id',
        'product_name',
        'unit',
        'product_description',
        'remaining_quantity',
        'available_quantity',
        'inward_quantity',
        'product_weight',
        'is_qc_done',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function inward()
    {
        return $this->belongsTo(Inward::class, 'inward_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchase_order_item()
    {
        return $this->belongsTo(PurchaseOrderItems::class, 'purchase_order_item_id');
    }
}
