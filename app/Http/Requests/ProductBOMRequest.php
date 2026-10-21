<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBOM;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductBOMRequest extends FormRequest
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
        $id = $request->route('product_bom') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_id' => [
                'required', Rule::exists((new Product())->getTable(), 'id')
            ],
            'bom_product_category_id' => [
                'required', Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'bom_product_variant_price_id' => [
                'required', Rule::exists((new Product())->getTable(), 'id')
            ],
            'material_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique((new ProductBOM())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)->where('product_id', $request->product_id);

                })->ignore($id),
            ],
            'last_process' => 'nullable',
            'required_material_qty' => 'required',
            'for_product_qty' => 'required',
            'use_in_operation' => 'required',
            'status' => 'required|in:active,inactive',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'product_id.required' => 'The Product is required.',
            'name.unique' => 'This name is already in use. Try a different one.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
