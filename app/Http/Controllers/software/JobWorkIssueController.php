<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkIssueExport;
use App\Http\Controllers\Controller;
use App\Models\JobWorkIssue;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\JobWorkIssueEwayBillService;
use App\Services\EInvoiceService;
use App\Models\JobWorkIssueEwayBill;
use App\Models\CompanySocial;
use App\Models\EwayBillVehicleHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use App\Helpers\Helper;
use App\Http\Requests\JobWorkIssueRequest;
use App\Models\JobWorkIssueItems;
use App\Models\MasterWarehouse;
use App\Models\ProductStockHistory;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\SalesStatus;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Jobs\GenerateJobWorkIssuePdfJob;
use Illuminate\Support\Facades\File;
use ZipArchive;

class JobWorkIssueController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Job Work Issue',
            'folder_path' => 'software.modules.job_work_management.job_work_issue',
            'route' => 'job-work-issue',
            'table_name' => (new JobWorkIssue())->getTable(),
            'module_name' => 'Job Work Issue',
            'permisstion_prefix' => 'job-work-issue',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
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
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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

        try {
            View::share('modules', $modules);

            $columns = [
                (object) ['data' => "select_all", 'name' => 'select_all', 'td_label' => '<input type="checkbox" class="form-check-input" id="select_all">', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
                (object) ['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "job_work_issue_no", 'name' => 'job_work_issue_no', 'td_label' => 'Job Work NO'],
                (object) ['data' => "job_work_issue_date", 'name' => 'job_work_issue_date', 'td_label' => 'Job Work Date', 'className' => ''],
                (object) ['data' => "job_work_type", 'name' => 'job_work_type', 'td_label' => 'Job Work Type', 'className' => ''],
                (object) ['data' => "job_work_order_status", 'name' => 'job_work_order_status', 'td_label' => 'Job Work Issue Order Status', 'className' => ''],
                (object) ['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Vendor Name', 'className' => ''],
                (object) ['data' => "grand_total", 'name' => 'grand_total', 'td_label' => 'Total Amount', 'className' => ''],
                (object) ['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' => ''],
                (object) ['data' => "eway_bill_no", 'name' => 'eway_bill_no', 'td_label' => 'E-Way Bill', 'className' => ''],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center'],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            // dd(!$modules['company_id'], $columns);
            View::share("columns", $columns);


            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = JobWorkIssue::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['vendor', 'company', 'job_work_type', 'jobWorkIssueEwayBill']);
                $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                //dd($data);

                $returnData = Datatables::of($data)
                    ->addColumn('select_all', function ($row) {
                        return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                    })
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_parent_status_id') && $request->filter_parent_status_id) {
                            $query->where('sales_status_id', $request->filter_parent_status_id);
                        }

                        if ($request->has('filter_vendor') && $request->filter_vendor) {
                            $query->where('vendor_id', $request->filter_vendor);
                        }

                        if ($request->has('filter_job_work_type_id') && $request->filter_job_work_type_id) {
                            $query->where('job_work_type_id', $request->filter_job_work_type_id);
                        }

                        if ($request->has('filter_job_work_issue_date') && $request->filter_job_work_issue_date) {

                            $filter_job_work_issue_date = $request->filter_job_work_issue_date;
                            $filter_job_work_issue_date = explode(" to ", $filter_job_work_issue_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_job_work_issue_date) == 2) {
                                $fromDate = Helper::convert_date($filter_job_work_issue_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_job_work_issue_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new JobWorkIssue())->getTable() . '.job_work_issue_date', ">=", $fromDate);
                                $query->whereDate((new JobWorkIssue())->getTable() . '.job_work_issue_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new JobWorkIssue())->getTable() . '.job_work_issue_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('job_work_issue_no', 'like', "%{$search}%")
                                    ->orWhereHas('vendor', function ($q2) use ($search) {
                                        $q2->where('vendor_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->editColumn('job_work_issue_date', function ($row) {
                        return \Carbon\Carbon::parse($row?->job_work_issue_date)->format('d-m-Y');
                    })
                    ->addColumn('vendor_name', function ($row) use ($modules) {
                        $vendorCode = optional($row->vendor)->vendor_code;
                        $vendorName = optional($row->vendor)->vendor_name;

                        $vendorInfo = '<span>' . $vendorCode . '<br>' . $vendorName . '</span>';
                        return $vendorInfo;
                    })
                    ->addColumn('job_work_type', function ($row) {
                        return $row?->job_work_type?->name ?? '-';
                    })
                    ->addColumn('job_work_order_status', function ($row) {
                        if (empty($row->sales_status)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row->sales_status->status_color . '">' . $row->sales_status->statusname . '</span>';
                    })
                    ->addColumn('eway_bill_no', function ($row) {
                        $eway = $row->jobWorkIssueEwayBill;
                        if (!$eway)
                            return '-';

                        $statusColor = 'secondary';
                        if ($eway->status == 'GENERATED')
                            $statusColor = 'success';
                        elseif ($eway->status == 'UPDATED')
                            $statusColor = 'info';
                        elseif ($eway->status == 'CANCELLED')
                            $statusColor = 'danger';
                        elseif ($eway->status == 'PENDING')
                            $statusColor = 'warning';

                        $html = '<span class="badge bg-' . $statusColor . '">' . $eway->eway_bill_no . '</span>';
                        if ($eway->status != 'CANCELLED' && $eway->valid_upto) {
                            $html .= '<br><small style="font-size: 10px;">Valid: ' . \Carbon\Carbon::parse($eway->valid_upto)->format('d-m-Y H:i') . '</small>';
                        }
                        return $html;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';

                        $action .= '
                        <div class="">
                            <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">';



                        $eway = $row->jobWorkIssueEwayBill;
                        if ($eway && in_array($eway->status, ['GENERATED', 'UPDATED'])) {
                            $action .= '<li><a href="javascript:void(0);" 
                                class="dropdown-item update-vehicle-btn" 
                                data-job-work-issue-id="' . $row->id . '"
                                data-eway-bill-no="' . $eway->eway_bill_no . '"
                                data-current-vehicle="' . $eway->vehicle_no . '"
                                data-bs-toggle="modal" data-bs-target="#updateVehicleModal">
                                <i class="ti ti-truck me-1"></i> Update Vehicle For E-Way Bill
                            </a></li>';


                            $action .= '<li><a href="javascript:void(0);" 
                                class="dropdown-item view-vehicle-history-btn" 
                                data-job-work-issue-id="' . $row->id . '"
                                data-eway-bill-no="' . $eway->eway_bill_no . '"
                                data-bs-toggle="modal" data-bs-target="#vehicleHistoryModal">
                                <i class="ti ti-history me-1"></i> Vehicle History
                            </a></li>';

                            // E-Way Bill Print
                            if (!empty($eway->eway_bill_no) || !empty($eway->id) || ($row->generate_eway ?? false)) {
                                $action .= '<li class="text-center">
                                    <a href="javascript:void(0);"
                                    onclick="window.open(\'' . route($modules['route'] . '.ewayBillPrint', [$row->id]) . '\', \'_blank\', \'width=1200,height=800\');"
                                    class="dropdown-item text-center">
                                        <i class="fa-solid fa-truck"></i> E-Way Bill Print
                                    </a>
                                </li>';
                            }
                        }

                        if (!$row?->deleted_at) {
                            if ($modules['update_permission'] == true) {

                                $salesStatusesLast = SalesStatus::where('company_id', $row->company_id)
                                    ->where('salestype', 'Job Work Issue')
                                    ->select("id")
                                    ->orderBy('display_order', 'desc')
                                    ->first();
                                if (!empty($salesStatusesLast->id) && $salesStatusesLast->id > 0 && $row->sales_status_id != $salesStatusesLast->id) {
                                    $action .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                        </li>';
                                }
                            }

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                    <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1">
                                        <i class="fa-solid fa-trash mt-1 me-1"></i> Delete
                                    </a>
                                </li>';
                            }


                            $action .= '<li>
                                <a href="' . route($modules["route"] . ".jobWorkIssueShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1">
                                    <i class="fa-solid fa fa-eye mt-1 me-1"></i> View
                                </a>
                            </li>';
                        } else {
                            $action .= '<li>
                                <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                    Restore
                                </a>
                            </li>';
                        }

                        $action .= '</ul></div>';

                        return $action;
                    })

                    ->rawColumns(['action', 'job_work_order_status', 'vendor_name', 'select_all', 'eway_bill_no'])
                    ->make(true);

                if (!$modules['view_permission']) {
                    abort(403, 'Unauthorized');
                }
                return $returnData;
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
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JobWorkIssueRequest $request, JobWorkIssueEwayBillService $ewayService)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
        //dd($request->all());
        $validated = $request->validated();
        DB::beginTransaction();
        try {

            // Server-side validation for Job Work Order Items

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    // Product Name
                    if (is_null($product) || trim($product) === '') {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["product_name.$key" => "Product name is required."])->withInput();
                    }

                    // Product Code
                    $product_code = $request->product_code[$key] ?? null;
                    if (is_null($product_code) || trim($product_code) === '') {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["product_code.$key" => "Product code is required."])->withInput();
                    }


                    // Rate in Amount
                    $rate = $request->rate_in_amount[$key] ?? null;
                    if (empty($rate) || floatval($rate) <= 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["rate_in_amount.$key" => "Rate must be greater than 0."])->withInput();
                    }

                    // Quantity
                    $taxable_amount = $request->taxable_amount[$key] ?? null;
                    if (is_null($taxable_amount) || $taxable_amount === '' || floatval($taxable_amount) <= 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["taxable_amount.$key" => "Taxable amount must be greater than 0."])->withInput();
                    }

                    // Amount (sub_total for this row)
                    $amount = $request->amount[$key] ?? null;
                    if (empty($amount) || floatval($amount) <= 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["amount.$key" => "Amount must be greater than 0."])->withInput();
                    }
                }
            }

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;

            if (!isset($validated['sub_total']) || $validated['sub_total'] == '' || (int) ($validated['sub_total']) <= 0) {
                return Redirect::back()->withErrors(['Sub total must be greater than 0.'])->withInput();
            }
            // dd($validated);

            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Job Work Issue')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }
            $validated['master_total_pending'] = $validated['total_gross_weight'];
            $jobWorkIssue = JobWorkIssue::create($validated);
            $lastInsertedId = $jobWorkIssue->id;

            if (!empty($validated['vendor_id'])) {
                $vendor = Vendor::find($validated['vendor_id']);
                if ($vendor) {
                    $vendor->update([
                        'address' => $request->address ?? $vendor->address,
                        'mobile_number' => $request->phone ?? $vendor->mobile_number,
                    ]);
                }
            }

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    $warehouse_id = $request?->warehouse_id;
                    if ($warehouse_id > 0) {
                        $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $validated['product_category_id'], $request->product_id[$key]);

                        $product = Product::find($request->product_id[$key]);
                        $productName = $product?->product_name ?? 'Unknown Product';

                        if ((float) $product_total_kg <= 0) {
                            DB::rollBack();
                            return Redirect::back()->withErrors(["No stock available for product '{$productName}'."])->withInput();
                        }


                        $total_issue_kg = $request->issue_weight[$key];
                        if ((float) $product_total_kg > 0) {
                            if ($product_total_kg < $total_issue_kg) {
                                $available = $product_total_kg;
                                $required = $total_issue_kg;

                                DB::rollBack();
                                return Redirect::back()->withErrors(["Insufficient stock for product '{$productName}'. Available: {$available}, Required: {$required}."])->withInput();
                            }

                            $total_remaining_kg = (float) $product_total_kg - (float) $total_issue_kg;

                            $total_quantity = 0;
                            if ($total_remaining_kg > 0) {

                                $wt = (float) $product->net_wt;
                                if ($wt == 0.0) {
                                    $wt = (float) $product->gross_wt;
                                }
                                $total_quantity = Helper::kgToQtyConvertForStock($total_remaining_kg, $wt);
                            }

                            ProductStockHistory::create([
                                'company_id' => $validated['company_id'],
                                'warehouse_id' => $warehouse_id,
                                'product_category_id' => $validated['product_category_id'],
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $request->product_id[$key],
                                'job_work_issue_id' => $lastInsertedId,
                                'total_quantity' => $total_quantity,
                                'total_kg' => (float) $product_total_kg - (float) $total_issue_kg,
                                'debit' => $total_issue_kg,
                                'date' => $validated['job_work_issue_date'],
                                'remark' => 'Job Work Issue Generate No. - ' . $validated['job_work_issue_no'],
                                'created_by' => $loginUserId
                            ]);
                        }

                        $JobWorkIssue = JobWorkIssueItems::create([
                            'company_id' => $validated['company_id'],
                            'job_work_issue_id' => $lastInsertedId,
                            'product_id' => $request->product_id[$key],
                            'display_order' => ($key + 1),
                            'product_name' => $product?->product_name,
                            'product_code' => $request->product_code[$key],
                            'product_stock' => $request->product_stock[$key],
                            'gross_weight' => $request->gross_weight[$key],
                            'issue_weight' => $request->issue_weight[$key] ?? null,
                            'remaining_weight' => $request->gross_weight[$key] ?? null,
                            'bl_percentage' => $request->bl_percentage[$key] ?? null,
                            'wt_pc' => $request->wt_pc[$key] ?? null,
                            'taxable_amount' => $request->taxable_amount[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'cgst_percentage' => $request->cgst_percentage[$key] ?? null,
                            'cgst_amount' => $request->cgst_amount[$key] ?? null,
                            'sgst_percentage' => $request->sgst_percentage[$key] ?? null,
                            'sgst_amount' => $request->sgst_amount[$key] ?? null,
                            'total_rate_in_amount' => $request->total_rate_in_amount[$key] ?? null,
                            'amount' => $request->amount[$key] ?? null,
                        ]);
                    }
                }
            }
            // Generate E-Way Bill if requested
            if ($request->submit_type === 'save_and_eway_bill' && $request->grand_total >= 50000) {
                $status = $this->generateEwayBill($jobWorkIssue, $ewayService, $request->only(['lr_no', 'lr_date', 'vehicle_no', 'vehicle_type']));

                if (!$status) {
                    throw new \Exception('E-Way Bill generation failed');
                }
            }

            DB::commit();

            // Dispatch PDF generation job
            GenerateJobWorkIssuePdfJob::dispatch($lastInsertedId);

            if ($request->submit_type === 'save_print') {
                return redirect()
                    ->route($modules['route'] . '.jobWorkIssuePrint', $lastInsertedId)
                    ->withSuccess($modules['title'] . ' created successfully');
            }
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
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
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            $edit = JobWorkIssue::with(['vendor', 'job_work_issue_items', 'jobWorkIssueEwayBill'])->findOrFail($id);

            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(JobWorkIssueRequest $request, JobWorkIssueEwayBillService $ewayService, string $id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $request['id'] = $id;

        $validated = $request->validated();
        DB::beginTransaction();
        try {
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    // Product Name
                    if (is_null($product) || trim($product) === '') {
                        return Redirect::back()->withErrors(["product_name.$key" => "Product name is required."])->withInput();
                        DB::rollBack();
                    }

                    // Product Code
                    $product_code = $request->product_code[$key] ?? null;
                    if (is_null($product_code) || trim($product_code) === '') {
                        return Redirect::back()->withErrors(["product_code.$key" => "Product code is required."])->withInput();
                        DB::rollBack();
                    }

                    // Rate in Amount
                    $rate = $request->rate_in_amount[$key] ?? null;
                    if (empty($rate) || floatval($rate) <= 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["rate_in_amount.$key" => "Rate must be greater than 0."])->withInput();
                    }

                    // Quantity
                    $taxable_amount = $request->taxable_amount[$key] ?? null;
                    if (is_null($taxable_amount) || $taxable_amount === '' || floatval($taxable_amount) <= 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["taxable_amount.$key" => "Taxable amount must be greater than 0."])->withInput();
                    }

                    // Amount (sub_total for this row)
                    $amount = $request->amount[$key] ?? null;
                    if (empty($amount) || floatval($amount) <= 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(["amount.$key" => "Amount must be greater than 0."])->withInput();
                    }
                }
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = JobWorkIssue::findOrFail($id);

            if (!isset($validated['sub_total']) || $validated['sub_total'] == '' || (int) ($validated['sub_total']) <= 0) {
                return Redirect::back()->withErrors(['Sub total must be greater than 0.'])->withInput();
                DB::rollBack();
            }
            if ($updateData) {
                unset($validated['id']);

                $status_data = SalesStatus::where('company_id', $validated['company_id'])
                    ->where('salestype', 'Job Work Issue')
                    ->where('display_order', 1)
                    ->first();
                if (!empty($status_data)) {
                    $validated['sales_status_id'] = $status_data->id;
                }
                $updateData->update($validated);

                if (!empty($validated['vendor_id'])) {
                    $vendor = Vendor::find($validated['vendor_id']);
                    if ($vendor) {
                        $vendor->update([
                            'address' => $request->address ?? $vendor->address,
                            'mobile_number' => $request->phone ?? $vendor->mobile_number,
                        ]);
                    }
                }

                $master_total_pending = 0;
                if ($request->has('product_name')) {
                    JobWorkIssueItems::where('job_work_issue_id', $id)->delete();
                    foreach ($request->product_name as $key => $product) {

                        $warehouse_id = $request?->warehouse_id;
                        if ($warehouse_id > 0) {

                            $product_stock_history_data = ProductStockHistory::where('job_work_issue_id', $id)->orderBy('id', 'DESC')->first();
                            $product_stock_history_list = $product_stock_history_data ? [$product_stock_history_data] : [];

                            if (is_array($product_stock_history_list) && !empty($product_stock_history_list)) {
                                foreach ($product_stock_history_list as $k => $value) {

                                    $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $validated['product_category_id'], $request->product_id[$key]);

                                    $ProductData = Product::findOrFail($value['product_variant_price_id']);

                                    $total_remaining_kg = (float) $product_total_kg + (float) $value['debit'];
                                    $total_quantity = 0;
                                    if ($total_remaining_kg > 0) {

                                        $wt = (float) $ProductData->net_wt;
                                        if ($wt == 0.0) {
                                            $wt = (float) $ProductData->gross_wt;
                                        }

                                        $total_quantity = Helper::kgToQtyConvertForStock($total_remaining_kg, $wt);
                                    }

                                    ProductStockHistory::where('job_work_issue_id', $id)->where("id", $value['id'])->delete();
                                    ProductStockHistory::create([
                                        'company_id' => $value['company_id'],
                                        'warehouse_id' => $warehouse_id,
                                        'product_category_id' => $value['product_category_id'],
                                        'product_subcategory_id' => '',
                                        'product_variant_price_id' => $value['product_variant_price_id'],
                                        'job_work_issue_id' => $id,
                                        'total_quantity' => $total_quantity,
                                        'total_kg' => (float) $product_total_kg + (float) $value['debit'],
                                        'credit' => (float) $value['debit'],
                                        'date' => $validated['job_work_issue_date'],
                                        'remark' => 'update Job Work Issue Generate No. - ' . $validated['job_work_issue_no'],
                                        'created_by' => $loginUserId
                                    ]);
                                }
                            }



                            $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $validated['product_category_id'], $request->product_id[$key]);

                            $product = Product::find($request->product_id[$key]);
                            $productName = $product?->product_name ?? 'Unknown Product';

                            if ((float) $product_total_kg <= 0) {
                                DB::rollBack();
                                return Redirect::back()->withErrors(["No stock available for product '{$productName}'."])->withInput();
                            }

                            $total_issue_kg = $request->issue_weight[$key];
                            if ((float) $product_total_kg > 0) {
                                if ($product_total_kg < $total_issue_kg) {
                                    $available = $product_total_kg;
                                    $required = $total_issue_kg;

                                    DB::rollBack();
                                    return Redirect::back()->withErrors(["Insufficient stock for product '{$productName}'. Available: {$available}, Required: {$required}."])->withInput();
                                }

                                $total_remaining_kg = (float) $product_total_kg - (float) $total_issue_kg;

                                $total_quantity = 0;
                                if ($total_remaining_kg > 0) {

                                    $wt = (float) $product->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float) $product->gross_wt;
                                    }
                                    $total_quantity = Helper::kgToQtyConvertForStock($total_remaining_kg, $wt);
                                }

                                ProductStockHistory::create([
                                    'company_id' => $validated['company_id'],
                                    'warehouse_id' => $warehouse_id,
                                    'product_category_id' => $validated['product_category_id'],
                                    'product_subcategory_id' => '',
                                    'product_variant_price_id' => $request->product_id[$key],
                                    'job_work_issue_id' => $id,
                                    'total_quantity' => $total_quantity,
                                    'total_kg' => (float) $product_total_kg - (float) $total_issue_kg,
                                    'debit' => $total_issue_kg,
                                    'date' => $validated['job_work_issue_date'],
                                    'remark' => 'Job Work Issue Generate No. - ' . $validated['job_work_issue_no'],
                                    'created_by' => $loginUserId
                                ]);
                            }

                            $gross_weight = floatval($request->gross_weight[$key] ?? 0);
                            // $master_total_pending += $gross_weight;
                            $JobWorkIssue = JobWorkIssueItems::create([
                                'company_id' => $validated['company_id'],
                                'job_work_issue_id' => $id,
                                'product_id' => $request->product_id[$key],
                                'display_order' => ($key + 1),
                                'product_name' => $productName,
                                'product_code' => $request->product_code[$key],
                                'product_stock' => $request->product_stock[$key],
                                'gross_weight' => $request->gross_weight[$key],
                                'issue_weight' => $request->issue_weight[$key] ?? null,
                                'remaining_weight' => $request->gross_weight[$key] ?? null,
                                'bl_percentage' => $request->bl_percentage[$key] ?? null,
                                'wt_pc' => $request->wt_pc[$key] ?? null,
                                'taxable_amount' => $request->taxable_amount[$key] ?? null,
                                'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                                'cgst_percentage' => $request->cgst_percentage[$key] ?? null,
                                'cgst_amount' => $request->cgst_amount[$key] ?? null,
                                'sgst_percentage' => $request->sgst_percentage[$key] ?? null,
                                'sgst_amount' => $request->sgst_amount[$key] ?? null,
                                'total_rate_in_amount' => $request->total_rate_in_amount[$key] ?? null,
                                'amount' => $request->amount[$key] ?? null,
                            ]);
                        }
                    }
                }

                $validated['master_total_pending'] = $request->total_gross_weight;
                $updateData->update($validated);
                DB::commit();

                // Dispatch PDF generation job
                GenerateJobWorkIssuePdfJob::dispatch($id);

                if ($request->submit_type === 'save_print') {
                    return redirect()
                        ->route($modules['route'] . '.jobWorkIssuePrint', $id)
                        ->withSuccess($modules['title'] . ' created successfully');
                }

                // Generate E-Way Bill if requested
                if ($request->submit_type === 'save_and_eway_bill' && $request->grand_total >= 50000) {

                    if (!empty($updateData->eway_bill_no)) {
                        try {
                            $credential = CompanySocial::where('company_id', $updateData->company_id)
                                ->where('platform', 'eway')
                                ->whereNotNull('platform')
                                ->firstOrFail();

                            $ewayBill = JobWorkIssueEwayBill::where('job_work_issue_id', $updateData->id)
                                ->where('eway_bill_no', $updateData->eway_bill_no)
                                ->first();

                            // CHECK 24-HOUR LIMIT BEFORE CANCELLATION
                            if ($ewayBill && $ewayBill->created_at->diffInHours(now()) > 24) {
                                return Redirect::back()->withErrors('E-Way Bill can only be cancelled within 24 hours of generation. Current E-Way Bill was generated on ' . $ewayBill->created_at->format('d-m-Y h:i A'))->withInput();
                            }

                            $ewayService->authenticate($credential);
                            $cancelResult = $ewayService->cancel(
                                $updateData->eway_bill_no,
                                '2', // Cancel reason code (2 = Data Entry Mistake)
                                'Regenerating E-Way Bill due to Job Work Issue update'
                            );

                            $ewayBill = JobWorkIssueEwayBill::where('job_work_issue_id', $updateData->id)
                                ->where('eway_bill_no', $updateData->eway_bill_no)
                                ->first();

                            // Update JobWorkIssueEwayBill table - set status to CANCELLED
                            if ($ewayBill) {
                                $ewayBill->update([
                                    'status' => 'CANCELLED',
                                    'cancelled_at' => now(),
                                    'cancelled_by' => $loginUserId,
                                    'cancel_reason' => 'Data Entry Mistake',
                                    'cancel_remarks' => 'Regenerating E-Way Bill due to Job Work Issue update',
                                    'cancel_response' => $cancelResult,
                                ]);

                                $ewayBill->delete();
                            }

                            // Clear the old E-Way Bill number
                            $updateData->update([
                                'eway_bill_no' => null,
                            ]);

                            $status = $this->generateEwayBill($updateData, $ewayService, $request->only(['lr_no', 'lr_date', 'vehicle_no', 'vehicle_type']));
                            if ($status) {
                                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' updated & E-Way Bill generated successfully');
                            } else {
                                return Redirect::route($modules['route'] . '.index')->with('warning', $modules['title'] . ' updated but E-Way Bill generation failed.');
                            }

                        } catch (\Exception $cancelException) {
                            return Redirect::route($modules['route'] . '.index')->withErrors($cancelException->getMessage());
                        }

                    } else {
                        $status = $this->generateEwayBill($updateData, $ewayService, $request->only(['lr_no', 'lr_date', 'vehicle_no', 'vehicle_type']));
                        if ($status) {
                            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' updated & E-Way Bill generated successfully');
                        } else {
                            return Redirect::route($modules['route'] . '.index')->with('warning', $modules['title'] . ' updated but E-Way Bill generation failed.');
                        }
                    }
                }

                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            DB::rollBack();

            if ($request->submit_type === 'save_print') {
                return redirect()
                    ->route($modules['route'] . '.jobWorkIssuePrint', $id)
                    ->withSuccess($modules['title'] . ' created successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['delete_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $dataDelete = JobWorkIssue::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {

                    $product_stock_history_data = ProductStockHistory::where('job_work_issue_id', $id)->get()->toArray();
                    if (is_array($product_stock_history_data) && !empty($product_stock_history_data)) {
                        foreach ($product_stock_history_data as $k => $value) {

                            $product_total_kg = Helper::getProductStockTotalKG($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);
                            $ProductData = Product::findOrFail($value['product_variant_price_id']);

                            $total_remaining_kg = (float) $product_total_kg + (float) $value['debit'];
                            $total_quantity = 0;
                            if ($total_remaining_kg > 0) {

                                $wt = (float) $ProductData->net_wt;
                                if ($wt == 0.0) {
                                    $wt = (float) $ProductData->gross_wt;
                                }
                                $total_quantity = Helper::kgToQtyConvertForStock($total_remaining_kg, $wt);
                            }

                            ProductStockHistory::where('job_work_issue_id', $id)->where("id", $value['id'])->delete();
                            ProductStockHistory::create([
                                'company_id' => $value['company_id'],
                                'warehouse_id' => $value['warehouse_id'],
                                'product_category_id' => $value['product_category_id'],
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $value['product_variant_price_id'],
                                'job_work_issue_id' => $id,
                                'total_quantity' => $total_quantity,
                                'total_kg' => (float) $product_total_kg + (float) $value['debit'],
                                'credit' => (float) $value['debit'],
                                'date' => $value['date'],
                                'remark' => 'delete Job Work Issue Generate No. - ' . $dataDelete->job_work_issue_no,
                                'created_by' => $loginUserId
                            ]);
                        }
                    }

                    return true;
                }
            }
            if ($isAjax) {
                return $this->sendResponse([], "something went wrong please try again later");
            }
            return false;
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function restore($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $jobWorkIssue = JobWorkIssue::withTrashed()->findOrFail($id);
            $jobWorkIssue->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
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
            $query = JobWorkIssue::with(['vendor', 'company', 'job_work_type'])
                ->orderBy('id', 'DESC');

            // Apply filters
            $query->when($request->filter_company, function ($q) use ($request) {
                $q->where('company_id', $request->filter_company);
            });

            $query->when($request->filter_vendor, function ($q) use ($request) {
                $q->where('vendor_id', $request->filter_vendor);
            });

            $query->when($request->filter_job_work_type_id, function ($q) use ($request) {
                $q->where('job_work_type_id', $request->filter_job_work_type_id);
            });
            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $query->where('sales_status_id', $request->parent_status_id);
            }
            // Job Work Issue Date filter
            if ($request->filter_job_work_issue_date) {
                $filter_job_work_issue_date = explode(" to ", $request->filter_job_work_issue_date);
                $fromDate = null;
                $toDate = null;

                if (count($filter_job_work_issue_date) == 2) {
                    $fromDate = Helper::convert_date($filter_job_work_issue_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($filter_job_work_issue_date[1], "d/m/Y", "Y-m-d");
                }

                if ($fromDate && $toDate) {
                    $query->whereBetween((new JobWorkIssue())->getTable() . '.job_work_issue_date', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $query->whereDate((new JobWorkIssue())->getTable() . '.job_work_issue_date', $fromDate);
                }
            }

            // Search filter (job_work_issue_no OR vendor_name)
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('job_work_issue_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%");
                        });
                });
            }

            $jobWorkIssues = $query->get();

            return view($modules['folder_path'] . '.print', compact('jobWorkIssues', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

        return Excel::download(new JobWorkIssueExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Job Work Issue-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function jobWorkIssueShow($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        // Permissions
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['view_permission']) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            // Fetch the JobWorkIssue
            $jobIssue = JobWorkIssue::with([
                'company',
                'vendor',
                'job_work_issue_items',
                'team_person',
                'admin_person',
                'issueForProcess',
                'job_work_type'
            ])->findOrFail($id);

            // Determine view format
            $viewFormat = $jobIssue?->company?->jobissue_view_format ?? 'general_view';

            return view(
                $modules['folder_path'] . '.' . $viewFormat,
                compact('jobIssue', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function jobWorkIssuePrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        try {
            View::share('modules', $modules);


            $jobIssue = JobWorkIssue::with([
                'company',
                'vendor',
                'job_work_issue_items',
                'team_person',
                'admin_person',
                'issueForProcess',
                'job_work_type'
            ])->findOrFail($id);


            $viewFormat = $jobIssue?->company?->jobissue_print_format ?? 'single_print';

            return view(
                $modules['folder_path'] . '.' . $viewFormat,
                compact('jobIssue', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function SendjobWorkIssuePdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        // Fetch Job Work Issue with all relations
        $jobIssue = JobWorkIssue::with([
            'company',
            'vendor',
            'job_work_issue_items',
            'team_person',
            'admin_person',
            'issueForProcess',
            'job_work_type'
        ])->findOrFail($id);


        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // If company-specific logos exist, use them
        if ($jobIssue->company_id) {
            $company = Helper::getCompanyDetailById($jobIssue->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

        $company_id = $jobIssue->company_id;

        $viewPath = $modules['folder_path'] . '.' . ($jobIssue?->company?->jobissue_print_format ?? 'single_pdf');
        // Render HTML
        $html = View::make($viewPath, compact('jobIssue', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

        // File path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($jobIssue->company->company_name ?? 'company');
        $folder = "uploads/{$company_id}-{$company_slug}/jobissues/{$year}-{$month}/";
        $uploadedPath = public_path($folder);
        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'JobWorkIssue_' . $jobIssue->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        // Email data
        $data = [
            'vendor_name' => $jobIssue->vendor?->vendor_name ?? '-',
            'company_name' => $jobIssue->company?->company_name ?? '-',
        ];

        $attachments = [
            [
                'file' => $filepath,
                'options' => [
                    'as' => $filename,
                    'mime' => 'application/pdf',
                ]
            ]
        ];

        // // Send mail
        Helper::mail_new(
            $jobIssue->company_id,
            'Job Work Issue',
            'jobissue_send_pdf',
            optional($jobIssue->vendor)->email,
            // "riddhibutaniocean@gmail.com",

            optional($jobIssue->vendor)->id,
            'JobWorkIssue/jobIssuePdf',
            $data,
            $attachments
        );

        return Redirect::route($modules['route'] . '.jobWorkIssueShow', [$jobIssue->id])
            ->with('success', 'Job Work Issue PDF sent via email.');
    }

    public function bulkDownload(Request $request)
    {
        $ids = $request->ids;

        if (empty($ids)) {
            return Redirect::back()->withErrors(['No items selected.'])->withInput();
        }

        $jobIssues = JobWorkIssue::with('company')->withTrashed()->whereIn('id', $ids)->get();
        if ($jobIssues->isEmpty()) {
            return Redirect::back()->withErrors(['No matching records found.'])->withInput();
        }

        $zip = new ZipArchive;
        $zipFileName = 'JobWorkIssues_' . now()->format('Ymd_His') . '.zip';
        $zipFilePath = public_path('uploads/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($jobIssues as $jobIssue) {
                $company_id = $jobIssue->company_id;
                $company_slug = Str::slug($jobIssue->company->company_name ?? 'company');
                $folder = "uploads/{$company_id}-{$company_slug}/jobissues_pdf/";
                $uploadedPath = public_path($folder);

                $safeIssueNo = str_replace(['/', '\\'], '-', $jobIssue->job_work_issue_no);
                $filename = 'JobWorkIssue_' . $safeIssueNo . '_' . $jobIssue->id . '.pdf';
                $filepath = $uploadedPath . $filename;

                if (!File::exists($filepath)) {
                    // Try to generate it on the fly
                    Helper::generateJobWorkIssuePdf($jobIssue->id);
                }

                if (File::exists($filepath)) {
                    $zip->addFile($filepath, $filename);
                }
            }

            $fileCount = $zip->numFiles;
            $zip->close();

            if (File::exists($zipFilePath) && $fileCount > 0) {
                return response()->download($zipFilePath)->deleteFileAfterSend(true);
            } else {
                if (File::exists($zipFilePath)) {
                    File::delete($zipFilePath);
                }
                return Redirect::back()->withErrors(['No PDF files found for selected items.'])->withInput();
            }
        }

    }

    /**
     * Generate E-Way Bill Helper
     */
    public function generateEwayBill(JobWorkIssue $jobWorkIssue, JobWorkIssueEwayBillService $service, array $transportData = [])
    {
        try {

            $eway = JobWorkIssueEwayBill::create([
                'company_id' => $jobWorkIssue->company_id,
                'job_work_issue_id' => $jobWorkIssue->id,
                'vehicle_no' => $transportData['vehicle_no'] ?? '',
                'vehicle_type' => $transportData['vehicle_type'] ?? '',
                'lr_no' => $transportData['lr_no'] ?? '',
                'lr_date' => $transportData['lr_date'] ?? null,
                'transporter_id' => $jobWorkIssue->transport_details?->transport_by_id,
                'transporter_name' => $jobWorkIssue->transport_details?->name,
                'transporter_gstin' => $jobWorkIssue->transport_details?->transport_gst_id,
                'status' => 'PENDING',
                'created_type' => $jobWorkIssue->created_type,
                'created_by' => $jobWorkIssue->created_by,
            ]);
            $lastEwayInsertedId = $eway->id;

            $credential = CompanySocial::where('company_id', $jobWorkIssue->company_id)
                ->where('platform', 'eway')
                ->whereNotNull('platform')
                ->firstOrFail();

            $service->authenticate($credential);
            $response = $service->generate($jobWorkIssue, $transportData);


            if (empty($response['data']['ewayBillNo'])) {
                throw new \Exception($response['error'] ?? 'E-Way Bill generation failed');
            }

            // Calculate validity?
            $validUpto = now()->addDays(1); // Default 1 day if distance not available
            if (isset($response['data']['validUpto'])) {

            }

            $eway->update([
                'eway_bill_no' => $response['data']['ewayBillNo'],
                'eway_bill_date' => now(),
                'valid_upto' => $validUpto,
                'status' => 'GENERATED',
                'generated_at' => now(),
                'api_response' => $response,
            ]);

            $jobWorkIssue->update([
                'eway_bill_id' => $lastEwayInsertedId,
                'eway_bill_no' => $response['data']['ewayBillNo'],
                'eway_bill_status' => 'GENERATED',
                'generate_eway' => 1,
            ]);

            return true;

        } catch (\Exception $e) {
            // Log error
            Log::error('Job Work E-Way Bill Generation Error: ' . $e->getMessage());
            if (isset($eway)) {
                $eway->update(['error_message' => $e->getMessage()]);
            }
            return false;
        }
    }


    public function updateVehicle(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = $this->currentGuard ?? null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Check permissions
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $value) {
                $modules[$value . '_permission'] =
                    (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['update_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        // Validation
        $validator = Validator::make($request->all(), [ // validation
            'job_work_issue_id' => 'required|exists:job_work_issue,id',
            'vehicle_no' => 'required|string|max:20',
            'vehicle_type' => 'nullable|in:R,RW,O',
            'reason_code' => 'required|in:1,2,3,4',
            'reason_remark' => 'nullable|string|max:100',
            'lr_no' => 'nullable|string|max:50',
            'lr_date' => 'nullable|date',
        ], [
            'job_work_issue_id.required' => 'Job Work Issue ID is required',
            'job_work_issue_id.exists' => 'Job Work Issue not found',
            'vehicle_no.required' => 'Vehicle number is required',
            'reason_code.required' => 'Reason for change is required',
            'reason_code.in' => 'Invalid reason code selected',
        ]);

        if ($validator->fails()) {
            return $this->sendError(
                $validator->messages()->first(),
                $validator->messages(),
                [],
                422
            );
        }

        DB::beginTransaction();

        try {
            $jobWorkIssue = JobWorkIssue::with(['company.city', 'jobWorkIssueEwayBill'])
                ->findOrFail($request->job_work_issue_id);

            $jobWorkIssueEwayBill = $jobWorkIssue->jobWorkIssueEwayBill;

            // E-Way Bill check
            if (!$jobWorkIssueEwayBill || empty($jobWorkIssueEwayBill->eway_bill_no)) {
                $msg = 'E-Way Bill not found for this Job Work Issue';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 404)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Same vehicle check
            if ($jobWorkIssueEwayBill->vehicle_no === $request->vehicle_no) {
                $msg = 'New vehicle number is same as current vehicle number';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 422)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Credentials
            $credential = CompanySocial::where('company_id', $jobWorkIssue->company_id)
                ->where('platform', 'eway')
                ->whereNotNull('platform')
                ->firstOrFail();

            if (!$credential) {
                $msg = 'E-Way Bill credentials not found. Please configure E-Way Bill settings first.';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 404)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Authenticate E-Way API (Using JobWorkIssueEwayBillService)
            $ewayService = new JobWorkIssueEwayBillService();
            $ewayService->authenticate($credential);

            $reasonRemarks = $request->reason_remark
                ?? $this->getReasonRemarkText($request->reason_code);

            // API call
            try {
                $updateResult = $ewayService->updateVehicle(
                    $jobWorkIssueEwayBill->eway_bill_no,
                    $request->vehicle_no,
                    $jobWorkIssue->transport_detail->name ?? '',
                    $request->vehicle_type ?? 'R',
                    $request->reason_code,
                    substr($jobWorkIssue->company->city->name ?? '', 0, 50),
                    $request->lr_no ?? '',
                    $request->lr_date ?? '',
                    $reasonRemarks
                );

            } catch (\Exception $e) {
                DB::rollBack();
                $msg = 'Vehicle update failed: ' . $e->getMessage();
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 500)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            if ($updateResult['status_cd'] != 1) {
                DB::rollBack();
                $errorMsg = $updateResult['error']['message'] ?? 'Vehicle update failed on portal';
                if (isset($updateResult['error']['errorResponse'])) {
                    $errorMsg .= ' - ' . (is_array($updateResult['error']['errorResponse']) ? json_encode($updateResult['error']['errorResponse']) : $updateResult['error']['errorResponse']);
                }
                return $request->ajax()
                    ? $this->sendError($errorMsg, [], [], 422)
                    : Redirect::back()->withErrors([$errorMsg])->withInput();
            }

            $updateResponseJson = json_encode($updateResult, JSON_UNESCAPED_UNICODE);

            // Update history
            EwayBillVehicleHistory::where('eway_bill_id', $jobWorkIssueEwayBill->id)
                ->update(['is_current' => false]);

            EwayBillVehicleHistory::create([
                'eway_bill_id' => $jobWorkIssueEwayBill->id,
                'eway_bill_no' => $jobWorkIssueEwayBill->eway_bill_no,
                'vehicle_no' => $request->vehicle_no,
                'vehicle_type' => $request->vehicle_type ?? 'R',
                'transport_mode' => '1',
                'transporter_name' => $jobWorkIssue->transport_detail->name ?? '',
                'lr_no' => $request->lr_no,
                'lr_date' => $request->lr_date,
                'from_place' => substr($jobWorkIssue->company->city->name ?? '', 0, 50),
                'from_state' => substr($credential->gstin, 0, 2),
                'reason_code' => $request->reason_code,
                'reason_remark' => $reasonRemarks,
                'update_response' => $updateResponseJson,
                'updated_by' => $loginUserId,
                'is_current' => true,
                'created_by' => $loginUserId,
                'created_at' => now(),
            ]);

            // Update tables
            $jobWorkIssueEwayBill->update([
                'vehicle_no' => $request->vehicle_no,
                'vehicle_type' => $request->vehicle_type ?? 'R',
                'lr_no' => $request->lr_no,
                'lr_date' => $request->lr_date,
                'vehicle_update_response' => $updateResponseJson,
                'status' => 'UPDATED'
            ]);

            DB::commit();

            $successMessage = 'Vehicle details updated successfully';

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => $successMessage,
                    'data' => [
                        'eway_bill_no' => $jobWorkIssueEwayBill->eway_bill_no,
                        'old_vehicle_no' => $jobWorkIssueEwayBill->getOriginal('vehicle_no'),
                        'new_vehicle_no' => $request->vehicle_no,
                        'valid_until' => $updateResult['validUpto'] ?? null,
                    ]
                ]);
            }

            return Redirect::route($modules['route'] . '.index')->withSuccess($successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            $msg = 'Vehicle update failed: ' . $e->getMessage();
            return $request->ajax()
                ? $this->sendError($msg, [], [], 500)
                : Redirect::route($modules['route'] . '.index')->withErrors($msg);
        }
    }

    public function getReasonRemarkText($code)
    {
        $reasons = [
            '1' => 'Due to Break Down',
            '2' => 'Due to Transshipment',
            '3' => 'Others',
            '4' => 'First Time',
        ];
        return $reasons[$code] ?? 'Others';
    }

    public function vehicleHistory(Request $request, $id)
    {
        if ($request->ajax()) {
            try {
                // $id is job_work_issue_id passed in URL
                $jobWorkIssue = JobWorkIssue::with('jobWorkIssueEwayBill')->find($id);
                $jobWorkIssueEwayBill = $jobWorkIssue?->jobWorkIssueEwayBill;

                if (!$jobWorkIssueEwayBill || empty($jobWorkIssueEwayBill->eway_bill_no)) {
                    return response()->json(['success' => false, 'message' => 'No E-Way Bill found']);
                }

                // Fetch history
                $history = EwayBillVehicleHistory::where('eway_bill_no', $jobWorkIssueEwayBill->eway_bill_no)
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function ($item) {
                        $updatedAt = $item->updated_at ?? $item->created_at ?? now();
                        return [
                            'updated_at' => \Carbon\Carbon::parse($updatedAt)->format('d-m-Y H:i'),
                            'vehicle_no' => $item->vehicle_no,
                            'vehicle_type' => $item->vehicle_type === 'R' ? 'Regular' : 'ODC',
                            'lr_no' => $item->lr_no,
                            'lr_date' => $item->lr_date ? \Carbon\Carbon::parse($item->lr_date)->format('d-m-Y') : '-',
                            'reason' => $this->getEwayReasonCodeText($item->reason_code),
                            'reason_remark' => $item->reason_remark,
                            'is_current' => (bool) $item->is_current,
                            'updated_by' => $item->updated_by // Should map to name if possible
                        ];
                    });

                return response()->json([
                    'success' => true,
                    'data' => [
                        'eway_bill_no' => $jobWorkIssueEwayBill->eway_bill_no,
                        'total_changes' => $history->count(),
                        'history' => $history
                    ]
                ]);

            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }

    private function getEwayReasonCodeText($code)
    {
        $reasons = [
            1 => 'Due to Break Down',
            2 => 'Due to Transhipment',
            3 => 'Others',
            4 => 'First Time'
        ];
        return $reasons[$code] ?? 'Unknown';
    }

    public function get_vehicle_history_eway_bill(Request $request)
    {
        if ($request->ajax()) {
            try {
                if (!$request->id) {
                    return $this->sendError('ID is required');
                }
                $jobWorkIssueEwayBill = JobWorkIssueEwayBill::findOrFail($request->id);

                if (empty($jobWorkIssueEwayBill->eway_bill_no)) {
                    // Fallback to fetch by ID if eway_bill_no is empty (though it shouldn't be)
                    // Or just return empty history
                    return $this->sendResponse([], 'No E-Way Bill Number found');
                }

                // Fetch history using eway_bill_no
                $history = EwayBillVehicleHistory::where('eway_bill_no', $jobWorkIssueEwayBill->eway_bill_no)
                    ->orderBy('id', 'desc')
                    ->get()
                    ->map(function ($item) {
                        $updatedAt = $item->updated_at ?? $item->created_at ?? now();
                        return [
                            'updated_at' => \Carbon\Carbon::parse($updatedAt)->format('d-m-Y H:i'),
                            'vehicle_no' => $item->vehicle_no,
                            'vehicle_type' => $item->vehicle_type === 'R' ? 'Regular' : 'ODC',
                            'lr_no' => $item->lr_no,
                            'lr_date' => $item->lr_date ? \Carbon\Carbon::parse($item->lr_date)->format('d-m-Y') : '-',
                            'valid_upto' => '',
                            'reason' => $this->getReasonCodeText($item->reason_code),
                            'reason_remark' => $item->reason_remark,
                        ];
                    });

                return $this->sendResponse($history, 'History fetched');
            } catch (\Exception $e) {
                return $this->sendError('Error: ' . $e->getMessage() . ' Line: ' . $e->getLine());
            }
        }
        return abort(404);
    }

    private function getReasonCodeText($code)
    {
        $remarks = [
            '1' => 'Vehicle breakdown - Changed to another vehicle',
            '2' => 'Goods transhipment to another vehicle',
            '3' => 'Vehicle number updated',
            '4' => 'First time vehicle entry',
        ];

        return $remarks[$code] ?? 'Vehicle number updated';
    }

    public function ewayBillPrint($id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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

        try {
            View::share('modules', $modules);
            $JobWorkIssue = JobWorkIssue::with([
                'company',
                'vendor',
                'team_person',
                'job_work_issue_items',
                'jobWorkIssueEwayBill',
                'transport_by',
                'transport_details',
            ])->findOrFail($id);

            // Get E-Way Bill details
            $ewayBillNo = $JobWorkIssue->jobWorkIssueEwayBill->eway_bill_no;
            $supplierGstin = $JobWorkIssue->company->gst_no;
            $genDateTime = \Carbon\Carbon::parse($JobWorkIssue->jobWorkIssueEwayBill->generated_at ?? now());

            $qrString = $ewayBillNo . '/' .
                $supplierGstin . '/' .
                $genDateTime->format('d/m/Y') . '/' .
                $genDateTime->format('h:i A');

            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($qrString)
                ->encoding(new Encoding('UTF-8'))
                ->size(450)
                ->margin(10)
                ->build();

            $qrCodeDataUri = $result->getDataUri();

            return view($modules['folder_path'] . '.ewaybill_print', compact('JobWorkIssue', 'qrCodeDataUri'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
