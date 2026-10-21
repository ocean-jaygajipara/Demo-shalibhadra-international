<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\PriceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceListRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(Request $request): array {
        $id = $request->route('price_list') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new PriceList())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ];
        return $rules;
    }

    public function messages() {
        return [
            'company_id.required' => 'The company name is required.',
            'name.required' => 'The price list name is required.',
            'name.unique' => 'The price list name must be unique.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}