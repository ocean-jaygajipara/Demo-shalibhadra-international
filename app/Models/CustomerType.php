<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CustomerType extends Model
{
    use SoftDeletes;
    public $table = "customer_type";

    protected $fillable = [
        'company_id',
        'name',
        'parent_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function parentTypeRelation()
    {
        return $this->belongsTo(CustomerType::class, 'parent_id');
    }
}
