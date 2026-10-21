<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskStatusLog extends Model
{
    protected $table = 'task_status_logs';

    protected $fillable = [
        'company_id',
        'task_id',
        'team_id',
        'task_status_id',
        'status_date',
        'remarks',
    ];

    protected $casts = [
        'status_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function task_status()
    {
        return $this->belongsTo(TaskStatus::class, 'task_status_id', 'id');
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_id', 'id');
    }

}
