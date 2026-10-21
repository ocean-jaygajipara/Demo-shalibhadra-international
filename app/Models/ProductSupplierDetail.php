<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSupplierDetail extends Model
{
    use SoftDeletes;
    public $table = "product_supplier_detail";

    protected $fillable = [
        'company_id',
        'product_id',
        'supplier_name',
        'process',
        'rate',
        'status'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
