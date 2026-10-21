<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamPersonMapCordinates extends Model
{
    public $table = 'team_person_map_cordinates';

    protected $fillable = [
        'company_id',
        'user_type',
        'user_id',
        'latitude',
        'longitude',
        'pin',
        'platform',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function user(){
        if($this->user_type == "team_person"){
            return $this->belongsTo(TeamPerson::class,'user_id', 'id')->where('company_id', $this->company_id);
        }
        return null;
    }
}
