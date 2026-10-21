<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\CompanyDetails;
use App\Models\ComplanType;
use App\Models\Designation;
use App\Models\DocumentType;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;
use App\Models\FollowUpReason;
use App\Models\LeaveType;
use App\Models\MainMenu;
use App\Models\ManageEmail;
use App\Models\MarketingStatus;
use App\Models\MasterTransportBy;
use App\Models\MasterTransportDetail;
use App\Models\MasterUnit;
use App\Models\MasterWarehouse;
use App\Models\Orderunit;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductTAX;
use App\Models\ProductVariant;
use App\Models\RolePermission;
use App\Models\SalesStatus;
use App\Models\SourceOfInquiry;
use App\Models\SubMenu;
use App\Models\TermsAndCondition;
use Illuminate\Http\Request;
use App\Models\CompanySocial;
use App\Models\Company;
use App\Models\MasterCountry;
use App\Models\PlanMaster;
use App\Models\TeamRole;
use App\Models\TeamPerson;
use App\Models\MailSetting;
use App\Models\CompanySubscriptionPlan;
use App\Models\CompanySubscriptionAddons;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\software\CompanySubscriptionPlanController;
use App\Models\MasterSetting;

class CompanyController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Company',
            'folder_path' => 'software.modules.company',
            'route' => 'company',
            'table_name' => (new Company())->getTable(),
            'permisstion_prefix' => 'company',
            'module_name' => 'Register Company'
        ];

        $app_right_list = Helper::getMainMenu(['platform' => 'app']);
        $panel_right_list = Helper::getMainMenu(['platform' => 'panel']);

        // dd("L-53", $app_right_list, $panel_right_list);
        View::share('app_right_list', $app_right_list);
        View::share('panel_right_list', $panel_right_list);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);

            View::share('modules', $modules);

            if ($request->ajax()) {
                if (!$modules['viewPermission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                // dd($request->all());
                $data = Company::with('plan')
                    ->where(function ($query) {
                        if (Auth::guard('team_person')->check()) {
                            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                            $query->where('id', $teamPersonCompanyId);
                        }
                    })
                    ->where(function ($query) use ($request) {
                        if ($request->has('search')) {
                            $query->where('gst_no', 'like', "%" . $request->search . "%");
                            $query->orwhere('app_key', 'like', "%" . $request->search . "%");
                            $query->orwhere('person_name', 'like', "%" . $request->search . "%");
                            $query->orwhere('company_name', 'like', "%" . $request->search . "%");
                            $query->orwhere('whatsapp_number', 'like', "%" . $request->search . "%");
                        }
                    })
                    ->orderBy('id', 'DESC');

                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_plan') && $request->filter_plan) {
                            $query->where('plan_id', $request->filter_plan);
                        }
                        if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
                            if ($request->status === 'expired') {
                                $query->whereIn('id', function ($sub) {
                                    $sub->selectRaw('company_id')
                                        ->from('company_subscription_plan')
                                        ->where('subscription_status', 'expired')
                                        ->whereRaw('id IN (SELECT MAX(id) FROM company_subscription_plan GROUP BY company_id)');
                                });
                            } else {
                                 $query->where('status', $request->status)
                                ->whereIn('id', function ($sub) {
                                    $sub->selectRaw('company_id')
                                        ->from('company_subscription_plan')
                                        ->where('subscription_status', '!=', 'expired')
                                        ->whereRaw('id IN (SELECT MAX(id) FROM company_subscription_plan GROUP BY company_id)');
                                });
                            }
                        }
                    })
                    ->setRowClass(function ($row) {
                        $latestPlan = CompanySubscriptionPlan::where('company_id', $row["id"])->where('plan_id', $row->plan_id)->orderBy('id', 'desc')->first();

                        return $latestPlan && $latestPlan?->subscription_status === 'expired' ? 'expired-row' : '';
                    })
                    ->editColumn('created_at', function ($row) {
                        $date = '-';

                        if ($row->created_at) {
                            $date = date('d-m-Y', strtotime($row->created_at));
                        }
                        return $date;
                    })
                    // Company Details
                    ->editColumn('plan_id', function ($row) {
                        return $row->plan->name;
                    })
                    // Contact Info
                    ->addColumn('company_details', function ($row) {
                        $html = '<strong>' . $row->company_name . '</strong><br>';
                        $html .= $row->gst_no;
                        return $html;
                    })
                    ->addColumn('prefix_info', function ($row) {
                        $html = '<strong>Quotation:</strong> ' . ($row->quotation_prefix ?? '-') . '<br>';
                        $html .= '<strong>Order:</strong> ' . ($row->order_prefix ?? '-') . '<br>';
                        $html .= '<strong>Dispatch:</strong> ' . ($row->dispatch_prefix ?? '-');
                        return $html;
                    })

                    // Contact Info
                    ->addColumn('contact_info', function ($row) {
                        $html = '';

                        // Person name
                        if (!empty($row->person_name)) {
                            $html .= '<i class="fa fa-user me-1 text-primary"></i>' . $row->person_name;
                        }

                        // WhatsApp number with link
                        if (!empty($row->whatsapp_number)) {
                            $wa_number = preg_replace('/[^0-9]/', '', $row->whatsapp_number);
                            $message = urlencode(""); // Optional: add default message here
                            $wa_link = "https://wa.me/{$wa_number}?text={$message}";
                            $html .= '<br><i class="fab fa-whatsapp text-success me-1"></i> <a href="' . $wa_link . '" target="_blank">' . $row->whatsapp_number . '</a>';
                        }

                        // Email with mailto link
                        if (!empty($row->email)) {
                            $html .= '<br><i class="fa fa-envelope text-danger me-1"></i> <a href="mailto:' . $row->email . '">' . $row->email . '</a>';
                        }

                        return $html;
                    })
                    // Plan & App Key
                    ->addColumn('plan_info', function ($row) {
                        $latestPlan = CompanySubscriptionPlan::where('company_id', $row["id"])->where('plan_id', $row->plan_id)->orderBy('id', 'desc')->first();

                        $totalPurchasePlan = CompanySubscriptionPlan::where('company_id', $row["id"])->count();

                        $planName = $row->plan->name ?? '-';
                        $appKey = $row->app_key ?? '-';

                        $planInfo = '<strong>' . $planName . '</strong><br>' . $appKey . '<br/>';

                        if (!empty($latestPlan?->plan_from)) {
                            $plan_from = \Carbon\Carbon::parse($latestPlan->plan_from)->format('d-m-Y');
                            $planInfo .= '<strong>Plan From:</strong> ' . $plan_from . '<br/>';
                        }

                        if (!empty($latestPlan?->plan_expiry_date)) {
                            $plan_expiry_date = \Carbon\Carbon::parse($latestPlan->plan_expiry_date)->format('d-m-Y');
                            $planInfo .= '<strong>Plan Expiry Date:</strong> ' . $plan_expiry_date . '<br/>';
                        }

                        if (!empty($latestPlan?->subscription_status)) {
                            $planInfo .= '<strong>Subscription Status: ' . ucfirst($latestPlan->subscription_status) . '</strong><br/>';
                        }
                        if ($totalPurchasePlan > 0) {
                            $planInfo .= '<strong>Total Purchase Plan: ' . $totalPurchasePlan . '</strong>';
                        }
                        return $planInfo;
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $latestPlan = CompanySubscriptionPlan::where('company_id', $row["id"])->where('plan_id', $row->plan_id)->orderBy('id', 'desc')->first();

                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';


                        if (!empty($latestPlan?->subscription_status) && $latestPlan?->subscription_status == 'expired') {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm w-100 waves-effect waves-light">' . ucfirst($latestPlan?->subscription_status) . '</button>';
                        } else {

                            $btnExpired = '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-dark update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="expired">Expire</a></li>';

                            if ($row->status == "active") {
                                $btn .= '<button type="button" class="btn btn-success btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport">' . ucfirst($row->status) . '</button>';
                                $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';

                                $dropdown .= $btnExpired;
                                // $btn = '<span class="badge bg-success bg-glow">Active</span>';
                            } elseif ($row->status == "inactive") {
                                $btn .= '<button type="button" class="btn btn-danger btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport">' . ucfirst($row->status) . '</button>';
                                $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
                                // $btn = '<span class="badge bg-danger bg-glow">In-Active</span>';

                                $dropdown .= $btnExpired;
                            } else {
                                return $btn;
                            }
                        }
                        $dropdown .= '</ul>';
                        $btn .= $dropdown;
                        return $btn;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $latestPlan = CompanySubscriptionPlan::where('company_id', $row["id"])->where('plan_id', $row->plan_id)->orderBy('id', 'desc')->first();

                        $btn = '';
                        if (!empty($modules['editPermission'])) {
                            $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1" title="Edit"> <i class="fa-solid fa-pen-to-square"></i> </a>';
                        }

                        $btn .= '
                            <div style="display:inline-block">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport" title="More options">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">';
                        if ($latestPlan && $latestPlan?->subscription_status === 'expired') {
                            $btn .= '<li>
                                        <a class="dropdown-item" href="' . route('company.upgrade_plan', [$row["id"]]) . '">
                                            <i class="fa-plus"></i> Upgrade Plan
                                        </a>
                                    </li>';
                        }

                        $btn .= '<li>
                                        <a class="dropdown-item" href="' . route('company.thirdpartyintegration', [$row["id"]]) . '">
                                            <i class="fa-solid fa-plug"></i> Third Party Integration
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="' . route('company.mail_setting', [$row["id"]]) . '">
                                            <i class="tf-icons ti ti-mail-cog"></i> Mail Configration
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="' . route('company.license_setting', [$row["id"]]) . '">
                                            <i class="tf-icons ti ti-settings"></i> License Setting
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="' . route('company-subscription-plan.show', [$row["id"]]) . '">
                                            <i class="tf-icons ti ti-history"></i> Subscription History
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        ';

                        if (($btn) === '') {
                            $btn = '-';
                        }

                        return $btn;
                    })

                    ->rawColumns(['status', 'action', 'company_details', 'contact_info', 'plan_info', 'prefix_info'])
                    ->make(true);
                return $returnData;
            }

            if (!$modules['viewPermission']) {
                abort(403, 'Unauthorized');
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $modules = $this->modules;
        try {
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            if (!$modules['addPermission']) {
                abort(403, 'Unauthorized');
            }

            $plans = PlanMaster::where('status', 'active')->get();
            View::share('modules', $modules);
            View::share('plans', $plans);

            return view($modules['folder_path'] . '.add');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CompanyRequest $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        View::share('modules', $modules);
        // return $request->all();

        $validated = $request->validated();
        $input = $request->all();

        DB::beginTransaction();
        try {
            //return $validated;
            $validated['created_by'] = $loginUserId;
            $validated['sp'] = Str::random(4) . $request->password . Str::random(3);

            $plan_data = PlanMaster::where('id', $request->plan_id)->first();
            $plan_from = date('Y-m-d');
            $plan_to = date('Y-m-d');


            if (!empty($plan_data->plan_valid_day)) {
                $plan_to = Carbon::now()->addDays($plan_data->plan_valid_day)->format('Y-m-d');
            }

            $plan_to = $plan_to;
            $validated['max_team_user_count'] = $plan_data->max_team_user_count;
            $validated['max_customer_count'] = $plan_data->max_customer_count;
            $validated['max_inquiry_count'] = $plan_data->max_inquiry_count;

            $country = MasterCountry::where('id', $request->country_id)->first();
            $validated['phonecode'] = $country->code;

            $validated['app_right'] = $plan_data->app_right;
            $validated['panel_right'] = $plan_data->panel_right;
            $validated['panel_url'] = url('/software/login');

            $defaultValues = [
                'mobile_min' => 10,
                'mobile_max' => 10,
                'order_view_format' => 'general_view',
                'quotation_view_format' => 'general_view',
                'purchaseOrder_view_format' => 'general_view',
                'dispatch_view_format' => 'v1',
                'order_prefix' => 'SO',
                'quotation_prefix' => 'QUO',
                'dispatch_prefix' => 'DIS',
                'packing_slip_prefix' => 'PS',
                'purchase_order_prefix' => 'PO',
                'order_title' => 'Sales Order',
                'quotation_title' => 'Quotation',
                'dispatch_title' => 'Dispatch',
                'packing_slip_title' => 'Packing Slip',
                'purchase_order_title' => 'Purchase Order',
                'order_view_color' => '#c7c6c6',
                'quotation_view_color' => '#c7c6c6',
                'dispatch_view_color' => '#c7c6c6',
                'packing_slip_view_color' => '#c7c6c6',
                'vendor_view_color' => '#c7c6c6',
                'purchase_order_color' => '#c7c6c6',
                'quantity' => 'qty',
                'tax_type' => 'product_wise',
                'stock_check' => 'yes',
                'status' => 'active',
            ];

            $validated = array_merge($validated, $defaultValues);


            $company = Company::create($validated);
            $company_id = $company->id;

            CompanyDetails::create([
                'company_id' => $company_id,
                'how_assign_inquiry' => $request->get('how_assign_inquiry', 'source_of_inquiry_wise'),
                'is_quantity_decimal' => $request->is_quantity_decimal ? 1 : 0,

            ]);


            $team_role = TeamRole::create([
                'company_id' => $company_id,
                'parent_id' => '0',
                'name' => $request->company_name,
                'created_type' => 'admin_software',
                'created_by' => $validated['created_by'],
                // 'updated_by' => $validated['created_by'],
            ]);





            $panelRights = $plan_data->panel_right;

            $mainMenus = MainMenu::where('status', 'active')->get();

            foreach ($mainMenus as $mainMenu) {
                $subMenus = SubMenu::where('main_menu_id', $mainMenu->id)
                    ->where('status', 'active')
                    ->get();

                foreach ($subMenus as $subMenu) {
                    if (in_array((string) $subMenu->id, $panelRights)) {  // Match by submenu ID
                        RolePermission::create([
                            'company_id' => $company_id,
                            'team_role_id' => $team_role->id,
                            'main_menu_id' => $mainMenu->id,
                            'sub_menu_id' => $subMenu->id,
                            'view_flag' => 1,
                            'add_flag' => 1,
                            'update_flag' => 1,
                            'delete_flag' => 1,
                            'restore_flag' => 1,
                            'print_flag' => 1,
                            'excel_flag' => 1,
                            'approval_flag' => 1,
                            'all_data_flag' => 1,
                            'status' => 'active',
                            'created_by' => $validated['created_by'],
                        ]);
                    }
                }
            }




            // Insert team person

            $teamPerson = TeamPerson::create([
                'company_id' => $company_id,
                'name' => $request->person_name,
                'parent_type_id' => 0,
                'mobile_no' => $request->whatsapp_number,
                'employee_code' => $this->generateEmployeeCode($company_id),
                'username' => $request->whatsapp_number,
                'password' => bcrypt($request->password),
                'sp' => Str::random(4) . $request->password . Str::random(3),
                'min_working_start_time' => '05:30:00',
                'max_working_start_time' => '05:30:00',
                'working_end_time' => '05:30:00',
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'email' => $request->email,
                'team_role_id' => $team_role->id,
                'created_by' => $validated['created_by'],
                // 'updated_by' => $validated['created_by'],
            ]);
            // Source Of Inquiry
            $sourceNames = ['Indiamart', 'Facebook', 'Call', 'By Visit'];

            foreach ($sourceNames as $name) {
                SourceOfInquiry::create([
                    'company_id' => $company_id,
                    'name' => $name,
                    'status' => 'active',
                    'created_by' => $validated['created_by'],
                ]);
            }
            //Designation
            Designation::create([
                'company_id' => $company_id,
                'name' => 'Admin',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Document Type
            DocumentType::create([
                'company_id' => $company_id,
                'name' => 'General Documents',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Transport By
            $transportBy = MasterTransportBy::create([
                'company_id' => $company_id,
                'name' => 'By Road',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // TransportDetail
            MasterTransportDetail::create([
                'company_id' => $company_id,
                'transport_by_id' => $transportBy->id,
                'name' => 'My Self',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Warehouse
            MasterWarehouse::create([
                'company_id' => $company_id,
                'name' => 'General Warehouse',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Unit
            MasterUnit::create([
                'company_id' => $company_id,
                'name' => 'Pcs',
                'status' => 'active',
                'platform' => 'web',
                'created_by' => $validated['created_by'],
            ]);

            // Order Unit
            Orderunit::create([
                'company_id' => $company_id,
                'name' => 'Qty',
                'status' => 'active',
                'platform' => 'web',
                'created_by' => $validated['created_by'],
            ]);

            // Product TAX
            ProductTAX::create([
                'company_id' => $company_id,
                'tax_name' => 'GST 18%',
                'tax_value' => 18,
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Product Variant
            ProductVariant::create([
                'company_id' => $company_id,
                'name' => 'Without Variant',
                'status' => 'active',
                'platform' => 'web',
                'created_by' => $validated['created_by'],
            ]);

            //  Product Category
            $productCategory = ProductCategory::create([
                'company_id' => $company_id,
                'name' => 'General Product Category',
                'status' => 'active',
                'platform' => 'web',
                'created_by' => $validated['created_by'],
            ]);

            //  Product Sub Category
            ProductSubcategory::create([
                'company_id' => $company_id,
                'product_category_id' => $productCategory->id,
                'name' => 'General Product Sub Category',
                'status' => 'active',
                'platform' => 'web',
                'created_by' => $validated['created_by'],
            ]);

            // Leave Type
            LeaveType::create([
                'company_id' => $company_id,
                'name' => 'General leave',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Expense Category
            $expenseCategory = ExpenseCategory::create([
                'company_id' => $company_id,
                'name' => 'General Expense Category',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Expense SubCategory
            ExpenseSubCategory::create([
                'company_id' => $company_id,
                'expense_category_id' => $expenseCategory->id,
                'team_person_ids' => (string) $teamPerson->id,
                'name' => 'General Expense Sub Category',
                'expense_type' => 'General',
                'is_image_required' => 0,
                'min_amount' => 1,
                'max_amount' => 10000,
                'per_km_rate' => null,
                'fix_amount' => null,
                'from_time' => null,
                'to_time' => null,
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // FollowUpReason
            FollowUpReason::create([
                'company_id' => $company_id,
                'name' => 'General Follow Up',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Marketing Status - Inquiry
            MarketingStatus::create([
                'company_id' => $company_id,
                'type' => 'Inquiry',
                'name' => 'New Inquiry',
                'display_order' => 1,
                'status_color' => '#c7c6c6',
                'status_color_2' => '#c7c6c6',
                'text_color' => '#000000',
                'is_default' => 1,
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Marketing Status - Follow Up
            MarketingStatus::create([
                'company_id' => $company_id,
                'type' => 'Followup',
                'name' => 'Generated',
                'display_order' => 1,
                'status_color' => '#c7c6c6',
                'status_color_2' => '#c7c6c6',
                'text_color' => '#000000    ',
                'is_default' => 1,
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Complain Type
            ComplanType::create([
                'company_id' => $company_id,
                'name' => 'General Complain',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            // Terms and Condition
            TermsAndCondition::create([
                'company_id' => $company_id,
                'title' => 'General Terms',
                'terms_and_condition_detail' => 'General Terms',
                'status' => 'active',
                'created_by' => $validated['created_by'],
            ]);

            ManageEmail::create([
            'company_id' => $company_id,
            'module' => 'Setting',
            'ntype' => 'forgot_password',
            'name' => 'Reset Password',
            'subject' => 'Reset Password',
            'body' => '<figure class="table">
                <table>
                    <tbody>
                        <tr>
                            <td style="padding:30px;text-align:center;">
                                <h2 style="margin-left:0;">Hello !</h2>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:30px;">
                                <p>You are receiving this email because we received a password reset request for your <strong>Ocean CRM</strong> account.</p>
                                <p style="margin-left:0;text-align:center;">[link]</p>
                                <p>This password reset link will expire in <strong>60 minutes</strong>.</p>
                                <p>If you did not request a password reset, no further action is required.</p>
                                <p>Regards,&nbsp;<br><strong>Ocean CRM Team</strong></p>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color:#f1f1f1;padding:15px;text-align:center;">
                                © ' . date('Y') . ' Ocean CRM. All rights reserved.
                            </td>
                        </tr>
                    </tbody>
                </table>
                </figure>',
                'status' => 'active',
                'created_by' => $validated['created_by'],
                'updated_by' => $validated['created_by'],
            ]);

            // Sales Statuses
            $salesStatuses = [
                'Quotation',
                'Sales Orders',
                'Invoice',
                'Purchase Order',
            ];

            foreach ($salesStatuses as $salesType) {
                SalesStatus::create([
                    'company_id' => $company_id,
                    'salestype' => $salesType,
                    'statusname' => 'Pending',
                    'statusslug' => 'pen',
                    'status_color' => '#000000',
                    'display_order' => 1,
                    'status' => 'active',
                    'is_redirect' => 0,
                    'is_revise' => 0,
                    'is_repeat' => 0,
                    'parent_status_id' => null,
                    'created_by' => $validated['created_by'],
                ]);
            }



            $today = Carbon::today();
            $subscription_status = '';
            if ($plan_from && $plan_to) {
                if ($today->between(Carbon::parse($plan_from), Carbon::parse($plan_to))) {
                    $subscription_status = 'active';
                } else {
                    $subscription_status =  'expired';
                }
            }

            CompanySubscriptionPlan::create([
                'company_id' => $company_id,
                'plan_id' => $request->plan_id,
                'plan_from' => $plan_from,
                'plan_to' => $plan_to,
                'extra_detail' => json_encode($plan_data->toArray()),
                'plan_expiry_date' => $plan_to,
                'subscription_status' => $subscription_status,
                'created_by' => $loginUserId,
            ]);

            DB::commit();
            return $this->sendResponse([], "Company created successfully.");
            //return response()->json([
            //    'status' => true,
            //    'message' => $modules['title'] . ' created successfully'
            //], 200);
            //return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            // return $e->getMessage();
            //return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
            Log::error('Error occurred while creating company: ' . $e->getMessage());
            return $this->sendError($e->getMessage());
            //return response()->json([
            //    'status' => false,
            //    'message' => 'Error occurred while creating company: ' . $e->getMessage()
            //], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ($id = "print") {
            // return self::print();
        }
        dd($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            $modules['updatePermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            if (!$modules['updatePermission']) {
                abort(403, 'Unauthorized');
            }

            $modules['loginType'] = isset($modules['currentGuard']) ? $modules['currentGuard'] : null;

            // return $modules;

            $plans = PlanMaster::where('status', 'active')->get();
            $countries = MasterCountry::where('status', 'active')->get();
            View::share('plans', $plans);

            View::share('countries', $countries);

            $edit = Company::with(['company_details'])->findOrFail($id);
// dd($edit);
            View::share('edit', $edit);

            View::share('modules', $modules);


            $quantity_type_arr = Company::$quantity_type;
            $tax_type_arr = Company::$tax_type;
            $stock_check_arr = Company::$stock_check;
            $quotation_view_format_arr = Company::$quotation_view_format;
            $purchaseOrder_view_format_arr = Company::$purchaseOrder_view_format;
            $invoice_view_format_arr = Company::$invoice_view_format;
            $how_assign_inquiry_arr = Company::$how_assign_inquiry;

            $order_view_format_arr = Company::$order_view_format;
            $account_effect_by_arr = Company::$account_effect_by;
            $duplication_check_type_arr = CompanyDetails::$duplication_check_type;
            View::share('quantity_type_arr', $quantity_type_arr);
            View::share('tax_type_arr', $tax_type_arr);
            View::share('stock_check_arr', $stock_check_arr);
            View::share('quotation_view_format_arr', $quotation_view_format_arr);
            View::share('purchaseOrder_view_format_arr', $purchaseOrder_view_format_arr);
            View::share('invoice_view_format_arr', $invoice_view_format_arr);
            View::share('how_assign_inquiry_arr', $how_assign_inquiry_arr);

            View::share('order_view_format_arr', $order_view_format_arr);
            View::share('account_effect_by_arr', $account_effect_by_arr);
            View::share('duplication_check_type_arr', $duplication_check_type_arr);

            $companySocial = CompanySocial::where('company_id', $id)->first();
            View::share('company_social', $companySocial);

            return view($modules['folder_path'] . '.edit');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        // return $request->all();

        $request['id'] = $id;

        $validated = $request->validated();
        $input = $request->all();
        $input['duplication_check_type'] = $request->duplication_check_type;
        // return $validated;
        try {
            $validated['updated_by'] = $loginUserId;

            $updateData = Company::findOrFail($id);

            // dd($validated);
            //$updateData->update($validated);

            CompanyDetails::updateOrCreate(
                ['company_id' => $updateData->id],
                [
                    'how_assign_inquiry' => $request->get('how_assign_inquiry'),
                    'duplication_check_type' => $request->get('duplication_check_type')
                ],
            );
            // dd($request->all(),  $request->get('duplication_check_type'));

            $request['is_copyright_view'] = $request->is_copyright_view ? 'yes' : 'no';

            $sub_folder_path = Helper::fileUploadPath($modules['authLoginUserDetail'], Company::$folderPath, $id);
            $company_name = $request?->company_name ?? 'default-company';

            if ($request->hasFile('company_logo')) {

                if ($updateData?->company_logo && file_exists($updateData?->company_logo)) {
                    unlink($updateData?->company_logo);
                };

                $image_name = Helper::make_slug('company_logo ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('company_logo');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['company_logo'] = $uploadedImage;
                }
            }

            if ($request->hasFile('white_labeling_logo')) {
                if ($updateData?->white_labeling_logo && file_exists($updateData?->white_labeling_logo)) {
                    unlink($updateData?->white_labeling_logo);
                };

                $image_name = Helper::make_slug('white_labeling_logo ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('white_labeling_logo');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['white_labeling_logo'] = $uploadedImage;
                }
            }

            if ($request->hasFile('company_favicon')) {
                if ($updateData?->company_favicon && file_exists($updateData?->company_favicon)) {
                    unlink($updateData?->company_favicon);
                };

                $image_name = Helper::make_slug('company_favicon ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('company_favicon');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['company_favicon'] = $uploadedImage;
                }
            }

            if ($request->hasFile('app_logo')) {
                if ($updateData?->app_logo && file_exists($updateData?->app_logo)) {
                    unlink($updateData?->app_logo);
                };

                $image_name = Helper::make_slug('app_logo ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('app_logo');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['app_logo'] = $uploadedImage;
                }
            }

            if ($request->hasFile('header_image')) {

                if ($updateData?->header_image && file_exists($updateData?->header_image)) {
                    unlink($updateData?->header_image);
                };

                $image_name = Helper::make_slug('header_image ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('header_image');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['order_header_logo'] = $uploadedImage;
                }
            }

            if ($request->hasFile('footer_image')) {


                if ($updateData?->footer_image && file_exists($updateData?->footer_image)) {
                    unlink($updateData?->footer_image);
                };

                $image_name = Helper::make_slug('footer_image ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('footer_image');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['order_footer_logo'] = $uploadedImage;
                }
            }


            if ($request->hasFile('invoice_letter_head')) {

                if ($updateData?->invoice_letter_head && file_exists($updateData?->invoice_letter_head)) {
                    unlink($updateData?->invoice_letter_head);
                };

                $image_name = Helper::make_slug('invoice_letter_head ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('invoice_letter_head');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['invoice_letter_head'] = $uploadedImage;
                }
            }

            if ($request->hasFile('authorised_signature')) {

                if ($updateData?->authorised_signature && file_exists($updateData?->authorised_signature)) {
                    unlink($updateData?->authorised_signature);
                };

                $image_name = Helper::make_slug('authorised_signature ' . (string)$loginUserId . ' ' . $company_name . ' ' . date('Ymd-His'));

                $file = $request->file('authorised_signature');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // return $uploadedImage;
                    $input['authorised_signature'] = $uploadedImage;
                }
            }

            if ($request->hasFile('brochure')) {
                if ($updateData?->brochure && file_exists(public_path($updateData?->brochure))) {
                    @unlink(public_path($updateData?->brochure));
                }

                $file = $request->file('brochure');
                $extenstion = $file->getClientOriginalExtension();
                $filename = 'brochure_' . (string)$loginUserId . '_' . Helper::make_slug($company_name) . '_' . date('Ymd-His') . '.' . $extenstion;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $input['brochure'] = $sub_folder_path . $filename;
                }
            }

            if ($request->hasFile('rate_sheet')) {
                $file = $request->file('rate_sheet');

                // 1. Create backup of old company rate sheet if exists
                if ($updateData?->rate_sheet && file_exists(public_path($updateData?->rate_sheet))) {
                    $backupDir = storage_path('app/rate_sheet_backups');
                    if (!file_exists($backupDir)) {
                        @mkdir($backupDir, 0777, true);
                    }
                    @copy(public_path($updateData->rate_sheet), $backupDir . '/rate_sheet_backup_' . date('Ymd_His') . '_' . basename($updateData->rate_sheet));
                    @unlink(public_path($updateData->rate_sheet));
                }

                $extenstion = $file->getClientOriginalExtension();
                $originalBaseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $cleanSlug = Helper::make_slug($originalBaseName ?: $company_name);
                $rateFilename = 'rate_sheet_' . (string)$loginUserId . '_' . $cleanSlug . '_' . date('Ymd-His') . '.' . $extenstion;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    @mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $rateFilename)) {
                    $savedPath = $sub_folder_path . $rateFilename;
                    $input['rate_sheet'] = $savedPath;

                    // Flush rate sheet cache
                    cache()->flush();

                    // 5. Automatically synchronize products database with new rates
                    try {
                        $productController = app(\App\Http\Controllers\software\ProductController::class);
                        if (method_exists($productController, 'perform_rate_sheet_sync')) {
                            $productController->perform_rate_sheet_sync();
                        }
                    } catch (\Exception $e) {
                        \Log::error('Rate sheet auto-sync on company profile update failed: ' . $e->getMessage());
                    }
                }
            }

            if ($request->input('rate_sheet_removed') == '1') {
                if ($updateData?->rate_sheet && file_exists(public_path($updateData?->rate_sheet))) {
                    $backupDir = storage_path('app/rate_sheet_backups');
                    if (!file_exists($backupDir)) {
                        @mkdir($backupDir, 0777, true);
                    }
                    @copy(public_path($updateData->rate_sheet), $backupDir . '/rate_sheet_backup_' . date('Ymd_His') . '_' . basename($updateData->rate_sheet));
                    @unlink(public_path($updateData->rate_sheet));
                }

                $targetDir = public_path('uploads/rate_sheet');
                if (is_dir($targetDir)) {
                    $files = glob($targetDir . '/*.{xlsx,xls,XLSX,XLS}', GLOB_BRACE);
                    if (!empty($files)) {
                        $backupDir = storage_path('app/rate_sheet_backups');
                        if (!file_exists($backupDir)) {
                            @mkdir($backupDir, 0777, true);
                        }
                        foreach ($files as $f) {
                            @copy($f, $backupDir . '/rate_sheet_backup_' . date('Ymd_His') . '_' . basename($f));
                            @unlink($f);
                        }
                    }
                }

                $input['rate_sheet'] = null;
                cache()->flush();
            }

            // dd("L-711", $request->all(), $input);
            if ($updateData) {
                unset($validated['id']);

                $colorFields = [
                    "status_bar_color",
                    "title_name_color",
                    "all_icon_color",
                    "edittext_title_color",
                    "screen_background_light_color",
                    "screen_background_dark_color",
                    "all_screen_header_color",
                    "all_screen_back_arrow_background_color",
                    "all_screen_back_arrow_color",
                    "data_list_border_color",
                    "login_text_color_1",
                    "login_text_color_2",
                    "background_shape_1",
                    "background_shape_2",
                    "background_shape_3",
                    "extra_color_1",
                    "extra_color_2",
                    "extra_color_3",
                    "per_minutes_request_current_location",
                    "is_quantity_decimal",
                    "how_assign_inquiry",
                    "inward_prefix",
                    "inward_title",
                    "inward_color",
                    "job_work_order_title",
                    "job_work_order_prefix",
                    "job_work_order_color",
                    "job_work_issue_title",
                    "job_work_issue_prefix",
                    "job_work_issue_color",
                    "job_work_receive_title",
                    "job_work_receive_prefix",
                    "job_work_receive_color",
                    "qc_inspection_title",
                    "qc_inspection_prefix",
                    "qc_inspection_color",
                    "invoice_title",
                    "invoice_prefix",
                    "invoice_color",
                ];

                $input['is_quantity_decimal'] = $request->has('is_quantity_decimal') ? 1 : 0;
                // Separate color fields from the main input
                $companyDetailData = [];
                foreach ($colorFields as $field) {
                    if (array_key_exists($field, $input)) {
                        $companyDetailData[$field] = $input[$field];
                        unset($input[$field]); // remove from input to avoid updating Company with these
                    }
                }

                // Update company record (main company table)
                $updateData->update($input);

                // Update or create related company detail record
                if (!empty($companyDetailData)) {
                    CompanyDetails::updateOrCreate(
                        ['company_id' => $updateData->id],
                        $companyDetailData
                    );
                }

                $companySocial = CompanySocial::where('company_id', $id)->where('platform', $request?->platform)->first();
                if ($companySocial) {
                    $companySocial->update([
                        'api_key' => $request->api_key,
                        'updated_by' => Auth::guard('admin_software')?->user()?->id,
                    ]);
                } else {
                    CompanySocial::create([
                        'company_id' => $id,
                        'platform' => $request->platform,
                        'api_key' => $request->api_key,
                        'created_by' => Auth::guard('admin_software')?->user()?->id,
                        'updated_by' => Auth::guard('admin_software')?->user()?->id,
                    ]);
                }

                if (!empty($request?->team_set) && $request?->team_set === 'team_update' && !empty($request?->tab && $request?->tab === 'profile-tab')) {
                    return Redirect::route($modules['route'] . '.detail', ['id' => $id, 'tab' => 'profile-tab'])->withSuccess('Company update successfully');
                } else {
                    return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
                }
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function check_company_exists(Request $request)
    {
        $company_name = $request->company_name;
        $gst_no = $request->gst_no;
        $company = Company::where('company_name', $company_name)->first();
        if ($company) {
            return $this->sendError('Company name already exists', $company);
        } else {
            return $this->sendResponse([], 'Company name does not exist');
        }
    }

    function generateEmployeeCode($companyId)
    {
        $lastEmployee = TeamPerson::where('company_id', $companyId)->orderBy('id', 'desc')->first();
        $lastCode = $lastEmployee ? intval(str_replace('EMP-', '', $lastEmployee->employee_code)) : 0;
        $nextNumber = $lastCode + 1;
        $employeeCode = 'EMP-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        return $employeeCode;
    }

    public function status_update(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        $modules = $this->modules;
        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'update_status' => ['required', 'in:active,inactive,expired']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }


        try {
            $country = Company::withTrashed()->findOrFail($request?->id);
            if ($country) {
                if ($request->update_status == 'expired') {
                    $companySubscriptionPlan = CompanySubscriptionPlan::where('company_id', $country->id)->where('plan_id', $country->plan_id)->where('subscription_status', 'active')->orderBy('id', 'desc')->first();
                    if ($companySubscriptionPlan) {
                        $companySubscriptionPlan->subscription_status = 'expired';
                        $companySubscriptionPlan->plan_expiry_date = date('Y-m-d');
                        $companySubscriptionPlan->platform = 'manually';
                        $companySubscriptionPlan->save();
                    }
                } else {
                    $country->status = $request->update_status;
                    $country->save();
                }

                if ($isAjax) {
                    return $this->sendResponse($country, $modules['title'] . ' status update successfully.');
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' status update successfully.');
            }
            if ($isAjax) {
                return $this->sendError('something went wrong please try again later');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function thirdPartyIntegration(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        View::share('modules', $modules);

        try {

            if ($request?->_token) {
                //dd($request->all());
                $rules = [
                    'company_id' => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                ];

                if ($request->filled('indiamart_api_key')) {
                    $rules['indiamart_api_key'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_key')
                    ];
                }

                if ($request->filled('tradeindia_api_key')) {
                    $rules['tradeindia_api_key'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_key')
                    ];
                }

                if ($request->filled('justdial_api_key')) {
                    $rules['justdial_api_key'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_key')
                    ];
                }

                if ($request->filled('linkedin_api_key')) {
                    $rules['linkedin_api_key'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_key')
                    ];
                }

                if ($request->filled('facebook_api_key')) {
                    $rules['facebook_api_key'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_key')
                    ];
                }

                $validator = Validator::make($request->all(), $rules);

                $updateData = Company::findOrFail($id);

                $formArray = $request->except(['_token']);
                if ($updateData) {

                    $platforms = [
                        'IndiaMart' => $request->indiamart_api_key,
                        'TradeIndia' => $request->tradeindia_api_key,
                        'JustDial' => $request->justdial_api_key,
                        'LinkedIn' => $request->linkedin_api_key,
                        'Facebook' => $request->facebook_api_key,
                    ];

                    foreach ($platforms as $platform => $apiKey) {
                        //if (!empty($apiKey)) {
                        $record = CompanySocial::firstOrNew([
                            'company_id' => $request->company_id,
                            'platform' => $platform,
                        ]);

                        $record->api_key = $apiKey;
                        if ($platform === 'TradeIndia' && $request->filled('tradeindia_user_id')) {
                            $record->user_id = $request->tradeindia_user_id;
                        }
                        if ($platform === 'TradeIndia' && $request->filled('tradeindia_profile_id')) {
                            $record->profile_id = $request->tradeindia_profile_id;
                        }
                        if ($platform === 'IndiaMart' && $request->filled('indiamart_minutes_time')) {
                            $record->minutes_time = $request->indiamart_minutes_time;
                        }

                        if ($record->exists) {
                            $record->updated_by = $loginUserId;
                        } else {
                            $record->created_by = $loginUserId;
                        }
                        $record->save();
                        //}
                    }
                }
                if (!empty($request?->team_set) && $request?->team_set === 'team_update' && !empty($request?->tab && $request?->tab === 'integration-tab')) {
                    return Redirect::route($modules['route'] . '.detail', ['id' => $id, 'tab' => 'integration-tab'])->withSuccess('Third Party Integration update successfully');
                } else {
                    return Redirect::route($modules['route'] . '.thirdpartyintegration', [$id])->withSuccess('Third Party Integration.');
                }
            }
            $company = Company::findOrFail($id);
            $company_social = CompanySocial::where('company_id', $id)->get()->mapWithKeys(fn($item) => [strtolower($item->platform) => $item]);
            View::share('company_social', $company_social);
            $edit = Company::findOrFail($id);

            View::share('edit', $edit);
            return view($modules['folder_path'] . '.thirdpartyintegration', compact('company', 'company_social'))->with([
                'edit' => $company,
            ]);
            // return view('company.thirdpartyintegration', compact('company'));
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function thirdPartyIntegrationPlatform(Request $request, $platform, $id = null)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['view' . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', ['view', 'Indiamart']);

        $modules['title'] = ucfirst($platform) . " - Third Party Integration";

        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        if (isset($modules['currentGuard']) && $modules['currentGuard'] != 'admin_software') {
            if (!$id && isset($modules['company_id']) && $modules['company_id']) {
                $id = $modules['company_id'];
            }
        } else {
            $id = $request?->company_id;
        }

        if (!$id && session()->has('selected_company_id')) {
            $id = session('selected_company_id');
        }

        try {
            View::share('platform', $platform);

            $pages = session('facebook_pages');
            $fb_user_token = session('fb_user_token');
            $facebook_company_id = session('facebook_company_id');

            $pages = [];
            $fb_user_token = null;

            // Only set these values if session company_id matches $id
            if (!empty($facebook_company_id) && $facebook_company_id == $id) {
                $pages = $session_pages ?? [];
                $fb_user_token = $session_fb_user_token ?? null;
            }
            View::share('pages', $pages);
            View::share('fb_user_token', $fb_user_token);
            View::share('facebook_company_id', $facebook_company_id);

            if ($request?->_token) {

                if (!isset($id) || $id == '' || (int)($id) <= 0) {
                    return back()->withInput()->withErrors(["Please Select Company."]);
                }

                $rules = [
                    'company_id' => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                    'platform' => ['required', 'in:indiamart,tradeindia,facebook,website,eway,einvoice'],
                ];

                if ($request->filled('platform') && $request?->platform == "indiamart") {
                    if ($request->filled($request?->platform . '_api_key')) {
                        $rules[$request?->platform . '_api_key'] = [
                            'required',
                            'string',
                            'max:255',
                            Rule::unique((new CompanySocial())->getTable(), 'api_key')
                        ];
                    }

                    if ($request->filled($request?->platform . '_minutes_time')) {
                        $rules[$request?->platform . '_minutes_time'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                } else if ($request->filled('platform') && $request?->platform == "tradeindia") {
                    if ($request->filled($request?->platform . '_api_key')) {
                        $rules[$request?->platform . '_api_key'] = [
                            'required',
                            'string',
                            'max:255',
                            Rule::unique((new CompanySocial())->getTable(), 'api_key')
                        ];
                    }

                    if ($request->filled($request?->platform . '_user_id')) {
                        $rules[$request?->platform . '_user_id'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_profile_id')) {
                        $rules[$request?->platform . '_profile_id'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                } else if ($request->filled('platform') && $request?->platform == "facebook") {
                    $rules['facebook_api_key'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_key')
                    ];
                } else if ($request->filled('platform') && $request?->platform == "website") {
                    $rules['website_api_code'] = [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('company_socials', 'api_code')
                    ];
                } else if ($request->filled('platform') && $request?->platform == "eway") {
                    if ($request->filled($request?->platform . '_gstin')) {
                        $rules[$request?->platform . '_gstin'] = [
                            'required',
                            'string',
                            'max:255',
                            Rule::unique((new CompanySocial())->getTable(), 'gstin')
                        ];
                    }
                    if ($request->filled($request?->platform . '_username')) {
                        $rules[$request?->platform . '_username'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_email')) {
                        $rules[$request?->platform . '_email'] = [
                            'required',
                            'email',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_password')) {
                        $rules[$request?->platform . '_password'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_api_client_id')) {
                        $rules[$request?->platform . '_api_client_id'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_api_client_secret')) {
                        $rules[$request?->platform . '_api_client_secret'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                } else if ($request->filled('platform') && $request?->platform == "einvoice") {
                    if ($request->filled($request?->platform . '_gstin')) {
                        $rules[$request?->platform . '_gstin'] = [
                            'required',
                            'string',
                            'max:255',
                            Rule::unique((new CompanySocial())->getTable(), 'gstin')
                        ];
                    }
                    if ($request->filled($request?->platform . '_username')) {
                        $rules[$request?->platform . '_username'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_email')) {
                        $rules[$request?->platform . '_email'] = [
                            'required',
                            'email',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_password')) {
                        $rules[$request?->platform . '_password'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_api_client_id')) {
                        $rules[$request?->platform . '_api_client_id'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                    if ($request->filled($request?->platform . '_api_client_secret')) {
                        $rules[$request?->platform . '_api_client_secret'] = [
                            'required',
                            'string',
                            'min:5',
                        ];
                    }
                } else {

                    if ($request->filled('justdial_api_key')) {
                        $rules['justdial_api_key'] = [
                            'required',
                            'string',
                            'max:255',
                            Rule::unique('company_socials', 'api_key')
                        ];
                    }

                    if ($request->filled('linkedin_api_key')) {
                        $rules['linkedin_api_key'] = [
                            'required',
                            'string',
                            'max:255',
                            Rule::unique('company_socials', 'api_key')
                        ];
                    }
                }

                $validator = Validator::make($request->all(), $rules);

                // $updateData = Company::findOrFail($id);
                $updateData = CompanySocial::where('company_id', $id)->where('platform', $request?->platform)->first();

                if ($request->platform == 'facebook') {
                    $pages = $request->input('pages', []);
                    $tokens = $request->input('access_tokens', []);
                    $jsonResponse = $request->input('json_response');
                    $facebookUserToken = $request->input('facebook_user_token');

                    CompanySocial::where('company_id', $id)
                        ->where('platform', $request?->platform)
                        ->forceDelete();

                    foreach ($pages as $pageId) {
                        CompanySocial::create([
                            'company_id' => $id,
                            'platform' => $request?->platform,
                            'page_id' => $pageId,
                            'api_key' => $tokens[$pageId] ?? '',
                            'user_id' => $facebookUserToken ?? null,
                            'json_response' => $jsonResponse ?? null,
                            'minutes_time' => 6,
                            'created_by' => $loginUserId ?? '',
                        ]);
                    }
                } else {
                    if (!$updateData) {
                        $updateData = new CompanySocial();
                        $updateData->company_id = $id;
                        $updateData->platform = $request?->platform;
                        $updateData->created_by = $loginUserId ?? '';
                    }else {
                        $updateData->updated_by = $loginUserId ?? '';
                    }
                    $updateData->api_key = $request[$request?->platform . '_api_key' ?? null];
                    $updateData->user_id = $request[$request?->platform . '_user_id' ?? null];
                    $updateData->profile_id = $request[$request?->platform . '_profile_id' ?? null];
                    $updateData->minutes_time = $request[$request?->platform . '_minutes_time' ?? null];
                    $updateData->api_code = $request[$request?->platform . '_api_code' ?? null];

                    $updateData->gstin = $request[$request?->platform . '_gstin' ?? null];
                    $updateData->username = $request[$request?->platform . '_username' ?? null];
                    $updateData->email = $request[$request?->platform . '_email' ?? null];
                    $updateData->password = $request[$request?->platform . '_password' ?? null];
                    $updateData->api_client_id = $request[$request?->platform . '_api_client_id' ?? null];
                    $updateData->api_client_secret = $request[$request?->platform . '_api_client_secret' ?? null];
                    $updateData->save();
                }

                return Redirect::back()->withSuccess(ucfirst($request?->platform) . ' Third Party Integration successfully.');
            }
            $company = Company::find($id);
            $company_social = CompanySocial::where('company_id', $id)->get()->mapWithKeys(fn($item) => [strtolower($item->platform) => $item]);
            View::share('company_social', $company_social);
            View::share('company', $company);

            return view($modules['folder_path'] . '.thirdparty-integration-platform');
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            return $th->getMessage();
            //throw $th;
        }
    }

    public function set_master_config(Request $request, $platform, $id = null)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $modules['title'] = ucfirst($platform) . " - Config";
        View::share('modules', $modules);

        if (isset($modules['currentGuard']) && $modules['currentGuard'] != 'admin_software') {
            if (isset($modules['company_id']) && $modules['company_id']) {
                $id = $modules['company_id'];
            }
        } else {
            $id = $request?->company_id;
        }

        try {

            View::share('platform', $platform);
            if ($request?->_token) {
                if (!isset($id) || empty($id) || (int)$id <= 0) {
                    return back()->withInput()->withErrors(["Please Select Company."]);
                }

                // Define validation rules
                $rules = [
                    'company_id'            => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                    'facebook_app_id'       => ['required'],
                    'facebook_app_secret'   => ['required'],
                    'facebook_redirect_url' => ['required', 'url'],
                ];

                // Run the validator
                $validator = Validator::make($request->all(), $rules);

                if ($validator->fails()) {
                    return back()->withErrors($validator)->withInput();
                }

                $updateData = MasterSetting::where('company_id', $id)->where('platform', $request?->platform)->first();
                if (!$updateData) {
                    $updateData = new MasterSetting();
                    $updateData->company_id = $id;
                    $updateData->platform = $request?->platform;
                }
                $updateData->app_id = $request[$request?->platform . '_app_id' ?? null];
                $updateData->app_secret = $request[$request?->platform . '_app_secret' ?? null];
                $updateData->redirect_url = $request[$request?->platform . '_redirect_url' ?? null];
                $updateData->save();


                return Redirect::back()->withSuccess(ucfirst($request?->platform) . ' Settings successfully.');
            }
            $company = Company::find($id);
            View::share('company', $company);

            return view($modules['folder_path'] . '.master_setting');
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            return $th->getMessage();
            //throw $th;
        }
    }

    public function set_company_session(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        try {
            $validator = Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            session(['selected_company_id' => $request->company_id]);
            return $this->sendResponse(['selected_company_id' => $request->company_id], 'Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function verify_website_api_code(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        try {
            $validator = Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $randomString = Helper::RandomString(32);
            $data = [
                'company_id' => $request->company_id,
                'api_code'   => $randomString,
            ];

            return $this->sendResponse($data, 'Website API token generated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }


    public function get_company_social(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')->where('status', 'active')
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {

            $pagesFromSession = session('facebook_pages');
            $fb_user_token = session('fb_user_token');
            $facebook_company_id = session('facebook_company_id');

            $pages = [];
            $session_fb_user_token = null;

            // Only set these values if session company_id matches $id
            if (!empty($facebook_company_id) && $facebook_company_id == $request?->company_id) {
                $pages = $pagesFromSession ?? [];
                $session_fb_user_token = $fb_user_token ?? null;
            }

            if ($request?->platform === 'facebook') {

                if (!empty($pages)) {

                    $page_ids_from_db = CompanySocial::where('company_id', $request?->company_id)
                        ->where('platform', $request?->platform)
                        ->whereNotNull('page_id')
                        ->pluck('page_id')
                        ->toArray();

                    $allPageIds = [];
                    foreach ($page_ids_from_db as $entry) {
                        $ids = explode(',', $entry);
                        foreach ($ids as $id) {
                            $trimmed = trim($id);
                            if (!empty($trimmed)) {
                                $allPageIds[] = $trimmed;
                            }
                        }
                    }

                    $data = collect([
                        (object)[
                            'platform'       => 'facebook',
                            'json_response'  => json_encode($pages),
                            'user_id'        => $session_fb_user_token ?? null,
                            'page_id'        => implode(',', $allPageIds),
                        ]
                    ]);
                } else {

                    $data = CompanySocial::where('company_id', $request?->company_id)
                        ->where('platform', $request?->platform)
                        ->whereNotNull('platform')
                        ->orderBy('id', 'asc')
                        ->get();
                }
            } else {
                $data = CompanySocial::where('company_id', $request?->company_id)->where('platform', $request?->platform);
                $data = $data->whereNotNull('platform');
                $data = $data->orderBy('id', 'asc');
                $data = $data->get();
            }

            // return $data;
            return $this->sendResponse($data, "Company social list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    public function get_master_social(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')->where('status', 'active')
            ]
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }
        try {
            $data = MasterSetting::where('company_id', $request?->company_id)->where('platform', $request?->platform);
            $data = $data->whereNotNull('platform');
            $data = $data->orderBy('id', 'asc');
            $data = $data->get();

            // return $data;
            return $this->sendResponse($data, "Master social list.");
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
        return $this->sendError("Something want to wrong in register API");
    }

    /** company wise mail setting */
    public function mail_setting(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['form_route'] = route($modules['route'] . '.mail_setting', [$id]);
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        // dd($modules);

        View::share('modules', $modules);

        try {
            if ($request?->_token) {

                // dd($request->all());
                $validator = Validator::make($request?->all(), [
                    'company_id'     => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                    'mailer'         => ['required', 'in:SMTP,sendmail,mail'], // Adjust if more options are allowed
                    'host'           => ['required', 'string', 'max:255'],
                    'port'           => ['required', 'integer'],
                    'username'       => ['required'], // or 'string' if not strictly email
                    'password'  => ['nullable', 'string', 'max:255'], // Allow empty value
                    'encryption'     => ['nullable', 'in:ssl,tls'], // optional but restrict values
                    'from_address'   => ['required', 'email'],
                    'from_name'      => ['required', 'string', 'max:255'],
                    'bcc'            => ['nullable', function ($attribute, $value, $fail) {
                        $emails = array_map('trim', explode(',', $value));
                        foreach ($emails as $email) {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $fail("The $attribute field contains an invalid email: $email");
                            }
                        }
                    }],
                ]);

                if ($validator->fails()) {
                    return Redirect::back()->withError($validator->messages())->withInput();
                }

                $company = Company::with(['MailSetting'])->findOrFail($id);

                $mailSetting = MailSetting::where('company_id', $request?->company_id)->first();

                $formArray = $request->except(['_token']);
                if (!$mailSetting) {
                    $formArray['created_by'] = $loginUserId;
                    // return $formArray;
                    MailSetting::create($formArray);
                    Session::flash('success', $company?->company_name . " Email setup successfully.");
                } else {
                    $formArray['updated_by'] = $loginUserId;
                    $mailSetting->update($formArray);
                    Session::flash('success', $company?->company_name . " Email setup successfully.");
                }
                // dd("L-809",$id, $mailSetting, $request->all());

                if (!empty($request?->team_set) && $request?->team_set === 'team_update' && !empty($request?->tab && $request?->tab === 'mail-tab')) {
                    return Redirect::route($modules['route'] . '.detail', ['id' => $id, 'tab' => 'mail-tab'])->withSuccess('Mail Setting update successfully');
                } else {
                    return Redirect::route($modules['route'] . '.mail_setting', [$id]);
                }
            }

            $company = Company::with(['MailSetting'])->findOrFail($id);

            View::share('company', $company);
            View::share('mail_setting', $company?->MailSetting);
            if ($request?->btn_submit === 'submit_and_exit') {
                return redirect()->route('company.index')
                    ->withSuccess('Company license details updated and redirected to company list.');
            }
            return view($modules['folder_path'] . '.mail_setting');
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /** company wise license setting */
    public function license_setting(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['form_route'] = route($modules['route'] . '.license_setting', [$id]);
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        // dd($modules);

        View::share('modules', $modules);

        try {
            if ($request?->_token) {

                // dd($request->all());
                $validator = Validator::make($request?->all(), [
                    'company_id'     => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                    'plan_id'              => ['required', 'exists:' . (new PlanMaster())->getTable() . ',id'],
                    'app_key'              => ['nullable', 'string', 'min:8'],
                    'panel_url'            => ['required', 'url'],
                    'max_team_user_count'  => ['required', 'integer', 'min:0'],
                    'max_customer_count'   => ['required', 'integer', 'min:0'],
                    'max_inquiry_count'    => ['required', 'integer', 'min:0'],
                    'app_right'            => ['nullable', 'string'],
                    'panel_right'          => ['required', 'string'],
                    'customer_panel_right' => ['nullable', 'string'],
                ], [
                    'plan_to.after_or_equal' => 'The plan to date must be after or equal to the plan from date.',
                    'panel_url.url' => 'The panel URL must be a valid URL.',
                ]);

                if ($validator->fails()) {
                    return Redirect::back()->withError($validator->messages())->withInput();
                }

                $updateData = Company::findOrFail($id);

                $formArray = $request->except(['_token']);


                $appRightArray = !empty($formArray['app_right']) ? array_filter(array_map('intval', explode(',', $formArray['app_right']))) : [];
                sort($appRightArray, SORT_NUMERIC);
                $formArray['app_right'] = implode(',', $appRightArray);

                $panelRightArray = !empty($formArray['panel_right']) ? array_filter(array_map('intval', explode(',', $formArray['panel_right']))) : [];
                sort($panelRightArray, SORT_NUMERIC);
                $formArray['panel_right'] = implode(',', $panelRightArray);

                if ($request?->customer_panel_right) {
                    $customerPanelRightArray = !empty($formArray['customer_panel_right']) ? array_filter(array_map('intval', explode(',', $formArray['customer_panel_right']))) : [];
                    sort($customerPanelRightArray, SORT_NUMERIC);
                    $formArray['customer_panel_right'] = implode(',', $customerPanelRightArray);
                }


                if ($updateData) {
                    // $formArray['plan_from'] = Helper::convert_date($request?->plan_from, "d/m/Y", "Y/m/d");
                    // $formArray['plan_to'] = Helper::convert_date($request?->plan_to, "d/m/Y", "Y/m/d");
                    // $formArray['updated_by'] = $loginUserId;
                    if (!$request?->app_right) {
                        $formArray['app_right'] = [];
                    }

                    if (!$request?->customer_panel_right) {
                        $formArray['customer_panel_right'] = [];
                    }

                    // return $formArray;
                    unset($formArray['plan_from']);
                    unset($formArray['plan_to']);
                    $updateData->update($formArray);

                    /** Update on the Team Role Permission table */
                    if($formArray['panel_right'] && !empty($formArray['panel_right'])){
                        $formArray['panel_right'] = explode(',', $formArray['panel_right']);
                        RolePermission::whereNotIn('sub_menu_id', $formArray['panel_right'])->where('company_id', $updateData?->id)->delete();
                    }

                    Session::flash('success', $updateData?->company_name . " Licence  detail successfully.");
                }

                if ($request?->btn_submit === 'submit_and_exit') {
                    return redirect()->route('company.index')
                        ->withSuccess('Company License Setting.');
                }
                // dd("L-870",$id, $updateData, $request->all());
                return Redirect::route($modules['route'] . '.license_setting', [$id]);
            }


            $company = Company::with(['plan'])->findOrFail($id);
            // Step 1: Get allowed panel_rights from the plan
            $allowedPanelRightIds = $company->plan && $company->plan->panel_right
                ? (is_array($company->plan->panel_right) ? $company->plan->panel_right : json_decode($company->plan->panel_right, true))
                : [];

            // Step 2: If it's a comma-separated string, convert to array
            if (!is_array($allowedPanelRightIds)) {
                $allowedPanelRightIds = explode(',', $allowedPanelRightIds);
            }

            // Step 3: Ensure all IDs are integers
            $allowedPanelRightIds = array_map('intval', $allowedPanelRightIds);

            // Step 4: Get all menus and filter by allowed ones
            $panel_right_list = Helper::getMainMenu(['platform' => 'panel']);

            foreach ($panel_right_list as $mainKey => &$main_menu) {
                $main_menu->sub_menu = collect($main_menu->sub_menu ?? [])->filter(function ($sub_menu) use ($allowedPanelRightIds) {
                    return in_array((int) $sub_menu->id, $allowedPanelRightIds);
                })->values();

                // Remove groups with no matching rights
                if ($main_menu->sub_menu->isEmpty()) {
                    unset($panel_right_list[$mainKey]);
                }
            }
            unset($main_menu);

            // Step 5: Share it to the view
            View::share('panel_right_list', $panel_right_list);


            $latestPlan = CompanySubscriptionPlan::where('company_id', $id)->where('plan_id', $company?->plan_id)->orderBy('id', 'desc')->first();

            if ($latestPlan && $latestPlan?->plan_id > 0 && !empty($latestPlan?->plan_from) && !empty($latestPlan?->plan_to)) {
                $company->setAttribute('plan_from', \Carbon\Carbon::parse($latestPlan?->plan_from)->format('d-m-Y'));
                $company->setAttribute('plan_to', \Carbon\Carbon::parse($latestPlan?->plan_to)->format('d-m-Y'));
            }
            View::share('company', $company);
            View::share('edit', $company?->plan);


            return view($modules['folder_path'] . '.license_setting');
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /** company upgrade plan */
    public function upgrade_plan(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['form_route'] = route($modules['route'] . '.upgrade_plan', [$id]);
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;


        View::share('modules', $modules);

        try {
            if ($request?->_token) {

                if (isset($request->is_plan_expire) && !empty($request->is_plan_expire)) {
                    $validator = Validator::make($request?->all(), [
                        'company_id'     => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                        'plan_id'              => ['required', 'exists:' . (new PlanMaster())->getTable() . ',id'],
                        'app_key'              => ['nullable', 'string', 'min:8'],
                        'panel_url'            => ['required', 'url'],
                    ], [
                        'panel_url.url' => 'The panel URL must be a valid URL.',
                    ]);

                    if ($validator->fails()) {
                        return Redirect::back()->withError($validator->messages())->withInput();
                    }

                    $updateData = Company::findOrFail($id);

                    $plan_data = PlanMaster::where('id', $request->plan_id)->first();
                    $validated['plan_id'] = $request->plan_id;
                    $validated['app_key'] = $request->app_key;
                    $plan_from = date('Y-m-d');
                    $plan_to = date('Y-m-d');
                    if (!empty($plan_data->plan_valid_day)) {
                        $plan_to = Carbon::now()->addDays($plan_data->plan_valid_day)->format('Y-m-d');
                    }
                    $plan_to = $plan_to;
                    $validated['max_team_user_count'] = $plan_data->max_team_user_count;
                    $validated['max_customer_count'] = $plan_data->max_customer_count;
                    $validated['max_inquiry_count'] = $plan_data->max_inquiry_count;
                    $validated['app_right'] = $plan_data->app_right;
                    $validated['panel_right'] = $plan_data->panel_right;
                    $validated['panel_url'] = url('/software/login');

                    $updateData->update($validated);

                    $today = Carbon::today();
                    $subscription_status = '';
                    if ($plan_from && $plan_to) {
                        if ($today->between(Carbon::parse($plan_from), Carbon::parse($plan_to))) {
                            $subscription_status = 'active';
                        } else {
                            $subscription_status =  'expired';
                        }
                    }

                    CompanySubscriptionPlan::create([
                        'company_id' => $id,
                        'plan_id' => $request->plan_id,
                        'plan_from' => $plan_from,
                        'plan_to' => $plan_to,
                        'extra_detail' => json_encode($plan_data->toArray()),
                        'plan_expiry_date' => $plan_to,
                        'subscription_status' => $subscription_status,
                        'created_by' => $loginUserId,
                    ]);
                }

                if ($request?->btn_submit === 'submit_and_exit') {
                    return redirect()->route('company.index')
                        ->withSuccess('Company License Setting.');
                }
                // dd("L-870",$id, $updateData, $request->all());
                //return Redirect::route($modules['route'] . '.upgrade_plan', [$id]);
                return redirect()->route('company.index')->withSuccess('Company License Setting.');
            }

            $company = Company::with(['plan'])->findOrFail($id);

            $latestPlan = CompanySubscriptionPlan::where('company_id', $id)->where('plan_id', $company?->plan_id)->orderBy('id', 'desc')->first();
            $is_plan_expire = false;
            if ($latestPlan && $latestPlan?->subscription_status === 'expired') {
                $is_plan_expire = true;
                $plans = PlanMaster::where('status', 'active')->get();
                View::share('is_plan_expire', $is_plan_expire);
                View::share('plans', $plans);
            }

            View::share('company', $company);
            View::share('edit', $company?->plan);

            return view($modules['folder_path'] . '.upgrade_plan');
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function detail(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        // dd($modules);

        View::share('modules', $modules);

        try {

            $company = Company::with(['plan'])->findOrFail($id);
            $tab = $request->query('tab');
            View::share('edit', $company);

            if (!$tab) {
                return redirect()->route('profile');
            }
            if ($tab == 'profile-tab') {
                $latestPlan = CompanySubscriptionPlan::where('company_id', $id)->where('plan_id', $company?->plan_id)->orderBy('id', 'desc')->first();
                if ($latestPlan && $latestPlan?->plan_id > 0 && !empty($latestPlan?->plan_from) && !empty($latestPlan?->plan_to)) {
                    $company->setAttribute('plan_from', \Carbon\Carbon::parse($latestPlan?->plan_from)->format('d-m-Y'));
                    $company->setAttribute('plan_to', \Carbon\Carbon::parse($latestPlan?->plan_to)->format('d-m-Y'));
                }
                View::share('company', $company);
            } else if ($tab == 'integration-tab') {
                $company_social = CompanySocial::where('company_id', $id)->get()->mapWithKeys(fn($item) => [strtolower($item->platform) => $item]);
                View::share('company_social', $company_social);
            } else if ($tab == 'mail-tab') {
                View::share('mail_setting', $company?->MailSetting);
            } else if ($tab == 'subscription-tab') {

                if ($request->ajax()) {
                    $data = CompanySubscriptionPlan::with(['plan', 'company'])->select('*')
                        ->where(function ($query) use ($modules, $loginUserId) {
                            // if (Auth::guard('team_person')->check()) {
                            //     $query->where('created_by', $loginUserId);
                            // }
                        })
                        ->where('company_id', $id)
                        ->orderBy('id', 'DESC');

                    $returnData = Datatables::of($data)
                        ->addIndexColumn()
                        ->filter(function ($query) use ($request) {
                            if ($request->has('filter_subscription_status_id') && $request->filter_subscription_status_id && $request->filter_subscription_status_id != 'all') {
                                $query->where('subscription_status', $request->filter_subscription_status_id);
                            }
                            if ($request->has('filter_plan') && $request->filter_plan) {
                                $query->where('plan_id', $request->filter_plan);
                            }
                            if ($request->has('search')) {
                                $search = $request->search;

                                $query->where(function ($q) use ($search) {
                                    $q->WhereHas('plan', function ($q2) use ($search) {
                                        $q2->where('name', 'like', "%{$search}%");
                                    });
                                });
                            }

                            if ($request->filled('filter_plan_date')) {
                                [$fromRaw, $toRaw] = explode(' to ', $request->filter_plan_date);

                                $fromDate = Helper::convert_date(trim($fromRaw), "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date(trim($toRaw), "d/m/Y", "Y-m-d");

                                if ($fromDate && $toDate) {
                                    $table = (new CompanySubscriptionPlan())->getTable();
                                    $query->whereBetween(DB::raw("DATE($table.plan_expiry_date)"), [$fromDate, $toDate]);
                                }
                            }
                        })
                        ->editColumn('company_arrow', function ($row) {

                            $sub_addon_count = CompanySubscriptionAddons::where('company_id', $row->company_id)->where('plan_id', $row->plan_id)->where('subscription_plan_id', $row->id)->count();

                            $arrow_row = '';
                            if ($sub_addon_count > 0) {
                                $arrow_row .= '<span class="toggle-icon company_sub_addon_expand cursor-pointer"
                                            data-company_id="' . $row->company_id . '"
                                            data-plan_id="' . $row->plan_id . '"
                                            data-subscription_id="' . $row->id . '">
                                            <i class="fa fa-caret-right fa-lg"></i>&nbsp;&nbsp;' . $row?->plan?->name . '
                                        </span>';
                            } else {
                                $arrow_row .= '<span>&nbsp;&nbsp;' . $row?->plan?->name . '</span>';
                            }
                            return $arrow_row;
                        })
                        ->editColumn('plan_from', function ($row) {
                            return \Carbon\Carbon::parse($row?->plan_from)->format('d-m-Y');
                        })
                        ->editColumn('plan_to', function ($row) {
                            return \Carbon\Carbon::parse($row?->plan_to)->format('d-m-Y');
                        })
                        ->editColumn('plan_expiry_date', function ($row) {
                            return \Carbon\Carbon::parse($row->plan_expiry_date)->format('d-m-Y');
                        })
                        ->editColumn('subscription_status', function ($row) use ($modules) {
                            if ($row->subscription_status == 'active') {
                                return $row->subscription_status;
                            } else {
                                return '<span class="badge bg-glow" style="background-color: #d66363">' . $row->subscription_status . '</span>';
                            }
                        })
                        ->addColumn('action', function ($row) use ($modules) {
                            $btn = '';
                            if (!$row?->deleted_at) {
                                if ($row->subscription_status == 'active') {
                                    $btn .= '<a href="javascript:void(0);" id="add_days_subscription_btn" data-company_id="' . $row->company_id . '" data-plan_id="' . $row->plan_id . '" data-subscription_id="' . $row->id . '" class="btn btn-primary btn-sm waves-effect waves-light text-white open-add-days-assign-modal"><i class="menu-icon ti ti-calendar"></i> Add days</a>';
                                }
                            }
                            if ($btn == '') {
                                $btn = '-';
                            }
                            return $btn;
                        })
                        ->rawColumns(['subscription_status', 'action', 'company_arrow'])
                        ->make(true);

                    $jsonData = $returnData->getData(true);
                    return response()->json($jsonData);
                }
            }

            return view($modules['folder_path'] . '.update_company_detail');
        } catch (\Exception $e) {
            return $e->getMessage();
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function remove_rate_sheet($id)
    {
        try {
            $company = Company::findOrFail($id);

            if (!empty($company->rate_sheet) && file_exists(public_path($company->rate_sheet))) {
                $backupDir = storage_path('app/rate_sheet_backups');
                if (!file_exists($backupDir)) {
                    @mkdir($backupDir, 0777, true);
                }
                @copy(public_path($company->rate_sheet), $backupDir . '/rate_sheet_backup_' . date('Ymd_His') . '_' . basename($company->rate_sheet));
                @unlink(public_path($company->rate_sheet));
            }

            // Remove any leftover files in uploads/rate_sheet
            $targetDir = public_path('uploads/rate_sheet');
            if (is_dir($targetDir)) {
                $files = glob($targetDir . '/*.{xlsx,xls,XLSX,XLS}', GLOB_BRACE);
                if (!empty($files)) {
                    $backupDir = storage_path('app/rate_sheet_backups');
                    if (!file_exists($backupDir)) {
                        @mkdir($backupDir, 0777, true);
                    }
                    foreach ($files as $f) {
                        @copy($f, $backupDir . '/rate_sheet_backup_' . date('Ymd_His') . '_' . basename($f));
                        @unlink($f);
                    }
                }
            }

            $company->rate_sheet = null;
            $company->save();

            cache()->flush();

            return response()->json([
                'status' => true,
                'message' => 'Rate sheet has been removed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to remove rate sheet: ' . $e->getMessage()
            ], 500);
        }
    }
}

