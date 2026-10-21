<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkReceiveItems extends Model
{
    use SoftDeletes;
    public $table = "job_work_receive_items";

    protected $fillable = [
        'company_id',
        'job_work_issue_item_id',
        'job_work_receive_id',
        'product_id',
        'display_order',
        'product_name',
        'product_code',
        'product_stock',
        'pending_weight',
        'received_weight',
        'gross_weight',
        'wt_pc',
        'quantity',
        'rate_in_amount',
        'bl_percentage',
        'incoming_scrap',
        'product_used',
        'our_stock',
        'challan_stock',
        'used_qty',
        'used_weight',
        'qc_check_weight',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function job_work_issue()
    {
        return $this->belongsTo(JobWorkIssue::class, 'job_work_issue_id');
    }
    public function job_work_receive()
    {
        return $this->belongsTo(JobWorkReceive::class, 'job_work_receive_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariantPrice()
    {
        return $this->belongsTo(ProductVariantPrice::class, 'product_variant_price_id');
    }
    public function job_work_issue_item()
    {
       
        return $this->belongsTo(JobWorkIssueItems::class, 'job_work_issue_item_id', 'id');
    }
}
