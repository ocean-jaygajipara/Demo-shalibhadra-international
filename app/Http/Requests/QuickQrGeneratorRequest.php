<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuickQrGeneratorRequest extends FormRequest
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
        $id = $request->route('quick-qr-generator') ?? 0;

        $rules = [
            'company_id'        => 'required', Rule::exists((new Company())->getTable(), 'id'),
            'label'             => 'required|string|max:255',
            'quantity'          => 'required|integer|min:1',
            'collect_points'       => 'required|integer|min:1',
            'status'            => 'required|in:active,inactive', // adjust status values if needed
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required'   => 'Please select a company.',
            'company_id.exists'     => 'The selected company is invalid.',
            'label.required'        => 'QR label is required.',
            'quantity.required'     => 'QR code quantity is required.',
            'quantity.integer'      => 'Quantity must be an integer.',
            'collect_points.required'  => 'Please enter QR point value.',
            'status.required'       => 'Please select a status.',
        ];
    }
}
