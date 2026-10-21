<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantPrice extends Model
{
    use SoftDeletes;

    protected $table = 'product_variant_prices';

    protected $fillable = [
        'product_id',
        'product_code',
        'variant_id',
        'including_gst',
        'description',
        'product_weight',
        'price',
        'inner_pack_qty',
        'outer_pack_qty',
        'min_stock_qty',
        'max_stock_qty',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }


    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id', 'id');
    }

    public function product_stock()
    {
        return $this->hasMany(ProductStockHistory::class, 'product_variant_price_id');
    }
    
}
