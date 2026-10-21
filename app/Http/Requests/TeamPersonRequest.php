<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('team_person') ?? 0;
        $isUpdate = $id ? true : false;

        $rules = [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'country_id' => ['required', 'exists:master_countries,id'],
            'state_id' => ['required', 'exists:master_state,id'],
            'city_id' => ['required', 'exists:master_city,id'],
            'area_id' => ['required', 'exists:master_area,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'team_role_id' => ['nullable', 'exists:team_role,id'],
            'parent_type_id' => ['nullable', 'exists:team_persons,id'],
            'name' => ['required', 'string', 'max:255'],
            'mobile_no' => [
                'required',
                'digits:10',
                Rule::unique('team_persons')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                        ->whereNull('deleted_at');
                })
            ],
            'username' => [
                'required',
                'max:255',
                Rule::unique('team_persons', 'username')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                        ->whereNull('deleted_at');
                }),
            ],
            //'password' => [$isUpdate ? 'nullable' : 'required', 'max:255'],
            'password' => array_merge(
                $isUpdate ? ['nullable'] : ['required'],
                ['regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/']
            ),
            'email' => ['nullable', 'max:255', 'email'],
            'birth_date' => ['nullable', 'date'],
            'min_working_start_time' => ['required'],
            'max_working_start_time' => ['required'],
            'working_end_time' => ['required'],
            'address' => ['required'],
            'status' => 'required|in:active,inactive',
            'employee_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('team_persons')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                        ->whereNull('deleted_at');
                })
            ],
        ];
        // dd("L-51", $rules, $id, $request->all());
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'company_id.exists' => 'The company is invalid.',

            'country_id.required' => 'The country is required.',
            'country_id.exists' => 'The country is invalid.',

            'state_id.required' => 'The state is required.',
            'state_id.exists' => 'The state is invalid.',

            'city_id.required' => 'The city is required.',
            'city_id.exists' => 'The city is invalid.',

            'area_id.required' => 'The area is required.',
            'area_id.exists' => 'The area is invalid.',

            'name.required' => 'The name is required.',
            'address.required' => 'The address is required.',

            'mobile_no.required' => 'The mobile number is required.',
            'mobile_no.digits' => 'The mobile number must be 10 digits.',
            'mobile_no.unique' => 'The mobile number is already taken.',

            'username.required' => 'The username is required.',
            'username.unique' => 'The username is already taken.',
            'password.required' => 'The password is required.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',

            'min_working_start_time.required' => 'The min working start time is required.',
            'max_working_start_time.required' => 'The max working start time is required.',
            'working_end_time.required' => 'The working end time is required.',

            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',

            'employee_code.required' => 'The employee code is required.',
            'employee_code.unique' => 'The employee code is already taken.',
        ];
    }
}
