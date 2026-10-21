<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PurposeVisitMaster extends Model
{
  use HasFactory, SoftDeletes;

    public $table = "purpose_visit_masters";

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
    }}
