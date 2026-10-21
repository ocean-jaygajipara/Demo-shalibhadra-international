<?php

namespace Database\Factories;

use App\Models\MasterUnit;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterUnitFactory extends Factory
{
    protected $model = MasterUnit::class;

    public function definition()
    {
        static $unitIndex = 0;

        $units = ['Pcs', 'Kgs'];
        $unitName = $units[$unitIndex % count($units)];
        $unitIndex++;

        return [
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1, // fallback to 1 if no company
            'name' => $unitName,
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
