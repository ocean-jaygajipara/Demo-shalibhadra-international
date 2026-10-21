<?php

namespace App\Http\Requests;

use App\Models\TeamRole;
use App\Models\MainMenu;
use App\Models\Company;
use App\Models\CustomerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(Request $request): array {
        $id = $request->route('permission') ?? 0;
        $rules = [
            // 'team_role_id' => [
            //     'required',
            //     Rule::exists((new TeamRole())->getTable(), 'id')
            // ],
            // 'customer_role_id' => [
            //     'required',
            //     Rule::exists((new CustomerType())->getTable(), 'id')
            // ],
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            // 'main_menu_id' => [
            //     'required',
            //     Rule::exists((new MainMenu())->getTable(), 'id')
            // ],
            'permissions' => 'required|array',
        ];
        return $rules;
    }

    public function messages() {
        return [
            // 'team_role_id.required' => 'The team role name is required.',
            // 'customer_role_id.required' => 'The customer role name is required.',
            'company_id.required' => 'The company name is required.',
            'main_menu_id.required' => 'The main menu name is required.',
            'permissions.required' => 'The permissions is required.',
        ];
    }
}
