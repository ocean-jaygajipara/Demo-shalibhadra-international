<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItems extends Model {
    use SoftDeletes;
    public $table = "purchase_order_items";

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'product_id',
        'product_variant_price_id',
        'display_order',
        'product_code',
        'product_name',
        'product_description',
        'hsn_code',
        'outer_quantity',
        'inner_quantity',
        'loose_quantity',
        'total_kg',
        'unit',
        'quantity',
        'remaining_quantity',
        'price_in_amount',
        'discount_flat',
        'discount_percentage',
        'rate_in_amount',
        'amount',
        'taxable_amount',
        'gst_in_percentage',
        'gst_amount',
        'sub_total',
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
