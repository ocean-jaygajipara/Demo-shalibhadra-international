<?php

namespace App\Http\Requests;
use App\Models\Dispatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DispatchRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(Request $request): array {
        $id = $request->route('dispatch_orders') ?? 0;
        return [
            'sales_order_item_id' => ['required', 'exists:sales_order_items,id'],
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_price_id' => ['required', 'exists:product_variant_prices,id'],
            'product_name' => ['required'],
            'product_description' => ['nullable'],
            'unit' => ['nullable'],
            'remaining_quantity' => ['required'],
            'available_quantity' => ['required'],
            'dispatch_quantity.*' => ['required', 'numeric', 'gt:0'],

            'sales_order_id' => ['required', 'exists:sales_orders,id'],
            'customer_name' => ['nullable'],
            'contact_person_name' => ['nullable'],
            'phone' => ['nullable'],
            'state' => ['nullable'],
            'address' => ['nullable'],
            'remarks' => ['nullable'],
            'price_list_id' => ['nullable', 'exists:price_lists,id'],

            'company_id' => ['required', 'exists:companies,id'],
            'warehouse_id' => ['required', 'exists:master_warehouse,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'dispatch_date' => ['required', 'date'],
            'expected_dispatch_date' => ['nullable', 'date'],
            'dispatch_no' => ['required', 'string', 'max:255',
                                Rule::unique((new Dispatch())->getTable())->where(function ($query) use ($request) {
                                    return $query->where('company_id', $request->company_id)
                                            ->whereNull('deleted_at');
                                })->ignore($id)
            ],
            'transport_by_id' => ['nullable', 'exists:master_transport_by,id'],
            'transport_details_id' => ['nullable', 'exists:master_transport_detail,id'],
        ];
    }

    public function messages(): array {
        return [
            'sales_order_item_id.required' => 'Sales order item is required.',
            'sales_order_item_id.exists' => 'Selected sales order item does not exist.',

            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',

            'product_variant_price_id.required' => 'Product variant price is required.',
            'product_variant_price_id.exists' => 'Selected product variant price does not exist.',

            'product_name.required' => 'Product name is required.',

            'remaining_quantity.required' => 'Remaining quantity is required.',
            'remaining_quantity.numeric' => 'Remaining quantity must be a number.',

            'available_quantity.required' => 'Available quantity is required.',
            'available_quantity.numeric' => 'Available quantity must be a number.',

            'dispatch_quantity.*.required' => 'Dispatch quantity is required.',
            'dispatch_quantity.*.numeric' => 'Dispatch quantity must be a number.',
            'dispatch_quantity.*.gt' => 'Dispatch quantity must be greater than 0.',
            'sales_order_id.required' => 'Sales order is required.',
            'sales_order_id.exists' => 'Selected sales order does not exist.',

            'company_id.required' => 'Company is required.',
            'company_id.exists' => 'Selected company does not exist.',

            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'Selected warehouse does not exist.',

            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'Selected customer does not exist.',

            'dispatch_date.required' => 'Dispatch date is required.',
            'dispatch_date.date' => 'Dispatch date must be a valid date.',

            'expected_dispatch_date.date' => 'Expected dispatch date must be a valid date.',

            'dispatch_no.required' => 'Dispatch number is required.',
            'dispatch_no.string' => 'Dispatch number must be a string.',
            'dispatch_no.max' => 'Dispatch number must not exceed 255 characters.',
            'dispatch_no.unique' => 'Dispatch number must be unique for this company.',

            'price_list_id.exists' => 'Selected price list does not exist.',
            'transport_by_id.exists' => 'Selected transport method does not exist.',
            'transport_details_id.exists' => 'Selected transport detail does not exist.',
        ];
    }
}
