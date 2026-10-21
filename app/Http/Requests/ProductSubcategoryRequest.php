<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MasterCountry;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductSubcategoryRequest extends FormRequest
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
        $id = $request->route('product_subcategory') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
           'product_category_id' => [
            'required',
            Rule::exists((new ProductCategory())->getTable(), 'id')
        ],

            // 'name' => 'required|string|max:255|unique:' . (new MasterCity())->getTable() . ',name',
           'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new ProductSubcategory())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                                    ->where('product_category_id', $request->product_category_id);
                    })
                    ->ignore($id),
            ],

            'status' => 'required|in:active,inactive',
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('master-city'), $request->route()->parameters());
        return $rules;
    }
}
