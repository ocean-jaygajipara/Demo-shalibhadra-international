<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaRequest extends FormRequest
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
        $id = $request->route('master_area') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'country_id' => [
                'required',
                'string',
                'max:255',
                Rule::exists((new MasterCountry())->getTable(), 'id')
            ],
            'state_id' => [
                'required',
                'string',
                'max:255',
                Rule::exists((new MasterState())->getTable(), 'id')
            ],
            'city_id' => [
                'required',
                'string',
                'max:255',
                Rule::exists((new MasterCity())->getTable(), 'id')
            ],

            'area_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new MasterArea())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('country_id', $request->country_id)
                            ->where('state_id', $request->state_id)
                            ->where('city_id', $request->city_id)
                            ->where('company_id', $request->company_id);
                    })
                    ->ignore($id)
            ],

            'status' => 'required|in:active,inactive',
        ];
        return $rules;
    }
    public function messages()
    {
        return [
            'country_id.required' => 'The country is required.',
            'state_id.required' => 'The state is required.',
            'city_id.required' => 'The city name is required.',
            'area_name.unique' => 'This name is already in use in this company. Try a different one.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
