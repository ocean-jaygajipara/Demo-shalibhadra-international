<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SaleExecutiveTracking;
use App\Models\TeamPerson;
use App\Models\TeamPersonMapCordinates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

class TrackingDashboardController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Tracking Dashboard',
            'folder_path' => 'software.tracking-dashboard.',
            // 'route' => 'tracking-dashboard',
            'route' => 'dashboard',
            // 'table_name' => (new ())->getTable(),
            'permisstion_prefix' => 'tracking-dashboard',
            'module_name' => 'Tracking Dashboard',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }
    public function tracking_dashboard()
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['module_name'] = 'Tracking Dashboard';

        try {
            $modules['module_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), $modules['module_name']]);
            $modules['module_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), $modules['module_name']]);
            $modules['module_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), $modules['module_name']]);

            if (!$modules['module_view']) {
                if (isset($request) && $request->ajax()) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                abort(403, 'Unauthorized');
            }
            View::share('modules', $modules);

            View::share('modules', $modules);

            //$main_menu = Helper::getModuleMenu();
            //return $main_menu;
            return view('software.tracking-dashboard');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function get_team_person_details(Request $request)
    {
        try {
            $team_person_id = $request->team_person_id;
            $date = date('Y-m-d', strtotime($request->date));
            $sync_pin = $request->sync_pin;


            $teamPersonDetails['team_person_name'] = "";
            $teamPersonDetails['team_person_contact'] = "";
            // $teamPersonDetails['app_version'] = "1.0.0";
            $teamPersonDetails['total_orders'] = 0;
            $teamPersonDetails['total_quotations'] = 0;
            $teamPersonDetails['total_visits'] = 0;
            $teamPersonDetails['total_expense_pass_amount'] = 0;
            $teamPersonDetails['total_expense_report_amount'] = 0;
            $teamPersonDetails['total_followups'] = 0;

            $data = [];


            $teamPerson = TeamPerson::where('id', $team_person_id)->first();

            if ($teamPerson) {
                $teamPersonDetails['team_person_name'] = $teamPerson->name;
                $teamPersonDetails['team_person_contact'] = $teamPerson->mobile_no;
                $teamPersonDetails['app_version'] = $teamPerson->app_version ?? "1.0.0";
            }

            // $type = ['', 'Login', 'Logout', 'Attendance In', 'Attendance Out', 'Tracking Sync', 'Create Order', 'Create Customer', 'Add Expense', 'Visit', 'Add Inquiry', 'Edit Inquiry', 'Delete Inquiry', 'Add Follow-Up', 'Add Leave', 'Attendance AutoOut', 'Add Quotation'];

            $type = SaleExecutiveTracking::SaleExecutiveTrackingTypes();

            $saleExecutiveTracking = SaleExecutiveTracking::query();
            $saleExecutiveTracking = $saleExecutiveTracking->where(function ($query) use ($sync_pin) {
                if ($sync_pin == 0) {
                    $query->where('type', '!=', 5);
                }
            });
            $saleExecutiveTracking = $saleExecutiveTracking->whereDate('created_at', $date);
            $saleExecutiveTracking = $saleExecutiveTracking->where('created_by', $team_person_id)->get();

            // return $saleExecutiveTracking;
            /*
            $saleExecutiveTracking = DB::table('sale_executive_tracking')
                ->whereDate('created_at', $date)
                ->where(function ($query) use ($sync_pin) {
                    if ($sync_pin == 0) {
                        $query->where('type', '!=', 5);
                    }
                })
                ->where('created_by', $team_person_id)
                ->get();
                */

            // return $saleExecutiveTracking;


            $pin_array = [];

            $total_orders = 0;
            $total_quotations = 0;
            $total_visits = 0;
            $total_followups = 0;

            foreach ($saleExecutiveTracking as &$saleRow) {

                if ($saleRow->type == 6) {
                    $total_orders++;
                }

                if ($saleRow->type == 16) {
                    $total_quotations++;
                }

                if ($saleRow->type == SaleExecutiveTracking::VISIT_END) {
                    $total_visits++;
                }

                if ($saleRow->type == SaleExecutiveTracking::FOLLOW_UP_ADD) {
                    $total_followups++;
                }

                $google_map_array['date'] = '';
                if (!empty($saleRow->current_datetime)) {
                    $google_map_array['date'] = date('d M Y h:i:s A', strtotime($saleRow->current_datetime));
                }

                $google_map_array['address'] = $saleRow->address;
                $google_map_array['title'] = '<b>' . $type[$saleRow->type] . '</b>';
                $google_map_array['type_id'] = $saleRow->type;
                $google_map_array['latitude'] = $saleRow->latitude;
                $google_map_array['longitude'] = $saleRow->longitude;

                $text_color = 'text-primary';

                if ($type[$saleRow->type] == 'Attendance In' || $type[$saleRow->type] == 'Login') {
                    $text_color = 'text-success';
                }

                if ($type[$saleRow->type] == 'Attendance Out' || $type[$saleRow->type] == 'Logout') {
                    $text_color = 'text-danger';
                }

                if ($type[$saleRow->type] == 'Tracking Sync') {
                    $text_color = 'text-dark';
                }

                $google_map_array['text_color'] = $text_color;

                if ($saleRow->type == 9) {
                    $customer_name = "Test";
                    $google_map_array['title'] = '<b>' . $type[$saleRow->type] . '</b><br><b>Customer Name:</b> ' . $customer_name;
                }

                $pin_array[] = $google_map_array;
            }

            $teamPersonDetails['total_orders'] = $total_orders;
            $teamPersonDetails['total_quotations'] = $total_quotations;
            $teamPersonDetails['total_visits'] = $total_visits;
            $teamPersonDetails['total_followups'] = $total_followups;


            $data['team_person_details'] = $teamPersonDetails;
            $data['pin_array'] = $pin_array;

            return $this->sendResponse($data, 'Team Person Details');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }


    /** Live Tracking Dashboard  */
    public function live_tracking_dashboard(Request $request)
    {

        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['module_name'] = 'Tracking Dashboard';

        try {
            $modules['module_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), $modules['module_name']]);
            $modules['module_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), $modules['module_name']]);
            $modules['module_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), $modules['module_name']]);

            if (!$modules['module_view']) {
                if (isset($request) && $request->ajax()) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                abort(403, 'Unauthorized');
            }
            View::share('modules', $modules);

            // return $modules;

            if ($request?->ajax()) {
                $teamPersonLiveCordinatase = TeamPersonMapCordinates::query();
                if ($request?->company_id) {
                    $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->where('company_id', $request->company_id);
                }

                if ($request?->date && $request?->date) {
                    // $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->whereDate('created_at', $request->date);
                }

                $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->orderBy('id', 'DESC');
                // $teamPersonLiveCordinatase = Helper::interpolateQuery($teamPersonLiveCordinatase->toSql(), $teamPersonLiveCordinatase->getBindings());
                $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->get();

                if ($teamPersonLiveCordinatase->count()) {
                    $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->unique('user_id');
                    $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->values();
                }

                $teamPersonLiveCordinatase = $teamPersonLiveCordinatase->map(function ($row) {
                    $temp = (object)[];
                    // $temp = $row;
                    $temp->id = $row?->id;
                    $temp->company_id = $row?->company_id;
                    $temp->user_id = $row?->user_id;
                    $temp->user_type = $row?->user_type;
                    $temp->latitude = $row?->latitude;
                    $temp->longitude = $row?->longitude;
                    $temp->address = $row?->address;
                    $temp->pin = $row?->pin;
                    $temp->platform = $row?->platform;
                    $temp->created_at = Helper::convert_date($row?->created_at, "Y-m-d H:i:s", "d-m-Y h:i A");


                    // $temp->user = $row?->user;
                    $temp->user_name = null;
                    if ($row?->user_type == "team_person" && $row?->user) {
                        $temp->username = $row?->user?->username;
                        $temp->user_name = $row?->user?->name;
                        $temp->user_status = $row?->user?->status;
                        $temp->user_employee_code = $row?->user?->employee_code;
                        $temp->user_email = $row?->user?->email;
                    }
                    return $temp;
                });


                return $this->sendResponse($teamPersonLiveCordinatase, 'Team Person Live Pin');
            }

            //$main_menu = Helper::getModuleMenu();
            //return $main_menu;
            return view('software.dashboard-live-tracking');
        } catch (\Exception $e) {
            if ($request?->ajax()) {
                return $this->sendError($e->getMessage(), $e->getMessage(), $e, 404);
            }
            return $e->getMessage();
        }
    }
}
