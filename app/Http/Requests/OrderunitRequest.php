<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;


class OrderunitRequest extends FormRequest
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
        $id = $request->route('orderunit');

        return [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('orderunits', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],

            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
