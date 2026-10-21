<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ExpoCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ExpoManagementRequest extends FormRequest
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
     */ public function rules(Request $request): array
    {
        $id = $request->route('leave_application') ?? 0; // Adjust 'leave' based on your route parameter name
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],

            'expo_category_id' => [
                'required',
                Rule::exists((new ExpoCategory())->getTable(), 'id')
            ],
            'name' => [
                'required',
                'string'

            ],
            'attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:1024',
            ],
            'start_date' => [
                'required',
                'date',
                'date_format:Y-m-d', // expects hidden input value like "2025-07-19"
            ],

            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date'
            ],

            'expo_url' => [
                'nullable',
                'string'

            ],
            'location' => [
                'nullable',
                'string'

            ],
            'status' => [
                'required',
                'in:active,inactive'
            ],

        ];
        // dd("L-88", $request->all(), $rules);
        return $rules;
    }
}
