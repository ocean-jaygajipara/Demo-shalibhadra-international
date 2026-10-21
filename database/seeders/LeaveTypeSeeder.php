<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\Company;  // Import the Company model

class LeaveTypeSeeder extends Seeder
{
    public function run()
    {
        // Fetch a random company_id from the database
        $company_id = Company::all()->random()->id;

        // Define an array of leave types with their data
        $leaveTypes = [
            [
                'name' => 'Sick Leave',
                'status' => 'active',  // 'active' status
            ],
            [
                'name' => 'Casual Leave',
                'status' => 'inactive',  // 'inactive' status
            ],
            [
                'name' => 'Company Leave',
                'status' => 'active',  // 'inactive' status
            ],
            // Add more leave types as needed with either 'active' or 'inactive' status
        ];

        // Insert the leave types into the database
        foreach ($leaveTypes as $leaveTypeData) {
            $leaveTypeData['company_id'] = Company::all()->random()->id;
            $leaveTypeData['created_by'] = 1;
            LeaveType::create($leaveTypeData);
        }
    }
}
