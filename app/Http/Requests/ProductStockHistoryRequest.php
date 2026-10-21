<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MasterWarehouse;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductVariantPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductStockHistoryRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(Request $request): array {
        $id = $request->route('designation') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'warehouse_id' => [
                'required',
                Rule::exists((new MasterWarehouse())->getTable(), 'id')
            ],
            'product_category_id' => [
                'required',
                Rule::exists((new ProductCategory())->getTable(), 'id')
            ],
            'product_variant_price_id' => [
                'required',
                Rule::exists((new Product())->getTable(), 'id')
            ],
            'total_quantity' => 'nullable|numeric|required_without:total_kg',
            'total_kg' => 'nullable|numeric|required_without:total_quantity',
            'stock_type' => 'nullable|in:qty,kg',
            'date' => 'required|date',
            'remark' => 'nullable|string',
        ];
        return $rules;
    }

    public function messages() {
        return [
            'company_id.required' => 'The company name is required.',
            'warehouse_id.required' => 'The warehouse name is required.',
            'product_category_id.required' => 'The product category name is required.',
            'product_variant_price_id.required' => 'The product variant price name is required.',
            'total_quantity' => 'nullable|numeric|required_without:total_kg',
            'total_kg'       => 'nullable|numeric|required_without:total_quantity',
            'date.required' => 'The date is required.',
            'remark.required' => 'The remark is required.',
            'stock_type.in'       => 'The stock type must be qty or kg.',
        ];
    }
}
