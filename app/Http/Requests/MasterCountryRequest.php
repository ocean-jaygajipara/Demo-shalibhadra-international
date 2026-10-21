<?php

namespace App\Http\Requests;

use App\Models\MasterCountry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterCountryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('master_country') ?? 0;
        $table = (new MasterCountry())->getTable();

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table, 'name')
                    ->ignore($id)
                // ->whereNull('deleted_at'),
            ],
            'short_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table, 'short_name')
                    ->ignore($id)
                // ->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'numeric',
                'digits_between:1,4',
                Rule::unique($table, 'code')
                    ->ignore($id)
                // ->whereNull('deleted_at'),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('master-city'), $request->route()->parameters());
        return $rules;
    }

    public function messages()
    {
        return [
            'name.unique' => 'This name is already in use. Try a different one.',
            'short_name.unique' => 'This short name is already in use. Try a different one.',
            'code.unique' => 'This code is already in use. Try a different one.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
    
}
