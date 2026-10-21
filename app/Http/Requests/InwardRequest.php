<?php

namespace App\Http\Requests;
use App\Models\Inward;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InwardRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(Request $request): array {
        $id = $request->route('inwards') ?? 0;
        return [
            'purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'product_id' => ['required', 'exists:products,id'],
            'product_name' => ['required'],
            'product_description' => ['nullable'],
            'unit' => ['nullable'],
            'remaining_quantity' => ['required'],
            'available_quantity' => ['required'],
            'inward_quantity.*' => ['required', 'numeric', 'gt:0'],

            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'vendor_name' => ['nullable'],
            'phone' => ['nullable'],
            'state' => ['nullable'],
            'address' => ['nullable'],
            'remarks' => ['nullable'],

            'company_id' => ['required', 'exists:companies,id'],
            'warehouse_id' => ['required', 'exists:master_warehouse,id'],
            'vendor_id' => [
                                'required',
                                Rule::exists((new Vendor())->getTable(), 'id')->where(function ($query) use ($request) {
                                    return $query->where('company_id', $request->company_id)
                                                ->where('status', 'active')
                                                ->whereNull('deleted_at');
                                }),
                            ],
            'inward_date' => ['required', 'date'],
            'inward_no' => ['required', 'string', 'max:255',
                            // Rule::unique((new Inward())->getTable())->where(function ($query) use ($request) {
                            //     return $query->where('company_id', $request->company_id)
                            //             ->whereNull('deleted_at');
                            // })->ignore($id)
            ],
            'transport_by_id' => ['nullable', 'exists:master_transport_by,id'],
            'transport_details_id' => ['nullable', 'exists:master_transport_detail,id'],
            'is_qc_required' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array {
        return [
            'purchase_order_item_id.required' => 'Purchase order item is required.',
            'purchase_order_item_id.exists' => 'Selected purchase order item does not exist.',

            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',

            'product_name.required' => 'Product name is required.',

            'remaining_quantity.required' => 'Remaining quantity is required.',
            'remaining_quantity.numeric' => 'Remaining quantity must be a number.',

            'available_quantity.required' => 'Available quantity is required.',
            'available_quantity.numeric' => 'Available quantity must be a number.',

            'inward_quantity.*.required' => 'Inward quantity is required.',
            'inward_quantity.*.numeric' => 'Inward quantity must be a number.',
            'inward_quantity.*.gt' => 'Inward quantity must be greater than 0.',

            'purchase_order_id.required' => 'Purchase order is required.',
            'purchase_order_id.exists' => 'Selected purchase order does not exist.',

            'company_id.required' => 'Company is required.',
            'company_id.exists' => 'Selected company does not exist.',

            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'Selected warehouse does not exist.',

            'vendor_id.required' => 'Vendor is required.',
            'vendor_id.exists' => 'Selected vendor does not exist.',

            'inward_date.required' => 'Inward date is required.',
            'inward_date.date' => 'Inward date must be a valid date.',

            'inward_no.required' => 'Inward number is required.',
            'inward_no.string' => 'Inward number must be a string.',
            'inward_no.max' => 'Inward number must not exceed 255 characters.',
            'inward_no.unique' => 'Inward number must be unique for this company.',

            'transport_by_id.exists' => 'Selected transport method does not exist.',
            'transport_details_id.exists' => 'Selected transport detail does not exist.',
        ];
    }
}
