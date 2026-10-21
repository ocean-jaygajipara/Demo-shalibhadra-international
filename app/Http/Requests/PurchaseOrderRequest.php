<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItems;
use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(Request $request): array {
        $id = $request->route('purchase_order') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],
            'vendor_id' => [
                'required', 'exists:vendors,id',
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'term_condition_id' => [
                'nullable', 'exists:terms_and_condition,id',
            ],
            'transport_by_id' => [
                'nullable', 'exists:master_transport_by,id',
            ],
            'transport_details_id' => [
                'nullable', 'exists:master_transport_detail,id',
            ],
            'shipping_address' => 'nullable',
            'billing_address' => 'nullable',
            'cash_discount_percentage' => 'nullable',
            'cash_discount_amount' => 'nullable',
            'additional_discount_percentage' => 'nullable',
            'additional_discount_amount' => 'nullable',
            'packing_forwarding_charge' => 'nullable|numeric|min:0',
            'transport_charge' => 'nullable|numeric|min:0',
            'total_taxable' => 'nullable',
            'gst_amount' => 'nullable',
            'tcs_amount' => 'nullable',
            'round_off' => 'nullable',
            'grand_total' => 'required',
            'is_gst_applicable' => 'nullable',
            'is_tcs_applicable' => 'nullable',
            'term_and_condition' => 'nullable',
            'regards' => 'nullable',
            'notes' => 'nullable',
            'master_quantity' => 'required',
            'master_amount' => 'required',
            'master_taxable_amount' => 'nullable',
            'master_gst_amount' => 'nullable',
            'master_sub_total' => 'required',
            'purchase_order_date' => 'required|date',
            'gst_no' => 'nullable',
            'vendor_name' => 'nullable',
            'phone' => 'nullable',
            'state' => 'nullable',
            'address' => 'nullable',
            'purchase_order_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new PurchaseOrder())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                            ->whereNull('deleted_at');
                })->ignore($id),
            ],
            'product_code' => ['required', 'array'],
            'product_name' => ['required', 'array'],
            'product_id' => ['required', 'array'],
            'product_variant_price_id' => ['required', 'array'],
            'price' => ['nullable', 'array'],
            'hsn_code' => ['nullable', 'array'],
            'item_id' => ['nullable', 'array'],
            'unit' => ['nullable', 'array'],
            'gst_in_percentage' => ['nullable', 'array'],
            'discount_percentage' => ['nullable', 'array'],
            'product_description' => ['nullable', 'array'],
            'outer' => ['nullable', 'array'],
            'inner' => ['nullable', 'array'],
            'loose' => ['nullable', 'array'],
            'quantity' => ['nullable', 'array'],
            'total_kg' => ['nullable', 'array'],
            'rate_in_amount' => ['nullable', 'array'],
            'amount' => ['nullable', 'array'],
            'taxable_amount' => ['nullable', 'array'],
            'subtotal' => ['nullable', 'array'],
            'gst_amount_array' => ['nullable', 'array'],
        ];
        return $rules;
    }

    public function messages() {
        return [
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company does not exist.',

            'vendor_id.required' => 'The vendor field is required.',
            'vendor_id.exists' => 'The selected vendor does not exist.',

            'term_condition_id.exists' => 'The selected term and condition does not exist.',
            'transport_by_id.exists' => 'The selected transport by option is invalid.',
            'transport_details_id.exists' => 'The selected transport detail is invalid.',

            'grand_total.required' => 'The grand total is required.',

            // 'term_and_condition.required' => 'The term and condition field is required.',
            // 'regards.required' => 'The regards field is required.',
            'master_quantity.required' => 'Master quantity is required.',
            'master_amount.required' => 'Master amount is required.',
            'master_taxable_amount.required' => 'Master taxable amount is required.',
            'master_gst_amount.required' => 'Master GST amount is required.',
            'master_sub_total.required' => 'Master sub total is required.',
            'purchase_order_date.required' => 'Purchase order date is required.',

            'purchase_order_no.required' => 'Purchase order number is required.',
            'purchase_order_no.string' => 'Purchase order number must be a string.',
            'purchase_order_no.max' => 'Purchase order number must not exceed 255 characters.',

            'product_name.required' => 'Product name is required.',
            'product_name.array' => 'Product name must be an array.',

            'product_code.required' => 'Product code is required.',
            'product_code.array' => 'Product code must be an array.',

            'product_id.required' => 'Product ID is required.',
            'product_id.array' => 'Product ID must be an array.',

            'product_variant_price_id.required' => 'Product variant price ID is required.',
            'product_variant_price_id.array' => 'Product variant price ID must be an array.',

            'price.array' => 'Price must be an array.',

            'hsn_code.array' => 'HSN code must be an array.',
            'item_id.array' => 'Item ID must be an array.',
            'unit.array' => 'Unit must be an array.',
            'gst_in_percentage.array' => 'GST percentage must be an array.',
            'discount_percentage.array' => 'Discount percentage must be an array.',
            'product_description.array' => 'Product description must be an array.',
            'outer.array' => 'Outer quantity must be an array.',
            'inner.array' => 'Inner quantity must be an array.',
            'loose.array' => 'Loose quantity must be an array.',
            'quantity.array' => 'Quantity must be an array.',
            'rate_in_amount.array' => 'Rate in amount must be an array.',
            'amount.array' => 'Amount must be an array.',
            'taxable_amount.array' => 'Taxable amount must be an array.',
            'subtotal.array' => 'Subtotal must be an array.',
            'gst_amount.array' => 'GST amount must be an array.',
        ];
    }
}
