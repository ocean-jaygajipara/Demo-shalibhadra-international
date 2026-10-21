<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentTerm extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "payment_terms";

    protected $fillable = [
        'company_id',
        'payment_terms',
        'credit_days',
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
