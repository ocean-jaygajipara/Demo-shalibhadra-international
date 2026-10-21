<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterCity extends Model
{
    use SoftDeletes, HasFactory;

    public $table = "master_city"; // The table name for cities

    protected $fillable = [
        'country_id',
        'state_id', // Associated state ID
        'name', // City name
        'status', // Active/Inactive status
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Relationship to the country the city belongs to.
     */
    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id', 'id'); // Belongs to country
    }

    /**
     * Relationship to the state the city belongs to.
     */
    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id', 'id'); // Belongs to state
    }
}
