<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\JobWorkIssue;
use App\Models\JobWorkIssueItems;
use App\Models\Vendor;
use App\Models\JobWorkReceive;
use App\Models\ProcessReceived;
use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobWorkReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('job_work_receive') ?? 0;
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
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'warehouse_id' => ['required', 'exists:master_warehouse,id'],
            'job_work_issue_id' => [
                'required',
                 Rule::exists((new JobWorkIssue())->getTable(), 'id')
            ],
            'job_work_issue_item_id'   => ['nullable', 'array'],
            'job_work_issue_item_id.*' => [
                'nullable',
                'integer',
                Rule::exists((new JobWorkIssueItems())->getTable(), 'id'),
            ],
            'mobile_no' => [
                'nullable',
                Rule::unique('job_work_receive', 'mobile_no')->ignore($id),
            ],
            'phone' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'job_work_receive_date' => 'required|date',
            'job_work_receive_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new JobWorkReceive())->getTable())
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                                                ->whereNull('deleted_at'))
                    ->ignore($id),
            ],
            'process_receive_id' => [
                'required',
                Rule::exists((new ProcessReceived())->getTable(), 'id')
            ],
            'remarks' => 'nullable|string',
            'master_total_pending' => 'nullable|numeric|min:0',
            'master_total_net_weight' => 'nullable|numeric|min:0',
            'master_total_gross_weight' => 'nullable|numeric|min:0',
            'master_total_current_pending' => 'nullable|numeric|min:0',
            'term_and_condition' => 'nullable|string',


            'product_name'             => ['required', 'array'],
            'product_code'             => ['required', 'array'],
            'product_id'               => ['required', 'array'],
            'product_stock'            => ['required', 'array'],
            'packing_name'             => ['nullable', 'array'],
            'pending_weight'           => ['required', 'array'],
            'received_weight'          => ['required', 'array'],
            'gross_weight'             => ['required', 'array'],
            'wt_pc'                    => ['required', 'array'],
            'quantity'                 => ['required', 'array'],
            'rate_in_amount'           => ['required', 'array'],
            'bl_percentage'            => ['nullable', 'array'],
            'incoming_scrap'            => ['nullable', 'array'],
            'product_used'             => ['nullable', 'array'],
            'our_stock'                => ['nullable', 'array'],
            'challan_stock'            => ['nullable', 'array'],
            'used_qty'                 => ['nullable', 'array'],
            'used_weight'              => ['nullable', 'array'],
            'qc_check_weight'          => ['nullable', 'array'],
        ];
        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Company is required.',
            'company_id.exists'   => 'Selected company does not exist.',

            'vendor_id.required' => 'Vendor is required.',
            'vendor_id.exists'   => 'Selected vendor does not exist.',

            'mobile_no.unique'   => 'This mobile number is already used.',

            'phone.max'          => 'Phone number may not be greater than 20 characters.',
            'state.max'          => 'State may not be greater than 100 characters.',

            'job_work_receive_date.required' => 'Job work receive date is required.',
            'job_work_receive_date.date'     => 'Job work receive date must be a valid date.',

            'job_work_receive_no.required' => 'Job work receive number is required.',
            'job_work_receive_no.string'   => 'Job work receive number must be a string.',
            'job_work_receive_no.unique'   => 'Job work receive number has already been taken.',

            'process_receive_id.required' => 'Process received is required.',
            'process_receive_id.exists'   => 'Selected process received does not exist.',

            'warehouse_id.required' => 'Warehouse is required.',
            'warehouse_id.exists' => 'Selected warehouse does not exist.',

            'master_total_pending.numeric'          => 'Master total pending must be numeric.',
            'master_total_net_weight.numeric'       => 'Master total net weight must be numeric.',
            'master_total_gross_weight.numeric'     => 'Master total gross weight must be numeric.',
            'master_total_current_pending.numeric'  => 'Master total current pending must be numeric.',

            // Array fields
            'product_name.required'      => 'Product name is required.',
            'product_name.array'         => 'Product name must be an array.',

            'product_code.required'      => 'Product code is required.',
            'product_code.array'         => 'Product code must be an array.',

            'product_id.required'        => 'Product is required.',
            'product_id.array'           => 'Product must be an array.',

            'product_stock.required'     => 'Product stock is required.',
            'product_stock.array'        => 'Product stock must be an array.',

            'pending_weight.required'    => 'Pending weight is required.',
            'pending_weight.array'       => 'Pending weight must be an array.',

            'received_weight.required'   => 'Received weight is required.',
            'received_weight.array'      => 'Received weight must be an array.',

            'gross_weight.required'      => 'Gross weight is required.',
            'gross_weight.array'         => 'Gross weight must be an array.',

            'quantity.required'          => 'Quantity is required.',
            'quantity.array'             => 'Quantity must be an array.',

            'rate_in_amount.required'    => 'Rate in amount is required.',
            'rate_in_amount.array'       => 'Rate in amount must be an array.',
        ];
    }
}
