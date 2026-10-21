<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterCountry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "master_countries";

    protected $fillable = [
        'name',
        'code',
        'short_name',
        'flag',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
