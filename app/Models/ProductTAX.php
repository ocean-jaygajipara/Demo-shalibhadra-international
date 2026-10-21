<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductTAX extends Model
{
    use SoftDeletes;

    public $table = "product_tax";

    protected $fillable = [
        'company_id',
        'tax_name',
        'tax_value',
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
