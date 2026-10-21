<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkReceiveReportExport;
use App\Http\Controllers\Controller;
use App\Models\JobWorkReceiveItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;
use App\Models\JobWorkReceive;
use Carbon\Carbon;

class JobWorkReceiveReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'JobWokReceiveReport',
            'folder_path' => 'software.modules.job_work_receive_report',
            'route' => 'jobwork-receive-report',
            'table_name' => '',
            'permisstion_prefix' => 'jobwork-receive-report',
            'module_name' => 'Job Work Receive Report',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
        // dd("hii");
    }

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


            $columns = [
                (object)['data' => "job_work_receive_no", 'name' => 'job_work_receive_no', 'td_label' => 'Ch. No'],
                (object)['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Party Name'],
                (object)['data' => "product_name", 'name' => 'product_name', 'td_label' => 'Item Name'],
                (object)['data' => "process_name", 'name' => 'process_name', 'td_label' => 'Process'],
                (object)['data' => "job_work_receive_date", 'name' => 'job_work_receive_date', 'td_label' => 'Date'],
                (object)['data' => "quantity", 'name' => 'quantity', 'td_label' => 'Recd. Qty', 'className' => 'text-end'],
                (object)['data' => "received_weight", 'name' => 'received_weight', 'td_label' => 'Recd. Kgs', 'className' => 'text-end'],
                (object)['data' => "incoming_scrap", 'name' => 'incoming_scrap', 'td_label' => 'Incoming Scrap', 'className' => 'text-end'],
                (object)['data' => "rate_in_amount", 'name' => 'rate_in_amount', 'td_label' => 'Rate', 'className' => 'text-end'],
                (object)['data' => "amount", 'name' => 'amount', 'td_label' => 'Amount', 'className' => 'text-end'],
            ];
            View::share("columns", $columns);

            if ($request->ajax()) {
                $query = JobWorkReceiveItems::with(['job_work_receive.vendor', 'job_work_receive.company', 'product'])
                    ->whereHas('job_work_receive', function ($q) use ($modules) {
                        if ($modules['company_id']) {
                            $q->where('company_id', $modules['company_id']);
                        }
                    });

                // Filters
                if ($request->filled('search')) {
                    $search = trim($request->search);
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('job_work_receive', fn($qq) => $qq->where('job_work_receive_no', 'like', "%{$search}%"))
                            ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$search}%"));
                    });
                }

                if ($request->filled('filter_vendor')) {
                    $query->whereHas('job_work_receive', fn($q) => $q->where('vendor_id', request('filter_vendor')));
                }

                if ($request->filled('filter_job_work_receive_date')) {
                    $range = explode(" to ", $request->filter_job_work_receive_date);
                    if (count($range) == 2) {
                        $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                        $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                        $query->whereHas('job_work_receive', fn($q) => $q->whereBetween('job_work_receive_date', [$from, $to]));
                    }
                }

                $query->orderByDesc('id');

                // Totals
                $totalRows = (clone $query)->get();
                $total_quantity = $totalRows->sum('quantity');
                $total_received_weight = $totalRows->sum('received_weight');

                return DataTables::of($query)
                    ->addColumn('job_work_receive_no', fn($row) => $row->job_work_receive->job_work_receive_no ?? '-')
                    ->addColumn('vendor_name', fn($row) => $row->job_work_receive->vendor->vendor_name ?? '-')
                    ->addColumn('product_name', fn($row) => $row->product->product_name ?? '-')
                    ->addColumn('process_name', fn($row) => $row->job_work_receive?->process_received?->name ?? '-')
                    ->addColumn('job_work_receive_date', fn($row) =>
                    optional($row->job_work_receive)->job_work_receive_date
                        ? Carbon::parse($row->job_work_receive->job_work_receive_date)->format('d/m/Y')
                        : '-')
                    ->addColumn('quantity', fn($row) => number_format($row->quantity ?? 0, 3))
                    ->addColumn('received_weight', fn($row) => number_format($row->received_weight ?? 0, 3))
                    ->addColumn('rate_in_amount', fn($row) => number_format($row->rate_in_amount ?? 0, 3))
                    ->addColumn('amount', fn($row) => number_format($row->amount ?? 0, 2))
                    ->addColumn('incoming_scrap', fn($row) => $row->incoming_scrap ?? '-')
                    ->with([
                        'footer' => [
                            'total_quantity' => number_format($total_quantity, 2),
                            'total_received_weight' => number_format($total_received_weight, 2),
                        ]
                    ])
                    ->make(true);
            }

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

        return Excel::download(new JobWorkReceiveReportExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Job Work Received Report -' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

            // Main query
            $query = JobWorkReceiveItems::with(['job_work_receive.vendor', 'job_work_receive.company', 'product'])
                ->orderBy('id', 'DESC');

            // Apply filters
            $query->when($request->company, function ($q) use ($request) {
                $q->where('company_id', $request->company);
            });

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('job_work_receive', fn($qq) => $qq->where('job_work_receive_no', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn($p) => $p->where('product_name', 'like', "%{$search}%"));
                });
            }

            if ($request->filled('vendor')) {
                $query->whereHas('job_work_receive', fn($q) => $q->where('vendor_id', request('vendor')));
            }

            if ($request->filled('job_work_receive_date')) {
                $range = explode(" to ", $request->job_work_receive_date);
                if (count($range) == 2) {
                    $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                    $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                    $query->whereHas('job_work_receive', fn($q) => $q->whereBetween('job_work_receive_date', [$from, $to]));
                }
            }



            $jobWorkIssueItems = $query->get();

            $grandTotals = [
                'received_qty' => $jobWorkIssueItems->sum('quantity'),
                'received_kg' => $jobWorkIssueItems->sum('received_weight'),
                'amount' => $jobWorkIssueItems->sum('amount'),
            ];
            return view($modules['folder_path'] . '.print', compact('jobWorkIssueItems','grandTotals', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
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
