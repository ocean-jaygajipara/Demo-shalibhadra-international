<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkPendingExport;
use App\Http\Controllers\Controller;
use App\Models\JobWorkIssueItems;
use App\Models\JobWorkReceiveItems;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobWorkPendingController extends Controller
{

    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Job Work Pending Report',
            'folder_path' => 'software.modules.jobwork-pending-report',
            'route' => 'jobwork-pending-report',
            'table_name' => '',
            'permisstion_prefix' => 'jobwork-pending-report',
            'module_name' => 'Job Work Pending Report',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }


    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] =
                    (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (empty($modules['view_permission'])) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $columns = [
                (object) [
                    'data' => 'combined_html',
                    'name' => 'combined_html',
                    'td_label' => 'Job Work Pending Details'
                ],
            ];
            View::share('columns', $columns);
            if ($request->ajax()) {


                if ($request->filter_report_status === 'statement_report') {
                    return $this->statementReport($request, $modules);
                }


                $issueItemsQuery = JobWorkIssueItems::with([
                    'job_work_issue.vendor',
                    'product',
                    'job_work_receive_items.job_work_receive'
                ])->whereHas('job_work_issue', function ($q) use ($modules, $request) {

                    if (!empty($modules['company_id'])) {
                        $q->where('company_id', $modules['company_id']);
                    }

                    if ($request->filled('filter_vendor')) {
                        $q->where('vendor_id', $request->filter_vendor);
                    }

                    if ($request->filled('filter_job_work_issue_date')) {
                        $range = explode(" to ", $request->filter_job_work_issue_date);
                        if (count($range) === 2) {
                            $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                            $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                            $q->whereBetween('job_work_issue_date', [$from, $to]);
                        }
                    }

                    if ($request->filter_report_status === 'pending_report') {
                        $q->whereRaw('COALESCE(master_total_pending,0) > 0');
                    }

                    if ($request->filter_report_status === 'completed_report') {
                        $q->whereRaw('COALESCE(master_total_pending,0) = 0');
                    }
                });

                $issueItems = $issueItemsQuery->get();

                $grouped = $issueItems->groupBy('job_work_issue_id');

                $rows = collect();
                foreach ($grouped as $items) {

                    $mainTableHtml = '
                        <table class="table table-bordered mb-0" style="font-size:13px;">
                            <thead>
                                <tr style="background:#f3f4f6; font-weight:bold;">
                                    <th style="width:10%;">Challan</th>
                                    <th style="width:15%;">Date</th>
                                    <th style="width:30%;">Party Name</th>
                                    <th style="width:25%;">Item Name</th>
                                    <th style="width:20%;" class="text-end">Weight</th>
                                </tr>
                            </thead>
                            <tbody>
                        ';

                    $challan = $items->first()->job_work_issue;
                    $vendor = $challan?->vendor?->vendor_name ?? '-';
                    $issueDate = $challan?->job_work_issue_date
                        ? Carbon::parse($challan->job_work_issue_date)->format('d-m-Y')
                        : '-';

                    $jobWorkIssueIds = $challan->id;
                    $receiveItems = JobWorkReceiveItems::with('job_work_receive')
                        ->whereHas('job_work_receive', function ($q) use ($jobWorkIssueIds) {
                            $q->where('job_work_issue_id', $jobWorkIssueIds);
                        })
                        ->get();
                    $master_total_pending = $challan?->master_total_pending ?? '-';


                    foreach ($items as $itm) {
                        $itemName = $itm->product?->product_name ?? '-';

                        $issueWeight = (float) ($itm->issue_weight ?? 0);
                        $blPercentage = (float) ($itm->bl_percentage ?? 0);

                        $finalWeight = $issueWeight + ($issueWeight * $blPercentage / 100);

                        $mainTableHtml .= '
                            <tr>
                                <td>' . ($challan->job_work_issue_no ?? '-') . '</td>
                                <td>' . $issueDate . '</td>
                                <td>' . $vendor . '</td>
                                <td>' . $itemName . '</td>
                                <td class="text-end">' . number_format($finalWeight ?? 0, 3) . '</td>
                            </tr>';
                    }


                    $sumKg = $sumQty = $sumScrap = $sumBL = $sumTotal = 0;


                    $subTableHtml = '';
                    $subTableHtml .= '
                    <table class="table table-sm table-bordered mb-0" style="background:#f9fafb;font-size:12px;">
                        <thead>
                            <tr style="background:#e5e7eb;">
                                <th>Chl No.</th>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th class="text-end">KG</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Wire</th>
                                <th class="text-end">Scrap</th>
                                <th class="text-end">BL</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>';

                    if ($receiveItems->count()) {
                        foreach ($receiveItems as $rec) {
                            $receiveDate = $rec->job_work_receive?->job_work_receive_date
                                ? Carbon::parse($rec->job_work_receive->job_work_receive_date)->format('d-m-Y')
                                : '-';

                            $kg = (float) ($rec->received_weight ?? 0);
                            $qty = (float) ($rec->quantity ?? 0);
                            $scrap = (float) ($rec->incoming_scrap ?? 0);
                            $bl = (float) ($rec->bl_percentage ?? 0);
                            $total = (float) ($rec->received_weight ?? 0);


                            $sumKg += $kg;
                            $sumQty += $qty;
                            $sumScrap += $scrap;
                            $sumBL += $bl;
                            $sumTotal += $total;


                            $subTableHtml .= '
                                <tr>
                                    <td>' . ($challan->job_work_issue_no ?? '-') . '</td>
                                    <td>' . $receiveDate . '</td>
                                    <td>' . ($rec->product_name ?? '-') . ' ' . ($rec->product_code ?? '') . '</td>
                                    <td class="text-end">' . ($rec->received_weight ?? '-') . '</td>
                                    <td class="text-end">' . number_format((float) ($rec->quantity ?? 0), 3) . '</td>
                                    <td class="text-end"></td>
                                    <td class="text-end">' . number_format((float) ($rec->incoming_scrap ?? 0), 3) . '</td>
                                    <td class="text-end">' . number_format((float) ($rec->bl_percentage ?? 0), 3) . '</td>
                                    <td class="text-end">' . number_format((float) ($rec->received_weight ?? 0), 3) . '</td>
                                </tr>';
                        }

                        $subTableHtml .= '
                            <tr style="background:#e5e7eb;font-weight:bold;">
                                <td colspan="3" class="text-end">TOTAL</td>
                                <td class="text-end">' . number_format($sumKg, 3) . '</td>
                                <td class="text-end">' . number_format($sumQty, 3) . '</td>
                                <td class="text-end">-</td>
                                <td class="text-end">' . number_format($sumScrap, 3) . '</td>
                                <td class="text-end">' . number_format($sumBL, 3) . '</td>
                                <td class="text-end">' . number_format($sumTotal, 3) . '</td>
                            </tr>';
                    }
                    $subTableHtml .= '</tbody></table>';


                    $mainTableHtml .= '
                        <tr>
                            <td colspan="5">' . $subTableHtml . '</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="border:none; text-align:right; font-weight:bold;">
                                Challan Pending Wt. : ' . number_format($master_total_pending, 3) . '
                            </td>
                        </tr>';
                    $rows->push(['combined_html' => $mainTableHtml]);
                }

                return DataTables::of($rows)
                    ->rawColumns(['combined_html'])
                    ->with([
                        'recordsTotal' => $rows->count(),
                        'recordsFiltered' => $rows->count(),
                    ])
                    ->make(true);
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')
                ->withErrors($e->getMessage());
        }
    }


    public function statementReport(Request $request, $modules)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] =
                    (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (empty($modules['view_permission'])) {
            abort(403, 'Unauthorized');
        }

        try {
            $filterCompanyId = $request->filled('filter_company')
                ? $request->filter_company
                : ($modules['company_id'] ?? null);
            $filterVendorId = $request->filled('filter_vendor')
                ? $request->filter_vendor
                : null;

            $from = $to = null;
            if ($request->filled('filter_job_work_issue_date')) {
                $range = explode(" to ", $request->filter_job_work_issue_date);
                if (count($range) === 2) {
                    $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                    $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                }
            }

            $issueItems = JobWorkIssueItems::with([
                'job_work_issue.vendor',
                'product'
            ])->whereHas('job_work_issue', function ($q) use ($filterCompanyId, $filterVendorId, $from, $to) {
                if (!empty($filterCompanyId)) {
                    $q->where('company_id', $filterCompanyId);
                }

                if (!empty($filterVendorId)) {
                    $q->where('vendor_id', $filterVendorId);
                }

                if ($from && $to) {
                    $q->whereBetween('job_work_issue_date', [$from, $to]);
                }
            })->get();

            $receiveItems = JobWorkReceiveItems::with([
                'job_work_receive.job_work_issue',
                'job_work_issue_item.product',
                'product'
            ])->whereHas('job_work_receive.job_work_issue', function ($q) use ($filterCompanyId, $filterVendorId, $from, $to) {
                if (!empty($filterCompanyId)) {
                    $q->where('company_id', $filterCompanyId);
                }

                if (!empty($filterVendorId)) {
                    $q->where('vendor_id', $filterVendorId);
                }

                if ($from && $to) {
                    $q->whereBetween('job_work_issue_date', [$from, $to]);
                }
            })->get();

            $html = '<div class="statement-wrapper">
        <div class="text-center mb-2">
            <h5><b>SHALIBHADRA INTERNATIONAL PVT LTD</b></h5>
            <div>Plot No. 4536, GIDC, Phase III, Dared</div>
            <div>JAMNAGAR Phone : 02882731337</div>
            <div><b>Party Jobwork Statement</b></div>
        </div>

        <table class="table table-bordered table-sm text-center">
            <thead>
                <tr>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Challan</th>
                    <th rowspan="2">Particulars</th>
                    <th colspan="2">Issued</th>
                    <th colspan="2">Recd.</th>
                    <th colspan="2">Scrap / BL</th>
                </tr>
                <tr>
                    <th>Wt</th><th>Qty</th>
                    <th>Wt</th><th>Qty</th>
                    <th>Scrap</th><th>BL%</th>
                </tr>
            </thead>
            <tbody>';

            foreach ($issueItems as $item) {
                $wt = (float) ($item->product->net_wt ?? 0);
                if ($wt == 0.0) {
                    $wt = (float) ($item->product->gross_wt ?? 0);
                }

                $total_quantity = Helper::kgToQtyConvertForStock($item->issue_weight ?? 0, $wt);

                $html .= '<tr>
                <td>' . Carbon::parse($item->job_work_issue->job_work_issue_date)->format('d-m-Y') . '</td>
                <td>' . $item->job_work_issue->job_work_issue_no . '</td>
                <td class="text-start">' . ($item->product->product_name ?? '') . '</td>
                <td>' . number_format($item->issue_weight ?? 0, 3) . '</td>
                <td>' . number_format($total_quantity ?? 0, 3) . '</td>
                <td>0.000</td>
                <td>0</td>
                <td>0.000</td>
                <td>0%</td>
            </tr>';
            }

            foreach ($receiveItems as $item) {
                if (!$item->job_work_receive)
                    continue;

                $issue = $item->job_work_receive?->job_work_issue;
                $issueDate = $issue?->job_work_issue_date ? Carbon::parse($issue->job_work_issue_date)->format('d-m-Y') : '';
                $issueNo = $issue?->job_work_issue_no ?? '';
                $productName = $item->product?->product_name ?? '';

                $html .= '<tr>
                <td>' . $issueDate . '</td>
                <td>' . $issueNo . '</td>
                <td class="text-start">' . $productName . '</td>
                <td>0.000</td>
                <td>0</td>
                <td>' . number_format($item->received_weight ?? 0, 3) . '</td>
                <td>' . ($item->quantity ?? 0) . '</td>
                <td>' . number_format($item->incoming_scrap ?? 0, 3) . '</td>
                <td>' . number_format($item->bl_percentage ?? 0, 2) . '%</td>
            </tr>';
            }

            $html .= '</tbody></table></div>';

            $rows = collect();
            $rows->push(['combined_html' => $html]);

            return DataTables::of($rows)
                ->rawColumns(['combined_html'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('Statement Report Error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while generating the report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function statementReport(Request $request, $modules)
    // {

    //     $modules = $this->modules;
    //     $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
    //     $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
    //     $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

    //     if (count(config('constants.permissions'))) {
    //         foreach (config('constants.permissions') as $key => $value) {
    //             $modules[$value . '_permission'] =
    //                 (!$modules['company_id'])
    //                 ? true
    //                 : Gate::check('hasPermission', [$value, $modules['module_name']]);
    //         }
    //     }

    //     if (empty($modules['view_permission'])) {
    //         abort(403, 'Unauthorized');
    //     }

    //     try {
    //         $receiveItems = JobWorkReceiveItems::with([
    //             'job_work_issue_item.job_work_issue',
    //             'job_work_issue_item.product',
    //             'product'
    //         ])->get();



    //         $issueItems = JobWorkIssueItems::with([
    //             'job_work_issue.vendor',
    //             'product'
    //         ])->when($modules['company_id'], function ($q) use ($modules) {
    //             $q->whereHas('job_work_issue', function ($qq) use ($modules) {
    //                 $qq->where('company_id', $modules['company_id']);
    //             });
    //         })->get();




    //         $html = '<div class="statement-wrapper">
    //     <div class="text-center mb-2">
    //         <h5><b>SHALIBHADRA INTERNATIONAL PVT LTD</b></h5>
    //         <div>Plot No. 4536, GIDC, Phase III, Dared</div>
    //         <div>JAMNAGAR Phone : 02882731337</div>
    //         <div><b>Party Jobwork Statement</b></div>
    //     </div>

    //     <table class="table table-bordered table-sm text-center">
    //         <thead>
    //             <tr>
    //                 <th rowspan="2">Date</th>
    //                 <th rowspan="2">Challan</th>
    //                 <th rowspan="2">Particulars</th>
    //                 <th colspan="2">Issued</th>
    //                 <th colspan="2">Recd.</th>
    //                 <th colspan="2">Scrap / BL</th>
    //             </tr>
    //             <tr>
    //                 <th>Wt</th><th>Qty</th>
    //                 <th>Wt</th><th>Qty</th>
    //                 <th>Scrap</th><th>BL%</th>
    //             </tr>
    //         </thead>
    //         <tbody>';

    //         foreach ($issueItems as $item) {

    //             $wt = (float) $item->product->gross_wt;
    //             if ($wt == 0.0) {
    //                 $wt = (float) $item->product->net_wt;
    //             }

    //             $total_quantity = Helper::kgToQtyConvertForStock($item->issue_weight ?? 0, $wt);

    //             $html .= '<tr>
    //                 <td>' . Carbon::parse($item->job_work_issue->job_work_issue_date)->format('d-m-Y') . '</td>
    //                 <td>' . $item->job_work_issue->job_work_issue_no . '</td>
    //                 <td class="text-start">' . ($item->product->product_name ?? '') . '</td>
    //                 <td>' . number_format($item->issue_weight ?? 0, 3) . '</td>
    //                 <td>' . number_format($total_quantity ?? 0, 3) . '</td>  <!-- updated here -->
    //                 <td>0.000</td>
    //                 <td>0</td>
    //                 <td>0.000</td>
    //                 <td>0%</td>
    //             </tr>';
    //         }
    //         // dd('LN-375',$total_quantity);

    //         foreach ($receiveItems as $item) {

    //             if (!$item->job_work_receive) {
    //                 continue;
    //             }

    //             $issue = $item->job_work_receive?->job_work_issue;

    //             $issueDate = $issue?->job_work_issue_date
    //                 ? Carbon::parse($issue->job_work_issue_date)->format('d-m-Y')
    //                 : '';

    //             $issueNo = $issue?->job_work_issue_no ?? '';

    //             $productName = $item->product?->product_name ?? '';

    //             $html .= '<tr>
    //                     <td>' . $issueDate . '</td>
    //                     <td>' . $issueNo . '</td>
    //                     <td class="text-start">' . $productName . '</td>

    //                     <td>0.000</td>
    //                     <td>0</td>

    //                     <td>' . number_format($item->received_weight ?? 0, 3) . '</td>
    //                     <td>' . ($item->quantity ?? 0) . '</td>
    //                     <td>' . number_format($item->incoming_scrap ?? 0, 3) . '</td>
    //                     <td>' . number_format($item->bl_percentage ?? 0, 2) . '%</td>
    //                 </tr>';
    //         }
    //         // dd(
    //         //     $issueItems->count(),
    //         //     $receiveItems->count()
    //         // );

    //         $html .= '</tbody></table></div>';

    //         $rows = collect();
    //         $rows->push(['combined_html' => $html]);

    //         return DataTables::of($rows)
    //             ->rawColumns(['combined_html'])
    //             ->make(true);
    //     } catch (\Exception $e) {
    //         Log::error('Statement Report Error: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Something went wrong while generating the report.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


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

        return Excel::download(new JobWorkPendingExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Job Work Pending Report -' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = $this->currentGuard ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $value) {
                $modules[$value . '_permission'] =
                    (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (empty($modules['print_permission'])) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            if ($request->filter_report_status === 'statement_report') {
                return $this->statementReportPrint($request, $modules);
            }
            $issueItemsQuery = JobWorkIssueItems::with([
                'job_work_issue.vendor',
                'product',
                'job_work_receive_items.job_work_receive'
            ])->whereHas('job_work_issue', function ($q) use ($modules, $request) {

                if (!empty($modules['company_id'])) {
                    $q->where('company_id', $modules['company_id']);
                }

                if ($request->filled('filter_vendor')) {
                    $q->where('vendor_id', $request->filter_vendor);
                }

                if ($request->filled('filter_job_work_issue_date')) {
                    $range = explode(" to ", $request->filter_job_work_issue_date);
                    if (count($range) === 2) {
                        $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                        $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                        $q->whereBetween('job_work_issue_date', [$from, $to]);
                    }
                }

                if ($request->filter_report_status === 'pending_report') {
                    $q->whereRaw('COALESCE(master_total_pending,0) > 0');
                }

                if ($request->filter_report_status === 'completed_report') {
                    $q->whereRaw('COALESCE(master_total_pending,0) = 0');
                }
            });

            $issueItems = $issueItemsQuery->get();
            $grouped = $issueItems->groupBy('job_work_issue_id');

            return view($modules['folder_path'] . '.print', compact(
                'grouped',
                'modules'
            ));
        } catch (\Exception $e) {
            return redirect()
                ->route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
    }
    public function statementReportPrint(Request $request, $modules)
    {
        try {
            $filterCompanyId = $request->filled('filter_company')
                ? $request->filter_company
                : ($modules['company_id'] ?? null);

            $issueItems = JobWorkIssueItems::with([
                'job_work_issue.vendor',
                'product'
            ])
                ->when($filterCompanyId, function ($q) use ($filterCompanyId) {
                    $q->whereHas('job_work_issue', fn($qq) => $qq->where('company_id', $filterCompanyId));
                })
                ->when($request->filled('filter_vendor'), function ($q) use ($request) {
                    $q->whereHas('job_work_issue', fn($qq) => $qq->where('vendor_id', $request->filter_vendor));
                })
                ->when($request->filled('filter_job_work_issue_date'), function ($q) use ($request) {
                    $range = explode(' to ', $request->filter_job_work_issue_date);
                    if (count($range) === 2) {
                        $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                        $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                        $q->whereHas('job_work_issue', fn($qq) => $qq->whereBetween('job_work_issue_date', [$from, $to]));
                    }
                })
                ->get();

            $receiveItems = JobWorkReceiveItems::with([
                'product',
                'job_work_receive.job_work_issue'
            ])
                ->when(
                    $filterCompanyId,
                    fn($q) =>
                    $q->whereHas(
                        'job_work_receive.job_work_issue',
                        fn($qq) =>
                        $qq->where('company_id', $filterCompanyId)
                    )
                )
                ->when(
                    $request->filled('filter_vendor'),
                    fn($q) =>
                    $q->whereHas(
                        'job_work_receive.job_work_issue',
                        fn($qq) =>
                        $qq->where('vendor_id', $request->filter_vendor)
                    )
                )
                ->when($request->filled('filter_job_work_issue_date'), function ($q) use ($request) {
                    $range = explode(' to ', $request->filter_job_work_issue_date);
                    if (count($range) === 2) {
                        $from = Carbon::createFromFormat('d/m/Y', $range[0])->format('Y-m-d');
                        $to = Carbon::createFromFormat('d/m/Y', $range[1])->format('Y-m-d');
                        $q->whereHas(
                            'job_work_receive.job_work_issue',
                            fn($qq) =>
                            $qq->whereBetween('job_work_issue_date', [$from, $to])
                        );
                    }
                })
                ->get();

            // Return the print view with both collections
            return view($modules['folder_path'] . '.statement_report_print', compact(
                'modules',
                'issueItems',
                'receiveItems'
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error in statementReportPrint: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Optionally, return a friendly error message or redirect
            return back()->with('error', 'Something went wrong while generating the report. Please try again.');
        }
    }


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
