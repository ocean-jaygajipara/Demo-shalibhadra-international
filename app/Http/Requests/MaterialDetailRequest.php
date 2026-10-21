<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MaterialDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialDetailRequest extends FormRequest
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
        $id = $request->route('material_detail') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],
            'qc_inspection_id' => [
                'required',
                Rule::exists((new MaterialDetail())->getTable(), 'id')
            ],
            'dimension' => 'nullable|string|max:255',
            'size'      => 'nullable|numeric|min:0',
            'ltl'       => 'nullable|numeric|min:0',
            'utl'       => 'nullable|numeric|min:0',
            'instrument'=> 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $rules["size$i"] = 'nullable|numeric|min:0';
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'company_id.required' => 'The company is required.',
            'company_id.exists'   => 'The selected company does not exist.',

            'qc_inspection_id.required' => 'The QC Inspection is required.',
            'qc_inspection_id.exists'   => 'The selected QC Inspection does not exist.',

            'dimension.required'  => 'The dimension field is required.',
            'dimension.string'    => 'The dimension must be a valid string.',
            'dimension.max'       => 'The dimension must not exceed 255 characters.',

            'size.numeric'        => 'The size must be a number.',
            'size.min'            => 'The size must be at least 0.',

            'ltl.numeric'         => 'The LTL (Lower Tolerance Limit) must be a number.',
            'ltl.min'             => 'The LTL must be at least 0.',

            'utl.numeric'         => 'The UTL (Upper Tolerance Limit) must be a number.',
            'utl.min'             => 'The UTL must be at least 0.',

            'instrument.string'   => 'The instrument must be a valid string.',
            'instrument.max'      => 'The instrument must not exceed 255 characters.',

            'status.required'     => 'The status is required.',
            'status.in'           => 'The status must be either "active" or "inactive".',
        ];

        for ($i = 1; $i <= 10; $i++) {
            $messages["size$i.numeric"] = "Size $i must be a number.";
            $messages["size$i.min"]     = "Size $i must be at least 0.";
        }

        return $messages;
    }
}
