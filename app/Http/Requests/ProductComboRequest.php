<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\ProductHSN;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use Illuminate\Http\Request;

class ProductComboRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filter out empty dynamic_fields before validation
     */
    protected function prepareForValidation()
    {
        if ($this->has('dynamic_fields')) {
            $filtered = collect($this->input('dynamic_fields'))->filter(function ($item) {
                return (!empty($item['product_variant_price_id']) ||
                    !empty($item['display_price']) ||
                    !empty($item['quantity']));
            })->values()->all();
            $this->merge([
                'dynamic_fields' => $filtered
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        $id = $request->route('product_combo') ?? 0;

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_combo_name' => [
                'required',
                Rule::unique('product_combos')
                    ->where(fn($query) => $query->where('company_id', $request->company_id))
                    ->ignore($id),
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'display_unit_id' => ['required', 'integer', 'exists:master_unit,id'],
            'price' => ['nullable', 'numeric'],
            'part_no' => ['required'],
            'gross_wt' => ['nullable', 'numeric', 'min:0'],
            'net_wt' => ['required', 'numeric', 'min:0'],
            'grosse_per_qty' => ['nullable', 'numeric', 'min:0'],
            'hsn_code' => [
                'nullable',
                Rule::exists((new ProductHSN())->getTable(), 'id'),
            ],
            'product_tax_id' => ['nullable', 'numeric', 'min:0'],
            // 'product_variant_price_id' => ['required', 'integer', 'exists:product_variant,id'],

            'status' => 'required|in:active,inactive',

            // 👇 Add these lines to validate the repeater combo items
            'dynamic_fields' => ['required', 'array', 'min:1'],
            'dynamic_fields.*.product_variant_price_id' => ['required', 'integer', 'exists:products,id'],
            'dynamic_fields.*.order_type' => ['required'],
            'dynamic_fields.*.display_price' => ['nullable', 'numeric', 'min:0'],
            'dynamic_fields.*.quantity' => ['required', 'numeric', 'min:1'],
        ];


        // dd("L-65", $request->all(), $rules);

        return $rules;
    }

    /**
     * Optional: Custom error messages
     */
    public function messages(): array
    {
        return [
            'dynamic_fields.*.product_variant_price_id.required' => 'Product variant is required.',
            'dynamic_fields.*.display_price.required' => 'Display price is required.',
            'dynamic_fields.*.order_type.required' => 'Order type is required.',
            'dynamic_fields.*.quantity.required' => 'Quantity is required.',
            'gross_wt.required' => 'Gross weight is required.',
            'gross_wt.numeric' => 'Gross weight must be a number.',
            'gross_wt.integer' => 'Gross weight must be an integer.',

            'net_wt.required' => 'Net weight is required.',
            'net_wt.numeric' => 'Net weight must be a number.',
            'net_wt.integer' => 'Net weight must be an integer.',

            'grosse_per_qty.required' => 'Gross per quantity is required.',
            'grosse_per_qty.numeric' => 'Gross per quantity must be a number.',
            'grosse_per_qty.integer' => 'Gross per quantity must be an integer.',

            'hsn_code.string' => 'The HSN code must be a valid string.',
            'hsn_code.max'    => 'HSN code should not exceed 6 characters.',
        ];
    }
}
