<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\JobWorkIssue;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;

class JobWorkIssueGstReportController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'GST Job Work Issue Report - 4',
            'folder_path' => 'software.modules.job_work_management.job_work_issue_gst_report',
            'route' => 'job-work-issue-gst-report',
            'module_name' => 'GST Job Work Issue Report', // Re-using permission of Job Work Issue
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

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

            // Table columns matching GST ITC-04
            $columns = [
                (object) ['data' => 'gstin', 'name' => 'gstin', 'td_label' => 'GSTIN of Job Worker'],
                (object) ['data' => 'state', 'name' => 'state', 'td_label' => 'State'],
                (object) ['data' => 'job_worker_type', 'name' => 'job_worker_type', 'td_label' => 'Job Worker Type'],
                (object) ['data' => 'challan_number', 'name' => 'challan_number', 'td_label' => 'Original Challan Number issued by Principal'],
                (object) ['data' => 'challan_date', 'name' => 'challan_date', 'td_label' => 'Original Challan Date Issued by Principal'],
                (object) ['data' => 'types_of_goods', 'name' => 'types_of_goods', 'td_label' => 'Types of Goods'],
                (object) ['data' => 'description', 'name' => 'description', 'td_label' => 'Description'],
                (object) ['data' => 'uqc', 'name' => 'uqc', 'td_label' => 'UQC'],
                (object) ['data' => 'quantity', 'name' => 'quantity', 'td_label' => 'Quantity', 'className' => 'text-end'],
                (object) ['data' => 'taxable_value', 'name' => 'taxable_value', 'td_label' => 'Taxable Value', 'className' => 'text-end'],
                (object) ['data' => 'integrated_tax_rate', 'name' => 'integrated_tax_rate', 'td_label' => 'Integrated Tax Rate (%)', 'className' => 'text-end'],
                (object) ['data' => 'central_tax_rate', 'name' => 'central_tax_rate', 'td_label' => 'Central Tax Rate (%)', 'className' => 'text-end'],
                (object) ['data' => 'state_tax_rate', 'name' => 'state_tax_rate', 'td_label' => 'State/UT Tax Rate (%)', 'className' => 'text-end'],
                (object) ['data' => 'cess', 'name' => 'cess', 'td_label' => 'Cess', 'className' => 'text-end'],
            ];
            View::share("columns", $columns);

            if ($request->ajax()) {
                try {
                    $query = JobWorkIssue::with(['vendor.state', 'job_work_type', 'job_work_issue_items.product.unit', 'job_work_issue_items.product.product_category']);

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

                    if ($request->filled('filter_job_work_issue_date')) {
                        $range = explode(" to ", $request->filter_job_work_issue_date);
                        if (count($range) == 2) {
                            try {
                                $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                                $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                                $query->whereBetween('job_work_issue_date', [$from, $to]);
                            } catch (\Exception $e) {
                                // Ignore invalid date
                            }
                        }
                    }

                    if ($request->filled('search')) {
                        $search = trim($request->search);
                        $query->where(function ($q) use ($search) {
                            $q->where('job_work_issue_no', 'like', "%{$search}%")
                                ->orWhereHas('vendor', function ($q2) use ($search) {
                                    $q2->where('vendor_name', 'like', "%{$search}%");
                                });
                        });
                    }

                    $data = $query->latest()->get();

                    // Flatten data manually for DataTables
                    $flattenedData = collect();
                    foreach ($data as $issue) {
                        if ($issue->job_work_issue_items->isEmpty()) {
                            continue;
                        }

                        foreach ($issue->job_work_issue_items as $item) {
                            $uqc = optional(optional($item->product)->unit)->name ?? '';

                            $flattenedData->push([
                                'gstin' => optional($issue->vendor)->gst_number ?? '-',
                                'state' => optional(optional($issue->vendor)->state)->name ?? '-',
                                'job_worker_type' => optional($issue->job_work_type)->name ?? 'SEZ',
                                'challan_number' => $issue->job_work_issue_no ?? '-',
                                'challan_date' => $issue->job_work_issue_date ? Carbon::parse($issue->job_work_issue_date)->format('d-m-Y') : '-',
                                'types_of_goods' => 'Inputs',
                                'description' => $item->product_name ?? '-',
                                'uqc' => $uqc,
                                'quantity' => number_format($item->gross_weight ?? 0, 3),
                                'taxable_value' => number_format($item->taxable_amount ?? 0, 2),
                                'integrated_tax_rate' => '-', // Placeholder
                                'central_tax_rate' => number_format($item->cgst_percentage ?? 0, 2),
                                'state_tax_rate' => number_format($item->sgst_percentage ?? 0, 2),
                                'cess' => '0',
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

        // Ensure user has permission (re-using general excel permission)
        // Note: checking generic excel_permission from parent controller logic usually requires passing it in modules
        // For now, simpler check or re-implementing permission check

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

        return Excel::download(new \App\Exports\JobWorkIssueGstExport($request, null, $this->modules), 'GST_Job_Work_Issue-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
}
