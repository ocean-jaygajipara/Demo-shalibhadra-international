<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EwayBill extends Model
{
    use SoftDeletes;
    protected $table = 'eway_bills';

    protected $fillable = [
        'company_id',
        'invoice_id',
        'eway_bill_no',
        'eway_bill_date',
        'valid_upto',
        'vehicle_no',
        'vehicle_type',
        'transporter_id',
        'transporter_name',
        'transporter_gstin',
        'status',
        'generated_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'vehicle_update_response',
        'lr_no',
        'lr_date',
        'api_response',
        'error_message',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function vehicleHistory()
    {
        return $this->hasMany(EwayBillVehicleHistory::class)->orderBy('updated_at', 'desc');
    }

    public function currentVehicle()
    {
        return $this->hasOne(EwayBillVehicleHistory::class)->where('is_current', true)->latest('updated_at');
    }
}
