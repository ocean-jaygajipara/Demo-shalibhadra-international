<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('task_status') ?? 0;

        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'status_type' => [
                'required',
                Rule::in(array_keys(TaskStatus::$statusTypes)),
            ],
            'display_order' => [
                'nullable',
                'integer',
                Rule::unique((new TaskStatus())->getTable())
                    ->where(function ($query) use ($id) {
                        $query->where('company_id', $this->company_id)
                              ->where('status_type', $this->status_type)
                              ->where('id', '!=', $id);
                    }),
            ],
            'status_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new TaskStatus())->getTable())
                    ->where(function ($query) use ($id) {
                        $query->where('company_id', $this->company_id)
                              ->where('status_type', $this->status_type)
                              ->where('id', '!=', $id);
                    }),
            ],
            'background_color' => ['nullable', 'string', 'max:255'],
            'gradient_color'   => ['nullable', 'string', 'max:255'],
            'font_color'       => ['nullable', 'string', 'max:255'],
            'status'           => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'  => 'The company is required.',
            'company_id.exists'    => 'The selected company is invalid.',
            'status_type.required' => 'Status type is required.',
            'status_type.in'       => 'Status type must be either Task or To-do List.',
            'status_name.required' => 'Status name is required.',
            'status_name.unique'   => 'This status name already exists for the company and type.',
            'display_order.unique' => 'This display order is already taken for the company and type.',
        ];
    }
}
