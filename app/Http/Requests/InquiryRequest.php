<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Inquiry;
use App\Models\MarketingStatus;
use App\Models\SourceOfInquiry;
use App\Models\TeamPerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('inquiry_assigned_to') && is_array($this->inquiry_assigned_to)) {
            $filtered = array_filter($this->inquiry_assigned_to, function($value) {
                return $value !== null && $value !== '';
            });
            $this->merge([
                'inquiry_assigned_to' => empty($filtered) ? null : array_values($filtered),
            ]);
        }
    }

    public function rules(Request $request): array
    {
        $id = $request->route('inquiry') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'customer_name' => ['required'],
            'contact_person_name' => ['nullable'],
            'whatsapp_no' => ['nullable', 'digits:10', 'numeric'],
            'mobile_no' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) use ($id) {
                    $companyId = $this->input('company_id');
                    $company = Company::find($companyId);

                    if ($company) {
                        $length = strlen((string) $value);

                        if ($company->mobile_min && $length < $company->mobile_min) {
                            $fail("The mobile number must be at least {$company->mobile_min} digits.");
                        }

                        if ($company->mobile_max && $length > $company->mobile_max) {
                            $fail("The mobile number must not exceed {$company->mobile_max} digits.");
                        }
                    } else {
                        $fail("Please select a company.");
                    }
                },
                // Rule::unique('inquiries', 'mobile_no')
                //     ->where(function ($query) {
                //         return $query->where('company_id', $this->input('company_id'))
                //             ->whereNull('deleted_at');
                //     })
                //     ->ignore($id),
            ],


            'email' => ['nullable', 'email'],
            'pincode' => ['nullable', 'digits:6'],
            'website' => ['nullable', 'url'],
            'followup_date' => ['nullable', 'date'],
            'followup_details' => ['nullable'],
            'requirement_details' => ['nullable'],
            'address' => ['nullable'],
            'inquiry_status_id' => ['required',Rule::exists((new MarketingStatus())->getTable(), 'id'),],
            'followup_status_id' => ['required',Rule::exists((new MarketingStatus())->getTable(), 'id'),],
            'source_of_inquiry_id' => ['nullable',Rule::exists((new SourceOfInquiry())->getTable(), 'id'),],
            'inquiry_date' => ['required', 'date'],
            'country_id' => ['nullable'],
            'state_id' => ['nullable'],
            'city_id' => ['nullable'],
            'area_id' => ['nullable'],
            'inquiry_created_by' => ['required',Rule::exists((new TeamPerson)->getTable(), 'id')],
            'inquiry_assigned_to' => ['nullable', 'array'],
            'inquiry_assigned_to.*' => [Rule::exists((new TeamPerson)->getTable(), 'id')],
            'attachment' => 'nullable|mimes:jpeg,png,jpg,gif,pdf,docx|max:2048',
            // 'inquiry_no' => [
            //     'required',
            //     'string',
            //     'max:255',
            //     Rule::unique((new Inquiry())->getTable())->where(function ($query) use ($request) {
            //         return $query->where('company_id', $request->company_id)
            //                 ->whereNull('deleted_at');
            //     })->ignore($id),
            // ],
        ];
        // dd("L-53", $rules, $request->all());
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company name is required.',
            'customer_name.required' => 'The customer name is required.',
            'customer_name.unique' => 'The customer name must be unique.',
            'contact_person_name.required' => 'The contact person name is required.',
            'contact_person_name.unique' => 'The contact person name must be unique.',
            'mobile_no.required' => 'The mobile no is required.',
            'mobile_no.unique' => 'The mobile no must be unique.',
            'whatsapp_no.required' => 'The whatsapp no is required.',
            'whatsapp_no.unique' => 'The whatsapp no must be unique.',
            'email.required' => 'The email is required.',
            'email.unique' => 'The email must be unique.',
            'website.required' => 'The website is required.',
            'website.unique' => 'The website must be unique.',
            'followup_date.required' => 'The followup date is required.',
            'followup_date.unique' => 'The followup date must be unique.',
            'followup_details.required' => 'The followup details is required.',
            'followup_details.unique' => 'The followup details must be unique.',
            'requirement_details.required' => 'The requirement details is required.',
            'requirement_details.unique' => 'The requirement details must be unique.',
            'address.required' => 'The address is required.',
            'address.unique' => 'The address must be unique.',
            'inquiry_status_id.required' => 'The inquiry status is required.',
            'inquiry_status_id.unique' => 'The inquiry status must be unique.',
            'followup_status_id.required' => 'The followup status is required.',
            'followup_status_id.unique' => 'The followup status must be unique.',
            'source_of_inquiry_id.required' => 'The source of inquiry is required.',
            'source_of_inquiry_id.unique' => 'The source of inquiry must be unique.',
            'inquiry_date.required' => 'The inquiry date is required.',
            'inquiry_date.unique' => 'The inquiry date must be unique.',
            'inquiry_created_by.required' => 'The inquiry created by is required.',
            'inquiry_created_by.unique' => 'The inquiry created by must be unique.',
            'inquiry_assigned_to.required' => 'The inquiry assigned to is required.',
            'inquiry_assigned_to.unique' => 'The inquiry assigned to must be unique.',
            'attachment.required' => 'The attachment is required.',
            'attachment.unique' => 'The attachment must be unique.',
            'inquiry_no.required' => 'The inquiry no is required.',
            'inquiry_no.unique' => 'The inquiry no must be unique.',
        ];
    }
}
