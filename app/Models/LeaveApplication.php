<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveApplication extends Model
{
    use SoftDeletes;
    public $table = "leave_applications";

    static $folderPath = "leave_applications/";

    protected $fillable = [
        'company_id',
        'team_person_ids',
        'leave_type_id',
        'fromdate_time',
        'todate_time',
        'halfday_fullday',
        'singleday_multipleday',
        'firsthalf_secondhalf',
        'leave_reason',
        'attachment',
        'rejection_reason',
        'attachment',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['attachment_url'];

    public static $leaveForDay = [
        'halfday' => "Half Day",
        'fullday' => "Full Day",
        // 'day' => "Day",
    ];
    public static $leaveByDays = [
        'singleday' => "Single Day",
        'multipleday' => "Mutiple Day",

    ];
    public static $leaveForHalfdays = [
        'firsthalf' => "First Half",
        'secondhalf' => "Second Half",

    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    // In Leave model
    public function leave_type()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'id');
    }
    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_ids', 'id');
    }


    public function getAttachmentUrlAttribute()
    {
        # attachment_url
        if ($this->attachment) {
            if (file_exists(public_path($this->attachment))) {
                return asset($this->attachment);
            }
            return public_path($this->attachment);
        }
        return null;
    }
}
