<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryProduct extends Model {
    public $table = "inquiry_products";

    protected $fillable = [
        'company_id',
        'inquiry_id',
        'product_id',
        'quantity',
        'requirements',
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function product_variants() {
        return $this->hasMany(ProductVariant::class, 'product_id', 'product_id');
    }

    public function product_variant_prices() {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'product_id');
    }
}