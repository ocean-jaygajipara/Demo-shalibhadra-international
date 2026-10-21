<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class QrCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow all users for now
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Get the customer ID for update case (PUT)
        $customerId = $this->route('customer') ?? 0;

        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'), // Validate company exists
            ],
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('customers', 'email')->ignore($customerId), // Unique, but ignore current for update
            ],
            'mobile' => [
                'required',
                'digits:10',
                Rule::unique('customers', 'mobile')->ignore($customerId), // Same for mobile
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Please select a company.',
            'company_id.exists'   => 'The selected company is invalid.',
            'code.required'       => 'Customer code is required.',
            'name.required'       => 'Customer name is required.',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Please enter a valid email address.',
            'email.unique'        => 'This email is already registered.',
            'mobile.required'     => 'Mobile number is required.',
            'mobile.digits'       => 'Mobile number must be 10 digits.',
            'mobile.unique'       => 'This mobile number is already registered.',
        ];
    }
}
