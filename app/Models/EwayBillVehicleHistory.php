<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EwayBillVehicleHistory extends Model
{
     public $timestamps = false;

    protected $table = 'eway_bill_vehicle_history';

    protected $fillable = [
        'eway_bill_id',
        'eway_bill_no',
        'vehicle_no',
        'vehicle_type',
        'transport_mode',
        'transporter_name',
        'lr_no',
        'lr_date',
        'from_place',
        'from_state',
        'reason_code',
        'reason_remark',
        'update_response',
        'is_current',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function ewayBill()
    {
        return $this->belongsTo(EwayBill::class);
    }
}
