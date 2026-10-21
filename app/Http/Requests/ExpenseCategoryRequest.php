<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class ExpenseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        $id = $request->route('expense_category') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new ExpenseCategory())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('expense_category'), $request->route()->parameters());
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'name.required' => 'The expense category name is required.',
            'name.unique' => 'The expense category name must be unique.',
            'status.required' => 'The status is required.',
        ];
    }
}
