<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\PaymentTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentTermRequest extends FormRequest
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
        $id = $request->route('payment_term') ?? 0;

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],

            'payment_terms' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new PaymentTerm())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id);
                    })->ignore($id),
            ],

            'credit_days' => [
                'required',
                'integer',
                'min:0',
                'max:365',
            ],

            'status' => 'required|in:active,inactive',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'payment_terms.required' => 'The payment term is required.',
            'payment_terms.unique' => 'This payment term is already in use. Try a different one.',
            'credit_days.required' => 'Credit days are required.',
            'credit_days.integer' => 'Credit days must be a valid number.',
            'credit_days.min' => 'Credit days cannot be negative.',
            'credit_days.max' => 'Credit days cannot be more than 365.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
