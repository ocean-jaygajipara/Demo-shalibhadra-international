<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Company;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
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
        //dd($request->all());
        $id = $request->route('company') ?? 0;
        $isEdit = $id ? true : false;
        return [
            'platform' => ['nullable', 'max:255'],
            'api_key' => ['nullable', 'max:255'],
            'gst_no' => [
                'nullable',
                'size:15', // GSTIN must be exactly 15 characters
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                Rule::unique((new Company())->getTable())->ignore($id),
            ],
            'lut_number' => [
                'nullable',
                'string',
                'max:50', // LUT number length limit
                'regex:/^[A-Z0-9]+$/', // Only uppercase letters and numbers
            ],
            'company_name' => [
                'required',
                'max:255',
                Rule::unique((new Company())->getTable())->ignore($id),
            ],
            'person_name' => [
                'required',
                'max:255'
            ],
            'whatsapp_number' => [
                'required',
                'numeric',
                'regex:/^\d{10}$/'
            ],
            'email' => [
                'required',
                'email'
            ],
            'password' => array_merge(
                $isEdit ? ['nullable'] : ['required'],
                ['regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/']
            ),
            'country_id' => [
                'required'
            ],
            'state_id' => $isEdit ? ['nullable'] : ['required'],
            'city_id' => $isEdit ? ['nullable'] : ['required'],
            'pincode' => 'nullable|numeric',
            'plan_id' => $isEdit ? ['nullable'] : ['required'],
            'otp' => $isEdit ? ['nullable'] : ['required'],
            'company_logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'white_labeling_logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'company_favicon' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'app_logo' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'header_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'footer_image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'brochure' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'rate_sheet' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'host' => ['nullable', 'regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'port' => 'nullable|numeric',
            'username' => ['nullable', 'regex:/^[a-zA-Z0-9]+\.[a-zA-Z0-9]+@[a-zA-Z0-9.-]+\.com$/'],
            //'password' => 'nullable|string',
            'encryption' => 'nullable|string|in:ssl,tls',
            'from_address' => ['nullable', 'regex:/^[a-zA-Z0-9]+\.[a-zA-Z0-9]+@[a-zA-Z0-9.-]+\.com$/'],
            'from_name' => 'nullable|string',
            'bcc' => ['nullable', 'string', 'regex:/^([^,]+@[^,]+\.[^,]+)(,\s*[^,]+@[^,]+\.[^,]+)*$/'],
        ];
    }

    public function messages()
    {
        return [
            'gst_no.required' => 'The GST number is required.',
            'gst_no.regex' => 'The GST number is invalid.',
            'company_name.required' => 'The company name is required.',
            'person_name.required' => 'The person name is required.',
            'whatsapp_number.required' => 'The whatsapp number is required.',
            'email.required' => 'The email is required.',
            'password.required' => 'The password is required.',
            'country_id.required' => 'The country is required.',
            'state_id.required' => 'The state is required.',
            'city_id.required' => 'The city is required.',
            'plan_id.required' => 'The plan is required.',
            'host.regex' => 'The host must be a valid domain, such as smtp.example.com.',
            'port.numeric' => 'The port must be a number.',
            'username.regex' => 'The username must be a valid domain or subdomain.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'encryption.in' => 'The encryption must be either ssl or tls.',
            'from_address.regex' => 'The from address must be a valid domain, like mail.example.com.',
            'from_name.string' => 'The from name must be a valid string.',
            'bcc.regex' => 'The BCC field must contain a valid email address or a comma-separated list of valid email addresses.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            // Return JSON response for API or AJAX requests
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422));
        }

        // Default behavior (redirect back with errors)
        parent::failedValidation($validator);
    }
}
