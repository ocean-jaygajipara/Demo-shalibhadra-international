<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItems extends Model
{
    use SoftDeletes;
    protected $table = 'invoice_items';
    protected $fillable = [
        'company_id',
        'is_combo',
        'invoice_id',
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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_variant_price()
    {
        return $this->belongsTo(ProductVariantPrice::class);
    }

    public function price_list_product()
    {
        return $this->belongsTo(PriceListProducts::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesOrder::class, 'invoice_id', 'id');
    }

    public function product_hsn()
    {
        // Assuming there is a ProductHSN model and the local column 'hsn_code' maps to the 'hsn_code' column in product_hsn table
        return $this->hasOne(ProductHSN::class, 'name', 'hsn_code');
    }
}
