<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceListProducts extends Model {
    use SoftDeletes;
    protected $table = 'price_list_products';

    protected $fillable = [
        'company_id',
        'category_id',
        'subcategory_id',
        'product_id',
        'product_variant_price_id',
        'price_list_id',
        'price',
        'discounted_selling_price',
        'discounted_amount',
        'discount_type',
        'discount_percentage_value',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}