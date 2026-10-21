<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkOrderItems extends Model {
    use SoftDeletes;
    public $table = "job_work_order_items";

    protected $fillable = [
        'company_id',
        'job_work_order_id',
        'product_id',
        'display_order',
        'product_name',
        'product_code',
        'product_stock',
        'quantity',
        'unit',
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
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function jobWorkOrder()
    {
        return $this->belongsTo(JobWorkOrder::class, 'job_work_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariantPrice()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'product_variant_price_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
