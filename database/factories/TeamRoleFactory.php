<?php

namespace Database\Factories;

use App\Models\TeamRole;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamRoleFactory extends Factory
{
    protected $model = TeamRole::class;

    public function definition()
    {
        $company = Company::inRandomOrder()->first();

        return [
            'company_id' => $company?->id ?? 1,
            'parent_id' => 0, // Default to Super Admin or 0
            'name' => ($company?->name ?? 'Default') . ' Role',
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
