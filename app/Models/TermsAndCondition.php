<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TermsAndCondition extends Model
{
    use SoftDeletes;

    public $table = "terms_and_condition";

    protected $fillable = [
        'company_id',
        'title',
        'terms_and_condition_detail',
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
