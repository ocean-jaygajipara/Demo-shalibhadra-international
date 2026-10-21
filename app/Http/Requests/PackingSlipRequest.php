<?php

namespace App\Http\Requests;

use App\Models\PackingSlip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackingSlipRequest extends FormRequest
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
        //dd($request->all());
        $id = $request->route('packing_slip') ?? 0;

        $rules = [
            'company_id' => ['required', 'exists:companies,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'customer_name' => ['nullable'],
            'contact_person_name' => ['nullable'],
            'phone' => ['nullable'],
            'state' => ['nullable'],
            'address' => ['nullable'],
            'shipping_address' => ['nullable'],
            'billing_address' => ['nullable'],
            'warehouse_id' => ['required', 'exists:master_warehouse,id'],
            'packing_slip_date' => ['required', 'date'],
            'packing_slip_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new PackingSlip())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                        ->whereNull('deleted_at');
                })->ignore($id)
            ],
            'sales_order_no' => ['nullable'],
            'sales_order_date' => ['nullable', 'date'],
            'sales_order_item_id' => ['required', 'array'],
            'product_name' => ['required', 'array'],
            'product_id' => ['required', 'array'],
            'product_variant_price_id' => ['nullable', 'array'],
            'product_code' => ['required', 'array'],
            'total_no_of_carton' =>[ 'nullable'],

            'box_no' => ['nullable', 'array'],
            'hsn_code' => ['nullable', 'array'],
            'unit' => ['nullable', 'array'],
            'product_description' => ['nullable', 'array'],
            'no_of_carton' => ['nullable', 'array'],
            'wt_pc' => ['required', 'array'],
            'qty_pc' => ['required', 'array'],
            'org_qty_pc' => ['nullable', 'array'],
            'total_packet' => ['nullable', 'array'],
            'qty_ctn' => ['nullable', 'array'],
            'actual_wt_ctn' => ['nullable', 'array'],
            'total_qty' => ['nullable', 'array'],
            'total_weight' => ['nullable', 'array'],
            'is_combo' => ['nullable', 'array'],
        ];
        // dd("L-66", $rules, $id, $request->all(), $request->route('packing_slips'), $request->route()->parameters());
        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Please select a company.',
            'company_id.exists' => 'The selected company is invalid.',

            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',

            'sales_order_id.exists' => 'The selected sales order is invalid.',

            'packing_slip_date.required' => 'Packing slip date is required.',
            'packing_slip_date.date' => 'Please enter a valid date for the packing slip.',

            'packing_slip_no.required' => 'Packing slip number is required.',
            'packing_slip_no.string' => 'Packing slip number must be a valid string.',
            'packing_slip_no.max' => 'Packing slip number may not be greater than 255 characters.',
            'packing_slip_no.unique' => 'This packing slip number already exists for the selected company.',


            'sales_order_item_id.required' => 'Sales order item details are missing.',
            'product_name.required' => 'Product name is required.',
            'product_id.required' => 'Product ID is required.',
            'product_variant_price_id.required' => 'Product variant is required.',
            'product_code.required' => 'Product code is required.',
            'wt_pc.required' => 'Weight per piece (WT/PC) is required.',
            'qty_pc.required' => 'Quantity per piece is required.',


            'customer_name.required' => 'Customer name is required.',
            'contact_person_name.required' => 'Contact person name is required.',
            'phone.required' => 'Customer phone number is required.',
            'state.required' => 'Customer state is required.',
            'address.required' => 'Customer address is required.',
        ];
    }
}
