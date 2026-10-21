<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\MasterTransportDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterTransportDetailRequest extends FormRequest
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
        $id = $request->route('master_transport_detail') ?? 0;
        $rules = [
            'company_id' => [
                'required', Rule::exists((new Company())->getTable(), 'id')
            ],

            'transport_by_id' => [
                'required'
            ],

            // 'name' => 'required|string|max:255|unique:' . (new MasterCity())->getTable() . ',name',
            'name' => [
                'required',
                Rule::unique((new MasterTransportDetail())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                    ->where('transport_by_id', $request->transport_by_id);
                })->ignore($id),
            ],
            'transport_gst_id' => ['nullable', 'string', 'max:15'],
            'status' => 'required|in:active,inactive',
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('master-city'), $request->route()->parameters());
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The Company Name is required.',
            'transport_by_id.required' => 'The Transport By is required.',
            'name.required' => 'The Name is required.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
