<?php

namespace App\Http\Controllers;

use App\Models\TeamAttendance;
use App\Models\CompanySubscriptionPlan;
use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use DateTime;
use Carbon\Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public $authenticateLoginUserDetails = null;
    public $currentGuard = null;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            
            foreach (array_keys(config('auth.guards')) as $kry => $guard) {
                if (Auth::guard($guard)->check()) {
                    $this->currentGuard = $guard;
                    break;
                }
            }

            $this->authenticateLoginUserDetails = (Auth::guard('admin_software')->check()) ? Auth::guard('admin_software')?->user() : null;
            if (!$this->authenticateLoginUserDetails) {
                $this->authenticateLoginUserDetails = (Auth::guard('team_person')->check()) ? Auth::guard('team_person')?->user() : null;
            }
            View::share('authenticateLoginUserDetails', $this->authenticateLoginUserDetails);

            // Do something with $this->user, like load user-specific settings
            View::share('currentGuard', $this->currentGuard);
            if ($this->currentGuard === 'team_person') {
                $user = Auth::guard('team_person')->user();
                $company_id = $user?->company_id;
                $parent_type_id = $user?->parent_type_id;

                View::share('parent_type_id', $parent_type_id);
                if($parent_type_id != 0){
                    $attendance = TeamAttendance::where('company_id', $company_id)
                        ->where('team_person_id', $user?->id)
                        ->whereNull('punch_out_time')
                        ->orderByDesc('id')
                        ->first();

                    $punchType = $attendance ? 'out' : 'in';
                    View::share('punchType', $punchType);
                    $mainAttendanceAddBtn = (isset($company_id) && !$company_id) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Team Attendance']);
                    View::share('mainAttendanceAddBtn', $mainAttendanceAddBtn);
                    if($attendance){

                        $date = new DateTime($attendance->punch_in_time);
                        $punch_in_time_formatted = $date->format('d-m-Y h:i A');
                        View::share('punchInTime', $punch_in_time_formatted);
                    }
                }

                $companyData = Company::with('plan')->where('id', $company_id)->select('id', 'plan_id')->firstOrFail();
                if(isset($companyData->plan_id) && !empty($companyData->plan_id)){
                    $latestPlan = CompanySubscriptionPlan::where('company_id', $company_id)->where('plan_id', $companyData->plan_id)->orderBy('id', 'desc')->first();
                    $expiryDate = Carbon::parse($latestPlan?->plan_expiry_date);
                    if ($latestPlan && !empty($expiryDate) && $expiryDate->between(Carbon::today(), Carbon::today()->addDays(10))) {
                         View::share('is_plan_expired', true);
                         View::share('mainPlanExpiryDate', $expiryDate);
                         View::share('mainPlanName', $companyData->plan->name);
                    }
                }
            }
            $company_id = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
            $mainInquiryAddBtn = (isset($company_id) && !$company_id) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Inquiry']);
            View::share('mainInquiryAddBtn', $mainInquiryAddBtn);

            return $next($request);
        });
    }

    public function sendResponse($result, $message, $extraData = [])
    {
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $result
        ];

        if (count($extraData) > 0) {
            $response['extraData'] = $extraData;
        }
        return response()->json($response, 200);
    }

    public function sendError($error, $errorMessages = [], $extraData = [], $code = 404)
    {
        $response = [
            'status' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['messages'] = $errorMessages;
        }

        if (count($extraData) > 0) {
            $response['extraData'] = $extraData;
        }
        return response()->json($response, $code);
    }
}
