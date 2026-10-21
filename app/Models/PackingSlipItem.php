<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingSlipItem extends Model
{
    use SoftDeletes;
    protected $table = 'packing_slip_item';

    protected $fillable = [
        'packing_slip_id',
        'sales_order_item_id',
        'product_id',
        'product_variant_price_id',
        'product_name',
        'product_code',
        'product_description',
        'box_no',
        'hsn_code',
        'unit',
        'no_of_carton',
        'is_combo',
        'sales_order_no',
        'sales_order_date',
        'wt_pc',
        'qty_pc',
        'org_qty_pc',
        'total_packet',
        'qty_ctn',
        'actual_wt_ctn',
        'total_qty',
        'total_weight',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function product_variant_price()
    {
        return $this->belongsTo(ProductVariantPrice::class);
    }

    public function sales_order_item()
    {
        return $this->belongsTo(SalesOrderItems::class, 'sales_order_item_id', 'id');
    }

    public function customerItem()
    {
        return $this->hasOne(ProductCustomerItem::class, 'product_id', 'product_id');
    }
}
