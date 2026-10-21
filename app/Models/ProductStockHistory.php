<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductStockHistory extends Model
{
    use SoftDeletes;
    protected $table = 'product_stock_history';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_category_id',
        'product_subcategory_id',
        'product_variant_price_id',
        'dispatch_id',
        'inward_id',
        'job_work_issue_id',
        'job_work_receive_id',
        'qc_inspection_id',
        'packing_slip_id',
        'process_wise_production_id',
        'total_quantity',
        'total_kg',
        'debit',
        'credit',
        'stock_type',
        'date',
        'remark',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function product_category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'id');
    }

    // public function product_subcategory()
    // {
    //     return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id', 'id');
    // }

    // public function product_variant_price()
    // {
    //     return $this->belongsTo(ProductVariantPrice::class, 'product_variant_price_id', 'id')->withTrashed();
    // }

    public function warehouse()
    {
        return $this->belongsTo(MasterWarehouse::class, 'warehouse_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_variant_price_id','id');
    }
}
