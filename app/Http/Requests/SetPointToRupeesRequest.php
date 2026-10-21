<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SetPointToRupeesRequest extends FormRequest
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
        $id = $request->route('set-point-to-rupees') ?? 0;
        $isUpdate = $id ? true : false;

        return [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'point'       => 'required|numeric|min:0',
            'rupees'      => 'required|numeric|min:0',
            'min_amount'  => 'required|numeric|min:0',
            'max_amount'  => 'required|numeric|min:0',
        ];

        // dd("L-51", $rules, $id, $request->all());
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The Company Name is required.',
            'company_id.exists' => 'The selected Company is invalid.',
            'point.required'   => 'The point field is required.',
            'point.numeric'    => 'The point value must be numeric.',
            'rupees.required'  => 'The rupees field is required.',
            'rupees.numeric'   => 'The rupees value must be numeric.',
            'min_amount.required'  => 'The Min Amount field is required.',
            'min_amount.numeric'   => 'The Min Amount value must be numeric.',
            'max_amount.required'  => 'The Max Amount field is required.',
            'max_amount.numeric'   => 'The Max Amount value must be numeric.',
        ];
    }
}
