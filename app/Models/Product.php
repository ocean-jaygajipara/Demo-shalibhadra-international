<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    public $table = "products";

    protected $fillable = [
        'company_id',
        'product_category_id',
        'product_name',
        'part_no',
        'product_grade_id',
        'unit_id',
        'hsn_code',
        'product_tax_id',
        //'product_mrp',
        'grosse_per_qty',
        'per_bag_quantity',
        'gross_wt',
        'net_wt',
        'min_stock',
        'opening_kg',
        'opening_pcs',
        'std_packing',
        'status',
        'attachment',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function getAttachmentsListAttribute()
    {
        $val = $this->attachment;
        if (empty($val)) {
            return [];
        }

        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return [
            [
                'file_path' => $val,
                'original_name' => basename($val),
                'uploaded_at' => $this->created_at ? $this->created_at->format('d-m-Y') : '',
            ]
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    public function product_bom()
    {
        return $this->hasMany(ProductBOM::class);
    }

    public function product_images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function product_variant_prices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'id');
    }

    public function product_tax()
    {
        return $this->belongsTo(ProductTAX::class, 'product_tax_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(MasterUnit::class, 'unit_id', 'id');
    }

    public function product_hsn()
    {
        return $this->belongsTo(ProductHSN::class, 'hsn_code', 'id');
    }

    public function product_grade()
    {
        return $this->belongsTo(ProductGrade::class, 'product_grade_id', 'id');
    }
}

