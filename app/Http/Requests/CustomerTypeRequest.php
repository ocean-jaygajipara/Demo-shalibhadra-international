<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\CustomerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class CustomerTypeRequest extends FormRequest
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
        $id = $request->route('customer_type') ?? 0;
        $table = (new CustomerType())->getTable();

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new CustomerType())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
            'parent_id' => [
                'nullable',
                // 'string',
                'max:255',
                Rule::unique((new CustomerType())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('name', $request->name)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];

        return $rules;
    }
}
