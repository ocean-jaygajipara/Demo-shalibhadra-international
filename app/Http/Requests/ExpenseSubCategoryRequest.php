<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;
use App\Models\TeamPerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class ExpenseSubCategoryRequest extends FormRequest
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
    public function rules(): array
    {
        $id = request()->route('expense_subcategory') ?? 0; // Correct way to get $id
        $table = (new ExpenseSubCategory())->getTable();

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'team_person_ids' => [
                'required',
                'array'
            ],
            'team_person_ids.*' => [
                Rule::exists((new TeamPerson())->getTable(), 'id')
            ],


            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table)->where(function ($query) {
                    return $query->where('company_id', $this->company_id)
                        ->where('expense_category_id', $this->expense_category_id)
                        ->where('team_person_ids', $this->team_person_ids);

                })->ignore($id),
            ],
            'expense_category_id' => [
                'required',
                Rule::exists((new ExpenseCategory())->getTable(), 'id')
            ],
            'expense_type' => 'required|string',
            'is_image_required' => 'nullable|boolean',
            'min_amount' => [
                'nullable',
                 'numeric',
                  'min:0',
                   'required_if:expense_type,General'
                ],
            'max_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'gte:min_amount',
                'required_if:expense_type,General'
            ],
            'per_km_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:expense_type,KM'
            ],
            'fix_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:expense_type,Food'
            ],
            'from_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:expense_type,Food'
            ],

            'to_time' => [
                'nullable',
                'date_format:H:i',
                'after:from_time'
            ],
            'status' => 'required|in:active,inactive',
        ];

        // dd("L-57",$rules);
        return $rules;
    }
}
