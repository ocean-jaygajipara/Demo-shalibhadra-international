<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'company_id',
        'name',
        'status',
        'platform',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
