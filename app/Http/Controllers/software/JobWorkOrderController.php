<?php

namespace App\Http\Controllers\software;

use App\Exports\JobWorkOrderExport;
use App\Http\Controllers\Controller;
use App\Models\JobWorkOrder;
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
use App\Http\Requests\JobWorkOrderRequest;
use App\Models\JobWorkOrderItems;
use App\Models\Vendor;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;


class JobWorkOrderController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Job Work Order',
            'folder_path' => 'software.modules.job_work_management.job_work_order',
            'route' => 'job-work-order',
            'table_name' => (new JobWorkOrder())->getTable(),
            'module_name' => 'Job Work Order',
            'permisstion_prefix' => 'job-work-order',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];

        if (count(config('constants.order_type'))) {
            $this->modules['order_type'] = config('constants.order_type');
        }
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
                (object) ['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "job_work_order_no", 'name' => 'job_work_order_no', 'td_label' => 'Job Work NO'],
                (object) ['data' => "job_work_order_date", 'name' => 'job_work_order_date', 'td_label' => 'Job Work Date', 'className' => ''],
                (object) ['data' => "work_type", 'name' => 'work_type', 'td_label' => 'Work Type', 'className' => ''],
                (object) ['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Vendor Name', 'className' => ''],
                (object) ['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' => ''],
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
                $data = JobWorkOrder::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['vendor', 'company']);
                $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_job_work_order_date') && $request->filter_job_work_order_date) {

                            $filter_job_work_order_date = $request->filter_job_work_order_date;
                            $filter_job_work_order_date = explode(" to ", $filter_job_work_order_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_job_work_order_date) == 2) {
                                $fromDate = Helper::convert_date($filter_job_work_order_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_job_work_order_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new JobWorkOrder())->getTable() . '.job_work_order_date', ">=", $fromDate);
                                $query->whereDate((new JobWorkOrder())->getTable() . '.job_work_order_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new JobWorkOrder())->getTable() . '.job_work_order_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('job_work_order_no', 'like', "%{$search}%")
                                    ->orWhereHas('vendor', function ($q2) use ($search) {
                                        $q2->where('vendor_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('job_work_order_date', function ($row) {
                        return \Carbon\Carbon::parse($row?->job_work_order_date)->format('d-m-Y');
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row?->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->addColumn('vendor_name', function ($row) use ($modules) {
                        $vendorCode = optional($row->vendor)->vendor_code;
                        $vendorName = optional($row->vendor)->vendor_name;

                        $vendorInfo = '<span style="white-space:nowrap;">' . $vendorCode . '<br>' . $vendorName . '</span>';
                        return $vendorInfo;
                    })
                    ->addColumn('work_type', function ($row) {
                        $map = [
                            'job_work' => 'Job Work',
                            'settle_po' => 'Settle Po',
                        ];
                        return $map[$row?->work_type] ?? '-';
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
                                <a href="' . route($modules["route"] . ".jobOrderShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1">
                                    <i class="fa-solid fa fa-eye mt-1 me-1"></i> View
                                </a>
                            </li>';
                            $vendorphone = preg_replace('/[^0-9]/', '', $row?->vendor?->mobile_number ?? '');
                            if ($vendorphone) {
                                $whatsappUrl = route('whatsapp.send', ['id' => $row->id, 'type' => 'job_work_order']);

                                $action .= '<li>
                                    <a href="' . $whatsappUrl . '" class="dropdown-item mx-1">
                                        <i class="fa-brands fa-whatsapp text-success"></i> Send WhatsApp
                                    </a>
                                </li>';
                            }
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
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JobWorkOrderRequest $request)
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

        try {

            // Server-side validation for Job Work Order Items
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    // Product Name
                    if (is_null($product) || trim($product) === '') {
                        return back()->withErrors([
                            "product_name.$key" => "Product name is required."
                        ])->withInput();
                    }

                    // Product Code
                    $product_code = $request->product_code[$key] ?? null;
                    if (is_null($product_code) || trim($product_code) === '') {
                        return back()->withErrors([
                            "product_code.$key" => "Product code is required."
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

            $jobWorkOrder = JobWorkOrder::create($validated);
            $lastInsertedId = $jobWorkOrder->id;

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
                    $JobWorkOrderItems = JobWorkOrderItems::create([
                        'company_id' => $validated['company_id'],
                        'job_work_order_id' => $lastInsertedId,
                        'product_id' => $request->product_id[$key],
                        'display_order' => ($key + 1),
                        'product_name' => $product,
                        'product_code' => $request->product_code[$key],
                        'product_stock' => $request->product_stock[$key],
                        'quantity' => $request->quantity[$key],
                        'unit' => $request->unit[$key] ?? null,
                        'price_in_amount' => $request->price[$key] ?? null,
                        'discount_flat' => $request->discount_flat[$key] ?? null,
                        'discount_percentage' => $request->discount_percentage[$key] ?? null,
                        'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                        'amount' => $request->amount[$key] ?? null,
                        'taxable_amount' => $request->taxable_amount[$key] ?? null,
                        'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                        'gst_amount' => $request->gst_amount_array[$key] ?? null,
                        'cgst_in_percentage' => $request->cgst_in_percentage[$key] ?? null,
                        'cgst_amount' => $request->cgst_amount_array[$key] ?? null,
                        'sgst_in_percentage' => $request->sgst_in_percentage[$key] ?? null,
                        'sgst_amount' => $request->sgst_amount_array[$key] ?? null,
                        'sub_total' => $request->subtotal[$key] ?? null,
                    ]);
                }
            }

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
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
            $edit = JobWorkOrder::with('vendor')->findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(JobWorkOrderRequest $request, string $id)
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
                        return back()->withErrors([
                            "product_name.$key" => "Product name is required."
                        ])->withInput();
                    }

                    // Product Code
                    $product_code = $request->product_code[$key] ?? null;
                    if (is_null($product_code) || trim($product_code) === '') {
                        return back()->withErrors([
                            "product_code.$key" => "Product code is required."
                        ])->withInput();
                    }

                    // Quantity
                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        return back()->withErrors([
                            "quantity.$key" => "Quantity must be greater than 0."
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
            $updateData = JobWorkOrder::findOrFail($id);

            if (!empty($validated['vendor_id'])) {
                $vendor = Vendor::find($validated['vendor_id']);
                if ($vendor) {
                    $vendor->update([
                        'address' => $request->address ?? $vendor->address,
                        'mobile_number' => $request->phone ?? $vendor->mobile_number,
                    ]);
                }
            }


            if ($updateData) {
                unset($validated['id']);
                $updateData->update($validated);


                if ($request->has('product_name')) {
                    JobWorkOrderItems::where('job_work_order_id', $id)->delete();
                    foreach ($request->product_name as $key => $product) {
                        $JobWorkOrderItems = JobWorkOrderItems::create([
                            'company_id' => $validated['company_id'],
                            'job_work_order_id' => $id,
                            'product_id' => $request->product_id[$key],
                            'display_order' => ($key + 1),
                            'product_name' => $product,
                            'product_code' => $request->product_code[$key],
                            'product_stock' => $request->product_stock[$key],
                            'quantity' => $request->quantity[$key],
                            'unit' => $request->unit[$key] ?? null,
                            'price_in_amount' => $request->price[$key] ?? null,
                            'discount_flat' => $request->discount_flat[$key] ?? null,
                            'discount_percentage' => $request->discount_percentage[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'amount' => $request->amount[$key] ?? null,
                            'taxable_amount' => $request->taxable_amount[$key] ?? null,
                            'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                            'gst_amount' => $request->gst_amount_array[$key] ?? null,
                            'cgst_in_percentage' => $request->cgst_in_percentage[$key] ?? null,
                            'cgst_amount' => $request->cgst_amount_array[$key] ?? null,
                            'sgst_in_percentage' => $request->sgst_in_percentage[$key] ?? null,
                            'sgst_amount' => $request->sgst_amount_array[$key] ?? null,
                            'sub_total' => $request->subtotal[$key] ?? null,
                        ]);
                    }
                }


                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
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

        $dataDelete = JobWorkOrder::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
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
            $salesOrder = JobWorkOrder::withTrashed()->findOrFail($id);
            $salesOrder->restore();
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
            $query = JobWorkOrder::with(['company', 'vendor'])
                ->orderBy('id', 'DESC');

            // Company filter
            if ($request->has('filter_company') && $request->filter_company) {
                $query->where('company_id', $request->filter_company);
            }

            if ($request->has('filter_vendor') && $request->filter_vendor) {
                $query->where('vendor_id', $request->filter_vendor);
            }

            // Job Work Order Date filter
            if ($request->has('filter_job_work_order_date') && $request->filter_job_work_order_date) {
                $filter_job_work_order_date = explode(" to ", $request->filter_job_work_order_date);

                $fromDate = null;
                $toDate = null;

                if (count($filter_job_work_order_date) == 2) {
                    $fromDate = Helper::convert_date($filter_job_work_order_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($filter_job_work_order_date[1], "d/m/Y", "Y-m-d");
                }

                if ($fromDate && $toDate) {
                    $query->whereBetween((new JobWorkOrder())->getTable() . '.job_work_order_date', [$fromDate, $toDate]);
                } elseif ($fromDate) {
                    $query->whereDate((new JobWorkOrder())->getTable() . '.job_work_order_date', $fromDate);
                }
            }

            // Search filter (job_work_order_no or vendor_name)
            if ($request->has('search') && $request->search) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('job_work_order_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%");
                        });
                });
            }

            $jobWorkOrders = $query->get();

            return view($modules['folder_path'] . '.print', compact('jobWorkOrders', 'company_id', 'modules'));
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

        return Excel::download(new JobWorkOrderExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Job Work Order-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function jobOrderShow($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;

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

            $jobOrder = JobWorkOrder::with([
                'company',
                'vendor',
                'job_work_order_items',
                'term_condition',
                'team_person',
                'admin_person',
                'vendor'
            ])->findOrFail($id);


            return view(
                $modules['folder_path'] . '.' . ($jobOrder?->company?->joborder_view_format ?? 'general_view'),
                compact('jobOrder', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function jobOrderPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        try {

            View::share('modules', $modules);
            $jobOrder = JobWorkOrder::with(['company', 'vendor', 'job_work_order_items', 'term_condition', 'team_person', 'admin_person', 'vendor'])->findOrFail($id);
            return view(
                $modules['folder_path'] . '.' . ($jobOrder?->company?->joborder_print_format ?? 'single_print'),
                compact('jobOrder', 'company_id')
            );
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function SendjobOrderPdf($id, Request $request = null)
    {
        ini_set('max_execution_time', 600);

        $request = $request ?? request();
        $isWithoutPrice = $request->get('without_price') == '1';

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        // Fetch Job Work Order with all relations
        $jobOrder = JobWorkOrder::with([
            'company',
            'vendor',
            'job_work_order_items',
            'term_condition',
            'team_person',
            'admin_person',
            'vendor'
        ])->findOrFail($id);

        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // If company-specific logos exist, use them
        if ($jobOrder->company_id) {
            $company = Helper::getCompanyDetailById($jobOrder->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

        $viewPath = $modules['folder_path'] . '.' . ($jobOrder?->company?->joborder_print_format ?? 'single_pdf');

        // Render HTML
        $html = View::make($viewPath, compact('jobOrder', 'company_id', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo', 'isWithoutPrice'))->render();

        // File path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($jobOrder->company->company_name ?? 'company');
        $folder = "uploads/{$company_id}-{$company_slug}/joborders/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'JobWorkOrder_' . $jobOrder->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait');
        $pdf->save($filepath);

        // Optional: send email - commented out as requested so PDF can be viewed directly
        /*
        $data = [
            'vendor_name' => $jobOrder->vendor?->vendor_name ?? '-',
            'company_name' => $jobOrder->company?->company_name ?? '-',
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
            $jobOrder->company_id,
            'Job Work Order',
            'joborder_send_pdf',
            optional($jobOrder->vendor)->email,
            // "riddhibutaniocean@gmail.com",
            optional($jobOrder->vendor)->id,
            'JobWorkOrder/jobOrderPdf',
            $data,
            $attachments
        );
        */

        return $pdf->stream($filename);
    }

    public function jobOrderPdf($id, Request $request = null)
    {
        return $this->SendjobOrderPdf($id, $request ?? request());
    }
}
