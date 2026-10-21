<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use SoftDeletes;

    public $table = "leave_types";
    
    protected $fillable = [
        'company_id',
        'name',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company(){
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
