<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCustomerItem extends Model
{
    use SoftDeletes;
    public $table = "product_customer_item";

    protected $fillable = [
        'company_id',
        'product_id',
        'customer_name',
        'customer_code',
        'customer_item_code',
        'customer_item_description',
        'status'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
