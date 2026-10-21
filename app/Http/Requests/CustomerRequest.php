<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\PaymentTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class CustomerRequest extends FormRequest
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
        $id = $request->route('customer') ?? 0; // adjust if the route param is named differently

        $rules = [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'gst_number' => ['nullable', 'string', 'max:15'],
            'pan_number' => ['nullable', 'string', 'max:10'],
            'customer_type_id' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'contact_person_name' => ['nullable', 'string', 'max:255'],
            'mobile_number' => [
                'nullable',
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
                        $fail("Please Select Company .");
                    }
                },
                Rule::unique('customers', 'mobile_number')
                    ->where(function ($query) {
                        return $query->where('company_id', $this->input('company_id'))
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
            'whatsapp_number' => ['nullable', 'string', 'max:15'],
            'payment_terms_id' => ['nullable', 'exists:' . (new PaymentTerm())->getTable() . ',id'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'balance_type' => ['required_with:opening_balance', 'in:Cr'],
            'is_active' => ['nullable', 'boolean'],
            'country_id' => ['required', 'exists:' . (new MasterCountry())->getTable() . ',id'],
            'state_id' => ['required', 'exists:' . (new MasterState())->getTable() . ',id'],
            'city_id' => ['required', 'exists:' . (new MasterCity())->getTable() . ',id'],
            'area_id' => ['nullable', 'nullable', 'exists:' . (new MasterArea())->getTable() . ',id'],
            'pincode' => ['required', 'string', 'max:10'],
            'address' => ['required', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'billing_address' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            // Contacts Details
            'contacts' => ['nullable', 'array'],
            'is_prospect' => ['nullable', 'in:0,1'],

            'contacts.*.id' => ['nullable', 'integer'],
            'contacts.*.department' => ['nullable', 'string', 'max:255'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.designation' => ['nullable', 'string', 'max:255'],
            'contacts.*.mobile' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) {
                    $companyId = $this->input('company_id');
                    $company = Company::find($companyId);

                    if ($company && $value) {
                        $length = strlen((string) $value);

                        if ($company->mobile_min && $length < $company->mobile_min) {
                            $fail("The {$attribute} must be at least {$company->mobile_min} digits.");
                        }

                        if ($company->mobile_max && $length > $company->mobile_max) {
                            $fail("The {$attribute} must not exceed {$company->mobile_max} digits.");
                        }
                    }
                },
            ],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            // Bank Details
            'banks' => ['nullable', 'array'],
            'banks.*.id' => ['nullable', 'integer'],
            'banks.*.bank_name' => ['nullable', 'string', 'max:255'],
            'banks.*.account_number' => ['nullable', 'numeric'],
            'banks.*.branch' => ['nullable', 'string', 'max:255'],
            'banks.*.ifsc' => ['nullable', 'string', 'max:50'],
            'banks.*.swift_code' => ['nullable', 'string', 'max:50'],
            // SALES ITEM
            'sales' => ['nullable', 'array'],
            'sales.*.id' => ['nullable', 'integer'],
            'sales.*.item_code' => ['nullable', 'string', 'max:50'],
            'sales.*.item_name' => ['nullable', 'string', 'max:255'],
            'sales.*.rate' => ['nullable', 'numeric'],
            'sales.*.type' => ['nullable', 'string', 'max:100'],
            'sales.*.date' => ['nullable', 'date'],
        ];

        if ($request->customer_type_id == 1) {
            $rules['gst_number'][] = 'required';
        }
        // If Prospected Customer page (create or store)
        if ($this->routeIs('prospected-customer.store') || $this->routeIs('prospected-customer.update')) {

            return [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'customer_name' => ['required', 'string', 'max:255'],
                'address'       => ['required', 'string'],
                'is_prospect'   => ['nullable', 'in:0,1'],
                'gst_number' => ['nullable', 'string', 'max:15'],
                'pan_number' => ['nullable', 'string', 'max:10'],
                'customer_type_id' => ['nullable', 'string', 'max:255'],
                'parent_id' => ['nullable', 'max:255'],

                'contact_person_name' => ['nullable', 'string', 'max:255'],
                'mobile_number' => [
                    'nullable',
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
                            $fail("Please Select Company .");
                        }
                    },
                    // Rule::unique('customers', 'mobile_number')
                    //     ->where(function ($query) {
                    //         return $query->where('company_id', $this->input('company_id'))
                    //             ->whereNull('deleted_at');
                    //     })
                    //     ->ignore($id),
                ],
                'whatsapp_number' => ['nullable', 'string', 'max:15'],
                'payment_terms_id' => ['nullable', 'exists:' . (new PaymentTerm())->getTable() . ',id'],
                'credit_days' => ['nullable', 'integer', 'min:0'],
                'opening_balance' => ['nullable', 'numeric', 'min:0'],
                'balance_type' => ['required_with:opening_balance', 'in:Cr'],
                'is_active' => ['nullable', 'boolean'],
                'country_id' => ['nullable', 'exists:' . (new MasterCountry())->getTable() . ',id'],
                'state_id' => ['nullable', 'exists:' . (new MasterState())->getTable() . ',id'],
                'city_id' => ['nullable', 'exists:' . (new MasterCity())->getTable() . ',id'],
                'area_id' => ['nullable', 'nullable', 'exists:' . (new MasterArea())->getTable() . ',id'],
                'pincode' => ['nullable', 'string', 'max:10'],

                'shipping_address' => ['nullable', 'string'],
                'billing_address' => ['nullable', 'string'],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => ['nullable', 'in:active,inactive'],
                'latitude' => ['nullable', 'numeric'],
                'longitude' => ['nullable', 'numeric'],
                // Contacts Details
                'contacts' => ['nullable', 'array'],
                // Contacts Details


                'contacts.*.id' => ['nullable', 'integer'],
                'contacts.*.department' => ['nullable', 'string', 'max:255'],
                'contacts.*.name' => ['nullable', 'string', 'max:255'],
                'contacts.*.designation' => ['nullable', 'string', 'max:255'],
                'contacts.*.mobile' => [
                    'nullable',
                    'numeric',
                    function ($attribute, $value, $fail) {
                        $companyId = $this->input('company_id');
                        $company = Company::find($companyId);

                        if ($company && $value) {
                            $length = strlen((string) $value);

                            if ($company->mobile_min && $length < $company->mobile_min) {
                                $fail("The {$attribute} must be at least {$company->mobile_min} digits.");
                            }

                            if ($company->mobile_max && $length > $company->mobile_max) {
                                $fail("The {$attribute} must not exceed {$company->mobile_max} digits.");
                            }
                        }
                    },
                ],
                'contacts.*.email' => ['nullable', 'email', 'max:255'],
                // Bank Details
                'banks' => ['nullable', 'array'],
                'banks.*.id' => ['nullable', 'integer'],
                'banks.*.bank_name' => ['nullable', 'string', 'max:255'],
                'banks.*.account_number' => ['nullable', 'numeric'],
                'banks.*.branch' => ['nullable', 'string', 'max:255'],
                'banks.*.ifsc' => ['nullable', 'string', 'max:50'],
                'banks.*.swift_code' => ['nullable', 'string', 'max:50'],
                // SALES ITEM
                'sales' => ['nullable', 'array'],
                'sales.*.id' => ['nullable', 'integer'],
                'sales.*.item_code' => ['nullable', 'string', 'max:50'],
                'sales.*.item_name' => ['nullable', 'string', 'max:255'],
                'sales.*.rate' => ['nullable', 'numeric'],
                'sales.*.type' => ['nullable', 'string', 'max:100'],
                'sales.*.date' => ['nullable', 'date'],
            ];
            if ($request->customer_type_id == 1) {
            $rules['gst_number'][] = 'required';
        }
        }

        // if ($request->is_prospect == 1) {
        //     $rules['customer_name'] = ['required', 'string', 'max:255'];
        //     $rules['address'] = ['required', 'string'];
        // }

        // dd("L-59", $rules, $request->all());
        return $rules;
    }
}
