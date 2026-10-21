<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobWorkType extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "job_work_type";

    protected $fillable = [
        'company_id',
        'name',
        'bl_checkbox',
        'bl_percentage',
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
