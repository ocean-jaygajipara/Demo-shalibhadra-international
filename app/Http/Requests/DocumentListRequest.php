<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\DocumentList;
use Illuminate\Http\Request;

class DocumentListRequest extends FormRequest
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
        $id = $request->route('document_list'); // 'document_list' should match the route parameter name

        return [
            'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new DocumentList())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                            ->where('document_type_id', $request->document_type_id)
                            ->whereNull('deleted_at');
                    })->ignore($id),
            ],
            'status' => ['required', 'in:active,inactive'],
            'image' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx,gif|max:10240',
        ];
    }

    /**
     * Custom error messages for the validation rules.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'document_type_id.required' => 'The document type is required.',
            'name.required' => 'The name is required.',
            'name.unique' => 'This document name already exists for the selected company and document type.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
