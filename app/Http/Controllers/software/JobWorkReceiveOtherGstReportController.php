<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkReceiveOtherGstExport;
use App\Http\Controllers\Controller;
use App\Models\JobWorkReceive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\Helper;

class JobWorkReceiveOtherGstReportController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'GST Job Work Receive Report (Other Job Worker) - 5(B)',
            'folder_path' => 'software.modules.job_work_management.job_work_receive_other_gst_report',
            'route' => 'job-work-receive-other-gst-report',
            'module_name' => 'Job Work Receive Other GST Report',
            'permission_prefix' => 'job-work-receive-other-gst-report',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

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

        View::share('modules', $modules);

        $columns = [
            (object) ['data' => "gstin", 'name' => 'gstin', 'td_label' => 'GSTIN of Job Worker', 'orderable' => false],
            (object) ['data' => "state", 'name' => 'state', 'td_label' => 'State', 'orderable' => false],
            (object) ['data' => "challan_number", 'name' => 'challan_number', 'td_label' => 'Original Challan Number', 'orderable' => false],
            (object) ['data' => "challan_date", 'name' => 'challan_date', 'td_label' => 'Original Challan Date', 'orderable' => false],
            (object) ['data' => "receive_challan_number", 'name' => 'receive_challan_number', 'td_label' => 'Challan Number issued by Job worker', 'orderable' => false],
            (object) ['data' => "receive_challan_date", 'name' => 'receive_challan_date', 'td_label' => 'Challan Date issued by worker', 'orderable' => false],
            (object) ['data' => "nature_of_job_work", 'name' => 'nature_of_job_work', 'td_label' => 'Nature of Job Work', 'orderable' => false],
            (object) ['data' => "description", 'name' => 'description', 'td_label' => 'Description of Goods', 'orderable' => false],
            (object) ['data' => "uqc", 'name' => 'uqc', 'td_label' => 'UQC', 'orderable' => false],
            (object) ['data' => "quantity", 'name' => 'quantity', 'td_label' => 'Quantity', 'orderable' => false],
            (object) ['data' => "losses_uqc", 'name' => 'losses_uqc', 'td_label' => 'Losses UQC', 'orderable' => false],
            (object) ['data' => "losses_quantity", 'name' => 'losses_quantity', 'td_label' => 'Losses Quantity', 'orderable' => false],
        ];

        View::share("columns", $columns);

        if ($request->ajax()) {

            // Join with job_work_issues to compare vendors
            $query = JobWorkReceive::with(['vendor', 'job_work_issue', 'process_received', 'job_work_receive_items.product.unit', 'vendor.state', 'company'])
                ->join('job_work_issue', 'job_work_issue.id', '=', 'job_work_receive.job_work_issue_id')
                ->whereColumn('job_work_issue.vendor_id', '!=', 'job_work_receive.vendor_id')
                ->select('job_work_receive.*'); // Select only receive fields to avoid collision

            // ->where(function ($query) use ($modules, $loginUserId) {
            //      // Standard privacy filters can go here
            // });

            if ($modules['company_id']) {
                $query->where('job_work_receive.company_id', $modules['company_id']);
            }


            if ($request->has('filter_company') && $request->filter_company) {
                $query->where('job_work_receive.company_id', $request->filter_company);
            }

            if ($request->has('filter_process_receive_id') && $request->filter_process_receive_id) {
                $query->where('job_work_receive.process_receive_id', $request->filter_process_receive_id);
            }

            if ($request->has('filter_vendor') && $request->filter_vendor) {
                $query->where('job_work_receive.vendor_id', $request->filter_vendor);
            }

            if ($request->has('filter_job_work_receive_date') && $request->filter_job_work_receive_date) {
                $filter_job_work_receive_date = $request->filter_job_work_receive_date;
                $filter_job_work_receive_date = explode(" to ", $filter_job_work_receive_date);
                if (count($filter_job_work_receive_date) == 2) {
                    $fromDate = Helper::convert_date($filter_job_work_receive_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($filter_job_work_receive_date[1], "d/m/Y", "Y-m-d");
                    if ($fromDate && $toDate) {
                        $query->whereDate('job_work_receive.job_work_receive_date', ">=", $fromDate)
                            ->whereDate('job_work_receive.job_work_receive_date', "<=", $toDate);
                    }
                }
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('job_work_receive.job_work_receive_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%")
                                ->orWhere('gst_no', 'like', "%{$search}%");
                        });
                });
            }


            $data = $query->latest('job_work_receive.created_at')->get();

            // Flatten data
            $flattenedData = collect();

            foreach ($data as $receive) {
                // The 'vendor' relation on $receive is the one who *returned* the goods (The 'Other' Job Worker)
                // The 'job_work_issue->vendor' relation is the 'Original' Job Worker.

                $gstin = $receive->vendor->gst_no ?? '-';
                $state = $receive->vendor->state->state_name ?? '-';
                $challan_number = $receive->job_work_issue->job_work_issue_no ?? '-';
                $challan_date = $receive->job_work_issue->job_work_issue_date ? \Carbon\Carbon::parse($receive->job_work_issue->job_work_issue_date)->format('d-m-Y') : '-';

                $receive_challan_number = $receive->job_work_receive_no ?? '-';
                $receive_challan_date = $receive->job_work_receive_date ? \Carbon\Carbon::parse($receive->job_work_receive_date)->format('d-m-Y') : '-';

                $nature = $receive->process_received->name ?? '-';

                foreach ($receive->job_work_receive_items as $item) {
                    $description = $item->product_name ?? '-'; // Or item->product->product_name
                    $uqc = $item->product->unit->name ?? '-'; // Assuming Product has unit rel
                    $quantity = $item->received_weight; // Or quantity field

                    // Losses and wastes
                    // Assuming 'incoming_scrap' represents waste/loss returned
                    $losses_qty = $item->incoming_scrap > 0 ? $item->incoming_scrap : 0;
                    $losses_uqc = $losses_qty > 0 ? $uqc : '-'; // Assuming same UQC for scrap? Or separate?
                    // Usually scrap has its own UQC, but here we assume same uqc or '-'

                    $flattenedData->push((object) [
                        'id' => $receive->id,
                        'gstin' => $gstin,
                        'state' => $state,
                        'challan_number' => $challan_number,
                        'challan_date' => $challan_date,
                        'receive_challan_number' => $receive_challan_number,
                        'receive_challan_date' => $receive_challan_date,
                        'nature_of_job_work' => $nature,
                        'description' => $description,
                        'uqc' => $uqc,
                        'quantity' => $quantity,
                        'losses_uqc' => $losses_uqc,
                        'losses_quantity' => $losses_qty > 0 ? $losses_qty : '-',
                    ]);
                }
            }

            return Datatables::of($flattenedData)
                ->make(true);
        }

        return view($this->modules['folder_path'] . '.index');
    }

    public function exportExcel(Request $request)
    {
        if (config('constants.permissions')) {
            $permission = (isset($this->modules['company_id']) && !$this->modules['company_id']) ? true : Gate::check('hasPermission', ['job_work_receive_report_export', 'Job Work Receive Report']);
            if (!$permission) {
                abort(403, 'Unauthorized');
            }
        }
        return Excel::download(new JobWorkReceiveOtherGstExport($request, null, $this->modules), 'gst_job_work_receive_other_report.xlsx');
    }
}
