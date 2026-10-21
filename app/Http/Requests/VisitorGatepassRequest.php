<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Department;
use App\Models\PurposeVisitMaster;
use App\Models\TeamPerson;
use App\Models\VisitorType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VisitorGatepassRequest extends FormRequest
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
    public function rules(): array
    {
        $company = Company::find($this->company_id);

        $mobileMin = $company?->mobile_min;
        $mobileMax = $company?->mobile_max;

        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'visitor_name'        => 'required|string|max:255',
            'contact_number' => [
                'required',
                'regex:/^[0-9]+$/',
                'min:' . $mobileMin,
                'max:' . $mobileMax,
            ],
            'email'               => 'nullable|email|max:255',
            'visitor_type_id' => [
                'nullable',
                Rule::exists((new VisitorType())->getTable(), 'id'),
            ],
            'organization_name'   => 'nullable|string|max:255',
            'visiting_datetime'   => 'required|date',
            'purpose_of_visit_id' => [
                'required',
                Rule::exists((new PurposeVisitMaster())->getTable(), 'id'),
            ],
            'team_person_id' => [
                'required',
                Rule::exists((new TeamPerson())->getTable(), 'id'),
            ],
            'department_id' => [
                'nullable',
                Rule::exists((new Department())->getTable(), 'id'),
            ],
            'id_proof_type'       => 'nullable|string|max:50',
            'id_proof_number'     => 'nullable|string|max:50',
            'vehicle_number'      => 'nullable|string|max:50',
            'items_carried_in'    => 'nullable|boolean',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'photo'               => 'nullable|string',
            'signature' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (request()->hasFile('signature')) {
                        return;
                    }

                    if (is_string($value) && preg_match('/^data:image\/(png|jpg|jpeg);base64,/', $value)) {
                        return;
                    }

                    $fail('The ' . $attribute . ' must be an image file or a valid base64-encoded image.');
                },
            ],
            'gate_location_name'  => 'nullable|string|max:255',
        ];
    }
}
