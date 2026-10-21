<?php

namespace App\Models;

// use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminSoftware extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    protected $guard = 'admin_software';

	public $table = 'admin_software';

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'email_verified_at',
        'password',
        'type',
        'sp',
        'status',
        'device_token',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static $user_type = [
        "admin_user"=> "Admin User",
        "office_user"=> "Office User",
    ];
}
