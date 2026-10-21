<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\SalesStatus;
use Illuminate\Http\Request;

class SalesStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('sales_status') ?? 0;
        $isUpdate = $id ? true : false;

        return [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'parent_status_id' => ['nullable', 'exists:sales_statuses,id'],
            'is_redirect' => ['nullable', Rule::in(['1', '0'])],
            'is_revise' => ['nullable', Rule::in(['1', '0'])],
            'is_repeat' => ['nullable', Rule::in(['1', '0'])],
            'salestype' => ['required', Rule::in(['Quotation', 'Sales Orders', 'Dispatch Orders', 'Packing Slip', 'Invoice', 'Purchase Order','Job Work Issue','GRN'])],
            'statusname' => [
                'required',
                Rule::unique('sales_statuses')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                        ->where('salestype', $request->salestype)
                        ->whereNull('deleted_at');
                }),
            ],
            'statusslug' => ['required'],
            'status_color' => ['required'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The Company Name is required.',
            'company_id.exists' => 'The selected Company is invalid.',
            'salestype.required' => 'The Sales Type is required.',
            'salestype.in' => 'Sales Type must be Quotation, Order, or Invoice.',
            'statusname.required' => 'The Status Name is required.',
            'statusname.unique' => 'This Status Name already exists for the selected Company and Sales Type.',
            'status_color.required' => 'The Status Color is required.',
            'status.required' => 'The Status is required.',
            'status.in' => 'The Status must be either active or inactive.',
        ];
    }
}
