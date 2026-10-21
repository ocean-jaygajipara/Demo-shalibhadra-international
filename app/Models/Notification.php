<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'user_type',
        'title',
        'body',
        'module_name',
        'module_id',
        'module_action',
        'notify_read',
        'status',
        'send_status',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    public function teamCreatedBy()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by');
    }

    public function adminCreatedBy()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by');
    }

    public function getCreatedByNameAttribute()
    {
        if ($this->created_type == "Team") {
            return optional($this->teamCreatedBy)->name ?? '-';
        } elseif ($this->created_type == "Admin") {
            return optional($this->adminCreatedBy)->name ?? '-';
        }
        return '-';
    }

    public function getUserNameAttribute()
    {
        if ($this->user_type == "Team") {
            return optional($this->teamUser)->name ?? '-';
        } elseif ($this->user_type == "Admin") {
            return optional($this->adminUser)->name ?? '-';
        }
        return '-';
    }

    public function teamUser()
    {
        return $this->belongsTo(TeamPerson::class, 'user_id');
    }

    public function adminUser()
    {
        return $this->belongsTo(AdminSoftware::class, 'user_id');
    }
}
