<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Task;
use App\Models\TeamPerson;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $this->route('task') ?? 0;

        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_status_id' => [
                'nullable',
                'integer',
                Rule::exists('task_statuses', 'id'),
            ],
            'department_id' => [
                'nullable',
                Rule::exists((new Department())->getTable(), 'id'),
            ],
            'task_created_by' => [
                'required',
                Rule::exists((new TeamPerson())->getTable(), 'id'),
            ],
            'task_assigned_to' => [
                'required',
                Rule::exists((new TeamPerson())->getTable(), 'id'),
            ],
            'priority' => [
                'required',
                Rule::in(array_keys(Task::$task_priority_arr)),
            ],
            'task_start_date_time' => ['nullable', 'date'],
            'arrival_date_time' => ['required', 'date'],
            'due_date_time' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'created_type' => ['nullable', Rule::in(['team', 'admin'])],
            'updated_type' => ['nullable', Rule::in(['team', 'admin'])],
            'created_by' => ['nullable', 'string'],
            'updated_by' => ['nullable', 'string'],
            'deleted_by' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'company_id.exists'   => 'The selected company is invalid.',
            'title.required'      => 'The task title is required.',
            'task_created_by.required' => 'The creator is required.',
            'task_assigned_to.required' => 'The assignee is required.',
            'priority.required'   => 'The task priority is required.',
            'priority.in'         => 'Priority must be High, Medium, or Low.',
            'arrival_date_time.before_or_equal' => 'Arrival date must be before or equal to due date.',
            'due_date_time.after_or_equal'      => 'Due date must be after or equal to arrival date.',
        ];
    }
}
