<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductRmDetail extends Model
{
    use SoftDeletes;
    public $table = "product_rm_detail";

    protected $fillable = [
        'company_id',
        'product_id',
        'size_premium',
        'od_mm',
        'id_mm',
        'shape',
        'alloy',
        'density',
        'tol_plus',
        'tol_minus',
        'status'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function product_alloy() {
        return $this->belongsTo(ProductAlloy::class, 'alloy', 'id');
    }
}
