<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermissionsApp extends Model
{
    protected $table = 'role_permissions_app';

    protected $appends = ['role_permission_app_uuid'];

    protected $fillable = [
        'company_id',
        'team_id',
        'team_role_id',
        'main_menu_id',
        'sub_menu_id',
        'view_flag',
        'add_flag',
        'update_flag',
        'approval_flag',
        'all_data_flag',
        'personal_data_flag',
        'status',
        'created_by',
        'updated_by',
    ];

    public $timestamps = true;

    public function getRolePermissionAppUuidAttribute()
    {
        # role_permission_app_uuid
        return $this->company_id . '-' . $this->team_role_id .'-' . $this->team_id . '-' . $this->main_menu_id . '-' . $this->sub_menu_id;
    }

    public function main_menu()
    {
        return $this->belongsTo(MainMenu::class, 'main_menu_id');
    }

    public function sub_menu()
    {
        return $this->belongsTo(SubMenu::class, 'sub_menu_id');
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
