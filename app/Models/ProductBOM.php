<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBOM extends Model
{
    use SoftDeletes;
    public $table = "product_bom";

    protected $fillable = [
        'company_id',
        'product_id',
        'bom_product_category_id',
        'bom_product_variant_price_id',
        'material_name',
        'last_process',
        'required_material_qty',
        'for_product_qty',
        'use_in_operation',
        'status'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class, 'bom_product_category_id', 'id');
    }

    public function product_bom_name()
    {
        return $this->belongsTo(Product::class, 'bom_product_variant_price_id','id');
    }
}
