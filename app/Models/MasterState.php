<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterState extends Model
{
    use SoftDeletes, HasFactory;


    public $table = "master_state";

    protected $fillable = [
        'country_id',
        'name',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',

    ];

    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id', 'id');
    }

    public function cities()
    {
        return $this->hasMany(MasterCity::class, 'state_id', 'id');
    }
}
