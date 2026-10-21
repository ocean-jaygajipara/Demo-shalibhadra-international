<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $id = $request->route('user') ?? 0;
        $isUpdate = $id ? true : false;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'max:255', Rule::unique('admin_software', 'username')->ignore($id)],
            'email' => ['required', 'max:255', 'email'],
            'phone' => [
                'required',
                'digits:10',
                Rule::unique('admin_software')->ignore($id)->where(function ($query) use ($request) {
                    return $query->whereNull('deleted_at');
                })
            ],
            'type' => ['required', 'string', 'max:255'],
            'password' => array_merge(
                $isUpdate ? ['nullable'] : ['required'],
                ['regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/']
            ),
            'status' => 'required|in:active,inactive',
        ];

        if (\App\Models\AdminSoftware::where('id', 1)->exists() && $id == 1) {
            unset($rules['type']);
        }
        // dd("L-51", $rules, $id, $request->all());
        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'The name is required.',

            'username.required' => 'The username is required.',
            'username.unique' => 'The username is already taken.',

            'email.required' => 'The email is required.',
            'email.email' => 'Please enter a valid email address.',

            'phone.required' => 'The mobile number is required.',
            'phone.digits' => 'The mobile number must be 10 digits.',
            'phone.unique' => 'The mobile number is already taken.',

            'type.required' => 'The type is required.',
            'password.required' => 'The password is required.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',

            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
