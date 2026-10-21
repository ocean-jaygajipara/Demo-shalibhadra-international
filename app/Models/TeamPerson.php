<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class TeamPerson extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    // protected $guard = 'team_person-api';

    public $table = "team_persons";


    protected $fillable = [
        'company_id',
        'name',
        'parent_type_id',
        'mobile_no',
        'employee_code',
        'username',
        'password',
        'sp',
        'min_working_start_time',
        'max_working_start_time',
        'working_end_time',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'email',
        'birth_date',
        'designation_id',
        'team_role_id',
        'app_version',
        'device_token',
        'device_token_app',
        'status',
        'created_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($teamPerson) {
            if (request()->has('password') && request('password') && !empty($teamPerson?->password)) {
                $teamPerson->sp = Str::random(4) . request('password') . Str::random(3);
            }
            if (request()->has('team_role_id') && is_null(request()->get('team_role_id'))) {
                $teamPerson->team_role_id = 0;
            }
            if (request()->has('parent_type_id') && is_null(request()->get('parent_type_id'))) {
                $teamPerson->parent_type_id = 0;
            }
        });

        static::updating(function ($teamPerson) {
            if (request()->has('password') && request('password') && !empty($teamPerson?->password)) {
                $teamPerson->sp = Str::random(4) . request('password') . Str::random(3);
            }
            if (request()->has('team_role_id') && is_null(request()->get('team_role_id'))) {
                $teamPerson->team_role_id = 0;
            }
            if (request()->has('parent_type_id') && is_null(request()->get('parent_type_id'))) {
                $teamPerson->parent_type_id = 0;
            }
        });
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class)->withDefault([
            'name' => '-'
        ]);
    }

    public function team_role()
    {
        return $this->belongsTo(TeamRole::class)->withDefault([
            'name' => '-'
        ]);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function company_details()
    {
        return $this->belongsTo(CompanyDetails::class, 'company_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id', 'id');
    }
    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id', 'id');
    }
    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id', 'id');
    }

    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'area_id', 'id')->where('company_id', $this->company_id)->where('country_id', $this->country_id)->where('state_id', $this->state_id);
    }

    public function role()
    {
        return $this->belongsTo(\App\Models\TeamRole::class, 'team_role_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
