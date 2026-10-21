<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterArea extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'master_area';

    protected $fillable = [
        'company_id',
        'country_id',
        'state_id',
        'city_id',
        'area_name',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the country that owns the area.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id');
    }

    /**
     * Get the state that owns the area.
     */
    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id');
    }

    /**
     * Get the city that owns the area.
     */
    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id');
    }
}
