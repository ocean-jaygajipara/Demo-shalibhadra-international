<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;
    protected $table = 'task';

    public static $task_priority_arr = [
        "high"   => "High",
        "medium" => "Medium",
        "low"    => "Low",
    ];

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'department_id',
        'task_status_id',
        'task_created_by',
        'task_assigned_to',
        'priority',
        'task_start_date_time',
        'arrival_date_time',
        'due_date_time',
        'status',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function getTaskAssignedToNamesAttribute()
    {
        $value = $this->getRawOriginal('task_assigned_to');

        if (!$value) {
            return [];
        }

        $ids = explode(',', $value);

        return TeamPerson::whereIn('id', $ids)->pluck('name')->toArray();
    }

    public function getTaskCreatedByNameAttribute()
    {
        $id = $this->getRawOriginal('task_created_by');

        if (!$id) {
            return '-';
        }

        return optional(TeamPerson::find($id))->name ?? '-';
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function task_status()
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id', 'id');
    }

    public function task_status_log()
    {
        return $this->hasMany(TaskStatusLog::class);
    }

    public function getTotalWorkedHoursAttribute()
    {
        $logs = $this->task_status_log()
        ->where('company_id', $this->company_id)
        ->orderBy('status_date')
        ->get();

        $startStatus = TaskStatus::where('company_id', $this->company_id)->where('display_order', 2)->first()?->id;

        $totalSeconds = 0;
        $startTime = null;

        foreach ($logs as $log) {
            if ($log->task_status_id == $startStatus) {
                $startTime = \Carbon\Carbon::parse($log->status_date);
            } elseif ($startTime) {
                $stopTime = \Carbon\Carbon::parse($log->status_date);
                $totalSeconds += $startTime->diffInSeconds($stopTime);
                $startTime = null;
            }
        }

        if ($startTime) {
            $totalSeconds += $startTime->diffInSeconds(now());
        }

        // Format into H:M
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf("%dh %02dm", $hours, $minutes);
    }
}
