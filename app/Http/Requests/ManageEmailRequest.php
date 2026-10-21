<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ManageEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManageEmailRequest extends FormRequest
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
        $id = $request->route('marketing_status') ?? 0;

        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],

            'module' => [
                'required',
                'string',
                'max:255',
            ],

            'ntype' => [
                'required',
                'string',
                'max:255',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'subject' => [
                'required',
                'string',
                'max:255',

            ],

            'body' => [
                'required',
                'string',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];

        return $rules;
    }


}
