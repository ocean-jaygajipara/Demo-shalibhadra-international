<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySubscriptionPlan;
use App\Models\PlanMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CompanySubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::whereNotNull('plan_id')
                        ->whereNotNull('plan_from')
                        ->whereNotNull('plan_to')
                        ->orderBy('id', 'ASC')
                        ->get();

        foreach ($companies as $company) {
            $exists = CompanySubscriptionPlan::where('company_id', $company->id)
                        ->where('plan_id', $company->plan_id)
                        ->whereDate('plan_from', $company->plan_from)
                        ->exists();
            if ($exists) continue;

            $plan_data = PlanMaster::where('id', $company->plan_id)->first();

            $today = Carbon::today();
            $subscription_status = '';
            if (!empty($company->plan_from) && !empty($company->plan_to)) {
                if ($today->between(Carbon::parse($company->plan_from), Carbon::parse($company->plan_to))) {
                   $subscription_status = 'active';
                } else {
                   $subscription_status =  'expired';
                }
            }

            CompanySubscriptionPlan::create([
                'company_id' => $company->id,
                'plan_id' => $company->plan_id,
                'plan_from' => $company->plan_from,
                'plan_to' => $company->plan_to,
                'extra_detail' => json_encode($plan_data->toArray()),
                'plan_expiry_date' => $company->plan_to,
                'subscription_status' => $subscription_status,
                'created_by' => $company->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}



