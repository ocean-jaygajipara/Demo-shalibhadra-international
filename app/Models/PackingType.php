<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackingType extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "packing_types";

    protected $fillable = [
        'company_id',
        'name',
        'weight',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
