<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Vendor;
use App\Models\JobWorkIssue;
use App\Models\JobWorkType;
use App\Models\ProductCategory;
use App\Models\SalesStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobWorkIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('job_work_issue') ?? 0;
        //dd($request->all());
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'vendor_id' => [
                'required',
                'exists:vendors,id',
            ],
            'job_work_type_id' => [
                'required',
                Rule::exists((new JobWorkType())->getTable(), 'id')
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'warehouse_id' => ['required', 'exists:master_warehouse,id'],
            'mobile_no' => [
                'nullable',
                Rule::unique('job_work_issue', 'mobile_no')->ignore($id),
            ],
            'phone' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'issue_for_process' => 'nullable|string',
            'job_work_issue_date' => 'required|date',
            'job_work_issue_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new JobWorkIssue())->getTable())
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                        ->whereNull('deleted_at'))
                    ->ignore($id),
            ],
            'vehicle_no' => 'nullable|string',
            'transport_by_id' => 'nullable',
            'transport_details_id' => 'nullable',
            'gst_no' => 'nullable|string',

            'remarks' => 'nullable|string',
            'master_total_pending' => 'nullable|numeric|min:0',
            'total_packing_weight' => 'nullable|numeric|min:0',
            'total_item_weight' => 'nullable|numeric|min:0',
            'total_gross_weight' => 'nullable|numeric|min:0',
            'total_cgst_percentage' => 'required|numeric|min:0|max:100',
            'total_cgst_amount' => 'required|numeric|min:0',
            'total_sgst_percentage' => 'required|numeric|min:0|max:100',
            'total_sgst_amount' => 'required|numeric|min:0',
            'master_total_rate' => 'required|numeric|min:0',
            'master_total_amount' => 'required|numeric|min:0',
            'master_rate' => 'required|numeric|min:0',
            'master_taxable' => 'required|numeric|min:0',
            'sub_total' => 'required|numeric|min:0',
            'round_off' => 'nullable|numeric',
            'grand_total' => 'required|numeric|min:0',
            'term_and_condition' => 'nullable|string',


            'product_name' => ['required', 'array'],
            'product_code' => ['required', 'array'],
            'product_id' => ['required', 'array'],
            'product_stock' => ['required', 'array'],
            'gross_weight' => ['required', 'array'],
            'issue_weight' => ['required', 'array'],
            'bl_percentage' => ['nullable', 'array'],
            'rate_in_amount' => ['required', 'array'],
            'taxable_amount' => ['required', 'array'],
            'cgst_percentage' => ['nullable', 'array'],
            'cgst_amount' => ['nullable', 'array'],
            'sgst_percentage' => ['nullable', 'array'],
            'sgst_amount' => ['nullable', 'array'],
            'total_rate_in_amount' => ['nullable', 'array'],
            'amount' => ['nullable', 'array'],
        ];
        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Company is required.',
            'company_id.exists' => 'Selected company does not exist.',

            'vendor_id.required' => 'Vendor is required.',
            'vendor_id.exists' => 'Selected vendor does not exist.',

            'job_type_id.required' => 'Job Type is required.',
            'job_type_id.exists' => 'Selected Job Type does not exist.',

            'product_category_id.required' => 'Product Category is required.',
            'product_category_id.exists' => 'Selected Product Category does not exist.',

            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'Selected warehouse does not exist.',

            'mobile_no.unique' => 'This mobile number is already assigned to another Job Work Issue.',

            'phone.max' => 'Phone number cannot exceed 20 characters.',

            'issue_for_process.required' => 'Issue for process is required.',

            'job_work_issue_date.required' => 'Issue Date is required.',
            'job_work_issue_date.date' => 'Issue Date must be a valid date.',

            'job_work_issue_no.required' => 'Job Work Issue Number is required.',
            'job_work_issue_no.string' => 'Job Work Issue Number must be a string.',
            'job_work_issue_no.max' => 'Job Work Issue Number cannot exceed 255 characters.',
            'job_work_issue_no.unique' => 'This Job Work Issue Number already exists for the company.',

            'total_packing_weight.numeric' => 'Total Packing Weight must be a number.',
            'total_packing_weight.min' => 'Total Packing Weight must be at least 0.',

            'total_item_weight.numeric' => 'Total Item Weight must be a number.',
            'total_item_weight.min' => 'Total Item Weight must be at least 0.',

            'total_gross_weight.numeric' => 'Total Gross Weight must be a number.',
            'total_gross_weight.min' => 'Total Gross Weight must be at least 0.',

            'total_cgst_percentage.required' => 'CGST % is required.',
            'total_cgst_percentage.numeric' => 'CGST % must be numeric.',
            'total_cgst_percentage.min' => 'CGST % must be at least 0.',
            'total_cgst_percentage.max' => 'CGST % cannot exceed 100.',

            'total_cgst_amount.required' => 'CGST Amount is required.',
            'total_cgst_amount.numeric' => 'CGST Amount must be numeric.',
            'total_cgst_amount.min' => 'CGST Amount must be at least 0.',

            'total_sgst_percentage.required' => 'SGST % is required.',
            'total_sgst_percentage.numeric' => 'SGST % must be numeric.',
            'total_sgst_percentage.min' => 'SGST % must be at least 0.',
            'total_sgst_percentage.max' => 'SGST % cannot exceed 100.',

            'total_sgst_amount.required' => 'SGST Amount is required.',
            'total_sgst_amount.numeric' => 'SGST Amount must be numeric.',
            'total_sgst_amount.min' => 'SGST Amount must be at least 0.',

            'sub_total.required' => 'Sub Total is required.',
            'sub_total.numeric' => 'Sub Total must be numeric.',
            'sub_total.min' => 'Sub Total must be at least 0.',

            'round_off.numeric' => 'Round Off must be numeric.',

            'grand_total.required' => 'Grand Total is required.',
            'grand_total.numeric' => 'Grand Total must be numeric.',
            'grand_total.min' => 'Grand Total must be at least 0.',

            'term_and_condition.string' => 'Terms & Conditions must be a valid string.',


            'product_name.required' => 'Product name is required.',
            'product_name.array' => 'Product name must be in array format.',
            'product_code.required' => 'Product code is required.',
            'product_code.array' => 'Product code must be in array format.',
            'product_id.required' => 'Product ID is required.',
            'product_id.array' => 'Product ID must be in array format.',
            'product_stock.required' => 'Product stock is required.',
            'product_stock.array' => 'Product stock must be in array format.',
            'gross_weight.required' => 'Gross weight is required.',
            'gross_weight.array' => 'Gross weight must be in array format.',
            'issue_weight.required' => 'Issue weight is required.',
            'issue_weight.array' => 'Issue weight must be in array format.',
            'rate_in_amount.required' => 'Rate in amount is required.',
            'rate_in_amount.array' => 'Rate in amount must be in array format.',
            'taxable_amount.required' => 'Taxable amount is required.',
            'taxable_amount.array' => 'Taxable amount must be in array format.',
            'cgst_percentage.array' => 'CGST percentage must be in array format.',
            'cgst_amount.array' => 'CGST amount must be in array format.',
            'sgst_percentage.array' => 'SGST percentage must be in array format.',
            'sgst_amount.array' => 'SGST amount must be in array format.',
            'total_rate_in_amount.array' => 'Total rate in amount must be in array format.',
            'amount.array' => 'Amount must be in array format.',

            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $submitType = $this->input('submit_type');

            // Only when Save & Generate E-Way Bill button is clicked
            if ($submitType === 'save_and_eway_bill') {

                if (empty($this->input('vehicle_no'))) {
                    $validator->errors()->add(
                        'vehicle_no',
                        'Vehicle Number is required for E-Way Bill.'
                    );
                }

                if (empty($this->input('vehicle_type'))) {
                    $validator->errors()->add(
                        'vehicle_type',
                        'Vehicle Type is required for E-Way Bill.'
                    );
                }

                if (empty($this->input('lr_no'))) {
                    $validator->errors()->add(
                        'lr_no',
                        'L.R. No. is required for E-Way Bill.'
                    );
                }

                if (empty($this->input('lr_date'))) {
                    $validator->errors()->add(
                        'lr_date',
                        'L.R. Date is required for E-Way Bill.'
                    );
                }
            }
        });
    }
}
