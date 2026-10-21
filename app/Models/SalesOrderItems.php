<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrderItems extends Model {
    use SoftDeletes;
    protected $table = 'sales_order_items';
    protected $fillable = [
        'company_id',
        'is_combo',
        'quotation_item_id',
        'sales_order_id',
        'dispatch_items_id',
        'product_id',
        'product_variant_price_id',
        'price_list_product_id',
        'display_order',
        'product_code',
        'product_name',
        'product_description',
        'hsn_code',
        'outer_quantity',
        'inner_quantity',
        'loose_quantity',
        'kg_quantity',
        'unit',
        'quantity',
        'remaining_quantity',
        'completation_time_adjustment_quantity',
        'price_in_amount',
        'discount_flat',
        'discount_percentage',
        'rate_in_amount',
        'amount',
        'taxable_amount',
        'gst_in_percentage',
        'gst_amount',
        'cgst_in_percentage',
        'cgst_amount',
        'sgst_in_percentage',
        'sgst_amount',
        'sub_total',
    ];

    public function quotation() {
        return $this->belongsTo(Quotation::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function product_variant_price() {
        return $this->belongsTo(ProductVariantPrice::class);
    }

    public function price_list_product() {
        return $this->belongsTo(PriceListProducts::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function sales_order() {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }
}
