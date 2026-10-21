<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PlanMaster extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "plan_masters";

    protected $fillable = [
        'name',
        'plan_valid_day',
        'max_team_user_count',
        'max_customer_count',
        'max_inquiry_count',
        'plan_type',
        'app_right',
        'panel_right',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static $PlanType = [
        'general' => "General",
        'private' => "Private",

    ];

    // Get value as array (accessor)
    public function getAppRightAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    // Store value as comma-separated string (mutator)
    public function setAppRightAttribute($value)
    {
        $this->attributes['app_right'] = is_array($value) ? implode(',', $value) : $value;
    }

    // Get value as array (accessor)
    public function getPanelRightAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    // Store value as comma-separated string (mutator)
    public function setPanelRightAttribute($value)
    {
        $this->attributes['panel_right'] = is_array($value) ? implode(',', $value) : $value;
    }
}
