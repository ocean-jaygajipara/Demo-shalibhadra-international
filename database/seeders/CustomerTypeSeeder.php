<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['company_id' => 1, 'name' => 'Super Stockiest', 'parent_type' => 0, 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Distributor', 'parent_type' => 1, 'status' => 'active'],
            ['company_id' => 1, 'name' => 'Dealer/Retailer', 'parent_type' => 2, 'status' => 'active'],
            ['company_id' => 1,'name' => 'B2B Customer', 'parent_type' => 0, 'status' => 'active'],
            ['company_id' => 1, 'name' => 'B2C Customer', 'parent_type' => 0, 'status' => 'active'],
        ];

        foreach ($data as $item) {
            CustomerType::create($item);
        }
    }
}
