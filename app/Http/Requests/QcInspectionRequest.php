<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\IssueForProcess;
use App\Models\JobWorkReceive;
use App\Models\JobWorkReceiveItems;
use App\Models\Location;
use App\Models\QcInspection;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\ProcessReceived;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QcInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('qc_inspection_report') ?? 0;
        //dd($request->all());
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
           'vendor_id' => [
                'required',
                Rule::exists((new Vendor())->getTable(), 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('company_id', $request->company_id);
                    }),
            ],
            'mobile_no' => [
                'nullable',
                Rule::unique('job_work_receive', 'mobile_no')->ignore($id),
            ],
            'phone' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'warehouse_id' => ['required', 'exists:master_warehouse,id'],
            'job_work_receive_id' => [
                'nullable',
                Rule::exists((new JobWorkReceive())->getTable(), 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('company_id', $request->company_id);
                    }),
            ],
            'job_work_receive_item_id' => [
                'nullable',
                Rule::exists((new JobWorkReceiveItems())->getTable(), 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('company_id', $request->company_id);
                    }),
            ],
            'inward_id' => [
                'nullable',
                Rule::exists('inwards', 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('company_id', $request->company_id);
                    }),
            ],
            'inward_item_id' => [
                'nullable',
                Rule::exists('inward_items', 'id'),
            ],
            'qc_inspection_date' => 'required|date',
            'qc_inspection_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new QcInspection())->getTable())
                    ->where(fn($query) => $query->where('company_id', $request->company_id)
                                                ->whereNull('deleted_at'))
                    ->ignore($id),
            ],
            'product_id' => [
                'required',
                Rule::exists((new Product())->getTable(), 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('company_id', $request->company_id);
                    }),
            ],
            'product_name' => 'required|string',
            'product_code' => 'required|string',
            'issue_for_process_id' => array_merge(
                ['nullable'],
                empty($request->inward_id) 
                    ? ['required', Rule::exists((new ProcessReceived())->getTable(), 'id')->where('company_id', $request->company_id)] 
                    : []
            ),
            'received_weight' => 'required|numeric|min:0',
            'ok_weight' => 'required|numeric|min:0',
            'ok_qty' => 'nullable|numeric|min:0',
            'wt_pc' => ['nullable','regex:/^\d+(\.\d+)?$/'],
            'rejection_qty' => ['nullable'],
            'available_qty' => ['nullable'],
        ];
        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $product = Product::with('product_category')->find($this->product_id);
            $categoryName = strtolower(trim(optional($product->product_category)->name));
            $isSizeRequired = ($categoryName === 'finish goods');

            if (!$isSizeRequired || !is_array($this->material)) {
                return;
            }

            foreach ($this->material as $index => $m) {
                $hasEntry =
                    !empty($m['dimension']) ||
                    !empty($m['size']) ||
                    !empty($m['ltl']) ||
                    !empty($m['utl']) ||
                    !empty($m['instrument']);

                if (!$hasEntry) {
                    continue;
                }

                for ($i = 1; $i <= 10; $i++) {
                    if (empty($m["size$i"])) {
                        $validator->errors()->add(
                            "material.$index.size$i",
                            "Size $i is required For.".$m['dimension']
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company field is required.',
            'company_id.exists'   => 'The selected company does not exist.',

            'vendor_id.required' => 'The vendor field is required.',
            'vendor_id.exists'   => 'The selected vendor does not belong to the selected company.',

            'mobile_no.unique' => 'This mobile number is already used in another job work receive entry.',

            'phone.max' => 'The phone number may not be greater than 20 characters.',
            'state.max' => 'The state may not be greater than 100 characters.',

            'qc_inspection_date.required' => 'The QC inspection date is required.',
            'qc_inspection_date.date'     => 'The QC inspection date must be a valid date.',

            'qc_inspection_no.required' => 'The QC inspection number is required.',
            'qc_inspection_no.unique'   => 'This QC inspection number is already used in this company.',

            'product_id.required' => 'The product field is required.',
            'product_id.exists'   => 'The selected product does not belong to the selected company.',

            'product_name.required' => 'The product name is required.',
            'product_code.required' => 'The product code is required.',
            'issue_for_process_id.exists' => 'The selected process does not belong to the selected company.',

            'wt_pc.regex' => 'The Weight Per Piece must be a decimal number and cannot contain "-".',

            'received_weight.numeric' => 'The received weight must be a number.',
            'received_weight.min'     => 'The received weight must be at least 0.',
            'ok_weight.numeric'       => 'The OK weight must be a number.',
            'ok_weight.min'           => 'The OK weight must be at least 0.',
        ];
    }
}
