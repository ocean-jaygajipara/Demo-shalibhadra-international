<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }
    public function productSubcategory()
    {
        return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id', 'id');
    }
    public function product_variant_price()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'product_variant_price_id', 'id')->withTrashed();
    }
    
}
