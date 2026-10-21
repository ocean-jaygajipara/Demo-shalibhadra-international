<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ProductHSN;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductHSNRequest extends FormRequest
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
        $id = $request->route('product_hsn') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:8',
                Rule::unique((new ProductHSN())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);

                })->ignore($id),
            ],
            'product_tax' => ['required', 'numeric', 'min:0'],
            'status' => 'required|in:active,inactive',
        ];
        return $rules;

    }

    public function messages()
    {
        return [
            'company_id.required' => 'The country is required.',
            'name.unique' => 'This name is already in use. Try a different one.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
            'product_tax.required' => 'Product tax is required.',
            'product_tax.numeric'  => 'Product tax must be a number.',
            'product_tax.min'      => 'Product tax cannot be negative.',
        ];
    }
}
