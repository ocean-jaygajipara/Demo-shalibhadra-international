<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductSupplierDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductSupplierDetailRequest extends FormRequest
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
        $id = $request->route('product_supplier_detail') ?? 0;

        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_id' => [
                'required',
                Rule::exists((new Product())->getTable(), 'id')
            ],
            'supplier_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new ProductSupplierDetail())->getTable())
                    ->ignore($id),
            ],
            'process' => [
                'required',
                'string',
                'max:255',
            ],
            'rate' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'product_id.required' => 'The product is required.',
            'supplier_name.required' => 'The supplier name is required.',
            'supplier_name.unique' => 'This supplier name already exists.',
            'process.string' => 'The process must be a string.',
            'rate.required' => 'The rate is required.',
            'rate.numeric' => 'The rate must be a number.',
            'rate.min' => 'The rate must be at least 0.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
