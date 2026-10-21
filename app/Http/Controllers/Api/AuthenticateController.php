<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\TeamPerson;
use App\Models\UserApplicationDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Passport\Token;

class AuthenticateController extends Controller
{
    public function __construct() {}

    public function verify_appkey(Request $request)
    {
        try {

            $validator =  Validator::make($request->all(), [
                'app_key' => [
                    'required',
                    Rule::exists((new Company())->getTable(), 'app_key')
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $data = Company::with(['company_details']);
            $data = $data->where('app_key', $request?->app_key);
            $data = $data->orderBy('id', 'DESC');
            $data = $data->first();


            $company_data = [];
            // $company_data = $data;

            $company_data['id'] = $data?->id . '';
            $company_data['app_key'] = $data?->app_key . '';
            $company_data['company_name'] = $data?->company_name . '';
            $company_data['app_logo'] = (isset($data?->app_logo)) . '' ? $data?->app_logo . '' : config('constants.app.logo');
            $company_data['app_logo_url'] = (isset($data?->app_logo_url)) . '' ? $data?->app_logo_url . '' : asset(config('constants.app.logo'));
            $company_data['status_bar_color'] = $data?->company_details?->status_bar_color . '' ? $data?->company_details?->status_bar_color :  config("constants.app.theme_primary_color");
            $company_data['title_name_color'] = $data?->company_details?->title_name_color . '' ? $data?->company_details?->title_name_color . '' : config("constants.app.theme_primary_color");
            $company_data['all_icon_color'] = $data?->company_details?->all_icon_color . '' ? $data?->company_details?->all_icon_color . '' : config("constants.app.theme_primary_color_light");
            $company_data['edittext_title_color'] = $data?->company_details?->edittext_title_color . '' ? $data?->company_details?->edittext_title_color . '' : config("constants.app.theme_primary_color_light");
            $company_data['screen_background_light_color'] = $data?->company_details?->screen_background_light_color . '' ? $data?->company_details?->screen_background_light_color . '' :  config("constants.app.theme_primary_color_light");
            $company_data['screen_background_dark_color'] = $data?->company_details?->screen_background_dark_color . '' ? $data?->company_details?->screen_background_dark_color . '' : config("constants.app.theme_primary_color");
            $company_data['all_screen_header_color'] = $data?->company_details?->all_screen_header_color . '' ? $data?->company_details?->all_screen_header_color . '' : config("constants.app.theme_primary_color");
            $company_data['all_screen_back_back_arrow_background_color'] = $data?->company_details?->all_screen_back_back_arrow_background_color . '' ? $data?->company_details?->all_screen_back_back_arrow_background_color . '' : config("constants.app.theme_primary_color");
            $company_data['all_screen_back_back_arrow_color'] = $data?->company_details?->all_screen_back_back_arrow_color . '' ? $data?->company_details?->all_screen_back_back_arrow_color . '' : config("constants.app.theme_primary_color");
            $company_data['data_list_border_color'] = $data?->company_details?->all_screen_back_back_arrow_color . '' ? $data?->company_details?->all_screen_back_back_arrow_color . '' : config("constants.app.theme_primary_color");
            $company_data['login_text_color_1'] = $data?->company_details?->login_text_color_1 . '' ? $data?->company_details?->login_text_color_1 . '' : config("constants.app.theme_primary_color");
            $company_data['login_text_color_2'] = $data?->company_details?->login_text_color_2 . '' ? $data?->company_details?->login_text_color_2 . '' : config("constants.app.theme_primary_color");
            $company_data['background_shape_1'] = $data?->company_details?->background_shape_1 . '' ? $data?->company_details?->background_shape_1 . '' : config("constants.app.theme_primary_color");
            $company_data['background_shape_2'] = $data?->company_details?->background_shape_2 . '' ? $data?->company_details?->background_shape_2 . '' : config("constants.app.theme_primary_color");
            $company_data['background_shape_3'] = $data?->company_details?->background_shape_3 . '' ? $data?->company_details?->background_shape_3 . '' : config("constants.app.theme_primary_color");
            $company_data['extra_color_1'] = $data?->company_details?->extra_color_1 . '' ? $data?->company_details?->extra_color_1 . '' : config("constants.app.theme_primary_color");
            $company_data['extra_color_2'] = $data?->company_details?->extra_color_2 . '' ? $data?->company_details?->extra_color_2 . '' : config("constants.app.theme_primary_color");
            $company_data['extra_color_3'] = $data?->company_details?->extra_color_3 . '' ? $data?->company_details?->extra_color_3 . '' : config("constants.app.theme_primary_color");

            return $this->sendResponse($company_data, "Key Verify Successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong.");
    }

    public function login(Request $request)
    {
        try {

            $validator =  Validator::make($request->all(), [
                'app_key' => [
                    'required',
                    function ($attribute, $value, $fail) {

                        // current_latest_plan
                        $company = Company::where('app_key', $value)
                            ->where('status', 'active')
                            // ->whereDate('plan_to', '>=', Carbon::today())
                            ->first();

                        if (!$company) {
                            $fail('The selected app_key is invalid or the company is inactive.');
                        }
                        $current_latest_plan = $company?->current_latest_plan;

                        if (!$current_latest_plan) {
                            $fail('The selected company subscription plan not found.');
                        }

                        $companyCurrentPlanExpiryDate = Helper::convert_date($current_latest_plan?->plan_expiry_date, "Y-m-d H:i:s", "Ymd") . "5959";
                        $now = Carbon::now()->format('YmdHi');
                        if ((int)$now >= (int)$companyCurrentPlanExpiryDate) {
                            $fail('The selected company subscription plan has expired.');
                        }
                    },
                ],
                'username' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $exists = TeamPerson::where(function ($query) use ($value) {
                            $query->where('username', $value)
                                ->orWhere('email', $value)
                                ->orWhere('mobile_no', $value);
                        })
                            ->exists();

                        if (!$exists) {
                            $fail('The provided username is not found.');
                        }
                    },
                ],
                'password' => ['required', 'string'], // Add your password rules as needed (e.g. min:6)
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $field = "username";
            if (is_numeric($request->get("username"))) {
                $field = "mobile_no";
            } else if (filter_var($request->get("username"), FILTER_VALIDATE_EMAIL)) {
                $field = "email";
            }

            // Step 1: Check the Team Person
            $teamPerson = TeamPerson::where($field, $request?->username)->first();
            if ($teamPerson) {
                // Step 2: Check password
                if (!$teamPerson || !Hash::check($request->password, $teamPerson->password)) {
                    return $this->sendError("Invalid credentials.", [], [], 401);
                }

                /*if (env("APP_URL") == "https://ocean-crm.oceaninfotechcrm.com/") {
                    // Revoke all previous tokens for this user
                    Token::where('user_id', $teamPerson->id)
                        // ->where('name', 'StudentToken') // important if using custom guard/model
                        ->where('name', env("APP_NAME")) // important if using custom guard/model
                        ->update(['revoked' => true]);
                }*/

                $login_profile = [];

                // $login_profile =  $teamPerson;

                // Step 3: Generate access token
                $token = $teamPerson->createToken(env("APP_NAME"))->accessToken;

                $newRequest = new Request();
                $newRequest['id'] = $teamPerson?->id;
                // $newRequest['directReturn'] = true;
                $login_profile = (array)self::get_profile_response($newRequest);

                $login_profile['token'] = $token;

                /** Store Device Detail */
                if ($request?->device_brand && isset($request?->device_brand)) {
                    $tbl_model = 'team_person';

                    // $userDeviceDetail = UserApplicationDetail::query()->where('tbl_model', $tbl_model)->where('tbl_model_id', $teamPerson?->id)->first();
                    $userDeviceDetail = [];
                    if (!$userDeviceDetail) {
                        // $userDeviceDetail = new UserApplicationDetail();
                        $userDeviceDetail['tbl_model'] = $tbl_model;
                        $userDeviceDetail['tbl_model_id'] = $teamPerson?->id;
                        $userDeviceDetail['created_by'] = $teamPerson?->id;
                    } else {
                        $userDeviceDetail['updated_by'] = $teamPerson?->id;
                    }
                    $userDeviceDetail['device_brand'] = $request?->device_brand;
                    $userDeviceDetail['device_model'] = $request?->device_model;
                    $userDeviceDetail['device_id'] = $request?->device_id;
                    $userDeviceDetail['device_sdk'] = $request?->device_sdk;
                    $userDeviceDetail['device_version_code'] = $request?->device_version_code;
                    $userDeviceDetail['device_host'] = $request?->device_host;
                    $userDeviceDetail['device_serial'] = $request?->device_serial;
                    // $userDeviceDetail->save();
                    UserApplicationDetail::updateOrCreate([
                        'tbl_model' => $tbl_model,
                        'tbl_model_id' => $teamPerson?->id,
                    ], $userDeviceDetail);
                }

                return $this->sendResponse($login_profile, "You are login successfully.");
            }
            return $this->sendError("This user name not found");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong.");
    }

    public function get_profile(Request $request)
    {
        try {
            // $loginUser = $request->user();
            $loginUser = Auth::user();
            if (!$loginUser) {
                return $this->sendError("Unauthorization", [], [], 401);
            }

            $newRequest = new Request();
            $newRequest['id'] = $loginUser?->id;

            $responseData = self::get_profile_response($newRequest);

            // dd(59, $responseData);

            return $this->sendResponse($responseData, 'Get Profile successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 201);
        }
        return $this->sendError("Something want to wrong in Get Profile API", [], [], 201);
    }

    public function get_profile_response(Request $request)
    {
        try {

            $responseData = [];

            $userDetail = TeamPerson::where('id', $request?->id)->first();
            if (!$userDetail && $request?->directReturn) {
                return (object)$responseData;
            }

            // $responseData['userDetail'] = $userDetail;
            // return $userDetail;
            // return $userDetail->toArray();
            $responseData['id'] = $userDetail?->id . '';
            $responseData['company_id'] = $userDetail?->company_id . '';
            $responseData['parent_type_id'] = $userDetail?->parent_type_id . '';
            $responseData['name'] = $userDetail?->name . '';
            $responseData['email'] = $userDetail?->email . '';
            $responseData['mobile_no'] = $userDetail?->mobile_no . '';
            $responseData['employee_code'] = $userDetail?->employee_code . '';
            $responseData['username'] = $userDetail?->username . '';
            $responseData['per_minutes_request_current_location'] = $userDetail?->company_details?->per_minutes_request_current_location . '';
            $responseData['min_working_start_time'] = $userDetail?->min_working_start_time . '';
            $responseData['max_working_start_time'] = $userDetail?->max_working_start_time . '';
            $responseData['working_end_time'] = $userDetail?->working_end_time . '';
            $responseData['second_half_time'] = Carbon::createFromTimeString($userDetail?->min_working_start_time)->addHours(4)->format('H:i:s') . '';

            $responseData['address'] = $userDetail?->address . '';
            $responseData['country_id'] = $userDetail?->country_id . '';
            $responseData['state_id'] = $userDetail?->state_id . '';
            $responseData['state_id'] = $userDetail?->state_id . '';
            $responseData['city_id'] = $userDetail?->city_id . '';
            $responseData['area_id'] = $userDetail?->area_id . '';

            $responseData['country_name'] = $userDetail?->country?->name ?? "";
            $responseData['state_name'] = $userDetail?->state?->name ?? "";
            $responseData['city_name'] = $userDetail?->city?->name ?? "";
            $responseData['area_name'] = $userDetail?->area?->area_name ?? "";

            $responseData['birth_date'] = $userDetail?->birth_date . '';

            $responseData['designation_id'] = $userDetail?->designation_id . '';
            $responseData['designation_name'] = $userDetail?->designation?->name . '';

            $responseData['team_role_id'] = $userDetail?->team_role_id . '';
            $responseData['team_role_name'] = $userDetail?->team_role?->name . '';

            $responseData['app_version'] = $userDetail?->app_version . '';
            $responseData['status'] = $userDetail?->status . '';

            $userDirectPermissions = [];
            /*
            $userDirectPermissions = $userDetail->getDirectPermissions();
            if (count($userDirectPermissions) == 0) {
                $userDirectPermissions = $userDetail->getPermissionsViaRoles();
            }
            $userDirectPermissions = $userDirectPermissions->map(function ($record) {
                $temp = [];
                // $temp = $record;

                $temp['group'] = $record?->group ?? "";
                $temp['name'] = $record?->name ?? "";
                return $temp;
            });
            */
            $responseData['module_permissions'] = $userDirectPermissions;

            if ($userDetail?->user_detail) {
                $user_detail = $userDetail?->user_detail->toArray();
                if (gettype($user_detail) == "array") {
                    // $responseData = array_merge($responseData, $user_detail);
                    foreach ($user_detail as $key => $value) {
                        $responseData[$key] = $value . "";
                    }
                }
                // $responseData['user_detail'] = $userDetail?->user_has_detail;
            }
            return (object)$responseData;
        } catch (\Exception $e) {
            return (object)[$e->getMessage()];
        }
        return (object)[];
    }

    public function update_profile(Request $request)
    {
        try {
            $loginUser = Auth::user();
            if (!$loginUser) {
                return $this->sendError("Unauthorization", [], [], 401);
            }

            $validator =  Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                ],
                'email' => [
                    'required',
                    'email',
                    Rule::unique((new TeamPerson())->getTable())->ignore($loginUser->id),
                ],
                'mobile_no' => [
                    'required',
                    'string',
                    Rule::unique((new TeamPerson())->getTable())->ignore($loginUser->id),
                ],
                'address' => [
                    'required',
                    'string',
                ],
                'country_id' => [
                    'required',
                    'string',
                    Rule::exists((new MasterCountry())->getTable(), 'id')
                ],
                'state_id' => [
                    'required',
                    'string',
                    Rule::exists((new MasterState())->getTable(), 'id')
                ],
                'city_id' => [
                    'required',
                    'string',
                    Rule::exists((new MasterCity())->getTable(), 'id')
                ],
                'area_id' => [
                    'required',
                    'string',
                    Rule::exists((new MasterArea())->getTable(), 'id')
                ],
                'birth_date' => [
                    'required',
                    'string',
                    'date_format:Y-m-d',
                    'before:today'
                ],
                'password' => ['nullable', 'string', 'min:5'], // Add your password rules as needed (e.g. min:6)
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $responseData = [];
            $updateParams = $request->all();
            $updateTeamPerson = TeamPerson::where('id', $loginUser?->id)->first();

            /*
            if($request?->email){ $updateTeamPerson['email'] = $request?->email; }
            if($request?->mobile_no){ $updateTeamPerson['mobile_no'] = $request?->mobile_no; }
            if($request?->address){ $updateTeamPerson['address'] = $request?->address; }
            if($request?->country_id){ $updateTeamPerson['country_id'] = $request?->country_id; }
            if($request?->state_id){ $updateTeamPerson['state_id'] = $request?->state_id; }
            if($request?->city_id){ $updateTeamPerson['city_id'] = $request?->city_id; }
            if($request?->area_id){ $updateTeamPerson['area_id'] = $request?->area_id; }
            if($request?->birth_date){ $updateTeamPerson['birth_date'] = $request?->birth_date; }
            */
            if (!$request?->password && !empty($request?->password) && $request?->password != null) {
                $updateParams['password'] = Hash::make($request?->password);
            } else {
                unset($updateParams['password']);
            }
            // return $updateParams;
            $updateTeamPerson->update($updateParams);

            $newRequest = new Request();
            $newRequest['id'] = $loginUser?->id;
            $login_profile = (array)self::get_profile_response($newRequest);

            return $this->sendResponse($login_profile, "Update profile successfully.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong.");
    }
}
