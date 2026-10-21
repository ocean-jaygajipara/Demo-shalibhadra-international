<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class qrgenerator extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "qrgenerators";

    protected $fillable = [
        'company_id',
        'qrlable',
        'quantity',
        'pointvalue',
        'status',
        'image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
