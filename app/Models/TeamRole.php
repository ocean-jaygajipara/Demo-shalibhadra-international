<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class TeamRole extends Model
{

    use SoftDeletes;

    public $table = 'team_role';

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'status',
        'created_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function getParentNameAttribute()
    {
        # parent_name
        if ($this->parent_id == 0) {
            return 'Super Admin';
        }

        return optional(self::find($this->parent_id))->name ?? 'N/A';
    }
}
