<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QcInspection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qc_inspection';

    protected $fillable = [
        'company_id',
        'vendor_id',
        'mobile_no',
        'phone',
        'state',
        'address',
        'warehouse_id',
        'job_work_receive_id',
        'job_work_receive_item_id',
        'qc_inspection_date',
        'qc_inspection_no',
        'product_id',
        'product_name',
        'product_code',
        'issue_for_process_id',
        'received_weight',
        'ok_weight',
        'ok_qty',
        'wt_pc',
        'rejection_qty',
        'available_qty',
        'status',
        'platform',
        'created_type',
        'updated_type',
        'inward_id',
        'inward_item_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function product_bom()
    {
        return $this->hasMany(ProductBOM::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function issue_for_process()
    {
        return $this->belongsTo(ProcessReceived::class, 'issue_for_process_id', 'id');
    }

    public function material_details()
    {
        return $this->hasMany(MaterialDetail::class, 'qc_inspection_id', 'id');
    }

    public function job_work_receive_items()
    {
        return $this->belongsTo(JobWorkReceiveItems::class, 'job_work_receive_item_id');
    }

    public function job_work_receive()
    {
        return $this->belongsTo(JobWorkReceive::class, 'job_work_receive_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(MasterWarehouse::class, 'warehouse_id', 'id');
    }
}
