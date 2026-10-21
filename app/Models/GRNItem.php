<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GRNItem extends Model
{
    use SoftDeletes;
    protected $table = 'grn_item';

    protected $fillable = [
        'grn_id',
        'product_id',
        'product_name',
        'product_code',
        'product_description',
        'last_process_id',
        'product_stock',
        'quantity',
        'unit',
        'wt_pc',
        'alt_qty',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function product_variant_price() {
        return $this->belongsTo(ProductVariantPrice::class);
    }

}
