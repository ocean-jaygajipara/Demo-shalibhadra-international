<?php

namespace App\Http\Controllers\software;

use App\Exports\CustomerReportExport;
use App\Helpers\Helper;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Dispatch;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;


class CustomerReportController extends Controller
{

    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'CustomerReport',
            'folder_path' => 'software.modules.customer_report',
            'route' => 'customer-report',
            'table_name' => '',
            'permisstion_prefix' => 'customer-report',
            'module_name' => 'Customer Report',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {

            View::share('modules', $modules);

            $columns = [
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  ''],
                (object)['data' => "customer_type", 'name' => 'customer_type', 'td_label' => 'Customer Type', 'className' =>  ''],
                (object)['data' => "customer_state_city", 'name' => 'customer_state_city', 'td_label' => 'Customer State City', 'className' =>  ''],
                (object)['data' => "quotation_value", 'name' => 'quotation_value', 'td_label' => 'Quotation Value', 'className' =>  ''],
                (object)['data' => "order_value", 'name' => 'order_value', 'td_label' => 'Order Value', 'className' =>  ''],
                (object)['data' => "dispatch_value", 'name' => 'dispatch_value', 'td_label' => 'Total Dispatch', 'className' =>  ''],
                //(object)['data' => "followup", 'name' => 'followup', 'td_label' => 'Follow Up', 'className' =>  ''],
                // (object)['data' => "total_visit", 'name' => 'total_visit', 'td_label' => 'Total Visit', 'className' =>  ''],
                // (object)['data' => "complain", 'name' => 'complain', 'td_label' => 'Complain', 'className' =>  ''],
            ];

            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });
                $columns = array_values($columns);
            }

            $modules['sales_order_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Sales Orders']);
            $modules['quotation_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Quotation']);
            $modules['dispatch_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Dispatch']);

            if(!$modules['sales_order_view']){
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'order_value';
                });
                $columns = array_values($columns);
            }

            if(!$modules['quotation_view']){
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'quotation_value';
                });
                $columns = array_values($columns);
            }

            if(!$modules['dispatch_view']){
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'dispatch_value';
                });
                $columns = array_values($columns);
            }

            View::share("columns", $columns);

            if ($request->ajax()) {

                $fromDate = null;
                $toDate = null;
                if ($request->has('filter_date') && $request->filter_date) {
                    $filter_date = explode(" to ", $request->filter_date);
                    if (count($filter_date) === 2) {
                        $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_date[0]))->format('Y-m-d');
                        $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_date[1]))->format('Y-m-d');
                    }
                }

                $data = Customer::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });

                if (Auth::guard('team_person')->check()) {
                    $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                    $data->where('company_id', $teamPersonCompanyId);
                }
                $data = $data->with(['company', 'customerType','state', 'city','salesOrder','quotation','dispatch']);
                $data = $data->orderBy('id', 'desc');

                $chartData = [
                    'labels' => [],
                    'order_values' => [],
                ];


                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('filter_customer') && $request->filter_customer) {
                            $query->where('id', $request->filter_customer);
                        }
                        if ($request->has('filter_customer_type') && $request->filter_customer_type) {
                            $query->where('customer_type_id', $request->filter_customer_type);
                        }
                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('customer_name', 'like', "%{$search}%")
                                    ->orWhereHas('state', function ($q2) use ($search) {
                                        $q2->where('name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('city', function ($q2) use ($search) {
                                        $q2->where('name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->addIndexColumn()
                    ->addColumn('company_id', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode   = $row->client_code;
                        $customerName = $row->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return '<span style="white-space:nowrap;">'.$clientCode.'<br>'.$customerName.'</span>';
                        }

                        return $clientCode;
                    })
                    ->addColumn('customer_type', function ($row) {
                        return optional($row?->customerType)?->name;
                    })
                    ->addColumn('customer_state_city', function ($row) {
                        return optional($row?->state)->name. " - ".optional($row?->city)->name;
                    })
                    ->addColumn('quotation_value', function ($row) use ($fromDate, $toDate) {

                        $Quotation = $row->quotation->filter(function ($quotation) use ($fromDate, $toDate) {
                            return $quotation?->quotation_date >= $fromDate && $quotation?->quotation_date <= $toDate;
                        });

                        $count_q = $Quotation->count();
                        $sum_q = $Quotation->sum('grand_total');

                        if ($count_q > 0) {
                            return '<span class="view_quotation text-blue-600 cursor-pointer underline"
                                        data-customer-id="' . $row?->id . '"
                                        data-company-id="' . $row?->company_id. '"
                                        data-from-date="' . $fromDate . '"
                                        data-to-date="' . $toDate . '"><strong>
                                        ' . number_format($sum_q, 2) . '</strong> (' . $count_q . ')
                                    </span>';
                        } else {
                            return '0';
                        }
                    })
                    ->addColumn('order_value', function ($row) use ($fromDate, $toDate) {

                        $salesOrders = $row->salesOrder->filter(function ($order) use ($fromDate, $toDate) {
                            return $order?->sales_order_date >= $fromDate && $order?->sales_order_date <= $toDate;
                        });

                        $count = $salesOrders->count();
                        $sum = $salesOrders->sum('grand_total');

                        if ($count > 0) {
                            return '<span class="view_sales_orders text-blue-600 cursor-pointer underline"
                                        data-customer-id="' . $row?->id . '"
                                        data-company-id="' . $row?->company_id. '"
                                        data-from-date="' . $fromDate . '"
                                        data-to-date="' . $toDate . '"><strong>
                                        ' . number_format($sum, 2) . '</strong> (' . $count . ')
                                    </span>';
                        } else {
                            return '0';
                        }
                    })
                    ->addColumn('dispatch_value', function ($row) use ($fromDate, $toDate) {
                        $dispatches = $row->dispatch->filter(function ($dispatch) use ($fromDate, $toDate) {
                            return $dispatch?->dispatch_date >= $fromDate && $dispatch?->dispatch_date <= $toDate;
                        });

                        $totalCount = $dispatches->count();

                        if ($totalCount > 0) {
                            return '<span class="view_dispatch text-blue-600 cursor-pointer underline"
                                        data-customer-id="' . $row?->id . '"
                                        data-company-id="' . $row?->company_id . '"
                                        data-from-date="' . $fromDate . '"
                                        data-to-date="' . $toDate . '">
                                        <strong>' . $totalCount . '</strong>
                                    </span>';
                        } else {
                            return '0';
                        }
                    })
                    // ->addColumn('followup', function ($row) use ($fromDate, $toDate) {
                    //     return '0';
                    // })
                    // ->addColumn('total_visit', function ($customer) use ($fromDate, $toDate) {
                    //     return '-';
                    // })
                    // ->addColumn('complain', function ($customer) use ($fromDate, $toDate) {
                    //     return '-';
                    // })
                    ->rawColumns(['order_value','quotation_value','dispatch_value','customer_name'])
                    ->make(true);

                $customers_chart = clone $data;
                $customers_chart = $customers_chart->get();

                foreach ($customers_chart as $customer) {
                    $chartData['labels'][] = $customer->customer_name;

                    $salesOrders = $customer->salesOrder->filter(function ($order) use ($fromDate, $toDate) {
                        return $order?->sales_order_date >= $fromDate && $order?->sales_order_date <= $toDate;
                    });
                    $orderTotal = $salesOrders->sum('grand_total');
                    $chartData['order_values'][] = round($orderTotal, 2);
                }

                if (!$modules['view_permission']) {
                    abort(403, 'Unauthorized');
                }
                $tableResponse = $returnData->getData(true);

                $tableResponse['chartData'] = $chartData;

                return response()->json($tableResponse);
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
    public function show(string $id)
    {
        //
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

    public function get_sales_order_report(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        try {
            $validator = Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'customer_id' => ['required', Rule::exists((new Customer())->getTable(), 'id')],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date'],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $companyId = $request->company_id;
            $customerId = $request->customer_id;
            $fromDate = Carbon::parse($request->from_date)->format('Y-m-d');
            $toDate = Carbon::parse($request->to_date)->format('Y-m-d');

            $query = SalesOrder::with(['sales_status','company','customer']);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }
            $query->whereBetween('sales_order_date', [$fromDate, $toDate]);
            $sales_orders = $query->get(['id','company_id','customer_id', 'sales_order_no', 'sales_order_date', 'grand_total', 'sales_status_id']);


            $salesOrderData = $sales_orders->map(function ($order) {
                return [
                    'company_name' => $order?->company?->company_name ?? '-' ,
                    'customer_name' => $order?->customer?->customer_name ?? '-' ,
                    'customer_mobile' => $order?->customer?->mobile_number ?? '-' ,
                    'customer_email' => $order?->customer?->email ?? '-' ,
                    'sales_order_no' => $order?->sales_order_no,
                    'sales_order_date' => Carbon::parse($order?->sales_order_date)->format('d-m-Y'),
                    'grand_total' => $order?->grand_total,
                    'sales_status' => $order?->sales_status?->statusname ?? null,
                    'sales_status_color' => $order?->sales_status?->status_color ?? null,
                ];
            });

            return $this->sendResponse($salesOrderData, 'Sales Orders Fetched Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function get_quotation_report(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        try {
            $validator = Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'customer_id' => ['required', Rule::exists((new Customer())->getTable(), 'id')],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date'],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $companyId = $request->company_id;
            $customerId = $request->customer_id;
            $fromDate = Carbon::parse($request->from_date)->format('Y-m-d');
            $toDate = Carbon::parse($request->to_date)->format('Y-m-d');

            $query = Quotation::with(['sales_status','company','customer']);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }
            $query->whereBetween('quotation_date', [$fromDate, $toDate]);
            $quotation = $query->get(['id','company_id','customer_id', 'quotation_no', 'quotation_date', 'grand_total', 'sales_status_id']);


            $quotationData = $quotation->map(function ($order) {
                return [
                    'company_name' => $order?->company?->company_name ?? '-' ,
                    'customer_name' => $order?->customer?->customer_name ?? '-' ,
                    'customer_mobile' => $order?->customer?->mobile_number ?? '-' ,
                    'customer_email' => $order?->customer?->email ?? '-' ,
                    'quotation_no' => $order?->quotation_no,
                    'quotation_date' => Carbon::parse($order?->quotation_date)->format('d-m-Y'),
                    'grand_total' => $order?->grand_total,
                    'sales_status' => $order?->sales_status?->statusname ?? null,
                    'sales_status_color' => $order?->sales_status?->status_color ?? null,
                ];
            });

            return $this->sendResponse($quotationData, 'Quotation Fetched Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function get_dispatch_report(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        try {
            $validator = Validator::make($request->all(), [
                'company_id' => ['required', Rule::exists((new Company())->getTable(), 'id')],
                'customer_id' => ['required', Rule::exists((new Customer())->getTable(), 'id')],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date'],
            ]);

            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            $companyId = $request->company_id;
            $customerId = $request->customer_id;
            $fromDate = Carbon::parse($request->from_date)->format('Y-m-d');
            $toDate = Carbon::parse($request->to_date)->format('Y-m-d');

            $query = Dispatch::with(['company','customer','sales_order','dispatch_items']);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }
            $query->whereBetween('dispatch_date', [$fromDate, $toDate]);
            $dispatch = $query->select(['id','company_id','customer_id', 'dispatch_no', 'dispatch_date'])->get();


            $dispatchData = $dispatch->map(function ($order) {
                return [
                    'company_name' => $order?->company?->company_name ?? '-' ,
                    'customer_name' => $order?->customer?->customer_name ?? '-' ,
                    'customer_mobile' => $order?->customer?->mobile_number ?? '-' ,
                    'customer_email' => $order?->customer?->email ?? '-' ,
                    'dispatch_no' => $order?->dispatch_no,
                    'dispatch_date' => Carbon::parse($order?->dispatch_date)->format('d-m-Y'),
                    'dispatch_quantity' => $order?->dispatch_items->sum('dispatch_quantity'),
                ];
            });

            return $this->sendResponse($dispatchData, 'Dispatch Fetched Successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation failed.', $e->errors(), [], 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', $e->getMessage(), [], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['excel_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(new CustomerReportExport($request->all(), $this->authenticateLoginUserDetails), 'CustomerReport-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

}
