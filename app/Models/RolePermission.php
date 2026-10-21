<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $appends = ['role_permission_uuid','role_permission_cuid'];

    protected $fillable = [
        'company_id',
        'team_role_id',
        'customer_role_id',
        'main_menu_id',
        'sub_menu_id',
        'view_flag',
        'add_flag',
        'update_flag',
        'delete_flag',
        'print_flag',
        'excel_flag',
        'approval_flag',
        'all_data_flag',
        'personal_data_flag',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getRolePermissionUuidAttribute()
    {
        # role_permission_uuid
        return $this->company_id . '-' . $this->team_role_id . '-' . $this->main_menu_id . '-' . $this->sub_menu_id;
    }

    public function getRolePermissionCuidAttribute()
    {
        # role_permission_cuid
        return $this->company_id . '-' . $this->customer_role_id . '-' . $this->main_menu_id . '-' . $this->sub_menu_id;
    }
}
