<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamPersonAssignArea extends Model
{
    public $timestamps = false;

    public $table = "team_person_assign_areas";

    protected $fillable = [
        'company_id',
        'team_person_id',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'created_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
     public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id');
    }

    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'area_id');
    }
}
