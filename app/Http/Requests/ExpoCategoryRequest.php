<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\ExpoCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpoCategoryRequest extends FormRequest
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
        $id = $request->route('expo_category') ?? 0;
        $rules = [
            // 'company_id' => [
            //     'required',
            //     Rule::exists((new Company())->getTable(), 'id')
            // ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new ExpoCategory())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id);
                })->ignore($id),
            ],
            'status' => 'required|in:active,inactive',
        ];
        // dd("L-33", $rules, $id, $request->all(), $request->route('expense_category'), $request->route()->parameters());
        return $rules;
    }
}
