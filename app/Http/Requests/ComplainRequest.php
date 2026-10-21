<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ComplainRequest extends FormRequest
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
        $rules = [

            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'source_complain_id' => ['required', 'string', 'max:255'],
            'complain_type_id' => ['required', 'string', 'max:255'],
            'customer_type_id' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'string', 'max:255'],
            'complain_no' => ['required'],
            'remark' => ['nullable', 'string', 'max:255'],
            'complain_created_by_id' => ['required', 'string', 'max:255'],
            'complain_assign_to_id' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf,doc,docx', 'max:10240'],
        ];

        return $rules;
    }
}
