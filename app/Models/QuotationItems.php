<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationItems extends Model {
    use SoftDeletes;
    protected $table = 'quotation_items';

    protected $fillable = [
        'company_id',
        'is_combo',
        'sales_order_item_id',
        'quotation_id',
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
        return $this->belongsTo(PriceListProduct::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }
}
