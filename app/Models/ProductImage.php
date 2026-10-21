<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use SoftDeletes;
    public $table = "product_image";

    protected $fillable = [
        'company_id',
        'product_id',
        'image_name',
        'image_path',
        'status'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
