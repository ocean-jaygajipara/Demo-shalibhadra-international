<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CompanySubscriptionAddons;
use App\Models\CompanySubscriptionPlan;
use App\Models\Company;
use App\Models\PlanMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class CompanySubscriptionPlanController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Company Subscription Plan',
            'folder_path' => 'software.modules.company_subscription_plan',
            'route' => 'company-subscription-plan',
            'table_name' => (new CompanySubscriptionPlan())->getTable(),
            'permisstion_prefix' => 'company-subscription-plan',
            'module_name' => 'Company Subscription Plan'
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            View::share('modules', $modules);

            $columns = [
                 (object)['data' => "company_arrow",'name' => 'company_id','td_label' => 'Company Name','className' => 'toggle-addons', 'orderable' => false,'searchable' => false],
               // (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "plan.name", 'name' => 'plan_id', 'td_label' => 'Plan Name', 'className' =>  ''],
                (object)['data' => "plan_from", 'name' => 'plan_from', 'td_label' => 'Plan From', 'className' =>  ''],
                (object)['data' => "plan_to", 'name' => 'plan_to', 'td_label' => 'Plan To', 'className' =>  ''],
                (object)['data' => "plan_expiry_date", 'name' => 'plan_expiry_date', 'td_label' => 'Plan Expiry Date', 'className' =>  ''],
                (object)['data' => "subscription_status", 'name' => 'subscription_status', 'td_label' => 'Subscription Status', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-center'],
            ];

            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });
                $columns = array_values($columns);
            }

            View::share("columns", $columns);
            if ($request->ajax()) {
                $data = CompanySubscriptionPlan::with(['plan','company'])->select('*')
                        ->where(function ($query) use ($modules, $loginUserId) {
                            if (Auth::guard('team_person')->check()) {
                                $query->where('created_by', $loginUserId);
                            }
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
                    ->addColumn('company_arrow', function ($row) {
                        $sub_addon_count = CompanySubscriptionAddons::where('company_id', $row->company_id)->where('plan_id', $row->plan_id)->where('subscription_plan_id', $row->id)->count();

                        $arrow_row = '';
                        if($sub_addon_count > 0){
                             $arrow_row .= '<span class="toggle-icon company_sub_addon_expand cursor-pointer"
                                    data-company_id="'.$row->company_id.'"
                                    data-plan_id="'.$row->plan_id.'"
                                    data-subscription_id="'.$row->id.'">
                                    <i class="fa fa-caret-right fa-lg"></i>&nbsp;&nbsp;'.$row->company->company_name.'
                                </span>';
                        }else{
                             $arrow_row .= '<span>&nbsp;&nbsp;'.$row->company->company_name.'</span>';
                        }
                        return $arrow_row;
                    })
                    ->editColumn('plan_id', function ($row) {
                        return $row?->plan?->name;
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
                        if($row->subscription_status == 'active'){
                           return $row->subscription_status;
                        }else{
                           return '<span class="badge bg-glow" style="background-color: #d66363">'.$row->subscription_status.'</span>';
                        }

                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row?->deleted_at) {
                            if($row->subscription_status == 'active'){
                                $btn .= '<a href="javascript:void(0);" id="add_days_subscription_btn" data-company_id="'. $row->company_id.'" data-plan_id="'. $row->plan_id.'" data-subscription_id="'. $row->id.'" class="btn btn-primary btn-sm waves-effect waves-light text-white open-add-days-assign-modal"><i class="menu-icon ti ti-calendar"></i> Add days</a>';
                            }
                        }
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->rawColumns(['subscription_status', 'action','company_arrow'])
                    ->make(true);
                return $returnData;
            }

            return view($modules['folder_path'] . '.show',compact('id'));
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function update_subscription_plan(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'nullable',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'plan_id' => [
                    'nullable',
                    Rule::exists((new PlanMaster())->getTable(), 'id')
                ],
                'subscription_id' => [
                    'required',
                    Rule::exists((new CompanySubscriptionPlan())->getTable(), 'id')
                ],
                'add_days' => ['required', 'integer', 'min:1'],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $company_subscription_addons = CompanySubscriptionAddons::create([
                'company_id' => $request?->company_id,
                'subscription_plan_id' => $request?->subscription_id,
                'plan_id' => $request?->plan_id,
                'add_days' => $request?->add_days,
                'created_by' => $loginUserId,
            ]);

            $subscription_plans = CompanySubscriptionPlan::findOrFail($request?->subscription_id);

            if (!empty($subscription_plans->plan_to) && !empty($request?->add_days)) {

                $subscription_updated_date = (isset($subscription_plans->plan_expiry_date) && !empty($subscription_plans->plan_expiry_date)) ? $subscription_plans->plan_expiry_date : $subscription_plans->plan_to;

                $days = (int) $request->add_days;
                $updated_plan_date = Carbon::parse($subscription_updated_date)->addDays($days)->format('Y-m-d');

                $subscription_plans->plan_expiry_date = $updated_plan_date;
                $subscription_plans->save();
            }

            return $this->sendResponse([], 'Subscription plan updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something want to wrong', $e->getMessage(), [], 500);
        }
    }

    public function get_subscription_addons(Request $request){
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

         try {
            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'nullable',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                'plan_id' => [
                    'nullable',
                    Rule::exists((new PlanMaster())->getTable(), 'id')
                ],
                'subscription_id' => [
                    'required',
                    Rule::exists((new CompanySubscriptionPlan())->getTable(), 'id')
                ]
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $subscription_plans = CompanySubscriptionPlan::findOrFail($request?->subscription_id);

            if (!empty($subscription_plans->plan_from) && !empty($subscription_plans->plan_to)) {
                    $subscription_addons = CompanySubscriptionAddons::with(['company', 'plan'])
                        ->where('company_id', $request->company_id)
                        ->where('subscription_plan_id', $request->subscription_id)
                        ->where('plan_id', $request->plan_id)
                        ->orderBy('id', 'ASC')
                        ->get();

                    $currentPlanTo = Carbon::parse($subscription_plans->plan_to);

                    foreach ($subscription_addons as $key => $value) {
                        if (!empty($value->add_days)) {

                            $days = (int) $value->add_days;
                            $currentPlanTo = $currentPlanTo->copy()->addDays($days);

                            $value->setAttribute('plan_from', Carbon::parse($subscription_plans->plan_from)->format('d-m-Y'));
                            $value->setAttribute('plan_to', $currentPlanTo->format('d-m-Y'));
                            $value->setAttribute('created_at_org', Carbon::parse($value->created_at)->format('d-m-Y'));
                        }
                    }

                    return $this->sendResponse($subscription_addons, 'Subscription Addons List');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something want to wrong', $e->getMessage(), [], 500);
        }
    }
}
