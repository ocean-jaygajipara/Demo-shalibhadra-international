<?php

namespace App\Http\Requests;

use App\Models\PlanMaster;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanMasterRequest extends FormRequest
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
        $id = $request->route('plan_master') ?? 0;
        $rules = [
            'name' => [
                'required',
                Rule::unique((new PlanMaster())->getTable())->ignore($id)
            ],
            'plan_valid_day' => [
                'required',
                // Rule::exists((new MasterState())->getTable(), 'id')
            ],
            // 'name' => 'required|string|max:255|unique:' . (new MasterCity())->getTable() . ',name',
            'max_team_user_count' => [
                'required',
                'string',
                'max:255',
                // Rule::unique((new MasterCity())->getTable())->where(function ($query) use ($request) {
                //     return $query->where('country_id', $request->country_id)
                //         ->where('state_id', $request->state_id);
                // })->ignore($id),
            ],
            'max_customer_count' => [
                'required',
                // Rule::exists((new MasterState())->getTable(), 'id')
            ],
            'plan_type' => [
                'required',
                'in:' . implode(",", array_keys(PlanMaster::$PlanType))
                // Rule::exists((new MasterState())->getTable(), 'id')
            ],
            'max_inquiry_count' => [
                'required',
                // Rule::exists((new MasterState())->getTable(), 'id')
            ],
            'app_right' => [
                'nullable',
            ],
            'panel_right' => [
                'required',
            ],

            'status' => 'required|in:active,inactive',
        ];
        // dd("L-54", $rules, $id, $request->all(), $request->route('plan_masters'), $request->route()->parameters());
        return $rules;
    }

    public function messages()
    {
        return [
            // 'country_id.required' => 'The country is required.',
            // 'state_id.required' => 'The state is required.',
            // 'name.required' => 'The city name is required.',
            // 'name.unique' => 'The city name must be unique.',
            // 'status.required' => 'The status is required.',
            // 'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
