<?php

namespace Database\Factories;

use App\Models\MasterWarehouse;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class MasterWarehouseFactory extends Factory
{
    protected $model = MasterWarehouse::class;

    public function definition()
    {
        $warehouseNames = [
            'Raw Material Warehouse',
            'Finished Goods Warehouse',
            'Distribution Center',
            'Cold Storage Warehouse',
            'Central Warehouse',
            'Packaging Warehouse',
            'Returns Processing Center',
            'Spare Parts Warehouse',
            'Hazardous Materials Warehouse',
            'Pharmaceutical Warehouse'
        ];

        return [
            'company_id' => Company::inRandomOrder()->value('id'), // Random existing company ID
            'name' => $this->faker->unique()->randomElement($warehouseNames), // Industry-style warehouse name
            'status' => 'active',
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => $this->faker->numberBetween(1, 5),
        ];
    }
}
