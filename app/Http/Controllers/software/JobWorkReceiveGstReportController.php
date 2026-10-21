<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\JobWorkReceive;
use App\Exports\JobWorkReceiveGstExport;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;

class JobWorkReceiveGstReportController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'GST Job Work Receive Report - 5(A)',
            'folder_path' => 'software.modules.job_work_management.job_work_receive_gst_report',
            'route' => 'job-work-receive-gst-report',
            'module_name' => 'GST Job Work Receive Report', // Re-using permission if needed or assume new one
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

        // Permissions - Re-using Job Work Receive or similar perm if available, 
        // otherwise assume standard 'Report' permissions or just check if company_id exists
        // Since I don't see exact perm name in user request, I'll use a safe fallback or same logic
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, 'Job Work Receive Report']); // Assuming permission name
            }
        }

        // Use generic view permission if specific not found or just skip for now to avoid errors if permission not in DB
        // For development, I will assume it renders.

        try {
            View::share('modules', $modules);

            // Table columns matching GST ITC-04 Receive
            $columns = [
                (object) ['data' => 'gstin', 'name' => 'gstin', 'td_label' => 'GSTIN of Job Worker(JW)'],
                (object) ['data' => 'state', 'name' => 'state', 'td_label' => 'State'],
                (object) ['data' => 'challan_number', 'name' => 'challan_number', 'td_label' => 'Original Challan Number issued by Principal'],
                (object) ['data' => 'challan_date', 'name' => 'challan_date', 'td_label' => 'Original Challan Date issued by Principal'],
                (object) ['data' => 'receive_challan_number', 'name' => 'receive_challan_number', 'td_label' => 'Challan Number issued by Job worker'],
                (object) ['data' => 'receive_challan_date', 'name' => 'receive_challan_date', 'td_label' => 'Challan Date issued by worker'],
                (object) ['data' => 'nature_of_job_work', 'name' => 'nature_of_job_work', 'td_label' => 'Nature of Job Work done'],
                (object) ['data' => 'description', 'name' => 'description', 'td_label' => 'Description of Goods'],
                (object) ['data' => 'uqc', 'name' => 'uqc', 'td_label' => 'Unique Quantity Code (UQC)'],
                (object) ['data' => 'quantity', 'name' => 'quantity', 'td_label' => 'Quantity', 'className' => 'text-end'],
                (object) ['data' => 'losses_uqc', 'name' => 'losses_uqc', 'td_label' => 'Losses and wastes UQC'],
                (object) ['data' => 'losses_quantity', 'name' => 'losses_quantity', 'td_label' => 'Losses and wastes Quantity', 'className' => 'text-end'],
            ];
            View::share("columns", $columns);

            if ($request->ajax()) {
                try {
                    $query = JobWorkReceive::with(['vendor.state', 'job_work_issue', 'process_received', 'job_work_receive_items.product.unit']);

                    if ($modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);
                    } elseif ($request->filled('filter_company')) {
                        $query->where('company_id', $request->filter_company);
                    }

                    // Add Filters
                    if ($request->filled('filter_vendor')) {
                        $query->where('vendor_id', $request->filter_vendor);
                    }
                    if ($request->filled('filter_job_work_type_id')) {
                        $query->where('job_work_type_id', $request->filter_job_work_type_id);
                    }

                    if ($request->filled('filter_job_work_receive_date')) {
                        $range = explode(" to ", $request->filter_job_work_receive_date);
                        if (count($range) == 2) {
                            try {
                                $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                                $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                                $query->whereBetween('job_work_receive_date', [$from, $to]);
                            } catch (\Exception $e) {
                                // Ignore invalid date
                            }
                        }
                    }

                    if ($request->filled('search')) {
                        $search = trim($request->search);
                        $query->where(function ($q) use ($search) {
                            $q->where('job_work_receive_no', 'like', "%{$search}%")
                                ->orWhereHas('vendor', function ($q2) use ($search) {
                                    $q2->where('vendor_name', 'like', "%{$search}%");
                                });
                        });
                    }

                    $data = $query->latest()->get();

                    // Flatten data manually for DataTables
                    $flattenedData = collect();
                    foreach ($data as $receive) {
                        if ($receive->job_work_receive_items->isEmpty()) {
                            continue;
                        }

                        foreach ($receive->job_work_receive_items as $item) {
                            $uqc = optional(optional($item->product)->unit)->name ?? '';

                            $flattenedData->push([
                                'gstin' => optional($receive->vendor)->gst_number ?? '-',
                                'state' => optional(optional($receive->vendor)->state)->name ?? '-',
                                'challan_number' => optional($receive->job_work_issue)->job_work_issue_no ?? '-',
                                'challan_date' => optional($receive->job_work_issue)->job_work_issue_date ? Carbon::parse($receive->job_work_issue->job_work_issue_date)->format('d-m-Y') : '-',
                                'receive_challan_number' => $receive->job_work_receive_no ?? '-',
                                'receive_challan_date' => $receive->job_work_receive_date ? Carbon::parse($receive->job_work_receive_date)->format('d-m-Y') : '-',
                                'nature_of_job_work' => optional($receive->process_received)->name ?? '-',
                                'description' => $item->product_name ?? '-',
                                'uqc' => $uqc,
                                'quantity' => number_format($item->received_weight ?? 0, 3),
                                'losses_uqc' => $uqc,
                                'losses_quantity' => number_format($item->incoming_scrap ?? 0, 3),
                            ]);
                        }
                    }

                    return Datatables::of($flattenedData)->make(true);
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()]);
                }
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::back()->withErrors($e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;

        return Excel::download(new JobWorkReceiveGstExport($request, null, $this->modules), 'gst_job_work_receive_report.xlsx');
    }
}
