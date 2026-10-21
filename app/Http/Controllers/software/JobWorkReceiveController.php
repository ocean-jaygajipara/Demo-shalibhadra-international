<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkReceiveExport;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateJobWorkReceivePdfJob;
use App\Models\JobWorkReceive;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use App\Helpers\Helper;
use App\Http\Requests\JobWorkReceiveRequest;
use App\Models\JobWorkIssue;
use App\Models\JobWorkIssueItems;
use App\Models\JobWorkReceiveItems;
use App\Models\MasterWarehouse;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStockHistory;
use App\Models\Vendor;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use ZipArchive;

class JobWorkReceiveController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Job Work Receive',
            'folder_path' => 'software.modules.job_work_management.job_work_receive',
            'route' => 'job-work-receive',
            'table_name' => (new JobWorkReceive())->getTable(),
            'module_name' => 'Job Work Receive',
            'permisstion_prefix' => 'job-work-receive',
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
                (object) ['data' => "job_work_receive_no", 'name' => 'job_work_receive_no', 'td_label' => 'Job Work Receive NO'],
                (object) ['data' => "job_work_issue_no", 'name' => 'job_work_issue_no', 'td_label' => 'Job Work Issue NO'],
                (object) ['data' => "job_work_receive_date", 'name' => 'job_work_receive_date', 'td_label' => 'Job Work Receive Date', 'className' => ''],
                (object) ['data' => "process_receive_id", 'name' => 'process_receive_id', 'td_label' => 'Process Receive', 'className' => ''],
                (object) ['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Vendor Name', 'className' => ''],
                (object) ['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' => ''],
                (object) ['data' => "master_total_pending", 'name' => 'master_total_pending', 'td_label' => 'Total Pending', 'className' => ''],
                (object) ['data' => "master_total_current_pending", 'name' => 'master_total_current_pending', 'td_label' => 'Current Pending', 'className' => ''],
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

                $data = JobWorkReceive::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['vendor', 'company', 'process_received', 'job_work_receive_items', 'job_work_issue']);
                $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }


                $returnData = Datatables::of($data)
                    ->addColumn('select_all', function ($row) {
                        return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                    })
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_process_receive_id') && $request->filter_process_receive_id) {
                            $query->where('process_receive_id', $request->filter_process_receive_id);
                        }

                        if ($request->has('filter_vendor') && $request->filter_vendor) {
                            $query->where('vendor_id', $request->filter_vendor);
                        }

                        if ($request->has('filter_job_work_receive_date') && $request->filter_job_work_receive_date) {

                            $filter_job_work_receive_date = $request->filter_job_work_receive_date;
                            $filter_job_work_receive_date = explode(" to ", $filter_job_work_receive_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_job_work_receive_date) == 2) {
                                $fromDate = Helper::convert_date($filter_job_work_receive_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_job_work_receive_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new JobWorkReceive())->getTable() . '.job_work_receive_date', ">=", $fromDate);
                                $query->whereDate((new JobWorkReceive())->getTable() . '.job_work_receive_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new JobWorkReceive())->getTable() . '.job_work_receive_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('job_work_receive_no', 'like', "%{$search}%")
                                    ->orWhereHas('vendor', function ($q2) use ($search) {
                                        $q2->where('vendor_name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('job_work_issue', function ($q3) use ($search) {
                                        $q3->where('job_work_issue_no', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('job_work_receive_date', function ($row) {
                        return \Carbon\Carbon::parse($row?->job_work_receive_date)->format('d-m-Y');
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->addColumn('job_work_issue_no', function ($row) {
                        return $row?->job_work_issue?->job_work_issue_no ?? '-';
                    })
                    ->addColumn('vendor_name', function ($row) use ($modules) {
                        $vendorCode = optional($row->vendor)->vendor_code;
                        $vendorName = optional($row->vendor)->vendor_name;

                        $vendorInfo = '<span>' . $vendorCode . '<br>' . $vendorName . '</span>';
                        return $vendorInfo;
                    })
                    ->addColumn('process_receive_id', function ($row) {
                        return $row?->process_received?->name ?? '-';
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';

                        $action .= '
                        <div class="">
                            <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {

                            if ($modules['update_permission'] == true) {
                                $action .= '<li>
                                        <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                    </li>';
                            }

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                    <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1">
                                        <i class="fa-solid fa-trash mt-1 me-1"></i> Delete
                                    </a>
                                </li>';
                            }
                            $action .= '<li>
                                <a href="' . route($modules["route"] . ".jobWorkReceiveShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1">
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

                    ->rawColumns(['action', 'vendor_name', 'select_all'])
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
    public function getCategoryByIssue($id)
    {
        $jobWork = JobWorkIssue::find($id);

        if ($jobWork) {
            return response()->json([
                'success' => true,
                'category_id' => $jobWork->product_category_id,
            ]);
        }

        return response()->json(['success' => false]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JobWorkReceiveRequest $request)
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
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            // Server-side validation for Job Work Order Items
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    // Product Name
                    if (is_null($product) || trim($product) === '') {
                        DB::rollBack();
                        return back()->withErrors([
                            "product_name.$key" => "Product name is required."
                        ])->withInput();
                    }

                    // Product Code
                    $product_code = $request->product_code[$key] ?? null;
                    if (is_null($product_code) || trim($product_code) === '') {
                        DB::rollBack();
                        return back()->withErrors([
                            "product_code.$key" => "Product code is required."
                        ])->withInput();
                    }

                    // master_total_gross_weight
                    $master_total_gross_weight = $request->master_total_gross_weight ?? null;
                    if (is_null($master_total_gross_weight) || $master_total_gross_weight === '' || floatval($master_total_gross_weight) <= 0) {
                        DB::rollBack();
                        return back()->withErrors([
                            "master_total_gross_weight" => "Total gross weight must be greater than 0."
                        ])->withInput();
                    }

                    // master_total_net_weight
                    $master_total_net_weight = $request->master_total_net_weight ?? null;
                    if (is_null($master_total_net_weight) || $master_total_net_weight === '' || floatval($master_total_net_weight) <= 0) {
                        DB::rollBack();
                        return back()->withErrors([
                            "master_total_net_weight" => "Total net weight must be greater than 0."
                        ])->withInput();
                    }
                }
            }

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;

            $JobWorkReceive = JobWorkReceive::create($validated);
            $lastInsertedId = $JobWorkReceive->id;

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

                    $warehouse = MasterWarehouse::where('company_id', $validated['company_id'])
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim('Incoming Floor'))])
                        ->first();

                    $warehouse_id = $warehouse?->id ?? null;
                    $incoming_scrap = $request->incoming_scrap[$key];
                    if ($warehouse_id > 0 && (float) $incoming_scrap > 0 && $request->wt_pc[$key] > 0) {

                        $productCategory = ProductCategory::where('company_id', $validated['company_id'])
                            ->whereRaw('LOWER(name) = ?', ['scrap item'])
                            ->first();

                        if ($productCategory) {
                            $productData = Product::where('company_id', $validated['company_id'])
                                ->where('product_category_id', $productCategory->id)
                                ->orderBy('id', 'asc')
                                ->first();
                        }

                        $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $productData->product_category_id, $productData->id);
                        $product_total_qty = Helper::getProductStockTotalQuantity($validated['company_id'], $warehouse_id, $productData->product_category_id, $productData->id);

                        $total_final_kg = (float) $product_total_kg + (float) $incoming_scrap;
                        $total_quantity = 0;
                        if ($total_final_kg > 0 && $request->wt_pc[$key] > 0) {
                            $total_quantity = Helper::kgToQtyConvertForStock((float) $incoming_scrap, $request->wt_pc[$key]);
                        }

                        ProductStockHistory::create([
                            'company_id' => $validated['company_id'],
                            'warehouse_id' => $warehouse_id,
                            'product_category_id' => $productData->product_category_id,
                            'product_subcategory_id' => '',
                            'product_variant_price_id' => $productData->id,
                            'job_work_receive_id' => $lastInsertedId,
                            'total_quantity' => (float) $product_total_qty + (float) $total_quantity,
                            'total_kg' => (float) $product_total_kg + (float) $incoming_scrap,
                            'credit' => (float) $incoming_scrap,
                            'date' => $validated['job_work_receive_date'],
                            'remark' => 'Job Work Receive Generate No. ' . $validated['job_work_receive_no'],
                            'created_by' => $loginUserId
                        ]);
                    }

                    $JobWorkReceive = JobWorkReceiveItems::create([
                        'company_id' => $validated['company_id'],
                        'job_work_receive_id' => $lastInsertedId,
                        'job_work_issue_item_id' => $request->job_work_issue_item_id[$key] ?? null,
                        'product_id' => $request->product_id[$key],
                        'display_order' => ($key + 1),
                        'product_name' => $product,
                        'product_code' => $request->product_code[$key],
                        'product_stock' => $request->product_stock[$key],
                        'pending_weight' => $request->pending_weight[$key],
                        'received_weight' => $request->received_weight[$key],
                        'gross_weight' => $request->gross_weight[$key],
                        'wt_pc' => $request->wt_pc[$key],
                        'quantity' => $request->quantity[$key] ?? null,
                        'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                        'bl_percentage' => $request->bl_percentage[$key] ?? null,
                        'incoming_scrap' => $request->incoming_scrap[$key] ?? null,
                        'product_used' => $request->product_used[$key] ?? null,
                        'our_stock' => $request->our_stock[$key] ?? null,
                        'challan_stock' => $request->challan_stock[$key] ?? null,
                        'used_qty' => $request->used_qty[$key] ?? null,
                        'used_weight' => $request->used_weight[$key] ?? null,
                    ]);

                    // if ($request->job_work_issue_item_id[$key] > 0) {
                    //     $jobWorkItem = JobWorkIssueItems::withTrashed()->findOrFail($request->job_work_issue_item_id[$key]);
                    //     $newRemainingWeight = $jobWorkItem->remaining_weight - $request->received_weight[$key];

                    //     if ($newRemainingWeight < 0) {
                    //         DB::rollBack();
                    //         return Redirect::back()->withErrors(['Job Work issue exceeds remaining weight.'])->withInput();
                    //     }
                    //     $jobWorkItem->update([
                    //         'remaining_weight' => $newRemainingWeight,
                    //     ]);
                    // }

                    $JobWorkIssue = JobWorkIssue::findOrFail($request->job_work_issue_id);
                    $JobWorkIssue->update([
                        'master_total_pending' => $request->master_total_pending,
                        'updated_by' => $loginUserId
                    ]);


                    // $jobWorkIssueItems = JobWorkIssueItems::where('job_work_issue_id', $request->job_work_issue_id)->get();

                    // $isFullyReceived = $jobWorkIssueItems->every(function ($item) {
                    //     return $item->remaining_weight == 0;
                    // });
                    //dd($isFullyDispatched);


                    if ($JobWorkIssue->master_total_pending == 0) {
                        $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "fd", 'Job Work Issue');
                    } else {
                        $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "pd", 'Job Work Issue');
                    }
                    if (!$sales_status_id) {
                        return "";
                    }
                    //dd($sales_status_id);
                    $JobWorkIssue->update([
                        'sales_status_id' => $sales_status_id,
                        'updated_by' => $loginUserId
                    ]);
                }
            }
            DB::commit();

            if ($request->submit_type === 'save_print') {
                return redirect()
                    ->route($modules['route'] . '.jobWorkReceivePrint', $lastInsertedId)
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
            $edit = JobWorkReceive::with(['vendor', 'job_work_receive_items', 'job_work_issue'])->findOrFail($id);

            //$master_gross_weight = 0;

            // $dataJobWorkItemsItems = JobWorkIssueItems::where('job_work_issue_id', $edit->job_work_issue_id)->get();

            // foreach ($edit->job_work_receive_items as $receiveItem) {
            //     if($receiveItem->job_work_issue_item_id){
            //         $jobitem = $dataJobWorkItemsItems->firstWhere('id', $receiveItem->job_work_issue_item_id);
            //         if ($jobitem) {
            //             $received_weight =  $receiveItem->received_weight;
            //             $remaining_weight = $jobitem->remaining_weight + $received_weight;

            //             $receiveItem->remaining_weight = $remaining_weight;
            //             $master_gross_weight += $remaining_weight;
            //         } else {
            //             $receiveItem->remaining_weight = 0; // fallback if not found
            //         }
            //     }else{
            //         $master_gross_weight += $receiveItem->received_weight;
            //     }
            // }

            // Add master total to main object
            // $edit->master_gross_weight = ($edit->job_work_issue->master_total_pending == 0)
            //         ? $edit->master_total_net_weight + $edit->job_work_issue->master_total_pending
            //         : $edit->job_work_issue->master_total_pending;

            $edit->master_gross_weight = $edit?->master_total_net_weight + $edit?->job_work_issue?->master_total_pending;

            //dd($edit);

            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(JobWorkReceiveRequest $request, string $id)
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
        try {
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    // Product Name
                    if (is_null($product) || trim($product) === '') {
                        DB::rollBack();
                        return back()->withErrors([
                            "product_name.$key" => "Product name is required."
                        ])->withInput();
                    }

                    // Product Code
                    $product_code = $request->product_code[$key] ?? null;
                    if (is_null($product_code) || trim($product_code) === '') {
                        DB::rollBack();
                        return back()->withErrors([
                            "product_code.$key" => "Product code is required."
                        ])->withInput();
                    }

                    // master_total_gross_weight
                    $master_total_gross_weight = $request->master_total_gross_weight ?? null;
                    if (is_null($master_total_gross_weight) || $master_total_gross_weight === '' || floatval($master_total_gross_weight) <= 0) {
                        DB::rollBack();
                        return back()->withErrors([
                            "master_total_gross_weight" => "Total gross weight must be greater than 0."
                        ])->withInput();
                    }

                    // master_total_net_weight
                    $master_total_net_weight = $request->master_total_net_weight ?? null;
                    if (is_null($master_total_net_weight) || $master_total_net_weight === '' || floatval($master_total_net_weight) <= 0) {
                        DB::rollBack();
                        return back()->withErrors([
                            "master_total_net_weight" => "Total net weight must be greater than 0."
                        ])->withInput();
                    }
                }
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = JobWorkReceive::findOrFail($id);

            if ($updateData) {
                unset($validated['id']);
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

                $warehouse = MasterWarehouse::where('company_id', $validated['company_id'])
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim('Incoming Floor'))])
                    ->first();
                $warehouse_id = $warehouse?->id ?? null;

                if ($request->has('product_name')) {
                    JobWorkReceiveItems::where('job_work_receive_id', $id)->delete();
                    foreach ($request->product_name as $key => $product) {


                        $db_incoming_scrap = $request?->db_incoming_scrap[$key];
                        if ($warehouse_id > 0 && $db_incoming_scrap > 0) {

                            $productCategory = ProductCategory::where('company_id', $validated['company_id'])
                                ->whereRaw('LOWER(name) = ?', ['scrap item'])
                                ->first();

                            if ($productCategory) {
                                $productData = Product::where('company_id', $validated['company_id'])
                                    ->where('product_category_id', $productCategory->id)
                                    ->orderBy('id', 'asc')
                                    ->first();
                            }

                            $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $productData->product_category_id, $productData->id);
                            $product_total_qty = Helper::getProductStockTotalQuantity($validated['company_id'], $warehouse_id, $productData->product_category_id, $productData->id);

                            $total_final_kg = (float) $product_total_kg - (float) $db_incoming_scrap;
                            $total_quantity = 0;
                            if ($total_final_kg > 0 && $request->wt_pc[$key] > 0) {
                                $total_quantity = Helper::kgToQtyConvertForStock((float) $db_incoming_scrap, $request->wt_pc[$key]);
                            }

                            ProductStockHistory::create([
                                'company_id' => $validated['company_id'],
                                'warehouse_id' => $warehouse_id,
                                'product_category_id' => $productData->product_category_id,
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $productData->id,
                                'job_work_receive_id' => $id,
                                'total_quantity' => (float) $product_total_qty + (float) $total_quantity,
                                'total_kg' => (float) $product_total_kg - (float) $db_incoming_scrap,
                                'credit' => (float) $db_incoming_scrap,
                                'date' => $validated['job_work_receive_date'],
                                'remark' => 'Job Work Receive Generate No. ' . $validated['job_work_receive_no'],
                                'created_by' => $loginUserId
                            ]);
                        }


                        $incoming_scrap = $request?->incoming_scrap[$key];
                        if ($warehouse_id > 0 && $incoming_scrap > 0) {

                            $productCategory = ProductCategory::where('company_id', $validated['company_id'])
                                ->whereRaw('LOWER(name) = ?', ['scrap item'])
                                ->first();

                            if ($productCategory) {
                                $productData = Product::where('company_id', $validated['company_id'])
                                    ->where('product_category_id', $productCategory->id)
                                    ->orderBy('id', 'asc')
                                    ->first();
                            }

                            $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $productData->product_category_id, $productData->id);
                            $product_total_qty = Helper::getProductStockTotalQuantity($validated['company_id'], $warehouse_id, $productData->product_category_id, $productData->id);

                            $total_final_kg = (float) $product_total_kg + (float) $incoming_scrap;
                            $total_quantity = 0;
                            if ($total_final_kg > 0 && $request->wt_pc[$key] > 0) {
                                $total_quantity = Helper::kgToQtyConvertForStock((float) $incoming_scrap, $request->wt_pc[$key]);
                            }

                            ProductStockHistory::create([
                                'company_id' => $validated['company_id'],
                                'warehouse_id' => $warehouse_id,
                                'product_category_id' => $productData->product_category_id,
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $productData->id,
                                'job_work_receive_id' => $id,
                                'total_quantity' => (float) $product_total_qty + (float) $total_quantity,
                                'total_kg' => (float) $product_total_kg + (float) $incoming_scrap,
                                'credit' => (float) $incoming_scrap,
                                'date' => $validated['job_work_receive_date'],
                                'remark' => 'Job Work Receive Generate No. ' . $validated['job_work_receive_no'],
                                'created_by' => $loginUserId
                            ]);
                        }


                        $JobWorkReceive = JobWorkReceiveItems::create([
                            'company_id' => $validated['company_id'],
                            'job_work_receive_id' => $id,
                            'job_work_issue_item_id' => null,
                            'product_id' => $request->product_id[$key],
                            'display_order' => ($key + 1),
                            'product_name' => $product,
                            'product_code' => $request->product_code[$key],
                            'product_stock' => $request->product_stock[$key],
                            'pending_weight' => $request->pending_weight[$key],
                            'received_weight' => $request->received_weight[$key],
                            'gross_weight' => $request->gross_weight[$key],
                            'wt_pc' => $request->wt_pc[$key],
                            'quantity' => $request->quantity[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'bl_percentage' => $request->bl_percentage[$key] ?? null,
                            'incoming_scrap' => $request->incoming_scrap[$key] ?? null,
                            'product_used' => $request->product_used[$key] ?? null,
                            'our_stock' => $request->our_stock[$key] ?? null,
                            'challan_stock' => $request->challan_stock[$key] ?? null,
                            'used_qty' => $request->used_qty[$key] ?? null,
                            'used_weight' => $request->used_weight[$key] ?? null,
                        ]);

                        // if ($request->job_work_issue_item_id[$key] > 0) {
                        //     $newRemainingQty = $request->issue_gross_weight[$key] - $request->received_weight[$key];
                        //     if ($newRemainingQty < 0) {
                        //         DB::rollBack();
                        //         return Redirect::back()->withErrors(['Job Work issue exceeds remaining weight.'])->withInput();
                        //     }
                        // }
                    }


                    // $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $request->job_work_issue_id)->pluck('id');
                    // if ($jobWorkItemsIds->isNotEmpty()) {
                    //     $dispatchItemsCount = JobWorkReceiveItems::whereIn('job_work_issue_item_id', $jobWorkItemsIds)
                    //         ->whereNull('deleted_at')
                    //         ->count();

                    //     $JobWorkIssue = JobWorkIssue::findOrFail($request->job_work_issue_id);
                    //     $job_pending_id   = Helper::getSalesStatusNameById($request->company_id, "pen", 'Job Work Issue');
                    //     $job_partially_id = Helper::getSalesStatusNameById($request->company_id, "pd", 'Job Work Issue');

                    //     $JobWorkIssue->update([
                    //         'sales_status_id' => $dispatchItemsCount == 0 ? $job_pending_id : $job_partially_id,
                    //         'updated_by'      => $loginUserId,
                    //     ]);
                    // }

                    $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $request->job_work_issue_id)->pluck('id');
                    $JobWorkIssue = JobWorkIssue::findOrFail($request->job_work_issue_id);

                    $JobWorkIssue->update([
                        'master_total_pending' => $request->master_total_pending,
                        'updated_by' => $loginUserId
                    ]);

                    $dispatchItemsCount = JobWorkReceiveItems::whereIn('job_work_issue_item_id', $jobWorkItemsIds)
                        ->whereNull('deleted_at')
                        ->count();


                    if ($JobWorkIssue->master_total_pending == 0) {
                        $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "fd", 'Job Work Issue');
                    } else {
                        $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "pd", 'Job Work Issue');
                    }

                    if (!$sales_status_id) {
                        return "";
                    }

                    $JobWorkIssue->update([
                        'sales_status_id' => $sales_status_id,
                        'updated_by' => $loginUserId
                    ]);


                    // $dataJobWorkItemsItems = JobWorkIssueItems::where('job_work_issue_id', $request->job_work_issue_id)->get();
                    // foreach ($dataJobWorkItemsItems as $jobitem) {
                    //     $received_weight = JobWorkReceiveItems::where('job_work_issue_item_id', $jobitem->id)
                    //         ->whereNull('deleted_at')
                    //         ->sum('received_weight');

                    //     $jobitem->update([
                    //         'remaining_weight' => ($received_weight == 0)
                    //             ? $jobitem->gross_weight
                    //             : ($jobitem->gross_weight - $received_weight)
                    //     ]);
                    // }
                }
                GenerateJobWorkReceivePdfJob::dispatch($id);
                // dd('heredfsf');
                if ($request->submit_type === 'save_print') {
                    return redirect()
                        ->route($modules['route'] . '.jobWorkReceivePrint', $id)
                        ->withSuccess($modules['title'] . ' created successfully');
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }

            if ($request->submit_type === 'save_print') {
                return redirect()
                    ->route($modules['route'] . '.jobWorkReceivePrint', $id)
                    ->withSuccess($modules['title'] . ' created successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
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
        if (!$modules['delete_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $dataDelete = JobWorkReceive::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {

            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                $jobWorkReceiveItems = JobWorkReceiveItems::where('job_work_receive_id', $id)->get();
                foreach ($jobWorkReceiveItems as $item) {
                    $item->update(['deleted_by' => $loginUserId]);
                    $item->delete();
                }
                $dataDelete->delete();


                $product_stock_history_data = ProductStockHistory::where('job_work_receive_id', $id)->get()->toArray();
                if (is_array($product_stock_history_data) && !empty($product_stock_history_data)) {
                    foreach ($product_stock_history_data as $k => $value) {

                        $product_total_kg = Helper::getProductStockTotalKG($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);
                        $ProductData = Product::findOrFail($value['product_variant_price_id']);

                        $product_total_qty = Helper::getProductStockTotalQuantity($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);

                        $total_remaining_kg = (float) $product_total_kg - (float) $value['credit'];
                        $total_quantity = 0;
                        if ($total_remaining_kg > 0 && $dataDelete->wt_pc > 0) {
                            $total_quantity = Helper::kgToQtyConvertForStock((float) $value['credit'], $dataDelete->wt_pc);
                        }

                        ProductStockHistory::where('qc_inspection_id', $id)->where("id", $value['id'])->delete();
                        ProductStockHistory::create([
                            'company_id' => $value['company_id'],
                            'warehouse_id' => $value['warehouse_id'],
                            'product_category_id' => $value['product_category_id'],
                            'product_subcategory_id' => '',
                            'product_variant_price_id' => $value['product_variant_price_id'],
                            'qc_inspection_id' => $id,
                            'total_quantity' => (float) $product_total_qty - (float) $total_quantity,
                            'total_kg' => (float) $product_total_kg - (float) $value['credit'],
                            'debit' => (float) $value['credit'],
                            'date' => $value['date'],
                            'remark' => 'delete Job Work Receive. - ' . $dataDelete->job_work_receive_no,
                            'created_by' => $loginUserId
                        ]);
                    }
                }

                // $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $dataDelete->job_work_issue_id)->pluck('id');
                // if ($jobWorkItemsIds->isNotEmpty()) {
                //     $dispatchItemsCount = JobWorkReceiveItems::whereIn('job_work_issue_item_id', $jobWorkItemsIds)
                //         ->whereNull('deleted_at')
                //         ->count();

                //     $JobWorkIssue = JobWorkIssue::findOrFail($dataDelete->job_work_issue_id);
                //     $job_pending_id   = Helper::getSalesStatusNameById($dataDelete->company_id, "pen", 'Job Work Issue');
                //     $job_partially_id = Helper::getSalesStatusNameById($dataDelete->company_id, "pd", 'Job Work Issue');

                //     $JobWorkIssue->update([
                //         'sales_status_id' => $dispatchItemsCount == 0 ? $job_pending_id : $job_partially_id,
                //         'updated_by'      => $loginUserId,
                //     ]);
                // }



                // $JobWorkIssue = JobWorkIssue::findOrFail($dataDelete->job_work_issue_id);
                // $dataJobWorkItemsItems = JobWorkIssueItems::where('job_work_issue_id', $dataDelete->job_work_issue_id)->get();

                // $jobWorkReceiveIds = [];
                // foreach ($dataJobWorkItemsItems as $jobitem) {
                //     $received_weight = JobWorkReceiveItems::where('job_work_issue_item_id', $jobitem->id)
                //         ->whereNull('deleted_at')
                //         ->sum('received_weight');

                //     $jobWorkReceiveIds = JobWorkReceiveItems::where('job_work_issue_item_id', $jobitem->id)
                //         ->whereNull('deleted_at')
                //         ->distinct()
                //         ->pluck('job_work_receive_id')
                //         ->toArray();

                //     $jobitem->update([
                //         'remaining_weight' => ($received_weight == 0)
                //             ? $jobitem->gross_weight
                //             : ($jobitem->gross_weight - $received_weight)
                //     ]);
                // }

                $totalReceivedWeight = 0;
                $JobWorkIssue = JobWorkIssue::findOrFail($dataDelete->job_work_issue_id);
                $jobWorkReceiveIds = JobWorkReceive::where('job_work_issue_id', $dataDelete->job_work_issue_id)
                    ->whereNull('deleted_at')
                    ->distinct()
                    ->pluck('id')
                    ->toArray();

                $jobWorkReceiveItems = JobWorkReceiveItems::whereIn('job_work_receive_id', $jobWorkReceiveIds)->get();
                foreach ($jobWorkReceiveItems as $item) {
                    $totalReceivedWeight += $item->received_weight;
                }
                $JobWorkIssue->update([
                    'master_total_pending' => $JobWorkIssue->total_item_weight - $totalReceivedWeight,
                ]);


                $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $dataDelete->job_work_issue_id)->pluck('id');
                if ($jobWorkItemsIds->isNotEmpty()) {
                    $JobWorkIssue = JobWorkIssue::findOrFail($dataDelete->job_work_issue_id);

                    if ($JobWorkIssue->master_total_pending == $JobWorkIssue->total_item_weight) {
                        $sales_status_id = Helper::getSalesStatusNameById($dataDelete->company_id, "pen", 'Job Work Issue');
                    } else {
                        if ($JobWorkIssue->master_total_pending == 0) {
                            $sales_status_id = Helper::getSalesStatusNameById($dataDelete->company_id, "fd", 'Job Work Issue');
                        } else {
                            $sales_status_id = Helper::getSalesStatusNameById($dataDelete->company_id, "pd", 'Job Work Issue');
                        }
                    }

                    if (!$sales_status_id) {
                        return "";
                    }

                    $JobWorkIssue->update([
                        'sales_status_id' => $sales_status_id,
                        'updated_by' => $loginUserId
                    ]);
                }
                return $this->sendResponse([], $modules['title'] . ' deleted successfully');
            }
            return $isAjax
                ? $this->sendResponse([], "Something went wrong, please try again later")
                : false;
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function restore(string $id)
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
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }


        $dataRestore = JobWorkReceive::withTrashed()->findOrFail($id);

        try {

            if ($dataRestore) {
                $validated['updated_by'] = $loginUserId;
                $validated['deleted_by'] = null;
                $dataRestore->update($validated);

                $dataRestore->restore();

                $jobWorkReceiveItems = JobWorkReceiveItems::withTrashed()->where('job_work_receive_id', $id)->get();

                foreach ($jobWorkReceiveItems as $item) {
                    $item->update(['updated_by' => $loginUserId, 'deleted_by' => null]);
                    $item->restore();
                }


                // $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $dataRestore->job_work_issue_id)->pluck('id');

                // if ($jobWorkItemsIds->isNotEmpty()) {
                //     $dispatchItemsCount = JobWorkReceiveItems::whereIn('job_work_issue_item_id', $jobWorkItemsIds)
                //         ->whereNull('deleted_at')
                //         ->count();

                //     $JobWorkIssue = JobWorkIssue::findOrFail($dataRestore->job_work_issue_id);

                //     $job_pending_id   = Helper::getSalesStatusNameById($dataRestore->company_id, "pen", 'Job Work Issue');
                //     $job_fully_id = Helper::getSalesStatusNameById($dataRestore->company_id, "fd", 'Job Work Issue');
                //     $job_partially_id = Helper::getSalesStatusNameById($dataRestore->company_id, "pd", 'Job Work Issue');

                //     $JobWorkIssue->update([
                //         'sales_status_id' => ($dispatchItemsCount == 0)
                //             ? $job_pending_id
                //             : (($dispatchItemsCount == $jobWorkItemsIds->count()) ? $job_fully_id : $job_partially_id),
                //         'updated_by'      => $loginUserId,
                //     ]);
                // }


                // $JobWorkIssue = JobWorkIssue::findOrFail($dataRestore->job_work_issue_id);
                // $dataJobWorkItemsItems = JobWorkIssueItems::where('job_work_issue_id', $dataRestore->job_work_issue_id)->get();

                // $jobWorkReceiveIds = [];
                // foreach ($dataJobWorkItemsItems as $jobitem) {
                //     $received_weight = JobWorkReceiveItems::where('job_work_issue_item_id', $jobitem->id)
                //         ->whereNull('deleted_at')
                //         ->sum('received_weight');


                //     $jobWorkReceiveIds = JobWorkReceiveItems::where('job_work_issue_item_id', $jobitem->id)
                //         ->whereNull('deleted_at')
                //         ->distinct()
                //         ->pluck('job_work_receive_id')
                //         ->toArray();

                //     $jobitem->update([
                //         'remaining_weight' => ($received_weight == 0)
                //             ? $jobitem->gross_weight
                //             : ($jobitem->gross_weight - $received_weight)
                //     ]);
                // }

                // $totalReceivedWeight = 0;
                // $jobWorkReceiveItems = JobWorkReceiveItems::whereIn('job_work_receive_id', $jobWorkReceiveIds)->get();
                // foreach ($jobWorkReceiveItems as $item) {
                //     $totalReceivedWeight += $item->received_weight;
                // }
                // $JobWorkIssue->update([
                //     'master_total_pending' => $JobWorkIssue->total_item_weight - $totalReceivedWeight,
                // ]);





                // $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $dataRestore->job_work_issue_id)->pluck('id');
                // if ($jobWorkItemsIds->isNotEmpty()) {
                //     $JobWorkIssue = JobWorkIssue::findOrFail($dataRestore->job_work_issue_id);
                //     $dispatchItemsCount = JobWorkReceiveItems::whereIn('job_work_issue_item_id', $jobWorkItemsIds)
                //         ->whereNull('deleted_at')
                //         ->count();

                //     if ($dispatchItemsCount == 0) {
                //         $sales_status_id = Helper::getSalesStatusNameById($dataRestore->company_id, "pen", 'Job Work Issue');
                //     } else {
                //         if ($JobWorkIssue->master_total_pending == 0) {
                //             $sales_status_id = Helper::getSalesStatusNameById($dataRestore->company_id, "fd", 'Job Work Issue');
                //         } else {
                //             $sales_status_id = Helper::getSalesStatusNameById($dataRestore->company_id, "pd", 'Job Work Issue');
                //         }
                //     }

                //     if (!$sales_status_id) {
                //         return "";
                //     }

                //     $JobWorkIssue->update([
                //         'sales_status_id' => $sales_status_id,
                //         'updated_by' => $loginUserId
                //     ]);
                // }
                $totalReceivedWeight = 0;
                $JobWorkIssue = JobWorkIssue::findOrFail($dataRestore->job_work_issue_id);
                $jobWorkReceiveIds = JobWorkReceive::where('job_work_issue_id', $dataRestore->job_work_issue_id)
                    ->whereNull('deleted_at')
                    ->distinct()
                    ->pluck('id')
                    ->toArray();

                $jobWorkReceiveItems = JobWorkReceiveItems::whereIn('job_work_receive_id', $jobWorkReceiveIds)->get();
                foreach ($jobWorkReceiveItems as $item) {
                    $totalReceivedWeight += $item->received_weight;
                }
                $JobWorkIssue->update([
                    'master_total_pending' => $JobWorkIssue->total_item_weight - $totalReceivedWeight,
                ]);


                $jobWorkItemsIds = JobWorkIssueItems::where('job_work_issue_id', $dataRestore->job_work_issue_id)->pluck('id');
                if ($jobWorkItemsIds->isNotEmpty()) {
                    $JobWorkIssue = JobWorkIssue::findOrFail($dataRestore->job_work_issue_id);

                    if ($JobWorkIssue->master_total_pending == $JobWorkIssue->total_item_weight) {
                        $sales_status_id = Helper::getSalesStatusNameById($dataRestore->company_id, "pen", 'Job Work Issue');
                    } else {
                        if ($JobWorkIssue->master_total_pending == 0) {
                            $sales_status_id = Helper::getSalesStatusNameById($dataRestore->company_id, "fd", 'Job Work Issue');
                        } else {
                            $sales_status_id = Helper::getSalesStatusNameById($dataRestore->company_id, "pd", 'Job Work Issue');
                        }
                    }

                    if (!$sales_status_id) {
                        return "";
                    }

                    $JobWorkIssue->update([
                        'sales_status_id' => $sales_status_id,
                        'updated_by' => $loginUserId
                    ]);
                }
                return $this->sendResponse([], $modules['title'] . ' restored successfully');
            }

            return $this->sendResponse([], "Something went wrong, please try again later");
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
            $query = JobWorkReceive::with(['vendor', 'company', 'process_received', 'job_work_receive_items'])
                ->orderBy('id', 'DESC');

            // Apply filters
            $query->when($request->company, function ($q) use ($request) {
                $q->where('company_id', $request->company);
            });

            $query->when($request->vendor, function ($q) use ($request) {
                $q->where('vendor_id', $request->vendor);
            });

            $query->when($request->process_receive_id, function ($q) use ($request) {
                $q->where('process_receive_id', $request->process_receive_id);
            });

            // Job Work Issue Date filter
            if ($request->job_work_receive_date) {
                $filter_job_work_receive_date = explode(" to ", $request->job_work_receive_date);
                $fromDate = null;
                $toDate = null;

                if (count($filter_job_work_receive_date) == 2) {
                    $fromDate = Helper::convert_date($filter_job_work_receive_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($filter_job_work_receive_date[1], "d/m/Y", "Y-m-d");
                }

                if ($fromDate && $toDate) {
                    $query->whereBetween((new JobWorkReceive())->getTable() . '.job_work_receive_date', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $query->whereDate((new JobWorkReceive())->getTable() . '.job_work_receive_date', $fromDate);
                }
            }

            // Search filter (job_work_issue_no OR vendor_name)
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('job_work_receive_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%");
                        });
                });
            }

            $JobWorkReceives = $query->get();

            return view($modules['folder_path'] . '.print', compact('JobWorkReceives', 'company_id', 'modules'));
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

        return Excel::download(new JobWorkReceiveExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Job Work Received-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function jobWorkReceiveShow($id)
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
            $jobReceive = JobWorkReceive::with([
                'company',
                'vendor',
                'job_work_receive_items',
                'team_person',
                'admin_person',
                'issueForProcess',
                'job_work_type'
            ])->findOrFail($id);

            // Determine view format
            $viewFormat = $jobReceive?->company?->jobreceive_view_format ?? 'general_view';

            return view(
                $modules['folder_path'] . '.' . $viewFormat,
                compact('jobReceive', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function jobWorkReceivePrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        try {
            View::share('modules', $modules);


            $jobReceive = JobWorkReceive::with([
                'company',
                'vendor',
                'job_work_receive_items',
                'team_person',
                'admin_person',
                'issueForProcess',
                'job_work_type'
            ])->findOrFail($id);


            $viewFormat = $jobReceive?->company?->jobreceive_print_format ?? 'single_print';

            return view(
                $modules['folder_path'] . '.' . $viewFormat,
                compact('jobReceive', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function SendjobWorkReceivePdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        // Fetch Job Work Issue with all relations
        $jobReceive = JobWorkReceive::with([
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
        if ($jobReceive->company_id) {
            $company = Helper::getCompanyDetailById($jobReceive->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);
        // dd(     $sidebarHeaderLogo);
        $company_id = $jobReceive->company_id;

        $viewPath = $modules['folder_path'] . '.' . ($jobReceive?->company?->jobreceive_print_format ?? 'single_pdf');
        // Render HTML
        $html = View::make($viewPath, compact('jobReceive', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

        // File path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($jobReceive->company->company_name ?? 'company');
        $folder = "uploads/{$company_id}-{$company_slug}/jobreceives/{$year}-{$month}/";
        $uploadedPath = public_path($folder);
        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'JobWorkRecive_' . $jobReceive->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        // Email data
        $data = [
            'vendor_name' => $jobReceive->vendor?->vendor_name ?? '-',
            'company_name' => $jobReceive->company?->company_name ?? '-',
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
            $jobReceive->company_id,
            'Job Work Issue',
            'jobreceive_send_pdf',
            optional($jobReceive->vendor)->email,
            // "riddhibutaniocean@gmail.com",

            optional($jobReceive->vendor)->id,
            'JobWorkIssue/jobReceivePdf',
            $data,
            $attachments
        );

        return Redirect::route($modules['route'] . '.jobWorkReceiveShow', [$jobReceive->id])
            ->with('success', 'Job Work Receive PDF sent via email.');
    }
    public function bulkDownload(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) {
            return Redirect::back()->withErrors(['No items selected.'])->withInput();
        }

        $jobReceives = JobWorkReceive::with('company')->withTrashed()->whereIn('id', $ids)->get();
        if ($jobReceives->isEmpty()) {
            return Redirect::back()->withErrors(['No matching records found.'])->withInput();
        }

        $zip = new ZipArchive;
        $zipFileName = 'JobWorkReceive_' . now()->format('Ymd_His') . '.zip';
        $zipFilePath = public_path('uploads/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($jobReceives as $jobReceive) {
                $company_id = $jobReceive->company_id;
                $company_slug = Str::slug($jobReceive->company->company_name ?? 'company');
                $folder = "uploads/{$company_id}-{$company_slug}/jobReceives_pdf/";
                $uploadedPath = public_path($folder);

                $safeReceiveNo = str_replace(['/', '\\'], '-', $jobReceive->job_work_receive_no);
                $filename = 'JobWorkReceive_' . $safeReceiveNo . '_' . $jobReceive->id . '.pdf';
                $filepath = $uploadedPath . $filename;

                if (!File::exists($filepath)) {
                    // Try to generate it on the fly
                    Helper::generateJobWorkReceivePdf($jobReceive->id);
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

        return Redirect::back()->withErrors(['Could not create zip file.'])->withInput();
    }
}
