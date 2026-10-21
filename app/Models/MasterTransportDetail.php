<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterTransportDetail extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "master_transport_detail";

    protected $fillable = [
        'company_id',
        'transport_by_id',
        'name',
        'transport_gst_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function transportby()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }
}
