<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\DispatchItems;
use App\Models\Followup;
use App\Models\Company;
use App\Models\MainMenu;
use App\Models\PackingSlipItem;
use App\Models\SalesStatus;
use App\Models\Product;
use App\Models\MarketingStatus;
use App\Models\ProductStockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SubMenu;
use App\Models\TeamPerson;
use App\Models\AdminSoftware;
use App\Models\CompanySubscriptionPlan;
use App\Models\PackingSlip;
use App\Models\PlanMaster;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Models\ProductCombo;

class DashboardController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();
        // You can add middleware here if needed
        $this->modules = [
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
            'route' => 'dashboard',
        ];
    }

    public function sidebar_menu(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            if ($modules['currentGuard'] != "admin_software") {
                abort(403, 'Unauthorized');
            }

            if ($request?->_token) {
                // dd("L-60", $request->all());
                $validateData = Validator::make($request->all(), [
                    'platform' => ['required', 'in:panel,app'],
                    'main_menu_id' => ['nullable', Rule::exists((new MainMenu())->getTable(), 'id')],
                    'name' => ['required'],
                    'menu_icon' => ['nullable'],
                    'status' => ['required', 'in:active,inactive'],
                ]);

                if ($validateData->fails()) {
                    return Redirect::back()->withErrors($validateData)->withInput();
                }

                // Add Main Menu
                if (!$request?->main_menu_id) {
                    $validateFormData = $validateData->validated();
                    $validateFormData['order_by'] = $request?->order_by;

                    $mainMenu = new MainMenu();
                    if ($request->edit_id) {
                        $mainMenu = MainMenu::where('id', $request->edit_id)->first();
                        $validateFormData['updated_by'] = $loginUserId;
                        if (!$mainMenu) {
                            return Redirect::back()->withErrors("Main menu not found.")->withInput();
                        }
                        $mainMenu->update($validateFormData);
                    } else {
                        $validateFormData['created_by'] = $loginUserId;
                        $mainMenu->create($validateFormData);
                    }

                    /*
                    if ($request?->order_by && $request?->order_by != $mainMenu?->order_by) {
                        $orderBy = (int)$request?->order_by;
                        $updateOrders = MainMenu::where('order_by', ">=", $orderBy)->get();
                        // dd("L-89", $request->all(), $mainMenu->toArray(), $updateOrders, $orderBy);
                        foreach ($updateOrders as $key => $value) {
                            $updateOrderBy = MainMenu::where('id', $value?->id)->first();
                            if($updateOrderBy){
                                $updateOrderBy->order_by = $orderBy;
                                $updateOrderBy->save();
                                $orderBy = $orderBy+1;
                            }
                        }
                    }
                        */

                    Session::flash('success', "Main menu update successfully.");
                    return Redirect::route('sidebar.menu');
                    dd("L-94", $request->all(), $mainMenu->toArray());
                }
                // Add Sub Main Menu
                else if ($request?->main_menu_id) {
                    $validateFormData = $validateData->validated();
                    $validateFormData['main_menu_id'] = $request?->main_menu_id;
                    $validateFormData['route_name'] = $request?->route_name;
                    $validateFormData['url'] = $request?->url;
                    if ($request?->order_by && !empty($request?->order_by)) {
                        $validateFormData['order_by'] = $request?->order_by;
                    }
                    // dd("L-127", $request->all(), $validateFormData);

                    $subMenu = new SubMenu();
                    if ($request->edit_id) {
                        // ->where('main_menu_id', $request->main_menu_id)
                        $subMenu = SubMenu::where('id', $request->edit_id)->first();
                        $validateFormData['updated_by'] = $loginUserId;
                        if (!$subMenu) {
                            return Redirect::back()->withErrors("Sub menu not found.")->withInput();
                        }
                        $subMenu->update($validateFormData);
                    } else {
                        $validateFormData['created_by'] = $loginUserId;
                        $subMenu->create($validateFormData);
                    }

                    /*
                    if ($request?->order_by && $request?->order_by != $subMenu?->order_by) {
                        $orderBy = (int)$request?->order_by;
                        $updateOrders = SubMenu::where('main_menu_id', $request->main_menu_id)->where('order_by', ">=", $orderBy)->get();
                        foreach ($updateOrders as $key => $value) {
                            $updateOrderBy = SubMenu::where('id', $value?->id)->first();
                            if($updateOrderBy){
                                $updateOrderBy->order_by = $orderBy;
                                $updateOrderBy->save();
                                $orderBy = $orderBy+1;
                            }
                        }
                        // dd("L-126", $request->all(), $subMenu->toArray(), $updateOrders);
                    }
                    */

                    Session::flash('success', "Sub menu update successfully.");
                    return Redirect::route('sidebar.menu');

                    dd("L-70", $request->all(), $validateData->failed());
                }
                dd("L-146", $request->all(), $validateData->failed());
            }

            $orderBy = ['platform' => 'desc'];
            $module_menu = \App\Helpers\Helper::getModuleMenu('all', $wheres = [], $orderBy);
            View::share('module_menu', $module_menu);
            // return $module_menu;

            return view('software.sidebar-menu-view');

            $request->all();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function sidebar_menu_delete(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            if ($modules['currentGuard'] != "admin_software") {
                abort(403, 'Unauthorized');
            }
            $validateData = Validator::make($request->all(), [
                'delete_id' => ['required'],
                'model' => ['required', 'in:sub_menu,main_menu'],
                'main_menu_id' => ['required_if:model,sub_menu'],
            ]);

            if ($validateData->fails()) {
                return Redirect::back()->withErrors($validateData)->withInput();
            }

            if ($request?->model == "main_menu") {
                $deletedMenu = MainMenu::where('id', $request?->delete_id)->first();
                if ($deletedMenu) {
                    $deletedMenu->update([
                        'deleted_by' => $loginUserId,
                        'deleted_at' => Carbon::now(),
                    ]);
                    $deletedMenu->delete();
                    return $this->sendResponse([], $deletedMenu?->name . " Main menu deleted successfully.");
                } else {
                    return $this->sendError('This sub menu not found. contact to develoer.', ['This sub menu not found. contact to develoer.']);
                }
            } else if ($request?->model == "sub_menu") {
                $deletedMenu = SubMenu::where('id', $request?->delete_id)->where('main_menu_id', $request?->main_menu_id)->first();
                if ($deletedMenu) {
                    $deletedMenu->update([
                        'deleted_by' => $loginUserId,
                        'deleted_at' => Carbon::now(),
                    ]);
                    $deletedMenu->delete();
                    return $this->sendResponse([], $deletedMenu?->name . " Sub menu deleted successfully.");
                } else {
                    return $this->sendError('This sub menu not found. contact to develoer.', ['This sub menu not found. contact to develoer.']);
                }
            }
            return $this->sendError('Your Menu type not found.', $request->all());
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Some thing want to wrong.");
    }

    public function dashboard(Request $request)
    {
        $modules = $this->modules;
        $modules['module_name'] = 'Dashboard';
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = isset($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails->parent_type_id : null;
        $loginUser = $modules['authLoginUserDetail'];
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            /** Inquiry Module Permmission */
            $modules['inquiry_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Inquiry']);
            $modules['inquiry_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Inquiry']);
            $modules['inquiry_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Inquiry']);

            $modules['followup_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Follow Up']);
            $modules['followup_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Follow Up']);
            $modules['followup_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Follow Up']);

            $modules['customer_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Customer']);
            $modules['customer_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Customer']);
            $modules['customer_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Customer']);

            $modules['visit_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Visit']);
            $modules['visit_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Visit']);
            $modules['visit_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Visit']);

            $modules['order_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Sales Orders']);
            $modules['order_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Sales Orders']);
            $modules['order_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Sales Orders']);

            $modules['dispatch_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Dispatch']);
            $modules['dispatch_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Dispatch']);
            $modules['dispatch_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Dispatch']);


            $modules['sales_order_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Sales Orders']);
            $modules['sales_order_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Sales Orders']);
            $modules['sales_order_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Sales Orders']);


            $modules['product_stock_history_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Product Stock']);
            $modules['product_stock_history_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Product Stock']);
            $modules['product_stock_history_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Product Stock']);

            // return $modules;
            View::share('modules', $modules);
            // dd("L-266", $modules, $this->currentGuard, $modules['currentGuard']);

            $loginType = $modules['company_id'] ? 'team' : 'admin';
            View::share('loginType', $loginType);

            if ($request->ajax()) {
                ($request->company_id) ? $modules['company_id'] = $request->company_id : null;

                $startOfMonth = (isset($request->start_date) && $request?->start_date) ? $request->input('start_date') : null;
                $endOfMonth = (isset($request->end_date) && $request?->end_date) ? $request->input('end_date') : null;

                if ($request?->filter_by_daterange && !$startOfMonth && !$endOfMonth) {
                    $filter_by_daterange = str_replace(' - ', ' to ', $request->filter_by_daterange);
                    $filter_by_daterange = explode(" to ", $filter_by_daterange);
                    $fromDate = null;
                    $toDate = null;

                    if (count($filter_by_daterange) == 2) {
                        $startOfMonth = Helper::convert_date($filter_by_daterange[0], "d/m/Y", "Y-m-d");
                        $endOfMonth = Helper::convert_date($filter_by_daterange[1], "d/m/Y", "Y-m-d");
                    }
                } else {
                    $startOfMonth = Carbon::now()->startOfMonth();
                    $endOfMonth = Carbon::now()->endOfMonth();
                }
                $dateRangeArray = [$startOfMonth, $endOfMonth];

                $marketingStatus = [];
                $showNumericModule = [];

                /** inquiry_view permission after show this design */
                if ($modules['inquiry_view']) {
                    $marketingStatus = MarketingStatus::withCount([
                        'inquiries as inquiries_count' => function ($query) use ($modules, $loginUserId, $startOfMonth, $endOfMonth) {
                            if (isset($modules['company_id']) && $modules['company_id']) {
                                $query->where('company_id', $modules['company_id']);
                            }

                            if ($modules['inquiry_personal_data'] && $modules['inquiry_all_data'] == false) {
                                $query->where('created_by', $loginUserId);
                            }

                            if ($startOfMonth && $endOfMonth) {
                                $query->whereDate('inquiry_date', ">=", $startOfMonth);
                                $query->whereDate('inquiry_date', "<=", $endOfMonth);
                            } else if ($startOfMonth) {
                                $query->where('inquiry_date', $startOfMonth);
                            } else if ($endOfMonth) {
                                $query->where('inquiry_date', $endOfMonth);
                            }
                        }
                    ]);
                    $marketingStatus = $marketingStatus->where('type', 'Inquiry')->where('status', 'active');


                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $marketingStatus = $marketingStatus->where('company_id', $modules['company_id']);
                    } else {
                        $marketingStatus = $marketingStatus->where('id', 0);
                    }
                    $marketingStatus = $marketingStatus->get();
                    // dd("L-332",$marketingStatus);

                    $totalInquiry = Inquiry::query();
                    if ($startOfMonth && $endOfMonth) {
                        $totalInquiry->whereDate('inquiry_date', ">=", $startOfMonth);
                        $totalInquiry->whereDate('inquiry_date', "<=", $endOfMonth);
                    } else if ($startOfMonth) {
                        $totalInquiry->where('inquiry_date', $startOfMonth);
                    } else if ($endOfMonth) {
                        $totalInquiry->where('inquiry_date', $endOfMonth);
                    }

                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $totalInquiry = $totalInquiry->where('company_id', $modules['company_id']);
                    }

                    if ($modules['inquiry_personal_data'] && !$modules['inquiry_all_data']) {
                        $totalInquiry->where('created_by', $loginUserId);
                    }
                    $showNumericModule['totalInquiry'] = $totalInquiry->count();
                }

                /** Follow up Module permission exist then show this box */
                if ($modules['followup_view']) {
                    $totalFollowups = Followup::query();

                    if ($startOfMonth && $endOfMonth) {
                        $totalFollowups->whereDate('date_time', ">=", $startOfMonth);
                        $totalFollowups->whereDate('date_time', "<=", $endOfMonth);
                    } else if ($startOfMonth) {
                        $totalFollowups->where('date_time', $startOfMonth);
                    } else if ($endOfMonth) {
                        $totalFollowups->where('date_time', $endOfMonth);
                    }

                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $totalFollowups = $totalFollowups->where('company_id', $modules['company_id']);
                    }

                    if ($modules['followup_personal_data'] && !$modules['followup_all_data']) {
                        $totalFollowups->where('created_by', $loginUserId);
                    }

                    $pendingFollowUp = $totalFollowups->clone();
                    $pendingFollowUp = $pendingFollowUp->whereNull((new Followup())->getTable() . '.response_date_time');

                    $responseAddedFollowUp = $totalFollowups->clone();
                    $responseAddedFollowUp = $responseAddedFollowUp->whereNotNull((new Followup())->getTable() . '.response_date_time');
                    // dd("L-160", Helper::interpolateQuery($totalFollowups->toSql(), $totalFollowups->getBindings()), $pendingFollowUp->get()->toArray(), $pendingFollowUp->count());
                    $showNumericModule['totalFollowups'] = $totalFollowups->count();
                    $showNumericModule['totalPendingFollowUp'] = $pendingFollowUp->count();
                    $showNumericModule['totalResponseAddedFollowUp'] = $responseAddedFollowUp->count();
                }

                /** Customer Module Permission exist then show this box */
                if ($modules['customer_view']) {
                    $totalCustomer = Customer::query();

                    if ($startOfMonth && $endOfMonth) {
                        $totalCustomer->whereDate('created_at', ">=", $startOfMonth);
                        $totalCustomer->whereDate('created_at', "<=", $endOfMonth);
                    } else if ($startOfMonth) {
                        $totalCustomer->where('created_at', $startOfMonth);
                    } else if ($endOfMonth) {
                        $totalCustomer->where('created_at', $endOfMonth);
                    }

                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $totalCustomer = $totalCustomer->where('company_id', $modules['company_id']);
                    }

                    if ($modules['customer_personal_data'] && !$modules['customer_all_data']) {
                        $totalCustomer->where('created_by', $loginUserId);
                    }

                    $showNumericModule['totalCustomer'] = $totalCustomer->count();
                }

                /** Visit Module Permission exist then show this box */
                if ($modules['visit_view']) {
                    $totalVisit = Visit::query();

                    if ($startOfMonth && $endOfMonth) {
                        $totalVisit->whereDate('created_at', ">=", $startOfMonth);
                        $totalVisit->whereDate('created_at', "<=", $endOfMonth);
                    } else if ($startOfMonth) {
                        $totalVisit->where('created_at', $startOfMonth);
                    } else if ($endOfMonth) {
                        $totalVisit->where('created_at', $endOfMonth);
                    }

                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $totalVisit = $totalVisit->where('company_id', $modules['company_id']);
                    }

                    if ($modules['visit_personal_data'] && !$modules['visit_all_data']) {
                        $totalVisit->where('created_by', $loginUserId);
                    }

                    $showNumericModule['totalVisit'] = $totalVisit->count();
                }

                /** Orders Module Permission exist then show this box */
                if ($modules['order_view']) {
                    $totalOrder = SalesOrder::query();

                    if ($startOfMonth && $endOfMonth) {
                        $totalOrder->whereDate('sales_order_date', ">=", $startOfMonth);
                        $totalOrder->whereDate('sales_order_date', "<=", $endOfMonth);
                    } else if ($startOfMonth) {
                        $totalOrder->where('sales_order_date', $startOfMonth);
                    } else if ($endOfMonth) {
                        $totalOrder->where('sales_order_date', $endOfMonth);
                    }

                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $totalOrder = $totalOrder->where('company_id', $modules['company_id']);
                    }

                    if ($modules['order_personal_data'] && !$modules['order_all_data']) {
                        $totalOrder->where('created_by', $loginUserId);
                    }

                    $showNumericModule['totalOrder'] = $totalOrder->count();
                    $showNumericModule['totalOrderAmount'] = $totalOrder->sum('grand_total');
                    // $showNumericModule['totalOrderAmountSql'] = Helper::interpolateQuery($totalOrder->toSql(), $totalOrder->getBindings());
                }

                /** Dispatch Module Permission exist then show this box */
                if ($modules['dispatch_view']) {
                    $totalDispatch = Dispatch::query();
                    $totalDispatchItemQuantity = DispatchItems::query();

                    if ($startOfMonth && $endOfMonth) {
                        $totalDispatch->whereDate('dispatch_date', ">=", $startOfMonth);
                        $totalDispatch->whereDate('dispatch_date', "<=", $endOfMonth);

                        $totalDispatchItemQuantity->whereDate('created_at', ">=", $startOfMonth);
                        $totalDispatchItemQuantity->whereDate('created_at', "<=", $endOfMonth);
                    } else if ($startOfMonth) {
                        $totalDispatch->where('dispatch_date', $startOfMonth);

                        $totalDispatchItemQuantity->where('created_at', $startOfMonth);
                    } else if ($endOfMonth) {
                        $totalDispatch->where('dispatch_date', $endOfMonth);
                        $totalDispatchItemQuantity->where('created_at', $endOfMonth);
                    }

                    if (isset($modules['company_id']) && $modules['company_id']) {
                        $totalDispatch = $totalDispatch->where('company_id', $modules['company_id']);
                    }

                    // if ($modules['dispatch_personal_data'] && !$modules['dispatch_all_data']) {
                    //     $totalDispatch->where('created_by', $loginUserId);
                    // }

                    $showNumericModule['totalDispatch'] = $totalDispatch->count();

                    if ($totalDispatch->count()) {
                        $totalDispatchItemQuantity->whereIn('dispatch_id', $totalDispatch->get()->pluck('id'));
                    } else {
                        $totalDispatchItemQuantity->where('dispatch_id', 0);
                    }
                    $showNumericModule['totalDispatchItemQuantity'] = $totalDispatchItemQuantity->sum('dispatch_quantity');
                    // $showNumericModule['totalDispatchItemQuantity'] = Helper::interpolateQuery($totalDispatchItemQuantity->toSql(), $totalDispatchItemQuantity->getBindings());
                }

                $companies = Company::get();
                $inactiveCompanies = Company::where('status', 'inactive')->count();

                $expiredCompanies = $companies->filter(function ($row) {
                    $latestPlan = CompanySubscriptionPlan::where('company_id', $row->id)
                        ->where('plan_id', $row->plan_id)
                        ->orderBy('id', 'desc')
                        ->first();

                    return $latestPlan && $latestPlan?->subscription_status === 'expired';
                })->count();
                $showNumericModule['totalCompanies'] = $companies->count();
                $showNumericModule['expiredCompanies'] = $expiredCompanies;
                $showNumericModule['inactiveCompanies'] = $inactiveCompanies;

                $totalPlans = PlanMaster::count();
                $inactivePlans = PlanMaster::where('status', 'inactive')->count();
                $generalPlans = PlanMaster::where('plan_type', 'general')->count();
                $privatePlans = PlanMaster::where('plan_type', 'private')->count();

                $showNumericModule['totalPlans'] = $totalPlans;
                $showNumericModule['inactivePlans'] = $inactivePlans;
                $showNumericModule['generalPlans'] = $generalPlans;
                $showNumericModule['privatePlans'] = $privatePlans;


                $returnResponse = [];
                // dd("showNumericModule 413", $showNumericModule);
                $statisticsHtml = view('software._utils.dashboarad_inquiry_statistics', compact(
                    'request',
                    'marketingStatus',
                    'showNumericModule'
                ))->render();

                $returnResponse['inquiryStatistics'] = $statisticsHtml;
                $returnResponse['showNumericModule'] = $showNumericModule;

                // Get minimum stock products
                $minimumStockProducts = $this->getMinimumStockProducts($modules, $loginUserId);
                $returnResponse['minimumStockProducts'] = $minimumStockProducts;

                return $this->sendResponse($returnResponse, 'Inquiry Statistics updated');

                dd("L-110 Dashboard Ajax");
            }
        } catch (\Exception $e) {
            abort(403, $e?->getMessage());
        }
        return view('software.dashboard');
    }



    public function getChartData(Request $request)
    {
        $loginUser = Auth::user();
        $companyId = $loginUser->company_id ?? null;
        $loginType = $companyId ? 'team' : 'admin';

        $now = Carbon::now();
        $currentYear = $now->year;
        $currentMonth = $now->month;

        if ($currentMonth < 4) {
            $startDate = Carbon::create($currentYear - 1, 4, 1)->startOfMonth();
            $endDate = Carbon::create($currentYear, 3, 31)->endOfMonth();
        } else {
            $startDate = Carbon::create($currentYear, 4, 1)->startOfMonth();
            $endDate = Carbon::create($currentYear + 1, 3, 31)->endOfMonth();
        }

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = $startDate->copy()->addMonths($i)->format('M');
        }

        // Helper for fetching monthly data
        $getMonthlyData = function ($model, $type = 'count', $column = null, $dateColumn = 'created_at') use ($companyId, $loginType, $startDate, $endDate) {
            return $model::selectRaw("DATE_FORMAT($dateColumn, '%b') as month, " .
                ($type === 'count' ? "COUNT(*)" : "SUM($column)") . " as value")
                ->when($loginType === 'team', fn($q) => $q->where('company_id', $companyId))
                ->whereBetween($dateColumn, [$startDate, $endDate])
                ->groupBy('month')
                ->orderByRaw("MIN($dateColumn)")
                ->pluck('value', 'month')
                ->toArray();
        };

        // Fetching all datasets
        $orders = $getMonthlyData(SalesOrder::class, 'count'); // count of orders
        $orderTotals = $getMonthlyData(SalesOrder::class, 'sum', 'grand_total', 'sales_order_date'); // sum of order grand_total
        $sales = $getMonthlyData(SalesOrder::class, 'sum', 'grand_total');
        $quotations = $getMonthlyData(Quotation::class, 'sum', 'grand_total', 'quotation_date');
        // $dispatches = $getMonthlyData(DispatchItems::class, 'sum', 'dispatch_quantity', 'created_at', 'salesOrderItem', function ($query) use ($companyId) {
        //     $query->where('company_id', $companyId);
        // });


        $purchaseOrders = $getMonthlyData(PurchaseOrder::class, 'sum', 'grand_total', 'purchase_order_date');

        // Calculate profit & income
        $profit = array_map(fn($v) => round($v * 0.2), $sales);
        $income = $sales;

        // Fill missing months with zero
        $fill = fn($data) => array_map(fn($m) => $data[$m] ?? 0, $months);

        return response()->json([
            'labels' => $months,
            'datasets' => [
                'orders' => $fill($orders),                   // Order count
                'order_totals' => $fill($orderTotals),        // Order total grand_total
                'sales' => $fill($sales),                     // Sales total (grand_total)
                'profit' => $fill($profit),                   // Profit (calculated)
                'income' => $fill($income),                   // Same as sales
                'quotations' => $fill($quotations),           // Quotation totals
                // 'dispatches' => $fill($dispatches),           // Dispatch count
                'purchase_orders' => $fill($purchaseOrders),  // Purchase order count
            ]
        ]);
    }


    public function getLocations(Request $request)
    {
        $modules = $this->modules;
        $modules['module_name'] = 'Dashboard';
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = isset($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails->parent_type_id : null;
        $loginUser = $modules['authLoginUserDetail'];
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $modules['customer_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Customer']);
        $modules['customer_all_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.all_data'), 'Customer']);
        $modules['customer_personal_data'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.personal_data'), 'Customer']);

        $startOfMonth = null;
        $endOfMonth = null;
        if ($request?->filter_by_daterange) {
            $filter_by_daterange = str_replace(' - ', ' to ', $request->filter_by_daterange);
            $filter_by_daterange = explode(" to ", $filter_by_daterange);

            if (count($filter_by_daterange) == 2) {
                $startOfMonth = Helper::convert_date($filter_by_daterange[0], "d/m/Y", "Y-m-d");
                $endOfMonth = Helper::convert_date($filter_by_daterange[1], "d/m/Y", "Y-m-d");
            }
        } else {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
        }



        $locations = Customer::select('id', 'customer_name', 'contact_person_name', 'mobile_number', 'latitude', 'longitude', 'platform')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');
        if ($startOfMonth && $endOfMonth) {
            $locations->whereDate('created_at', ">=", $startOfMonth);
            $locations->whereDate('created_at', "<=", $endOfMonth);
        } else if ($startOfMonth) {
            $locations->where('created_at', $startOfMonth);
        } else if ($endOfMonth) {
            $locations->where('created_at', $endOfMonth);
        }

        if (isset($modules['company_id']) && $modules['company_id']) {
            $locations = $locations->where('company_id', $modules['company_id']);
        }

        if ($modules['customer_personal_data'] && !$modules['customer_all_data']) {
            $locations->where('created_by', $loginUserId);
        }
        $locations = $locations->get();



        $totalCustomers = Customer::query();
        if ($startOfMonth && $endOfMonth) {
            $totalCustomers->whereDate('created_at', ">=", $startOfMonth);
            $totalCustomers->whereDate('created_at', "<=", $endOfMonth);
        } else if ($startOfMonth) {
            $totalCustomers->where('created_at', $startOfMonth);
        } else if ($endOfMonth) {
            $totalCustomers->where('created_at', $endOfMonth);
        }

        if (isset($modules['company_id']) && $modules['company_id']) {
            $totalCustomers = $totalCustomers->where('company_id', $modules['company_id']);
        }

        if ($modules['customer_personal_data'] && !$modules['customer_all_data']) {
            $totalCustomers->where('created_by', $loginUserId);
        }
        $totalCustomers = $totalCustomers->count();


        $locCountCustomers = Customer::whereNotNull('latitude')->whereNotNull('longitude');
        if ($startOfMonth && $endOfMonth) {
            $locCountCustomers->whereDate('created_at', ">=", $startOfMonth);
            $locCountCustomers->whereDate('created_at', "<=", $endOfMonth);
        } else if ($startOfMonth) {
            $locCountCustomers->where('created_at', $startOfMonth);
        } else if ($endOfMonth) {
            $locCountCustomers->where('created_at', $endOfMonth);
        }
        if (isset($modules['company_id']) && $modules['company_id']) {
            $locCountCustomers = $locCountCustomers->where('company_id', $modules['company_id']);
        }
        if ($modules['customer_personal_data'] && !$modules['customer_all_data']) {
            $locCountCustomers->where('created_by', $loginUserId);
        }
        $locCountCustomers = $locCountCustomers->count();

        return response()->json([
            'locations' => $locations,
            'totalCustomers' => $totalCustomers,
            'locCountCustomers' => $locCountCustomers,
        ]);
    }



    public function order_stock(Request $request)
    {

        $modules = $this->modules;
        $modules['currentGuard'] = $this->currentGuard ?? null;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $companyId = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['company_id'] = $companyId;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        $modules['module_name'] = 'Sales Orders';

        $loginUser = null;
        if (Auth::guard('admin_software')->check()) {
            $loginUser = Auth::guard('admin_software')->user();
        } elseif (Auth::guard('team_person')->check()) {
            $loginUser = Auth::guard('team_person')->user();
        }

        $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
        $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
        $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
        $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
        $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
        $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

        // Check packing slip add permission
        $modules['packing_slip_add_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Packing Slip']);

        if (!$modules['viewPermission']) {
            abort(404);
        }

        try {
            $columns = [
                ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => '#', 'orderable' => false, 'searchable' => false],
                ['data' => 'sales_order_no', 'name' => 'sales_order_no', 'td_label' => 'OA No'],
                ['data' => 'sales_order_date', 'name' => 'sales_order_date', 'td_label' => 'Date'],
                ['data' => 'party_code', 'name' => 'party_code', 'td_label' => 'Party Code'],
                ['data' => 'item_code', 'name' => 'item_code', 'td_label' => 'Item Code'],
                ['data' => 'item_name', 'name' => 'item_name', 'td_label' => 'Item Name'],
                ['data' => 'master_quantity', 'name' => 'master_quantity', 'td_label' => 'Pending'],
                ['data' => 'fg_stock_qty', 'name' => 'fg_stock_qty', 'td_label' => 'FG Stock (Qty)'],
                ['data' => 'fg_stock_kg', 'name' => 'fg_stock_kg', 'td_label' => 'FG Stock (KG)'],
                ['data' => 'packing_slip_date', 'name' => 'packing_slip_date', 'td_label' => 'Del. Date'],
                ['data' => 'action', 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ];
            View::share('modules', $modules);
            View::share('columns', $columns);

            if ($request->ajax()) {
                // Get status IDs for Pending, Partially Packing, and Create Packing Slip
                // ,'pen'
                $allowedStatusSlugs = ['app', 'ps', 'cp']; // Pending, Partially Packing, Create Packing Slip

                // Get all companies if company_id is not set, or filter by specific company
                $companyIds = $modules['company_id'] ? [$modules['company_id']] : SalesOrder::distinct()->pluck('company_id')->toArray();

                if (empty($companyIds)) {
                    return DataTables::of(collect([]))
                        ->addIndexColumn()
                        ->make(true);
                }

                $allowedStatusIds = SalesStatus::whereIn('company_id', $companyIds)
                    ->where('salestype', 'Sales Orders')
                    ->whereIn('statusslug', $allowedStatusSlugs)
                    ->pluck('id')
                    ->toArray();

                $warehouse_id = null;
                if (!empty($companyIds)) {
                    $warehouse = \App\Models\MasterWarehouse::whereIn('company_id', $companyIds)
                        ->where('status', 'active')
                        ->where(function ($query) {
                            $query->where('name', 'like', '%Finished Goods%')
                                ->orWhere('name', 'like', '%FG%');
                        })
                        ->first();
                    $warehouse_id = $warehouse?->id;
                }

                if (empty($allowedStatusIds)) {
                    return DataTables::of(collect([]))
                        ->addIndexColumn()
                        ->make(true);
                }

                $orders = SalesOrder::with(['sales_order_items.product', 'customer', 'sales_status'])
                    ->whereIn('sales_status_id', $allowedStatusIds)
                    ->orderByDesc('id');
                // return Helper::interpolateQuery($orders->toSql(), $orders->getBindings());
                $orders = $orders->get();

                $data = [];

                // Get filter parameters
                $filterItemCode = $request->get('filter_item_code', '');
                $filterItemName = $request->get('filter_item_name', '');
                $filterCustomerNumber = $request->get('filter_customer_number', '');
                $filterOANo = $request->get('filter_oa_no', '');

                foreach ($orders as $order) {
                    foreach ($order->sales_order_items as $item) {
                        if ($item->remaining_quantity > 0) {
                            // Apply filters
                            $itemCode = $item->product->part_no ?? '-';
                            $itemName = $item->product_name ?? '-';
                            $customerNumber = $order->customer->client_code ?? '-';
                            $oaNo = $order->sales_order_no ?? '-';

                            // Filter by Item Code
                            if (!empty($filterItemCode) && stripos($itemCode, $filterItemCode) === false) {
                                continue;
                            }

                            // Filter by Item Name
                            if (!empty($filterItemName) && stripos($itemName, $filterItemName) === false) {
                                continue;
                            }

                            // Filter by Customer Number
                            if (!empty($filterCustomerNumber) && stripos($customerNumber, $filterCustomerNumber) === false) {
                                continue;
                            }

                            // Filter by OA No
                            if (!empty($filterOANo) && stripos($oaNo, $filterOANo) === false) {
                                continue;
                            }

                            // FG stock quantity and KG
                            $fgStockQty = 0;
                            $fgStockKg = 0;

                            if ((int) $item->is_combo === 1) {
                                $combo = ProductCombo::with(['combo_items.product'])->where('id', $item->product_id)->first();
                                if ($combo) {
                                    $itemCode = $combo->part_no ?? $itemCode;
                                    $itemName = $combo->product_combo_name ?? $itemName;

                                    if ($warehouse_id) {
                                        $fgStockQty = (int) Helper::getProductStockTotalQuantity($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, true);
                                        $fgStockKg = round(Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, true), 3);
                                    }
                                }
                            } else {
                                $variantId = $item->product_variant_price_id ?? $item->product_id ?? null;
                                if ($variantId) {
                                    $latestStock = ProductStockHistory::where('product_variant_price_id', $variantId)
                                        ->whereNull('deleted_at')
                                        ->orderBy('id', 'desc')
                                        ->first();
                                    $fgStockQty = $latestStock ? $latestStock->total_quantity : 0;
                                    $fgStockKg = $latestStock ? round($latestStock->total_kg, 3) : 0;
                                }
                            }

                            // Build action button HTML
                            $actionHtml = '';
                            if ($modules['packing_slip_add_permission']) {
                                $packingSlipUrl = route('packing_slip.insert', $order->id);
                                $actionHtml = '<a href="' . $packingSlipUrl . '" class="btn btn-sm btn-primary" title="Create Packing Slip">
                                    <i class="ti ti-box"></i> Create PS
                                </a>';
                            } else {
                                $actionHtml = '-';
                            }
                            $itemDetail = "<b>OA No. : </b>$order->sales_order_no<br>";
                            $itemDetail .= "<b>Item Code : </b>$itemCode<br>";
                            $itemDetail .= "<b>Item Name : </b>$itemName";
                            $data[] = [
                                'sales_order_no' => $order->sales_order_no,
                                'sales_order_date' => $order->sales_order_date
                                    ? date('d-m-Y', strtotime($order->sales_order_date))
                                    : '-',
                                'party_code' => $customerNumber, // Customer's client code (Party Code)
                                'item_code' => $itemCode, // Product part number (Item Code)
                                'item_name' => $itemName,
                                'item' => $itemDetail,
                                'master_quantity' => round($item->remaining_quantity),
                                'fg_stock_qty' => round($fgStockQty),
                                'fg_stock_kg' => round($fgStockKg, 3),
                                'packing_slip_date' => $order->expected_dispatch_date
                                    ? date('d-m-Y', strtotime($order->expected_dispatch_date))
                                    : '-',
                                'action' => $actionHtml,
                                'sales_order_id' => $order->id, // Hidden field for reference
                            ];
                        }
                    }
                }

                return DataTables::of(collect($data))
                    ->addIndexColumn()
                    ->rawColumns(['item', 'action'])
                    ->make(true);
            }

            // The order stock table is displayed in the main dashboard view
            return Redirect::route('software.dashboard');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }





    public function tracking_dashboard()
    {
        try {
            $modules = [];
            $modules['module_name'] = 'TrackingDashboard';

            $loginUser = null;
            if (Auth::guard('admin_software')->check()) {
                $loginUser = Auth::guard('admin_software')->user();
            } elseif (Auth::guard('team_person')->check()) {
                $loginUser = Auth::guard('team_person')->user();
            }

            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            if (!$modules['viewPermission']) {
                abort(404);
            }

            return view('software.tracking-dashboard');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }



    public function reports()
    {
        try {
            $modules = [];
            $modules['module_name'] = 'Reports';

            $loginUser = null;
            if (Auth::guard('admin_software')->check()) {
                $loginUser = Auth::guard('admin_software')->user();
            } elseif (Auth::guard('team_person')->check()) {
                $loginUser = Auth::guard('team_person')->user();
            }

            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            if (!$modules['viewPermission']) {
                abort(403, 'Unauthorized');
            }

            return view('software.reports');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function profile()
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        // dd($modules);
        try {
            View::share('modules', $modules);

            $user = Auth::user();

            if (!empty($user?->company_id)) {
                $company = Company::with(['plan'])->findOrFail($user->company_id);
            } else {
                $company = null;
            }
            View::share('company', $company);
            View::share('userName', $user->name);
            View::share('roleName', $user->role->name ?? 'User');
            View::share('profileIcon', $user->profile_photo ?? 'default.png');
            View::share('edit', $user);

            return view('software.profile');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function permissions()
    {
        $user = Auth::user();
        return view('software.permissions-view');
    }

    public function updateProfile(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            View::share('modules', $modules);

            $request->validate([
                'old_password' => 'required',
                'new_password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
                ],
            ], [
                'new_password.confirmed' => 'New password and confirm password do not match.',
                'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            ], [
                'new_password' => 'New Password',
            ]);

            if (!Hash::check($request->old_password, $modules['authLoginUserDetail']->password)) {
                return back()->withErrors(['old_password' => 'Old password does not match our records.']);
            }

            if ($modules['currentGuard'] == 'admin_software') {
                $updateData = AdminSoftware::findOrFail($loginUserId);
                $validated['sp'] = $request->new_password;
                $validated['password'] = Hash::make($request->new_password);
                $validated['updated_by'] = $loginUserId;
                if ($updateData) {
                    unset($validated['id']);
                    $updateData->update($validated);
                }
            } else {
                $updateData = TeamPerson::findOrFail($loginUserId);
                $validated['sp'] = Str::random(4) . $request->new_password . Str::random(3);
                $validated['password'] = Hash::make($request->new_password);
                $validated['updated_by'] = $loginUserId;
                if ($updateData) {
                    unset($validated['id']);
                    $updateData->update($validated);
                }
            }

            return Redirect::back()->withSuccess(' Password updated successfully.');
            //return back()->with('success', 'Password updated successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage())->withInput();
        }
    }


    public function dashboard_customer()
    {
        $modules = $this->modules;
        $modules['module_name'] = 'Dashboard';
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = isset($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails->parent_type_id : null;
        $loginUser = $modules['authLoginUserDetail'];
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        return view('software.dashboard_customer');
    }






    public function updateProfileImage(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($modules['currentGuard'] == 'admin_software') {
                $updateData = AdminSoftware::findOrFail($loginUserId);
            } else {
                $updateData = TeamPerson::findOrFail($loginUserId);
            }

            // ✅ Profile Image Upload
            if ($request->hasFile('profile_image')) {
                $company = Company::find($modules['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $file = $request->file('profile_image');
                $extension = $file->getClientOriginalExtension();
                $image_name = 'profile_' . $loginUserId . '_' . date('Ymd-His');
                $filename = $image_name . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $modules['company_id'] . "-" . $company_slug . "/profile/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $folder . $filename;
                    if (in_array($extension, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                        if ($webP) {
                            $uploadedImage = $folder . $webP;
                        }
                    }
                    $updateData->profile_image = $uploadedImage;
                }
            }

            $updateData->save();

            return Redirect::back()->withSuccess('Profile image updated successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage())->withInput();
        }
    }

    private function getMinimumStockProducts($modules, $loginUserId)
    {
        try {
            $companyId = $modules['company_id'] ?? null;
            if (!$companyId) {
                return [];
            }

            // Get Finished Goods warehouses for the company
            $warehouses = \App\Models\MasterWarehouse::where('company_id', $companyId)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('name', 'like', '%Finished Goods%')
                        ->orWhere('name', 'like', '%FG%');
                })
                ->get();

            // Get Finished Goods product category IDs
            $finishedGoodsCategoryIds = \App\Models\ProductCategory::where('company_id', $companyId)
                ->where(function ($query) {
                    $query->where('name', 'like', '%Finished Goods%')
                        ->orWhere('name', 'like', '%FG%');
                })
                ->pluck('id')
                ->toArray();

            // If no Finished Goods warehouses and no Finished Goods categories, return empty
            if ($warehouses->isEmpty() && empty($finishedGoodsCategoryIds)) {
                return [];
            }

            $lowStockProducts = [];

            // Get all product variants with min_stock_qty set
            // Filter by Finished Goods category if categories exist, otherwise get all
            $variantsQuery = \App\Models\ProductVariantPrice::whereHas('product', function ($query) use ($companyId, $finishedGoodsCategoryIds) {
                $query->where('company_id', $companyId);
                if (!empty($finishedGoodsCategoryIds)) {
                    $query->whereIn('product_category_id', $finishedGoodsCategoryIds);
                }
            })
                ->whereNotNull('min_stock_qty')
                ->where('min_stock_qty', '>', 0)
                ->where('status', 'active')
                ->with('product');

            $variants = $variantsQuery->get();

            foreach ($variants as $variant) {
                $product = $variant->product;
                if (!$product)
                    continue;

                // Only process if product is in Finished Goods category OR we're checking Finished Goods warehouses
                $isFinishedGoodsProduct = empty($finishedGoodsCategoryIds) || in_array($product->product_category_id, $finishedGoodsCategoryIds);

                if (!$isFinishedGoodsProduct && $warehouses->isEmpty()) {
                    continue;
                }

                // Use Finished Goods warehouses if available, otherwise check all warehouses for FG category products
                $warehousesToCheck = $warehouses->isEmpty()
                    ? \App\Models\MasterWarehouse::where('company_id', $companyId)->where('status', 'active')->get()
                    : $warehouses;

                foreach ($warehousesToCheck as $warehouse) {
                    // If checking all warehouses, only include products in Finished Goods categories
                    if ($warehouses->isEmpty() && !empty($finishedGoodsCategoryIds) && !in_array($product->product_category_id, $finishedGoodsCategoryIds)) {
                        continue;
                    }

                    $currentStock = Helper::getProductStockTotalQuantity(
                        $companyId,
                        $warehouse->id,
                        $product->product_category_id,
                        $variant->id
                    );

                    $minStock = (float) $variant->min_stock_qty;
                    $currentStockValue = (float) ($currentStock ?? 0);

                    if ($currentStockValue < $minStock) {
                        $lowStockProducts[] = [
                            'product_name' => $product->product_name ?? '-',
                            'part_no' => $product->part_no ?? '-',
                            'warehouse' => $warehouse->name,
                            'current_stock' => $currentStockValue,
                            'min_stock' => $minStock,
                            'shortage' => $minStock - $currentStockValue,
                        ];
                    }
                }
            }

            // Also check products without variants but with min_stock, filtered by Finished Goods category
            $productsWithoutVariantsQuery = Product::where('company_id', $companyId)
                ->whereNotNull('min_stock')
                ->where('min_stock', '>', 0)
                ->whereDoesntHave('product_variant_prices', function ($query) {
                    $query->where('status', 'active');
                });

            if (!empty($finishedGoodsCategoryIds)) {
                $productsWithoutVariantsQuery->whereIn('product_category_id', $finishedGoodsCategoryIds);
            }

            $productsWithoutVariants = $productsWithoutVariantsQuery->get();

            foreach ($productsWithoutVariants as $product) {
                // Use Finished Goods warehouses if available, otherwise check all warehouses for FG category products
                $warehousesToCheck = $warehouses->isEmpty()
                    ? \App\Models\MasterWarehouse::where('company_id', $companyId)->where('status', 'active')->get()
                    : $warehouses;

                foreach ($warehousesToCheck as $warehouse) {
                    // If checking all warehouses, only include products in Finished Goods categories
                    if ($warehouses->isEmpty() && !empty($finishedGoodsCategoryIds) && !in_array($product->product_category_id, $finishedGoodsCategoryIds)) {
                        continue;
                    }
                    // Try to get stock using product_id as variant_id (for products without variants)
                    $stockHistory = ProductStockHistory::where('company_id', $companyId)
                        ->where('warehouse_id', $warehouse->id)
                        ->where('product_category_id', $product->product_category_id)
                        ->where('product_variant_price_id', $product->id)
                        ->whereNull('deleted_at')
                        ->orderBy('id', 'desc')
                        ->first();

                    $currentStockValue = $stockHistory ? (float) $stockHistory->total_quantity : 0;
                    $minStock = (float) $product->min_stock;

                    if ($currentStockValue < $minStock) {
                        $lowStockProducts[] = [
                            'product_name' => $product->product_name ?? '-',
                            'part_no' => $product->part_no ?? '-',
                            'warehouse' => $warehouse->name,
                            'current_stock' => $currentStockValue,
                            'min_stock' => $minStock,
                            'shortage' => $minStock - $currentStockValue,
                        ];
                    }
                }
            }

            // Sort by shortage (highest first)
            usort($lowStockProducts, function ($a, $b) {
                return $b['shortage'] <=> $a['shortage'];
            });

            return array_slice($lowStockProducts, 0, 10); // Return top 10
        } catch (\Exception $e) {
            return [];
        }
    }
}
