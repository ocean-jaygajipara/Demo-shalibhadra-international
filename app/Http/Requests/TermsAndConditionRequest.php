<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\TermsAndCondition;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TermsAndConditionRequest extends FormRequest
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
        $id = $request->route('terms_and_condition') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],

            // 'name' => 'required|string|max:255|unique:' . (new MasterCity())->getTable() . ',name',
            'title' => [
                'required',
                Rule::unique((new TermsAndCondition())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })->ignore($id),
            ],
            'terms_and_condition_detail' => [
                'required'
            ],
            'status' => 'required|in:active,inactive',
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('master-city'), $request->route()->parameters());
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The Company Name is required.',
            'title.required' => 'The Title is required.',
            'terms_and_condition_detail.required' => 'The Terms and Condition is required.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
