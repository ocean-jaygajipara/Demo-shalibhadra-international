<?php

namespace Database\Factories;

use App\Models\FollowUpReason;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class FollowUpReasonFactory extends Factory
{
    protected $model = FollowUpReason::class;

    public function definition()
    {
        $reasons = [
            'Requested more information',
            'Waiting for budget approval',
            'Client not responding',
            'Follow-up after demo',
            'Need to check with manager',
            'Reschedule call',
            'Interested but not ready',
            'Wants to compare with competitor',
            'Under discussion internally',
            'Will get back next week',
        ];

        $statuses = [
            'active' => 1,
            'inactive' => 0,
            'pending' => 2,
            'on hold' => 3,
        ];

        return [
            'company_id' => Company::inRandomOrder()->value('id'),
            'name' => $this->faker->randomElement($reasons),
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'deleted_by' => null,
        ];
    }
}
