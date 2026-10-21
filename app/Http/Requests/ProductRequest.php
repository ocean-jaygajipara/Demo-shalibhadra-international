<?php

namespace App\Http\Requests;

use App\Models\CompanyDetails;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductTAX;
use App\Models\Company;
use App\Models\MasterUnit;
use App\Models\Orderunit;
use App\Models\ProductGodown;
use App\Models\ProductGrade;
use App\Models\ProductHSN;
use App\Models\ProductRack;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('product') ?? 0;
        $companyId = $request->input('company_id');
        $company = Company::find($companyId);
        $isTaxType = $company && $company->tax_type === 'order_wise';

        $duplicationType = CompanyDetails::where('company_id', $companyId)
            ->value('duplication_check_type');

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'product_name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($duplicationType, $companyId, $id) {
                    if ($duplicationType === 'product') {
                        $exists = Product::where('company_id', $companyId)
                            ->where('product_category_id', $this->product_category_id)
                            ->where('product_name', $value)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail("Product name '{$value}' already exists in this category.");
                        }
                    }
                },
            ],
            'part_no' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($duplicationType, $companyId, $id) {
                    if ($duplicationType === 'product') {
                        $exists = Product::where('company_id', $companyId)
                            ->where('part_no', $value)
                            ->where('id', '!=', $id)
                            ->exists();

                        if ($exists) {
                            $fail("Part number '{$value}' already exists.");
                        }
                    }
                },
            ],
            'product_grade_id' => [
                'nullable',
                Rule::exists((new ProductGrade())->getTable(), 'id'),
            ],
            'unit_id' => [
                'nullable',
                Rule::exists((new MasterUnit())->getTable(), 'id')
            ],
            'hsn_code' => [
                'nullable',
                Rule::exists((new ProductHSN())->getTable(), 'id'),
            ],
            //'product_mrp' => ['required', 'numeric', 'min:0'],
            'product_tax_id' => ['nullable', 'numeric', 'min:0'],
            'grosse_per_qty' => ['nullable', 'numeric', 'min:0'],
            'per_bag_quantity' => ['nullable', 'integer', 'min:0'],
            'gross_wt' => ['nullable', 'numeric', 'min:0'],
            'net_wt' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'string'],
            'opening_kg' => ['nullable', 'numeric', 'min:0'],
            'opening_pcs' => ['nullable', 'integer', 'min:0'],
            'std_packing' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,gif,pdf,docx', 'max:5120'],
            'removed_attachments' => ['nullable', 'array'],
            'removed_attachments.*' => ['string'],
        ];

        // dd("L-92", $duplicationType,$rules, $request?->all());
        return $rules;
    }

    public function messages()
    {
        return [

            'company_id.required' => 'The company name is required.',
            'company_id.exists' => 'The selected company does not exist.',

            'product_category_id.required' => 'The product category is required.',
            'product_category_id.exists' => 'The selected product category does not exist.',

            'product_name.required' => 'The product name is required.',
            'product_name.string' => 'The product name must be a valid string.',
            'product_name.max' => 'The product name should not exceed 255 characters.',

            'part_no.required' => 'The part number is required.',
            'part_no.string' => 'The part number must be a valid string.',
            'part_no.max' => 'The part number should not exceed 100 characters.',
            'part_no.unique' => 'This part number already exists. Please use a different one.',

            'product_grade_id.required' => 'The product grade is required.',
            'product_grade_id.exists' => 'The selected product grade does not exist.',

            'unit_id.exists' => 'The selected unit does not exist.',

            'hsn_code.string' => 'The HSN code must be a valid string.',
            'hsn_code.max' => 'HSN code should not exceed 6 characters.',

            'gross_wt.numeric' => 'The gross weight must be a valid number.',
            'gross_wt.min' => 'The gross weight cannot be negative.',

            'net_wt.numeric' => 'The net weight must be a valid number.',
            'net_wt.min' => 'The net weight cannot be negative.',

            'opening_kg.numeric' => 'The opening stock (kg) must be a valid number.',
            'opening_kg.min' => 'The opening stock (kg) cannot be negative.',

            'opening_pcs.integer' => 'The opening stock (pcs) must be a whole number.',
            'opening_pcs.min' => 'The opening stock (pcs) cannot be negative.',

            'std_packing.string' => 'The standard packing must be a valid string.',
            'std_packing.max' => 'The standard packing should not exceed 100 characters.',

            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
