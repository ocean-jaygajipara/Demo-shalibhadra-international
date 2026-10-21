<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskStatus extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "task_statuses";

    protected $fillable = [
        'company_id',
        'status_type',
        'status_name',
        'display_order',
        'background_color',
        'gradient_color',
        'font_color',
        'status',
        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static $statusTypes = [
        'task' => 'Task',
        'todo' => 'To-do List',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getStatusTypeLabelAttribute()
    {
        return self::$statusTypes[$this->status_type] ?? $this->status_type;
    }
}
