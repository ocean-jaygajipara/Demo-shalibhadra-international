<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductComboItems extends Model
{
    protected $table = 'product_combo_items';

    protected $fillable = [
        'product_combo_id',
        'product_variant_price_id',
        'price',
        'order_type',
        'quantity',
    ];

    // Optional relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_variant_price_id');
    }

    public function combo()
    {
        return $this->belongsTo(ProductCombo::class, 'product_combo_id');
    }
}
