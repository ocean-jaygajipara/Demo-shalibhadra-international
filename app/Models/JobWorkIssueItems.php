<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkIssueItems extends Model
{
    use SoftDeletes;
    public $table = "job_work_issue_items";

    protected $fillable = [
        'company_id',
        'job_work_issue_id',
        'product_id',
        'display_order',
        'product_name',
        'product_code',
        'product_stock',
        'gross_weight',
        'issue_weight',
        'remaining_weight',
        'bl_percentage',
        'rate_in_amount',
        'taxable_amount',
        'cgst_percentage',
        'cgst_amount',
        'sgst_percentage',
        'sgst_amount',
        'total_rate_in_amount',
        'amount',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function job_work_issue()
    {
        return $this->belongsTo(JobWorkIssue::class, 'job_work_issue_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariantPrice()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'product_variant_price_id');
    }

    public function job_work_receive_items()
    {
        return $this->hasMany(JobWorkReceiveItems::class, 'job_work_issue_item_id', 'id');
    }
    public function job_work_receives()
    {
        return $this->hasMany(JobWorkReceive::class, 'job_work_issue_id', 'id');
    }
}
