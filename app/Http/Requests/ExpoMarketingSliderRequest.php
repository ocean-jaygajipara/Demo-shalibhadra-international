<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpoMarketingSliderRequest extends FormRequest
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
        $id = $request->route('expo_marketing_sliders') ?? 0;

        $rules = [
            'company_id' => [
                'nullable',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
            'redirect_url' => [
                'required',
                'string',
                'url',

            ],
            'status' => 'required|in:active,inactive',
        ];

        return $rules;
    }
}
