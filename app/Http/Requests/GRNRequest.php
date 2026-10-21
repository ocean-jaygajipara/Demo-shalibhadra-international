<?php

namespace App\Http\Requests;

use App\Models\GRN;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GRNRequest extends FormRequest
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
        $id = $request->route('grn') ?? 0;
        $rules = [
            'company_id' => ['required', 'exists:companies,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'customer_name' => ['nullable'],
            'contact_person_name' => ['nullable'],
            'phone' => ['nullable'],
            'state' => ['nullable'],
            'address' => ['nullable'],
            'grn_date' => ['required', 'date'],
            'grn_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new GRN())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                        ->whereNull('deleted_at');
                })->ignore($id)
            ],
            'dos_date' => ['required', 'date'],
            'remark' => ['nullable'],

            'product_name' => ['required', 'array'],
            'product_id' => ['required', 'array'],
            'product_code' => ['required', 'array'],
            'product_description' => ['nullable', 'array'],
            'last_process_id' => ['nullable', 'array'],
            'product_stock' => ['nullable', 'array'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'numeric'],
            'unit' => ['required', 'array'],
            'wt_pc' => ['required', 'array'],
            'alt_qty' => ['required', 'array'],
        ];
        return $rules;
    }

    public function messages(): array
    {
        return [
             // Main GRN fields
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company is invalid.',

            'customer_id.required' => 'The customer field is required.',
            'customer_id.exists' => 'The selected customer is invalid.',

            'grn_date.required' => 'The GRN date is required.',
            'grn_date.date' => 'The GRN date must be a valid date.',

            'grn_no.required' => 'The GRN number is required.',
            'grn_no.string' => 'The GRN number must be a valid string.',
            'grn_no.max' => 'The GRN number may not be greater than 255 characters.',
            'grn_no.unique' => 'The GRN number has already been taken for this company.',

            'dos_date.required' => 'The DOS date is required.',
            'dos_date.date' => 'The DOS date must be a valid date.',

            // Optional info
            'customer_name.nullable' => 'The customer name field is optional.',
            'contact_person_name.nullable' => 'The contact person name field is optional.',
            'phone.nullable' => 'The phone field is optional.',
            'state.nullable' => 'The state field is optional.',
            'address.nullable' => 'The address field is optional.',
            'remark.nullable' => 'The remark field is optional.',

            // Product details
            'product_name.required' => 'Please enter product name(s).',
            'product_name.array' => 'Product names must be in array format.',

            'product_id.required' => 'Please select at least one product.',
            'product_id.array' => 'Product IDs must be in array format.',

            'product_code.required' => 'Product code is required.',
            'product_code.array' => 'Product codes must be in array format.',

            'last_process_id.required' => 'Please select last process for each product.',
            'last_process_id.array' => 'Last process IDs must be in array format.',

            'quantity.required' => 'Quantity is required for each product.',
            'quantity.array' => 'Quantities must be in array format.',
            'quantity.*.required' => 'Quantity is required.',
            'quantity.*.numeric' => 'Quantity must be a valid number.',

            'unit.required' => 'Unit is required for each product.',
            'unit.array' => 'Units must be in array format.',

            'wt_pc.required' => 'Weight per piece is required.',
            'wt_pc.array' => 'Weight per piece values must be in array format.',

            'alt_qty.required' => 'Alternate quantity is required.',
            'alt_qty.array' => 'Alternate quantities must be in array format.',
        ];
    }
}
