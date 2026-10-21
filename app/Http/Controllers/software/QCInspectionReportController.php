<?php

namespace App\Http\Controllers\software;

use App\Exports\QcInspectionExport;
use App\Http\Controllers\Controller;
use App\Models\QcInspection;
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
use App\Http\Requests\QcInspectionRequest;
use App\Models\JobWorkReceiveItems;
use App\Models\MaterialDetail;
use App\Models\MaterialDetailMap;
use App\Models\MasterWarehouse;
use App\Models\Product;
use App\Models\ProductStockHistory;
use App\Models\Vendor;
use App\Models\Inward;
use App\Models\InwardItems;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;


class QCInspectionReportController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'QC Inspection Report',
            'folder_path' => 'software.modules.qc.qc-inspection-report',
            'route' => 'qc-inspection-report',
            'table_name' => (new QcInspection())->getTable(),
            'module_name' => 'QC Inspection Report',
            'permisstion_prefix' => 'qc-inspection-report',
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
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "qc_inspection_no", 'name' => 'qc_inspection_no', 'td_label' => 'QC Inspection No'],
                (object)['data' => "qc_inspection_date", 'name' => 'qc_inspection_date', 'td_label' => 'QC Inspection Date', 'className' =>  ''],
                (object)['data' => "product_name", 'name' => 'product_name', 'td_label' => 'Product Name', 'className' =>  ''],
                (object)['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Vendor Name', 'className' =>  ''],
                (object)['data' => "received_weight", 'name' => 'received_weight', 'td_label' => 'Received Weight', 'className' =>  ''],
                (object)['data' => "ok_weight", 'name' => 'ok_weight', 'td_label' => 'Ok Weight', 'className' =>  ''],
                (object)['data' => "ok_qty", 'name' => 'ok_qty', 'td_label' => 'Ok Qty', 'className' =>  ''],
                (object)['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-center'],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            View::share("columns", $columns);


            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = QcInspection::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['vendor', 'company', 'product']);
                $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_vendor') && $request->filter_vendor) {
                            $query->where('vendor_id', $request->filter_vendor);
                        }

                        if ($request->has('filter_qc_inspection_date') && $request->filter_qc_inspection_date) {

                            $filter_qc_inspection_date = $request->filter_qc_inspection_date;
                            $filter_qc_inspection_date = explode(" to ", $filter_qc_inspection_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_qc_inspection_date) == 2) {
                                $fromDate = Helper::convert_date($filter_qc_inspection_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_qc_inspection_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new QcInspection())->getTable() . '.qc_inspection_date', ">=", $fromDate);
                                $query->whereDate((new QcInspection())->getTable() . '.qc_inspection_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new QcInspection())->getTable() . '.qc_inspection_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;
                            $query->where(function ($q) use ($search) {
                                $q->where('qc_inspection_no', 'like', "%{$search}%")
                                    ->orWhereHas('vendor', function ($q2) use ($search) {
                                        $q2->where('vendor_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('qc_inspection_date', function ($row) {
                        return \Carbon\Carbon::parse($row->qc_inspection_date)->format('d-m-Y');
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row->admin_person->name;
                        } else {
                            return $row->team_person->name;
                        }
                    })
                    ->addColumn('vendor_name', function ($row) use ($modules) {
                        $vendorCode = optional($row->vendor)->vendor_code;
                        $vendorName = optional($row->vendor)->vendor_name;

                        $vendorInfo = '<span style="white-space:nowrap;">' . $vendorCode . '<br>' . $vendorName . '</span>';
                        return $vendorInfo;
                    })
                    ->addColumn('product_name', function ($row) {
                        $code = $row->product_code ? "({$row->product_code})" : '';
                        return ($code ?? '-') . ' ' . $row->product_name;
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
                                <a href="' . route($modules["route"] . ".QCInspectionShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1">
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

                    ->rawColumns(['action', 'vendor_name'])
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
            $inward_id = request()->get('inward_id');
            View::share('inward_id', $inward_id);
            if ($inward_id) {
                $inward = Inward::with(['vendor', 'company'])->find($inward_id);
                View::share('pre_fill_inward', $inward);
            }
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(QcInspectionRequest $request)
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

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;

            $received = (float)$request->received_weight;
            $ok = (float)$request->ok_weight;
            $rejection = (float)$request->rejection_qty;

            // check both together
            if (($ok + $rejection) > $received) {
                DB::rollBack();
                return Redirect::back()
                    ->withErrors(['ok_weight' => 'OK Weight + Rejection cannot be greater than Weight.'])
                    ->withInput();
            }

            $qcInspection = QcInspection::create($validated);
            $lastId = $qcInspection->id;

            if (!empty($validated['vendor_id'])) {
                $vendor = Vendor::find($validated['vendor_id']);
                if ($vendor) {
                    $vendor->update([
                        'address'        => $request->address     ?? $vendor->address,
                        'mobile_number'  => $request->phone       ?? $vendor->mobile_number,
                    ]);
                }
            }

            $warehouse_id = $request?->warehouse_id;
            if ($warehouse_id > 0) {
                $product = Product::find($validated['product_id']);

                $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $product->product_category_id, $validated['product_id']);

                $product_total_qty = Helper::getProductStockTotalQuantity($validated['company_id'], $warehouse_id, $product->product_category_id, $validated['product_id']);

                $total_issue_kg = $request->ok_weight;

                $total_final_kg = (float)$product_total_kg + (float)$total_issue_kg;
                $total_quantity = 0;
                if ($total_final_kg > 0 && $request?->wt_pc > 0) {
                    $total_quantity = Helper::kgToQtyConvertForStock((float)$total_issue_kg, $request?->wt_pc);
                }

                $credit_value = $request->ok_weight; // Default for Job Work
                $new_total_qty = (float)$product_total_qty + (int)$total_quantity;
                $new_total_kg = (float)$product_total_kg + (float)$total_issue_kg;

                if (!empty($request->inward_item_id)) {
                    $credit_value = $request->ok_qty;
                    // For Inward, total_quantity is based on ok_qty
                    $new_total_qty = (float)$product_total_qty + (float)$request->ok_qty;
                    // total_kg is already calculated from ok_weight
                }

                ProductStockHistory::create([
                    'company_id' => $validated['company_id'],
                    'warehouse_id' => $warehouse_id,
                    'product_category_id' => $product->product_category_id,
                    'product_subcategory_id' => '',
                    'product_variant_price_id' => $validated['product_id'],
                    'qc_inspection_id' => $lastId,
                    'total_quantity' => $new_total_qty,
                    'total_kg' => $new_total_kg,
                    'credit' => $credit_value,
                    'date' => $validated['qc_inspection_date'],
                    'remark' => 'QC Inspection. - ' . $validated['qc_inspection_no'],
                    'created_by' => $loginUserId
                ]);
            }


            if (!empty($request?->job_work_receive_item_id)) {
                $jobWorkReceiveItem = JobWorkReceiveItems::findOrFail($request?->job_work_receive_item_id);

                $totalOkWeight = QcInspection::where('job_work_receive_item_id', $jobWorkReceiveItem->id)->sum('ok_weight');
                $total_rejection_qty = QcInspection::where('job_work_receive_item_id', $jobWorkReceiveItem->id)->sum('rejection_qty');

                $qc_total_kg = $totalOkWeight + $total_rejection_qty;
                $jobWorkReceiveItem->update([
                    'qc_check_weight' => $qc_total_kg,
                ]);
            }

            if (!empty($request?->inward_item_id)) {
                $inwardItem = InwardItems::findOrFail($request?->inward_item_id);
                $inwardItem->update([
                    'is_qc_done' => 1,
                ]);

                // Update Inward status if all items are done
                $allDone = InwardItems::where('inward_id', $inwardItem->inward_id)->where('is_qc_done', 0)->count() == 0;
                // if ($allDone) {
                //     Inward::where('id', $inwardItem->inward_id)->update(['status' => 'completed']);
                // }
            }

            if (!empty($request?->material) && is_array($request?->material)) {
                MaterialDetailMap::where('company_id', $request?->company_id)
                    ->where('product_id', $request?->product_id)
                    ->forceDelete();
            }

            // Material Details
            if (!empty($request?->material) && is_array($request?->material)) {
                foreach ($request?->material as $contact) {
                    $materialData = [
                        'qc_inspection_id' => $lastId,
                        'company_id'       => $request?->company_id,
                        'dimension'        => $contact['dimension'] ?? null,
                        'size'             => $contact['size'] ?? null,
                        'ltl'              => $contact['ltl'] ?? null,
                        'utl'              => $contact['utl'] ?? null,
                        'instrument'       => $contact['instrument'] ?? null,
                        'remarks'          => $contact['remarks'] ?? null,
                        'status'           => 'active',
                        'created_by'       => $loginUserId,
                    ];

                    for ($s = 1; $s <= 10; $s++) {
                        $materialData["size$s"] = $contact["size$s"] ?? null;
                    }

                    $material = MaterialDetail::create($materialData);
                    MaterialDetailMap::create([
                        'company_id'  => $request?->company_id,
                        'material_id' => $material->id,
                        'product_id'  => $request?->product_id,
                    ]);
                }
            }
            DB::commit();
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
            $edit = QcInspection::with(['vendor', 'material_details', 'issue_for_process', 'job_work_receive_items', 'job_work_receive'])->findOrFail($id);
            View::share('edit', $edit);

            $material_detail = $edit->material_details ?? collect();
            View::share('material_detail', $material_detail);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(QcInspectionRequest $request, string $id)
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

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;


            $received = (float)$request->received_weight;
            $ok = (float)$request->ok_weight;
            $rejection = (float)$request->rejection_qty;

            // check both together
            if (($ok + $rejection) > $received) {
                DB::rollBack();
                return Redirect::back()
                    ->withErrors(['ok_weight' => 'OK Weight + Rejection cannot be greater than Weight.'])
                    ->withInput();
            }

            $warehouse_id = $request?->warehouse_id;
            if ($warehouse_id > 0) {

                $product_stock_history_data = ProductStockHistory::where('qc_inspection_id', $id)->orderBy('id', 'DESC')->first();
                $product_stock_history_list = $product_stock_history_data ? [$product_stock_history_data] : [];

                if (is_array($product_stock_history_list) && !empty($product_stock_history_list)) {
                    foreach ($product_stock_history_list as $k => $value) {

                        $product = Product::findOrFail($value['product_variant_price_id']);
                        $product_total_kg = Helper::getProductStockTotalKG($value['company_id'], $warehouse_id, $product->product_category_id, $value['product_variant_price_id']);

                        $product_total_qty = Helper::getProductStockTotalQuantity($value['company_id'], $warehouse_id, $product->product_category_id, $value['product_variant_price_id']);

                        $total_remaining_kg = (float)$product_total_kg - (float)$value['credit'];
                        $total_quantity = 0;
                        if ($total_remaining_kg > 0 && $request->wt_pc > 0) {
                            $total_quantity = Helper::kgToQtyConvertForStock((float)$value['credit'], $request->wt_pc);
                        }

                        ProductStockHistory::where('qc_inspection_id', $id)->where("id", $value['id'])->delete();
                        ProductStockHistory::create([
                            'company_id' => $value['company_id'],
                            'warehouse_id' => $warehouse_id,
                            'product_category_id' => $product->product_category_id,
                            'product_subcategory_id' => $value['product_subcategory_id'],
                            'product_variant_price_id' => $value['product_variant_price_id'],
                            'qc_inspection_id' => $id,
                            'total_quantity' => (float)$product_total_qty - (int)$total_quantity,
                            'total_kg' => (float)$product_total_kg - (float)$value['credit'],
                            'debit' => (float)$value['credit'],
                            'date' => $validated['qc_inspection_date'],
                            'remark' => 'update QC Inspection. - ' . $validated['qc_inspection_no'],
                            'created_by' => $loginUserId
                        ]);
                    }
                }

                $product_total_kg = Helper::getProductStockTotalKG($validated['company_id'], $warehouse_id, $product->product_category_id, $validated['product_id']);

                $product_total_qty = Helper::getProductStockTotalQuantity($validated['company_id'], $warehouse_id, $product->product_category_id, $validated['product_id']);

                $product = Product::find($validated['product_id']);
                $total_issue_kg = $request->ok_weight;

                $total_final_kg = (float)$product_total_kg + (float)$total_issue_kg;
                $total_quantity = 0;
                if ($total_final_kg > 0 && $request->wt_pc > 0) {
                    $total_quantity = Helper::kgToQtyConvertForStock((float)$total_issue_kg, $request->wt_pc);
                }

                ProductStockHistory::create([
                    'company_id' => $validated['company_id'],
                    'warehouse_id' => $warehouse_id,
                    'product_category_id' => $product->product_category_id,
                    'product_subcategory_id' => '',
                    'product_variant_price_id' => $validated['product_id'],
                    'qc_inspection_id' => $id,
                    'total_quantity' => (float)$product_total_qty + (int)$total_quantity,
                    'total_kg' => (float)$product_total_kg + (float)$total_issue_kg,
                    'credit' => $total_issue_kg,
                    'date' => $validated['qc_inspection_date'],
                    'remark' => 'QC Inspection. - ' . $validated['qc_inspection_no'],
                    'created_by' => $loginUserId
                ]);
            }



            $updateData = QcInspection::with(['vendor', 'material_details'])->findOrFail($id);

            if ($updateData) {
                unset($validated['id']);
                $updateData->update($validated);

                if (!empty($validated['vendor_id'])) {
                    $vendor = Vendor::find($validated['vendor_id']);
                    if ($vendor) {
                        $vendor->update([
                            'address'        => $request->address     ?? $vendor->address,
                            'mobile_number'  => $request->phone       ?? $vendor->mobile_number,
                        ]);
                    }
                }

                $jobWorkReceiveItem = JobWorkReceiveItems::findOrFail($updateData?->job_work_receive_item_id);
                $totalOkWeight = QcInspection::where('job_work_receive_item_id', $jobWorkReceiveItem->id)->sum('ok_weight');
                $total_rejection_qty = QcInspection::where('job_work_receive_item_id', $jobWorkReceiveItem->id)->sum('rejection_qty');

                // $total_rejection_kg = 0;
                // if (!empty($total_rejection_qty) && !empty($request->wt_pc)) {
                //     $total_rejection_kg = ($total_rejection_qty * $request->wt_pc) / 1000;
                // }
                $qc_total_kg = $totalOkWeight + $total_rejection_qty;
                $jobWorkReceiveItem->update([
                    'qc_check_weight' => $qc_total_kg,
                ]);

                // Material Details
                $submittedMaterial = $request?->material ?? [];
                $existingMaterialIds = $updateData->material_details->pluck('id')->toArray();
                $submittedMaterialIds = [];

                MaterialDetailMap::where('company_id', $request?->company_id)
                    ->where('product_id', $request?->product_id)
                    ->forceDelete();

                foreach ($submittedMaterial as $materialData) {
                    $sizeData = [];
                    for ($s = 1; $s <= 10; $s++) {
                        $sizeData["size$s"] = $materialData["size$s"] ?? null;
                    }

                    if (!empty($materialData['id']) && in_array($materialData['id'], $existingMaterialIds)) {

                        $material = MaterialDetail::find($materialData['id']);
                        $material->update(array_merge([
                            'dimension'  => $materialData['dimension'] ?? null,
                            'size'       => $materialData['size'] ?? null,
                            'ltl'        => $materialData['ltl'] ?? null,
                            'utl'        => $materialData['utl'] ?? null,
                            'instrument' => $materialData['instrument'] ?? null,
                            'remarks'    => $materialData['remarks'] ?? null,
                        ], $sizeData)); // merge size1 to size10

                        $submittedMaterialIds[] = $materialData['id'];

                        MaterialDetailMap::create([
                            'company_id'  => $request?->company_id,
                            'material_id' => $materialData['id'],
                            'product_id'  => $request?->product_id,
                        ]);
                    } else {

                        $newMaterial = MaterialDetail::create(array_merge([
                            'qc_inspection_id' => $updateData->id,
                            'company_id'       => $request?->company_id,
                            'dimension'        => $materialData['dimension'] ?? null,
                            'size'             => $materialData['size'] ?? null,
                            'ltl'              => $materialData['ltl'] ?? null,
                            'utl'              => $materialData['utl'] ?? null,
                            'instrument'       => $materialData['instrument'] ?? null,
                            'remarks'          => $materialData['remarks'] ?? null,
                            'status'           => 'active',
                            'created_by'       => $loginUserId,
                        ], $sizeData)); // merge size1 to size10

                        $submittedMaterialIds[] = $newMaterial->id;

                        MaterialDetailMap::create([
                            'company_id'  => $request?->company_id,
                            'material_id' => $newMaterial->id,
                            'product_id'  => $request?->product_id,
                        ]);
                    }
                }


                // Delete material removed from the form
                $materialToDelete = array_diff($existingMaterialIds, $submittedMaterialIds);
                if (!empty($materialToDelete)) {
                    MaterialDetail::whereIn('id', $materialToDelete)->delete();
                }
                DB::commit();
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            DB::rollBack();
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

        $dataDelete = QcInspection::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {

                    $product_stock_history_data = ProductStockHistory::where('qc_inspection_id', $id)->get()->toArray();
                    if (is_array($product_stock_history_data) && !empty($product_stock_history_data)) {
                        foreach ($product_stock_history_data as $k => $value) {

                            $product_total_kg = Helper::getProductStockTotalKG($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);
                            $ProductData = Product::findOrFail($value['product_variant_price_id']);

                            $product_total_qty = Helper::getProductStockTotalQuantity($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);

                            $total_remaining_kg = (float)$product_total_kg - (float)$value['credit'];
                            $total_quantity = 0;
                            if ($total_remaining_kg > 0 && $dataDelete->wt_pc > 0) {
                                $total_quantity = Helper::kgToQtyConvertForStock((float)$value['credit'], $dataDelete->wt_pc);
                            }

                            ProductStockHistory::where('qc_inspection_id', $id)->where("id", $value['id'])->delete();
                            ProductStockHistory::create([
                                'company_id' => $value['company_id'],
                                'warehouse_id' => $value['warehouse_id'],
                                'product_category_id' => $value['product_category_id'],
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $value['product_variant_price_id'],
                                'qc_inspection_id' => $id,
                                'total_quantity' => (float)$product_total_qty - (int)$total_quantity,
                                'total_kg' => (float)$product_total_kg - (float)$value['credit'],
                                'debit' => (float)$value['credit'],
                                'date' => $value['date'],
                                'remark' => 'delete QC Inspection. - ' . $dataDelete->qc_inspection_no,
                                'created_by' => $loginUserId
                            ]);
                        }
                    }


                    $jobWorkReceiveItem = JobWorkReceiveItems::findOrFail($dataDelete->job_work_receive_item_id);
                    $totalOkWeight = QcInspection::where('job_work_receive_item_id', $jobWorkReceiveItem->id)->sum('ok_weight');
                    $jobWorkReceiveItem->update([
                        'qc_check_weight' => $totalOkWeight,
                    ]);

                    MaterialDetail::where('qc_inspection_id', $id)->delete();
                    return $this->sendResponse([], $modules['title'] . ' delete successfully');
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
            $QcInspection = QcInspection::withTrashed()->findOrFail($id);
            $QcInspection->restore();

            $jobWorkReceiveItem = JobWorkReceiveItems::findOrFail($QcInspection->job_work_receive_item_id);
            $totalOkWeight = QcInspection::where('job_work_receive_item_id', $jobWorkReceiveItem->id)->sum('ok_weight');
            $jobWorkReceiveItem->update([
                'qc_check_weight' => $totalOkWeight,
            ]);

            MaterialDetail::withTrashed()->where('qc_inspection_id', $id)->restore();

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
            $query = QcInspection::with(['vendor', 'company', 'product'])
                ->orderBy('id', 'DESC');

            // Company filter
            if ($request->has('filter_company') && $request->filter_company) {
                $query->where('company_id', $request->filter_company);
            }

            if ($request->has('filter_vendor') && $request->filter_vendor) {
                $query->where('vendor_id', $request->filter_vendor);
            }

            // Job Work Order Date filter
            if ($request->has('filter_qc_inspection_date') && $request->filter_qc_inspection_date) {
                $filter_qc_inspection_date = explode(" to ", $request->filter_qc_inspection_date);

                $fromDate = null;
                $toDate = null;

                if (count($filter_qc_inspection_date) == 2) {
                    $fromDate = Helper::convert_date($filter_qc_inspection_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($filter_qc_inspection_date[1], "d/m/Y", "Y-m-d");
                }

                if ($fromDate && $toDate) {
                    $query->whereBetween((new QcInspection())->getTable() . '.qc_inspection_date', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $query->whereDate((new QcInspection())->getTable() . '.qc_inspection_date', $fromDate);
                }
            }

            // Search filter (job_work_order_no or vendor_name)
            if ($request->has('search') && $request->search) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('qc_inspection_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%");
                        });
                });
            }

            $QcInspections = $query->get();

            return view($modules['folder_path'] . '.print', compact('QcInspections', 'company_id', 'modules'));
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

        return Excel::download(new QcInspectionExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Qc Inspection Report-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function QCInspectionShow($id)
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
            $QcInspection = QcInspection::with([
                'vendor',
                'company',
                'product',
                'material_details'
            ])->findOrFail($id);


            $sidebarMainLogo = public_path('software/img/logo.png');
            $sidebarSmallLogo = public_path('software/img/ring.png');
            $sidebarHeaderLogo = '';

            // // If company-specific logos exist, use them
            // if ($QcInspection->company_id) {
            //     $company = Helper::getCompanyDetailById($QcInspection->company_id);

            //     if ($company && $company?->id) {
            //         $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            //         $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            //         $sidebarHeaderLogo = $company?->order_header_logo_url ?? '';
            //     }
            // }

            // $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
            // $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);
            // dd($sidebarMainLogo,$sidebarHeaderLogo);

            // Determine view format
            $viewFormat = 'general_view';

            return view(
                $modules['folder_path'] . '.' . $viewFormat,
                compact('QcInspection', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo')
            );
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function QcInspectionPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            View::share('modules', $modules);
            $QcInspection = QcInspection::with([
                'vendor',
                'company',
                'product',
                'material_details'
            ])->findOrFail($id);

            return view($modules['folder_path'] . '.single_print', compact('QcInspection', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function QcInspectionPdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        $QcInspection = QcInspection::with([
            'vendor',
            'company',
            'product',
            'material_details'
        ])->findOrFail($id);

        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // If company-specific logos exist, use them
        if ($QcInspection->company_id) {
            $company = Helper::getCompanyDetailById($QcInspection->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

        $company_id = $QcInspection->company_id;

        $viewPath = $modules['folder_path'] . '.single_pdf' ?? 'default_view';


        $html = View::make($viewPath, compact('QcInspection', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

        // Define folder path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($QcInspection->company->company_name);
        $folder = "uploads/{$company_id}-{$company_slug}/QcInspection/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'QcInspection_' . $QcInspection->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        $data = [
            'customer_name' => $QcInspection->customer?->customer_name ?? '-',
            'company_name' => $QcInspection->company->company_name,
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

        Helper::mail_new(
            $QcInspection->company_id,
            'QC Inspection Report',
            'qcInspection_send_pdf',
            optional($QcInspection->customer)->email,
            optional($QcInspection->customer)->id,
            'QcInspection/QcInspectionPdf',
            $data,
            $attachments
        );

        return Redirect::route($modules['route'] . '.QCInspectionShow', [$QcInspection->id])->with('success', 'QcInspection PDF sent via email.');
    }
}
