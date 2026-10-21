<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SetPointToRupees extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "set_point_to_rupees";

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function getPointAttribute()
    {
        return json_decode($this->attributes['value'])->point ?? null;
    }

    public function getRupeesAttribute()
    {
        return json_decode($this->attributes['value'])->rupees ?? null;
    }

    public function getMinAmountAttribute()
    {
        return json_decode($this->attributes['value'])->min_amount ?? null;
    }

    public function getMaxAmountAttribute()
    {
        return json_decode($this->attributes['value'])->max_amount ?? null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
