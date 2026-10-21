<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCustomerItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCustomerItemRequest extends FormRequest
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
        $id = $request->route('product_customer_item') ?? 0;

        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_id' => [
                'required',
                Rule::exists((new Product())->getTable(), 'id')
            ],
            'customer_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new ProductCustomerItem())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('company_id', $request->company_id)
                            ->where('product_id', $request->product_id);
                    })
                    ->ignore($id),
            ],
            'customer_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new ProductCustomerItem())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query
                            ->where('company_id', $request->company_id)
                            ->where('product_id', $request->product_id);
                    })
                    ->ignore($id),
            ],
            'customer_item_code' => [
                'nullable',
                'string',
                'max:255',
            ],
            'customer_item_description' => [
                'nullable',
                'string'
            ],
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'product_id.required' => 'The product is required.',
            'customer_name.required' => 'The customer name is required.',
            'customer_name.unique' => 'This customer item already exists for the selected product and company.',
            'customer_code.unique' => 'This customer code already exists for the selected product and company.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
