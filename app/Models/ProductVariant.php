<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use SoftDeletes, HasFactory;

    public $table = "product_variant";

    protected $fillable = [
        'company_id',
        'name',
        'status',
        'platform',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant_price()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'id', 'variant_id'); // or correct foreign key
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id'); // or correct foreign key
    }
}
