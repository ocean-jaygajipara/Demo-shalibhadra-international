<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAlloy;
use App\Models\ProductRmDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductRmDetailRequest extends FormRequest
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
        $id = $request->route('product_rm_detail') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_id' => [
                'required', Rule::exists((new Product())->getTable(), 'id')
            ],
            'size_premium' => [
                'required',
                'string',
                'max:255'
            ],
            'od_mm' => [
                'required',
                'numeric'
            ],
            'id_mm' => [
                'required',
                'numeric'
            ],
            'shape' => [
                'nullable',
                'string',
                'max:255'
            ],
            'alloy' => [
                'required',
                Rule::exists((new ProductAlloy())->getTable(), 'id')
            ],
            'density' => [
                'nullable',
                'numeric'
            ],
            'tol_plus' => [
                'nullable',
                'numeric'
            ],
            'tol_minus' => [
                'nullable',
                'numeric'
            ],
            'status' => [
                'required',
                'in:active,inactive'
            ],
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'product_id.required' => 'The Product is required.',
            'size_premium.string' => 'The size premium must be a string.',
            'od_mm.numeric' => 'The outer diameter (OD) must be a number.',
            'id_mm.numeric' => 'The inner diameter (ID) must be a number.',
            'shape.string' => 'The shape must be a string.',
            'alloy.string' => 'The alloy must be a string.',
            'density.numeric' => 'The density must be a number.',
            'tol_plus.numeric' => 'The tolerance plus value must be a number.',
            'tol_minus.numeric' => 'The tolerance minus value must be a number.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
