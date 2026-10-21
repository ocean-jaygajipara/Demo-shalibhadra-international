<?php

namespace App\Http\Controllers\software;

use App\Exports\ProductSalesReportExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderItems;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;

class ProductSalesReportController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'ProductSalesReport',
            'folder_path' => 'software.modules.product_sales_report',
            'route' => 'product-sales-report',
            'table_name' => '',
            'permisstion_prefix' => 'product-sales-report',
            'module_name' => 'ProductSales Report',
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

                $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
                $toDate   = Carbon::parse($toDate)->format('Y-m-d');
                $query = SalesOrderItems::with([
                        'sales_order.customer',
                        'product_variant_price.variant'
                    ]);
                if (Auth::guard('team_person')->check()) {
                    $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                    $query->where('company_id', $teamPersonCompanyId);
                }
                $query->whereHas('sales_order', function ($q) use ($fromDate, $toDate, $request) {
                    $q->whereBetween('sales_order_date', [$fromDate, $toDate]);

                    if ($request->filled('filter_company')) {
                        $q->where('company_id', $request->filter_company);
                    }

                    if ($request->filled('filter_customer')) {
                        $q->where('customer_id', $request->filter_customer);
                    }
                });
                $query->whereHas('product_variant_price.product', function ($q) use ($request) {
                    if ($request->filled('filter_product_category')) {
                        $q->where('product_category_id', $request->filter_product_category);
                    }

                    if ($request->filled('filter_product_subcategory')) {
                        $q->where('product_sub_category_id', $request->filter_product_subcategory);
                    }
                });
                $query->whereHas('product_variant_price', function ($q) use ($request) {
                    if ($request->filled('filter_product_variant')) {
                        $q->where('id', $request->filter_product_variant);
                    }
                });
                $salesOrderItems = $query->get();


                $grouped = $salesOrderItems->groupBy(function ($item) {
                    return $item?->sales_order?->customer_id . '-' . $item?->product_variant_price?->variant?->id;
                });

                $data = [];
                $chartData = [
                    'labels' => $dateLabels,
                    'datasets' => [],
                ];
                $srNo = 1;

                foreach ($grouped as $items) {

                    $firstItem = $items->first();
                    $customer = $firstItem->sales_order->customer;
                    $productName = optional($firstItem->product)->product_name ?? 'N/A';
                    $product_code = optional($firstItem->product_variant_price)->product_code ?? 'N/A';
                    $productVariantName = optional($firstItem->product_variant_price)->variant?->name ?? 'N/A';

                    $row = [
                        'sr_no' => $srNo++,
                        'customer_name' => optional($customer)->customer_name ?? '-',
                        'product_name' => $productName." - ".$productVariantName." - ".$product_code,
                        'total_price' => number_format($items->sum('amount'), 2),
                        'total_qty' => number_format($items->sum('quantity'), 2),
                    ];

                    foreach ($dateLabels as $label) {
                        $filtered = $items->filter(function ($item) use ($label, $filter_type) {
                            $date = Carbon::parse($item->sales_order->sales_order_date);
                            $formatted = match ($filter_type) {
                                'month' => $date->format('m-Y'),
                                'year' => $date->format('Y'),
                                default => $date->format('d-m-Y'),
                            };
                            return $formatted === $label;
                        });

                        $qty = $filtered->sum('quantity');
                        $sales = $filtered->sum('rate_in_amount');

                        $row[$label . '_price'] = number_format($sales, 2);
                        $row[$label . '_qty'] = $qty;

                        $chartDataMap[$label] = ($chartDataMap[$label] ?? 0) + $sales;
                    }

                    $data[] = $row;
                }

                // Build column headers
                $columns = collect(['sr_no', 'customer_name', 'product_name', 'total_price', 'total_qty']);
                foreach ($dateLabels as $label) {
                    $columns->push($label . '_price');
                    $columns->push($label . '_qty');
                }
               $colors = ['#3e95cd', '#ff6384', '#36a2eb', '#ffce56', '#9966ff']; // customize

                foreach ($data as $index => $row) {
                    $productName = $row['product_name'];
                    $baseColor = $colors[$index % count($colors)];

                    $priceData = [];
                    $qtyData = [];

                    foreach ($dateLabels as $label) {
                        $priceKey = $label . '_price';
                        $qtyKey = $label . '_qty';

                        $priceData[] = (float) str_replace(',', '', $row[$priceKey] ?? 0);
                        $qtyData[] = (float) str_replace(',', '', $row[$qtyKey] ?? 0);
                    }

                    // Line for Price
                    $chartData['datasets'][] = [
                        'label' => $productName . ' (Price)',
                        'data' => $priceData,
                        'borderColor' => $baseColor,
                        'backgroundColor' => 'transparent',
                        'yAxisID' => 'yPrice',
                        'tension' => 0.4,
                    ];

                    // Line for Quantity
                    $chartData['datasets'][] = [
                        'label' => $productName . ' (Qty)',
                        'data' => $qtyData,
                        'borderColor' => $baseColor,
                        'backgroundColor' => 'transparent',
                        'borderDash' => [5, 5], // dashed line for Qty
                        'yAxisID' => 'yQty',
                        'tension' => 0.4,
                    ];
                }


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

        return Excel::download(new ProductSalesReportExport($request->all(), $this->authenticateLoginUserDetails), 'ProductSalesReport-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
}
