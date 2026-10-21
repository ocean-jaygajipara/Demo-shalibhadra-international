<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ProductSubcategory extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "product_subcategories";

    protected $fillable = [
        'company_id',
        'product_category_id',
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
    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }


}
