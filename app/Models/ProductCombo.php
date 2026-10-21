<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class ProductCombo extends Model
{
    use SoftDeletes;
    public $table = "product_combos";

    protected $fillable = [
        'company_id',
        'product_combo_name',
        'product_category_id',
        'display_unit_id',
        'price',
        'part_no',
        'gross_wt',
        'net_wt',
        'grosse_per_qty',
        'hsn_code',
        'product_tax_id',
        // 'product_variant_price_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function displayUnit()
    {
        return $this->belongsTo(MasterUnit::class, 'display_unit_id');
    }

    public function productVariantPrice()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'product_variant_price_id');
    }
    public function combo_items()
    {
        return $this->hasMany(ProductComboItems::class, 'product_combo_id');
    }

    public function items()
    {
        return $this->hasMany(ProductComboItems::class, 'product_combo_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function product_category() {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }
}
