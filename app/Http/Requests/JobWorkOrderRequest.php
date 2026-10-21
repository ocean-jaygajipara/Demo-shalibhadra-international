<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Vendor;
use App\Models\JobWorkOrder;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('job_work_order') ?? 0;
        //dd($request->all(),$id);
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'vendor_id' => [
                'required',
                'exists:vendors,id',
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'work_type' => 'required|string',
            'mobile_no' => [
                'nullable',
            ],
            'phone' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'job_work_order_date' => 'required|date',
            'job_work_order_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new JobWorkOrder())->getTable())
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                                                ->whereNull('deleted_at'))
                    ->ignore($id),
            ],
            'term_and_condition' => 'nullable|string',
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
            'master_taxable_amount' => 'required',
            'master_gst_amount' => 'required',
            'master_sub_total' => 'required',

            'product_name'             => ['required', 'array'],
            'product_code'             => ['required', 'array'],
            'product_id'               => ['required', 'array'],
            'product_stock'            => ['required', 'array'],
            'quantity'                 => ['required', 'array'],
            'unit'                     => ['nullable', 'array'],
            'rate_in_amount'           => ['nullable', 'array'],
            'amount'                   => ['nullable', 'array'],
            'taxable_amount'           => ['nullable', 'array'],
            'subtotal'                 => ['nullable', 'array'],
            'gst_amount_array'         => ['nullable', 'array'],
            'cgst_in_percentage'       => ['nullable', 'array'],
            'cgst_amount_array'        => ['nullable', 'array'],
            'sgst_in_percentage'       => ['nullable', 'array'],
            'sgst_amount_array'        => ['nullable', 'array'],
        ];
        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company field is required.',
            'company_id.exists'   => 'The selected company does not exist.',

            'vendor_id.required'  => 'The vendor field is required.',
            'vendor_id.exists'    => 'The selected vendor does not exist.',

            'mobile_no.required' => 'Mobile number is required.',
            'mobile_no.numeric'  => 'Mobile number must contain only digits.',
            'mobile_no.regex'    => 'Mobile number must contain only digits.', // if you switch to regex
            'mobile_no.unique'   => 'This mobile number is already in use for the selected company.',

            'work_type.required' => 'Work type is required.',
            'work_type.string'   => 'Work type must be a valid string.',

            'job_work_order_date.required' => 'The job work order date is required.',
            'job_work_order_date.date'     => 'The job work order date must be a valid date.',

            'job_work_order_no.required' => 'The job work order number is required.',
            'job_work_order_no.string'   => 'The job work order number must be a string.',
            'job_work_order_no.max'      => 'The job work order number must not exceed 255 characters.',
            'job_work_order_no.unique'   => 'The job work order number has already been taken for this company.',


            'product_name.required'             => 'Product name is required.',
            'product_code.required'             => 'Product code is required.',
            'product_id.required'               => 'Product ID is required.',
            'product_variant_price_id.required' => 'Product variant price is required.',
            'product_stock.required'            => 'Product stock is required.',
            'quantity.required'                 => 'Quantity is required.',
            'rate_in_amount.array'              => 'Rate in amount must be an array.',
            'amount.array'                      => 'Amount must be an array.',
            'taxable_amount.array'              => 'Taxable amount must be an array.',
            'subtotal.array'                    => 'Subtotal must be an array.',
            'gst_amount.array'                  => 'GST amount must be an array.',
        ];
    }
}
