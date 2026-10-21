<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Exports\GRNExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\GRN;
use App\Models\GRNItem;
use App\Models\Customer;
use App\Models\SalesStatus;
use App\Models\Company;
use App\Http\Requests\GRNRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;


class GRNController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'GRN',
            'folder_path' => 'software.modules.sales_management.grn',
            'route' => 'grn',
            'table_name' => (new GRN())->getTable(),
            'permisstion_prefix' => 'grn',
            'module_name' => 'GRN',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];

        if (count(config('constants.order_type')) ) {
           $this->modules['order_type'] = config('constants.order_type');
        }
    }

    public function index(Request $request)
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

            $columns = [
                (object)['data' => "grn_no", 'name' => 'grn_no', 'td_label' => 'GRN No'],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "grn_date", 'name' => 'grn_date', 'td_label' => 'GRN Date', 'className' =>  ''],
                (object)['data' => "dos_date", 'name' => 'dos_date', 'td_label' => 'DOS Date', 'className' =>  ''],
                (object)['data' => "grn_status", 'name' => 'grn_status', 'td_label' => 'GRN Status', 'className' =>  ''],
                (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  ''],
                (object)['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "total_qty", 'name' => 'total_qty', 'td_label' => 'Total QTY', 'className' =>  ''],
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
                $data = GRN::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['company', 'customer', 'team_person', 'admin_person','grn_item']);
                $data = $data->orderBy('id', 'desc');


                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_parent_status_id') && $request->filter_parent_status_id) {
                            $query->where('sales_status_id', $request->filter_parent_status_id);
                        }

                        if ($request->has('filter_team_person') && $request->filter_team_person) {
                            $query->where('created_by', $request->filter_team_person)->where('created_type', 'team');
                        }

                        if ($request->has('filter_grn_date') && $request->filter_grn_date) {

                            $filter_grn_date = $request->filter_grn_date;
                            $filter_grn_date = explode(" to ", $filter_grn_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_grn_date) == 2) {
                                $fromDate = Helper::convert_date($filter_grn_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_grn_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new GRN())->getTable() . '.grn_date', ">=", $fromDate);
                                $query->whereDate((new GRN())->getTable() . '.grn_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new GRN())->getTable() . '.grn_date', $fromDate);
                            }
                        }
                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('grn_no', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($q2) use ($search) {
                                        $q2->where('customer_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('grn_date', function ($row) {
                        return \Carbon\Carbon::parse($row->grn_date)->format('d-m-Y');
                    })
                    ->editColumn('dos_date', function ($row) {
                        return \Carbon\Carbon::parse($row->dos_date)->format('d-m-Y');
                    })
                    ->addColumn('grn_no', function ($row) {
                        return $row?->grn_no;
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row?->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode   = optional($row->customer)->client_code;
                        $customerName = $row->customer_name ?? optional($row->customer)->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return '<span style="white-space:nowrap;">'.$clientCode.'<br>'.$customerName.'</span>';
                        }

                        return $clientCode;
                    })
                    ->addColumn('company_name', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('grn_status', function ($row) {
                        if (empty($row->sales_status)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row->sales_status->status_color . '">' . $row->sales_status->statusname . '</span>';
                    })
                    ->addColumn('total_qty', function ($row) {
                        if ($row?->grn_item && $row?->grn_item->count() > 0) {
                            $totalQtyPc = $row?->grn_item->sum('quantity');
                            return number_format($totalQtyPc, 2);
                        }
                        return '0.00';
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';
                        $action .= '
                            <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            if ($modules['update_permission'] == true) {

                                 $action .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                        </li>';
                            }

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </li>';
                            }


                            if (!empty($row?->sales_status_id)) {
                                $currentStatus = SalesStatus::find($row?->sales_status_id);

                                $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                        ->where('salestype', 'GRN')
                                        ->where('parent_status_id', $currentStatus->id)
                                        ->where('id', '>', $currentStatus->id)
                                        ->orderBy('display_order', 'asc')
                                        ->first();

                                if (!empty($nextStatus)) {
                                    $action .= '<li class="dropdown-divider"></li>';
                                    $url = route($modules["route"] . ".change-grn-status");
                                    if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                        $action .= '<li>
                                                    <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                </li>';
                                    }

                                }
                            } else {
                                $nextStatus = SalesStatus::where('company_id', $row->company->id)->where('parent_status_id', null)->where('salestype', 'GRN')->orderBy('display_order', 'asc')->first();
                                if (!empty($nextStatus)) {
                                    if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                        $action .= '<li class="dropdown-divider"></li>
                                                <li>
                                                    <a data-url="' . route($modules["route"] . ".change-grn-status") . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                </li>';
                                    }
                                }
                            }
                        } else {
                            $action .= '<li>
                                            <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                                Restore
                                            </a>
                                        </li>';
                        }
                        $action .= '</ul>
                                </div>
                            ';

                        return $action;
                    })
                    ->rawColumns(['status', 'action', 'grn_status', 'grn_no', 'customer_name'])
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
             $generateGRNNo = true;
            View::share('generateGRNNo', $generateGRNNo);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(GRNRequest $request)
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

        View::share('modules', $modules);
        $validated = $request->validated();

        try {

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    if (is_null($product) || trim($product) === '') {
                        return back()->withErrors(["product_name.$key" => "Product name is required."]);
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (is_null($quantity) || trim((string) $quantity) === '') {
                        return back()->withErrors(["quantity.$key" => "Quantity is required."]);
                    }
                    if (!is_numeric($quantity)) {
                        return back()->withErrors(["quantity.$key" => "Quantity must be a valid number."]);
                    }
                }
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;


            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'GRN')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }

            $grn = GRN::create($validated);
            $lastInsertedId = $grn->id;

            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->update([
                        'address'        => $request->address     ?? $customer->address,
                        'mobile_number'  => $request->phone       ?? $customer->mobile_number,
                    ]);
                }
            }

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    $data = [
                        'grn_id'   => $lastInsertedId,
                        'company_id'   => $validated['company_id'],
                        'product_id' => $request->product_id[$key],
                        'display_order' => ($key + 1),
                        'product_name' => $product,
                        'product_code' => $request->product_code[$key] ?? null,
                        'product_description' => $request->product_description[$key] ?? null,
                        'last_process_id' => $request->last_process_id[$key] ?? null,
                        'product_stock' => $request->product_stock[$key] ?? null,
                        'quantity' => $request->quantity[$key] ?? null,
                        'unit' => $request->unit[$key] ?? null,
                        'wt_pc' => $request->wt_pc[$key] ?? null,
                        'alt_qty' => $request->alt_qty[$key],
                    ];
                    $grnItems = GRNItem::create($data);
                }
            }

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            // dd($e->getMessage());
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

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

        $dataDelete = GRN::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
                    if ($isAjax) {
                        return $this->sendResponse([], $modules['title'] . ' delete successfully');
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

    public function status_update(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;
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

        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'update_status' => ['required', 'in:active,inactive']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $grn = GRN::withTrashed()->findOrFail($request?->id);
            if ($grn) {
                $grn->status = $request->update_status;
                $grn->save();
                if ($isAjax) {
                    return $this->sendResponse($grn, $modules['title'] . ' status update successfully.');
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' status update successfully.');
            }
            if ($isAjax) {
                return $this->sendError('something went wrong please try again later');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
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
            $grn = GRN::with(['customer'])->withTrashed()->findOrFail($id);
            $grn->restore();

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

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
            $edit = GRN::with('customer','grn_item')->findOrFail($id);
            View::share('edit', $edit);
            $generateGRNNo = false;
            View::share('generateGRNNo', $generateGRNNo);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(GRNRequest $request, string $id)
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
        View::share('modules', $modules);
        $request['id'] = $id;

        $validated = $request->validated();

        try {
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    if (is_null($product) || trim($product) === '') {
                        return back()->withErrors(["product_name.$key" => "Product name is required."]);
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (is_null($quantity) || trim((string) $quantity) === '') {
                        return back()->withErrors(["quantity.$key" => "Quantity is required."]);
                    }
                    if (!is_numeric($quantity)) {
                        return back()->withErrors(["quantity.$key" => "Quantity must be a valid number."]);
                    }
                }
            }

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = GRN::findOrFail($id);

            if ($updateData) {
                unset($validated['id']);

                $salesStatus = SalesStatus::where('salestype', 'GRN')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
                $validated['sales_status_id'] = $salesStatus->id ?? null;
                $updateData->update($validated);

                if (!empty($validated['customer_id'])) {
                    $customer = Customer::find($validated['customer_id']);
                    if ($customer) {
                        $customer->update([
                            'address'        => $request->address     ?? $customer->address,
                            'mobile_number'  => $request->phone       ?? $customer->mobile_number,
                        ]);
                    }
                }

                if ($request->has('product_name')) {
                    GRNItem::where('grn_id', $id)->forceDelete();
                    foreach ($request->product_name as $key => $product) {
                        $data = [
                            'grn_id'   => $id,
                            'company_id'   => $validated['company_id'],
                            'product_id' => $request->product_id[$key],
                            'display_order' => ($key + 1),
                            'product_name' => $product,
                            'product_code' => $request->product_code[$key] ?? null,
                            'product_description' => $request->product_description[$key] ?? null,
                            'last_process_id' => $request->last_process_id[$key] ?? null,
                            'product_stock' => $request->product_stock[$key] ?? null,
                            'quantity' => $request->quantity[$key] ?? null,
                            'unit' => $request->unit[$key] ?? null,
                            'wt_pc' => $request->wt_pc[$key] ?? null,
                            'alt_qty' => $request->alt_qty[$key],
                        ];
                        GRNItem::create($data);
                    }
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function change_grn_status(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'next_status_id' => ['required', 'exists:sales_statuses,id']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $grn = GRN::with(['customer'])->withTrashed()->findOrFail($request?->id);
            if ($grn) {
                $old_salesorder_status_id = $grn->sales_status_id;
                $grn->sales_status_id = $request->next_status_id;
                $grn->save();

                if ($isAjax) {
                    return $this->sendResponse($grn, $modules['title'] . ' status update successfully.');
                }

                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' status update successfully.');
            }
            if ($isAjax) {
                return $this->sendError('something went wrong please try again later');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
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

        return Excel::download(new GRNExport($request->all(), $this->authenticateLoginUserDetails,$modules), 'GRN-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $data = GRN::select('*')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
            $data = $data->with(['company', 'customer', 'team_person', 'admin_person','grn_item']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $data->where('sales_status_id', $request->parent_status_id);
            }

            if ($request->has('team_person') && $request->team_person) {
                $data->where('created_by', $request->team_person)->where('created_type', 'team');
            }

            if ($request->has('grn_date') && $request->grn_date) {

                $grn_date = $request->grn_date;
                $grn_date = explode(" to ", $grn_date);
                $fromDate = null;
                $toDate = null;

                if (count($grn_date) == 2) {
                    $fromDate = Helper::convert_date($grn_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($grn_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new GRN())->getTable() . '.grn_date', ">=", $fromDate);
                    $data->whereDate((new GRN())->getTable() . '.grn_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new GRN())->getTable() . '.grn_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('grn_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%{$search}%");
                        });
                });
            }
            $grn = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('grn', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

}
