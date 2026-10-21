<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\SourceOfInquiry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SourceOfInquiryRequest extends FormRequest
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
        $id = $request->route('marketing_source_of_inquiry') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],

            // 'name' => 'required|string|max:255|unique:' . (new MasterCity())->getTable() . ',name',
            'name' => [
                'required',
                Rule::unique((new SourceOfInquiry())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The Company Name is required.',
            'name.required' => 'The name is required.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
