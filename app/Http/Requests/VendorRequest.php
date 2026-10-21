<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class VendorRequest extends FormRequest
{
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
        $id = $request->route('vendor');

        $rules = [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'gst_number' => ['nullable', 'string', 'max:255'],
            'vendor_name' => ['required', 'string', 'max:255'],
            'mobile_number' => [
                'nullable',
                'digits:10',
                Rule::unique('vendors', 'mobile_number')
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
            'whatsapp_number' => ['nullable', 'string', 'max:15'],
            'vendor_code' => [
                'required',
                Rule::unique('vendors', 'vendor_code')
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
            'pan_number' => ['nullable', 'string', 'size:10', 'regex:/[A-Z]{5}[0-9]{4}[A-Z]{1}/'], // PAN format
            'uan_number' => ['nullable', 'string', 'max:20'],
            'country_id' => ['required', 'exists:' . (new MasterCountry())->getTable() . ',id'],
            'state_id' => ['required', 'exists:' . (new MasterState())->getTable() . ',id'],
            'city_id' => ['required', 'exists:' . (new MasterCity())->getTable() . ',id'],
            'area_id' => ['nullable', 'exists:' . (new MasterArea())->getTable() . ',id'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'address' => ['required', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'email_id' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'Company is required',
            'gst_number.required' => 'GST Number is required',
            'vendor_name.required' => 'Name is required',
            'mobile_number.required' => 'Mobile Number is required',
            'mobile_number.digits' => 'Mobile Number must be 10 digits',
            'vendor_code.required' => 'Vendor Code is required',
            'pan_number.size' => 'PAN Number must be 10 characters',
            'pan_number.regex' => 'PAN Number format is invalid (e.g., ABCDE1234F)',
            'uan_number.max' => 'UAN Number cannot exceed 20 characters',
            'country_id.required' => 'Country is required',
            'state_id.required' => 'State is required',
            'city_id.required' => 'City is required',
            'area_id.required' => 'Area is required',
            'pincode.required' => 'Pincode is required',
            'address.required' => 'Address is required',
            'email_id.email' => 'Email address has not proper format',
            'birth_date.required' => 'Birth Date is required',
            'status.required' => 'Status is required',
        ];
    }
}
