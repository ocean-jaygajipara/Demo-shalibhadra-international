<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\CompanySubscriptionPlan;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class CompanySubscriptionPlanExpired extends Command
{

    protected $signature = 'app:company-subscription-plan-expired';
    protected $description = 'Company Subscription Plan Expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::error('CompanySubscriptionPlanExpired command run' );
        try {
            $today = Carbon::today();

            $companies = Company::orderBy('id', 'DESC')->get();

            foreach ($companies as $company) {
                if (!$company->plan_id) {
                    continue;
                }

                $plan = CompanySubscriptionPlan::where('company_id', $company->id)
                    ->where('plan_id', $company->plan_id)
                    ->where(function ($q) use ($today) {
                        $q->whereDate('plan_from', '>', $today)
                        ->orWhereDate('plan_expiry_date', '<', $today);
                    })
                    ->orderBy('id', 'desc')
                    ->first();

                if ($plan) {
                    // Log or update status if needed
                    //Log::info("Plan issue for company ID: {$company->id}, Plan ID: {$company->plan_id}");

                    $plan->subscription_status = 'expired';
                    $plan->save();
                }
            }

            $this->info('Company subscription plan status check completed.');

        } catch (\Exception $e) {
            Log::error('CompanySubscriptionPlanExpired command failed: ' . $e->getMessage());
        }
    }
}
