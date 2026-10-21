<?php

namespace App\Http\Controllers\software;

use App\Exports\SalesOrderReportExport;
use App\Helpers\Helper;
use App\Models\Quotation;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;

class SalesOrderReportController extends Controller
{

    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'SalesOrderReport',
            'folder_path' => 'software.modules.sales_order_report',
            'route' => 'sales-order-report',
            'table_name' => '',
            'permisstion_prefix' => 'sales-order-report',
            'module_name' => 'SalesOrder Report',
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
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
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

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                $filter_type = $request->get('filter_type');

                $fromDate = null;
                $toDate = null;

                if ($request->has('filter_sales_date') && $request->filter_sales_date) {
                    $filter_sales_date = explode(" to ", $request->filter_sales_date);
                    if (count($filter_sales_date) === 2) {
                        $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_sales_date[0]));
                        $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_sales_date[1]));
                    }
                }

                if (!$fromDate || !$toDate) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please select start date and end date'
                    ], 422);
                }

                // Prepare date labels based on filter_type
                if ($filter_type === 'month') {
                    $period = CarbonPeriod::create($fromDate->copy()->startOfMonth(), '1 month', $toDate->copy()->startOfMonth());
                } elseif ($filter_type === 'year') {
                    $startYear = $fromDate->copy()->startOfYear();
                    $endYear = $toDate->copy()->startOfYear();
                    $period = CarbonPeriod::create($startYear, '1 year', $endYear);
                } else {
                    $period = CarbonPeriod::create($fromDate, '1 day', $toDate);
                }

                $dateLabels = collect();
                foreach ($period as $date) {
                    if ($filter_type === 'month') {
                        $dateLabels->push($date->format('m-Y'));
                    } elseif ($filter_type === 'year') {
                        $dateLabels->push($date->format('Y'));
                    } else {
                        $dateLabels->push($date->format('d-m-Y'));
                    }
                }

                // Fetch all customers (even those without sales)
                $customersQuery = Customer::with('company');

                if (Auth::guard('team_person')->check()) {
                    $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                    $customersQuery->where('company_id', $teamPersonCompanyId);
                }

                if ($request->has('filter_company') && $request->filter_company) {
                    $customersQuery->where('company_id', $request->filter_company);
                }

                if ($request->has('filter_customer') && $request->filter_customer) {
                    $customersQuery->where('id', $request->filter_customer);
                }

                if ($request->has('filter_customer_type') && $request->filter_customer_type) {
                    $customersQuery->where('customer_type_id', $request->filter_customer_type);
                }

                $allCustomers = $customersQuery->get();

                $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
                $toDate   = Carbon::parse($toDate)->format('Y-m-d');

                $allSalesOrders = SalesOrder::whereBetween('sales_order_date', [$fromDate, $toDate])
                    ->get()
                    ->groupBy('customer_id');

                $data = [];
                $chartDataMap = collect();
                $srNo = 1;

                foreach ($allCustomers as $customer) {
                    $orders = $allSalesOrders[$customer->id] ?? collect();
                    $row = [
                        'sr_no' => $srNo++,
                        'customer_name' => "{$customer?->customer_name} ({$customer?->contact_person_name})",
                        'total_sales' => number_format($orders->sum('grand_total'), 2),
                    ];

                    foreach ($dateLabels as $label) {
                        $filtered = $orders->filter(function ($order) use ($label, $filter_type) {
                            $orderDate = \Carbon\Carbon::parse($order->sales_order_date);
                            $formattedOrderDate = match ($filter_type) {
                                'month' => $orderDate->format('m-Y'),
                                'year' => $orderDate->format('Y'),
                                default => $orderDate->format('d-m-Y'),
                            };
                            return $formattedOrderDate === $label;
                        });

                        $sum = $filtered->sum('grand_total');
                        $row[$label] = number_format($sum, 2);
                        $chartDataMap[$label] = ($chartDataMap[$label] ?? 0) + $sum;
                    }

                    $data[] = $row;
                }

                $columns = collect(['sr_no', 'customer_name', 'total_sales'])->merge($dateLabels)->values();

                $chartData = $chartDataMap->map(function ($value, $label) {
                    return [
                        'label' => $label,
                        'value' => round($value, 2),
                    ];
                })->values();

                return response()->json([
                    'data' => $data,
                    'columns' => $columns,
                    'chartData' => $chartData,
                ]);
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

        return Excel::download(new SalesOrderReportExport($request->all(), $this->authenticateLoginUserDetails), 'SalesOrderReport-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['print_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $filter_type = $request->get('filter_type');
            $display_format = $request->get('display_format');
            $filter_company = $request->get('filter_company');

            $fromDate = null;
            $toDate = null;

            if ($request->has('filter_sales_date') && $request->filter_sales_date) {
                $filter_sales_date = explode(" to ", $request->filter_sales_date);
                if (count($filter_sales_date) === 2) {
                    $fromDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_sales_date[0]));
                    $toDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($filter_sales_date[1]));
                }
            }

            if (!$fromDate || !$toDate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please select start date and end date'
                ], 422);
            }

            // Prepare date labels
            if ($filter_type === 'month') {
                $period = CarbonPeriod::create($fromDate->copy()->startOfMonth(), '1 month', $toDate->copy()->startOfMonth());
            } elseif ($filter_type === 'year') {
                $startYear = $fromDate->copy()->startOfYear();
                $endYear = $toDate->copy()->startOfYear();
                $period = CarbonPeriod::create($startYear, '1 year', $endYear);
            } else {
                $period = CarbonPeriod::create($fromDate, '1 day', $toDate);
            }

            $dateLabels = collect();
            foreach ($period as $date) {
                if ($filter_type === 'month') {
                    $dateLabels->push($date->format('m-Y'));
                } elseif ($filter_type === 'year') {
                    $dateLabels->push($date->format('Y'));
                } else {
                    $dateLabels->push($date->format('d-m-Y'));
                }
            }

            // Fetch all customers (even those without sales)
            $customersQuery = Customer::with('company');

            if (Auth::guard('team_person')->check()) {
                $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                $customersQuery->where('company_id', $teamPersonCompanyId);
            }

            if ($request->has('filter_company') && $request->filter_company) {
                $customersQuery->where('company_id', $request->filter_company);
            }

            if ($request->has('filter_customer') && $request->filter_customer) {
                $customersQuery->where('id', $request->filter_customer);
            }

            if ($request->has('filter_customer_type') && $request->filter_customer_type) {
                $customersQuery->where('customer_type_id', $request->filter_customer_type);
            }

            $allCustomers = $customersQuery->orderby("id","DESC")->get();

            $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
            $toDate   = Carbon::parse($toDate)->format('Y-m-d');
            $allSalesOrders = SalesOrder::whereBetween('sales_order_date', [$fromDate, $toDate])->get()->groupBy('customer_id');

            $data = [];
            $chartDataMap = collect();
            $srNo = 1;
            foreach ($allCustomers as $customer) {
                $orders = $allSalesOrders[$customer->id] ?? collect();
                $row = [
                    'sr_no' => $srNo++,
                    'customer_name' => "{$customer->customer_name} ({$customer->company->company_name})",
                    'total_sales' => number_format($orders->sum('grand_total'), 2),
                ];

                foreach ($dateLabels as $label) {
                    $filtered = $orders->filter(function ($order) use ($label, $filter_type) {
                        $orderDate = \Carbon\Carbon::parse($order->sales_order_date);
                        $formattedOrderDate = match ($filter_type) {
                            'month' => $orderDate->format('m-Y'),
                            'year' => $orderDate->format('Y'),
                            default => $orderDate->format('d-m-Y'),
                        };
                        return $formattedOrderDate === $label;
                    });

                    $sum = $filtered->sum('grand_total');
                    $row[$label] = number_format($sum, 2);
                    $chartDataMap[$label] = ($chartDataMap[$label] ?? 0) + $sum;
                }

                $data[] = $row;
            }

            $columns = collect(['sr_no', 'customer_name', 'total_sales'])->merge($dateLabels)->values();

            $chartData = $chartDataMap->map(function ($value, $label) {
                return [
                    'label' => $label,
                    'value' => round($value, 2),
                ];
            })->values();

            return view($modules['folder_path'] . '.print', compact('data', 'columns', 'chartData','display_format'));

        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

}
