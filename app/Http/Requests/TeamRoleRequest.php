<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamRoleRequest extends FormRequest
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
        $id = $request->route('team_role') ?? 0;
        $parent_id = "nullable";
        if (!Auth::guard('admin_software')->check()) {
            $parent_id = 'required';
        }
        return [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'parent_id' => [
                $parent_id,
                // Rule::exists('your_table_name', 'id'), // Replace 'your_table_name' with the relevant table name
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                // Rule::unique('your_table_name', 'name')->ignore($id), // For update requests
            ],
            'status' => [
                'required',
                'in:active,inactive', // Adjust based on your actual status values
            ],
             'created_type' => [
            
            'in:Admin,Team',
        ],

        ];
    }
}
