<?php

namespace App\Http\Requests;

use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityRequest extends FormRequest
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
        $id = $request->route('master_city') ?? 0;
        $rules = [
            'country_id' => [
                'required',
                Rule::exists((new MasterCountry())->getTable(), 'id')
            ],
            'state_id' => [
                'required',
                Rule::exists((new MasterState())->getTable(), 'id')
            ],
            // 'name' => 'required|string|max:255|unique:' . (new MasterCity())->getTable() . ',name',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new MasterCity())->getTable())->where(function ($query) use ($request) {
                    return $query->where('country_id', $request->country_id)
                        ->where('state_id', $request->state_id);
                })->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('master-city'), $request->route()->parameters());
        return $rules;
    }

    public function messages()
    {
        return [
            'country_id.required' => 'The country is required.',
            'state_id.required' => 'The state is required.',
            'name.required' => 'The city name is required.',
            'name.unique' => 'The city name must be unique.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
