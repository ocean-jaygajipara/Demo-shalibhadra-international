<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkIssueReportExport;
use App\Http\Controllers\Controller;
use App\Models\JobWorkIssue;
use App\Models\JobWorkIssueItems;
use App\Models\SalesStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;

class JobWorkIssueReportController extends Controller
{

    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'JobWokIssueReport',
            'folder_path' => 'software.modules.job_work_isuee_report',
            'route' => 'jobwork-issue-report',
            'table_name' => '',
            'permisstion_prefix' => 'jobwork-issue-report',
            'module_name' => 'Job Work Issue Report',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permissions
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['view_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            // Table columns
            $columns = [
                (object)['data' => "job_work_issue_no", 'name' => 'job_work_issue_no', 'td_label' => 'Challan No'],
                (object)['data' => "job_work_issue_date", 'name' => 'job_work_issue_date', 'td_label' => 'Date'],
                (object)['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Party Name'],
                (object)['data' => "item_name", 'name' => 'item_name', 'td_label' => 'Item Name'],
                (object)['data' => "issue_weight", 'name' => 'issue_weight', 'td_label' => 'Issued Qty', 'className' => 'text-end'],
                (object)['data' => "taxable_amount", 'name' => 'taxable_amount', 'td_label' => 'Taxable Amt', 'className' => 'text-end'],
                (object)['data' => "cgst_amount", 'name' => 'cgst_amount', 'td_label' => 'CGST', 'className' => 'text-end'],
                (object)['data' => "sgst_amount", 'name' => 'sgst_amount', 'td_label' => 'SGST', 'className' => 'text-end'],
                (object)['data' => "igst_amount", 'name' => 'igst_amount', 'td_label' => 'IGST', 'className' => 'text-end'],
                (object)['data' => "total_gst", 'name' => 'total_gst', 'td_label' => 'Total GST', 'className' => 'text-end'],
                (object)['data' => "grand_total", 'name' => 'grand_total', 'td_label' => 'Grand Total', 'className' => 'text-end'],
            ];
            View::share("columns", $columns);

            if ($request->ajax()) {
                $query = JobWorkIssueItems::with([
                    'job_work_issue.vendor',
                    'job_work_issue.company',
                    'product'
                ])
                    ->whereHas('job_work_issue', function ($q) use ($modules) {
                        if ($modules['company_id']) {
                            $q->where('company_id', $modules['company_id']);
                        }
                    });

                // Filters
                // inside your if ($request->ajax()) { ... }

                if ($request->filled('search')) {
                    $search = trim($request->search);

                    $query->where(function ($q) use ($search) {
                        // Search on job_work_issue.job_work_issue_no OR product.product_name
                        $q->whereHas('job_work_issue', function ($qq) use ($search) {
                            $qq->where('job_work_issue_no', 'like', "%{$search}%");
                        })
                            ->orWhereHas('product', function ($p) use ($search) {
                                $p->where('product_name', 'like', "%{$search}%");
                            });
                    });
                }

                if ($request->filled('filter_vendor')) {
                    $query->whereHas('job_work_issue', function ($q) use ($request) {
                        $q->where('vendor_id', $request->filter_vendor);
                    });
                }

                if ($request->filled('filter_job_work_issue_date')) {
                    $range = explode(" to ", $request->filter_job_work_issue_date);
                    if (count($range) == 2) {
                        $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                        $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                        $query->whereHas('job_work_issue', function ($q) use ($from, $to) {
                            $q->whereBetween('job_work_issue_date', [$from, $to]);
                        });
                    }
                }

                $query->orderByDesc('id');

                // 👉 Clone query for totals
                $totalRows = (clone $query)->get();
                $total_issue_weight = $totalRows->sum('issue_weight');
                $total_taxable_amount = $totalRows->sum('taxable_amount');
                $total_cgst = $totalRows->sum('cgst_amount');
                $total_sgst = $totalRows->sum('sgst_amount');
                $total_igst = $totalRows->sum('igst_amount');
                $total_gst = $total_cgst + $total_sgst + $total_igst;
                $total_grand_total = $total_taxable_amount + $total_gst;

                return Datatables::of($query)
                    ->addColumn('job_work_issue_no', fn($row) => $row->job_work_issue->job_work_issue_no ?? '-')
                    ->addColumn('job_work_issue_date', function ($row) {
                        return $row->job_work_issue && $row->job_work_issue->job_work_issue_date
                            ? Carbon::parse($row->job_work_issue->job_work_issue_date)->format('d/m/Y')
                            : '-';
                    })
                    ->addColumn('vendor_name', fn($row) => $row->job_work_issue->vendor->vendor_name ?? '-')
                    ->addColumn('item_name', fn($row) => $row->product->product_name ?? '-')
                    ->addColumn('issue_weight', fn($row) => number_format($row->issue_weight ?? 0, 3))
                    ->addColumn('taxable_amount', fn($row) => number_format($row->taxable_amount ?? 0, 2))
                    ->addColumn('cgst_amount', fn($row) => number_format($row->cgst_amount ?? 0, 2))
                    ->addColumn('sgst_amount', fn($row) => number_format($row->sgst_amount ?? 0, 2))
                    ->addColumn('igst_amount', fn($row) => number_format($row->igst_amount ?? 0, 2))
                    ->addColumn('total_gst', function ($row) {
                        $total_gst = ($row->cgst_amount ?? 0) + ($row->sgst_amount ?? 0) + ($row->igst_amount ?? 0);
                        return number_format($total_gst, 2);
                    })
                    ->addColumn('grand_total', function ($row) {
                        $grand_total = ($row->taxable_amount ?? 0) +
                            ($row->cgst_amount ?? 0) +
                            ($row->sgst_amount ?? 0) +
                            ($row->igst_amount ?? 0);
                        return number_format($grand_total, 2);
                    })
                    // 👇 Add totals to JSON footer
                    ->with([
                        'footer' => [
                            'total_issue_weight' => number_format($total_issue_weight, 2),
                            'total_taxable_amount' => number_format($total_taxable_amount, 2),
                            'total_cgst' => number_format($total_cgst, 2),
                            'total_sgst' => number_format($total_sgst, 2),
                            'total_igst' => number_format($total_igst, 2),
                            'total_gst' => number_format($total_gst, 2),
                            'total_grand_total' => number_format($total_grand_total, 2),
                        ]
                    ])
                    ->rawColumns(['vendor_name', 'item_name'])
                    ->make(true);
            }


            // Normal View
            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
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

        return Excel::download(new JobWorkIssueReportExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Job Work Issue Report -' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;


        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = ($modules['company_id'] ? Gate::check('hasPermission', [$value, $modules['module_name']]) : true);
            }
        }

        if (empty($modules['print_permission'])) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);


            $query = JobWorkIssueItems::with([
                'product',
                'job_work_issue.vendor',
                'job_work_issue.company',
            ])->orderBy('id', 'DESC');


            if ($request->filled('filter_company')) {
                $query->whereHas('job_work_issue', function ($q) use ($request) {
                    $q->where('company_id', $request->filter_company);
                });
            } elseif ($modules['company_id']) {

                $query->whereHas('job_work_issue', function ($q) use ($modules) {
                    $q->where('company_id', $modules['company_id']);
                });
            }


            if ($request->filled('filter_vendor')) {
                $query->whereHas('job_work_issue', function ($q) use ($request) {
                    $q->where('vendor_id', $request->filter_vendor);
                });
            }


            if ($request->filled('filter_job_work_issue_date')) {
                $dates = explode(' to ', $request->filter_job_work_issue_date);
                if (count($dates) == 2) {
                    $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->format('Y-m-d');
                    $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->format('Y-m-d');
                    $query->whereHas('job_work_issue', function ($q) use ($from, $to) {
                        $q->whereBetween('job_work_issue_date', [$from, $to]);
                    });
                }
            }


            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('job_work_issue', function ($sub) use ($search) {
                        $sub->where('job_work_issue_no', 'like', "%{$search}%");
                    })->orWhereHas('product', function ($sub2) use ($search) {
                        $sub2->where('product_name', 'like', "%{$search}%");
                    });
                });
            }


            $jobWorkIssueItems = $query->get();


            // Calculate dynamic totals
            $grandTotals = [
                'issue_weight'    => 0,
                'taxable_amount'  => 0,
                'cgst_amount'     => 0,
                'sgst_amount'     => 0,
                'igst_amount'     => 0,
                'total_gst'       => 0,
                'grand_total'     => 0,
            ];

            foreach ($jobWorkIssueItems as $row) {
                $cgst = $row->cgst_amount ?? 0;
                $sgst = $row->sgst_amount ?? 0;
                $igst = $row->igst_amount ?? 0;
                $taxable = $row->taxable_amount ?? 0;

                // Calculate totals per item
                $row->total_gst = $cgst + $sgst;
                $row->grand_total = $taxable + $cgst + $sgst + $igst;

                // Accumulate grand totals
                $grandTotals['issue_weight'] += $row->issue_weight ?? 0;
                $grandTotals['taxable_amount'] += $taxable;
                $grandTotals['cgst_amount'] += $cgst;
                $grandTotals['sgst_amount'] += $sgst;
                $grandTotals['igst_amount'] += $igst;
                $grandTotals['total_gst'] += $row->total_gst;
                $grandTotals['grand_total'] += $row->grand_total;
            }


            return view($modules['folder_path'] . '.print', compact('jobWorkIssueItems', 'grandTotals', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors('Error: ' . $e->getMessage());
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
}
