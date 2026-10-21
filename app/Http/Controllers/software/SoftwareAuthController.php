<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\AdminSoftware;
use App\Models\Company;
use App\Models\TeamPerson;
use App\Models\CompanySubscriptionPlan;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\TrustedIp;
use Illuminate\Support\Str;

class SoftwareAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        // $this->middleware('admin_software')->only('logout');
    }

    /** Method Over write */
    public function showLoginForm(Request $request)
    {
        if (Auth::guard("admin_software")->check() || Auth::guard("team_person")->check()) {
            return Redirect::route("software.dashboard");
        }
        // Auth::logout(); // Logs out the user

        // $request->session()->invalidate(); // Invalidates the session
        // $request->session()->regenerateToken(); // Regenerates the CSRF token

        return view('software.auth.login');
    }

    public function verificationPending()
    {

        return view('software.auth.verify_ip');
    }

    protected function getSoftwareIpDebugInfo(Request $request): array
    {
        $clientIp = $request->ip();
        $forwardedFor = $request->header('X-Forwarded-For');
        $realIp = $request->header('X-Real-IP');
        $cfConnectingIp = $request->header('CF-Connecting-IP');
        $serverIp = gethostbyname(gethostname());

        $isPrivateClientIp = false;
        if (!empty($clientIp)) {
            $isPrivateClientIp = filter_var(
                $clientIp,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) === false;
        }

        return [
            'app_env' => app()->environment(),
            'client_ip' => $clientIp,
            'request_ip_method' => $request->ip(),
            'client_ips_chain' => $request->ips(),
            'client_ip_is_private_or_reserved' => $isPrivateClientIp,
            'x_forwarded_for' => $forwardedFor,
            'x_real_ip' => $realIp,
            'cf_connecting_ip' => $cfConnectingIp,
            'server_ip' => $serverIp,
            'note' => [
                'client_ip is the address visible to the server.',
                'on live server you usually get the visitor public IP, not visitor local LAN IP like 192.168.x.x.',
                'visitor private/local PC IPv4 cannot be read directly over the internet from PHP/Laravel.',
                'server_ip is the machine IP where Laravel is running, not the visitor PC IP.',
            ],
        ];
    }

    public function softwareIpTest(Request $request)
    {
        dd($this->getSoftwareIpDebugInfo($request));
    }

    public function verifyIp(Request $request, $token)
    {
        $trustedIp = TrustedIp::where('verification_token', $token)->first();

        if (!$trustedIp) {
            return Redirect::route('software.login')->withErrors('Invalid verification token.');
        }

        if ($trustedIp->ip_address !== $request->ip()) {
            return Redirect::route('software.login')->withErrors('IP address mismatch. Please verify from the device you are logging in from.');
        }

        $trustedIp->update([
            'verified_at' => now(),
            'verification_token' => null, // Clear token after use
        ]);

        return Redirect::route('software.login')->withSuccess('IP address verified. You can now login.');
    }

    protected function needsIpVerification($user)
    {
        // For TeamPerson, check the role name
        if ($user instanceof TeamPerson) {
            $roleName = $user->role?->name;

            // The user wants to check for "Shalibhadra International PVT LTD" role
            if ($roleName === 'Shalibhadra International PVT LTD') {
                return true;
            }

            // For now, exclude other roles unless specified
            return false;
        }

        return false;
    }

    protected function isIpTrusted($user, $ip)
    {
        return TrustedIp::where('user_id', $user->id)
            ->where('user_type', $user->getMorphClass())
            ->where('ip_address', $ip)
            ->whereNotNull('verified_at')
            ->exists();
    }

    public function logout(Request $request)
    {
        if (Auth::guard("admin_software")->check()) {
            $user_id = Auth::guard("admin_software")->id();
            \App\Models\AdminSoftware::where('id', $user_id)->update([
                'device_token' => null
            ]);
            Auth::guard("admin_software")->logout();
        } elseif (Auth::guard("team_person")->check()) {
            $user_id = Auth::guard("team_person")->id();
            \App\Models\TeamPerson::where('id', $user_id)->update([
                'device_token' => null
            ]);
            Auth::guard("team_person")->logout();
        }
        // dd("L-59", $request->all());
        Session::flush();
        // return $request->all();
        Auth::logout(); // Logs out the user

        $request->session()->invalidate(); // Invalidates the session
        $request->session()->regenerateToken(); // Regenerates the CSRF token
        return Redirect::route("software.login");
    }

    public function submitLoginForm(Request $request)
    {
        try {

            if (Auth::guard("admin_software")->check() || Auth::guard("team_person")->check()) {
                return Redirect::route("software.dashboard");
            }

            $isAjax = ($request->ajax()) ? true : false;

            $rules = [];
            $field = "username";
            $customerField = 'contact_person_name';
            if (is_numeric($request->get("username"))) {
                $field = "phone";
                $customerField = 'mobile_number';
            } else if (filter_var($request->get("username"), FILTER_VALIDATE_EMAIL)) {
                $field = "email";
                $customerField = 'email';
            }
            $rules = [
                $field => [
                    'required'
                ]
            ];
            $request[$field] = $request->get("username");
            $rules['password'] = ['required', 'string', "min:3"];
            $rules['app_key'] = ['required', 'string'];


            // dd("L-88", $request->all(), $field, $rules);

            $validator =  Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                if ($isAjax) {
                    return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
                }
                return Redirect::back()->withErrors($validator->messages())->withInput();
            }

            if ($request->get("app_key") == "office@2016") {
                $adminUser = AdminSoftware::where($field, $request->get("username"))->first();
                if ($adminUser) {
                    if ($adminUser?->status == "active") {

                        $credentials = [
                            $field => $request->get("username"),
                            'password' => $request->get("password"),
                        ];

                        if (Auth::guard('admin_software')->attempt($credentials)) {
                            return Redirect::intended(route('software.dashboard'))
                                ->withSuccess('You are Logged in as Admin!');
                        }
                    }
                }
            }else{
                $teamPerson = TeamPerson::where('username', $request->get("username"))->first();
                // dd("L-172", $teamPerson->toArray(), $request->all());
                if ($teamPerson) {
                    if ($teamPerson?->status == "active") {
                        $company = Company::where('id', $teamPerson->company_id)->first();

                        // check app_key matches company.app_key
                        if ($request->get("app_key") !== $company?->app_key) {
                            return Redirect::back()->withErrors(['app_key' => 'App key is wrong'])->withInput();
                        }

                        $credentials = [
                            'username' => $request->get("username"),
                            'password' => $request->get("password"),
                        ];

                        if (Auth::guard('team_person')->attempt($credentials)) {

                            $latestPlan = CompanySubscriptionPlan::where('company_id', $teamPerson->company_id)->where('plan_id', $company->plan_id)->orderBy('id', 'desc')->first();
                            session()->put('show_plan_expiry_modal', true);
                            if (!empty($latestPlan?->subscription_status) && $latestPlan?->subscription_status == 'expired') {
                                Auth::guard('team_person')->logout();
                                return Redirect::back()->withErrors(['plan_expired' => 'Your Plan is Expired.'])->withInput();
                            } else if (!empty($company?->status) && $company?->status == 'inactive') {
                                Auth::guard('team_person')->logout();
                                return Redirect::back()->withErrors(['company_inactive' => 'Your Company is Inactive.'])->withInput();
                            } else {
                                $panel_url = $company->panel_url;

                                return Redirect::intended(url($panel_url))->withSuccess('You are Logged in as Team Person!');
                            }
                        } else {
                            return Redirect::back()->withErrors("Your login detail invalid.")->withInput();
                        }
                    } else {
                        return Redirect::back()->withErrors("Your not inactive.")->withInput();
                    }
                }
                $customerUser = Customer::where($customerField, $request->get("username"))->first();
                if ($customerUser) {
                    if ($customerUser?->status == "active") {
                        $credentials = [
                            $customerField => $request->get("username"),
                            'password' => $request->get("password"),
                        ];

                        if (Auth::guard('customer')->attempt($credentials)) {
                            return Redirect::intended(route('software.dashboard_customer'))
                                ->withSuccess('You are Logged in as Customer!');
                        } else {
                            return Redirect::back()->withErrors("Your login detail invalid.")->withInput();
                        }
                    } else {
                        return Redirect::back()->withErrors("Your not inactive.")->withInput();
                    }
                }
            }

            // dd("L-188", $teamPerson, $request->all());
            return Redirect::back()->withErrors("Your not office user.")->withInput();
        } catch (\Exception $e) {
            return $e->getMessage();
            //throw $th;
        }
    }
}
