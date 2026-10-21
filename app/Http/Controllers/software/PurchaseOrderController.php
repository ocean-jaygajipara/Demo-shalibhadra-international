<?php

namespace App\Http\Controllers\software;

use App\Exports\PurchaseOrderExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesStatus;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\PurchaseOrderItems;
use App\Http\Requests\PurchaseOrderRequest;
use Illuminate\Support\Facades\Gate;
use App\Helpers\Helper;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendPurchaseOrderJob;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Purchase Order',
            'folder_path' => 'software.modules.purchase_management.purchase_order',
            'route' => 'purchase-order',
            'table_name' => (new PurchaseOrder())->getTable(),
            'module_name' => 'Purchase Order',
            'permisstion_prefix' => 'purchase-order',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];

        if (count(config('constants.purchase_order_slug'))) {
            $this->modules['purchase_order_status_slug'] = config('constant.purchase_order_slug');
        }

        if (count(config('constants.order_type'))) {
            $this->modules['order_type'] = config('constants.order_type');
        }
    }

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
                (object) ['data' => "purchase_order_no", 'name' => 'purchase_order_no', 'td_label' => 'PO NO'],
                (object) ['data' => "purchase_order_date", 'name' => 'purchase_order_date', 'td_label' => 'PO Date', 'className' => ''],
                (object) ['data' => "purchase_order_status", 'name' => 'purchase_order_status', 'td_label' => 'PO Status', 'className' => ''],
                (object) ['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Vendor Name', 'className' => ''],
                (object) ['data' => "grand_total", 'name' => 'grand_total', 'td_label' => 'Total Amount', 'className' => ''],
                (object) ['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' => ''],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center'],
            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
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
                $data = PurchaseOrder::select('*')
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
                $editPermisstion = true;
                $deletePermisstion = true;

                $returnData = Datatables::of($data)
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

                        if ($request->has('filter_purchase_order_date') && $request->filter_purchase_order_date) {

                            $filter_purchase_order_date = $request->filter_purchase_order_date;
                            $filter_purchase_order_date = explode(" to ", $filter_purchase_order_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_purchase_order_date) == 2) {
                                $fromDate = Helper::convert_date($filter_purchase_order_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_purchase_order_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', ">=", $fromDate);
                                $query->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('purchase_order_no', 'like', "%{$search}%")
                                    ->orWhereHas('vendor', function ($q2) use ($search) {
                                        $q2->where('vendor_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('purchase_order_date', function ($row) {
                        return \Carbon\Carbon::parse($row->purchase_order_date)->format('d-m-Y');
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->addColumn('vendor_name', function ($row) use ($modules) {
                        $vendorCode = optional($row->vendor)->vendor_code;
                        $vendorName = $row->vendor_name ?? optional($row->vendor)->vendor_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            $vendorInfo = '<span style="white-space:nowrap;">' . $vendorCode . '<br>' . $vendorName . '</span>';
                        } else {
                            $vendorInfo = $vendorCode ?? '-';
                        }
                        return $vendorInfo;
                    })
                    ->addColumn('purchase_order_status', function ($row) {
                        if (empty($row?->sales_status_id)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row?->sales_status?->status_color . '">' . $row?->sales_status?->statusname . '</span>';
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
                                $salesStatusesLast = SalesStatus::where('company_id', $row->company_id)
                                    ->where('salestype', 'Purchase Order')
                                    ->select("id")
                                    ->orderBy('display_order', 'desc')
                                    ->first();

                                if (!empty($salesStatusesLast->id) && $salesStatusesLast->id > 0 && $row->sales_status_id != $salesStatusesLast->id) {
                                    $action .= '<li>
                                <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </a>
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
                            <a href="' . route($modules["route"] . ".purchaseOrderShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1">
                                <i class="fa-solid fa fa-eye mt-1 me-1"></i> View
                            </a>
                        </li>';

                            $vendorphone = preg_replace('/[^0-9]/', '', $row?->vendor?->mobile_number ?? '');
                            if ($vendorphone) {
                                $whatsappUrl = route('whatsapp.send', ['id' => $row->id, 'type' => 'purchase_order']);

                                $action .= '<li>
                                    <a href="' . $whatsappUrl . '" class="dropdown-item mx-1">
                                        <i class="fa-brands fa-whatsapp text-success"></i> Send WhatsApp
                                    </a>
                                </li>';
                            }



                            if (!empty($row->sales_status_id)) {
                                $currentStatus = SalesStatus::find($row->sales_status_id);

                                if ($currentStatus?->statusslug == 'pi') {
                                    $nextStatus = SalesStatus::where('statusslug', 'ci')
                                        ->where('salestype', 'Purchase Order')
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                } else {
                                    $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                        ->where('salestype', 'Purchase Order')
                                        ->where('parent_status_id', $currentStatus->id)
                                        ->where('id', '>', $currentStatus->id)
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                }

                                if (!empty($nextStatus)) {
                                    $action .= '<li class="dropdown-divider"></li>';

                                    if ($nextStatus->is_redirect == 1) {
                                        $url = route('inwards.insert', $row->id);
                                        if (!empty($modules['approval_permission'])) {
                                            $action .= '<li>
                            <a href="' . $url . '" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">
                                ' . $nextStatus->statusname . '
                            </a>
                        </li>';
                                        }
                                    } else {
                                        $url = route($modules["route"] . ".change-purchase-order-status");
                                        if (!empty($modules['approval_permission'])) {
                                            $action .= '<li>
                            <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">
                                Change to ' . $nextStatus->statusname . '
                            </a>
                        </li>';
                                        }
                                    }
                                }
                            } else {
                                $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                    ->where('parent_status_id', null)
                                    ->where('salestype', 'Purchase Order')
                                    ->orderBy('display_order', 'asc')
                                    ->first();

                                if (!empty($nextStatus) && !empty($modules['approval_permission'])) {
                                    $action .= '<li class="dropdown-divider"></li>
                    <li>
                        <a data-url="' . route($modules["route"] . ".change-purchase-order-status") . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">
                            Change to ' . $nextStatus->statusname . '
                        </a>
                    </li>';
                                }
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

                    ->rawColumns(['purchase_order_status', 'action', 'vendor_name'])
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
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(PurchaseOrderRequest $request)
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

        try {
            // dd('L-169', $request->all());
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    if (is_null($product) || trim($product) === '') {
                        return back()->withErrors(["product_name.$key" => "Product name is required."]);
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        return back()->withErrors(["quantity.$key" => "Quantity must be greater than 0."]);
                    }
                    /*
                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
                        return back()->withErrors(["price.$key" => "Price must be greater than 0."]);
                    }
                        */
                }
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;
            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Purchase Order')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }

            // if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int)($validated['total_taxable']) <= 0) {
            //     return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            // }

            $validated['master_taxable_amount'] = $request->master_taxable_amount ?? 0;
            $validated['master_gst_amount'] = $request->master_gst_amount ?? 0;

            $salesOrder = PurchaseOrder::create($validated);
            $lastInsertedId = $salesOrder->id;

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

                    $total_kg = $request->quantity[$key];
                    $remaining_quantity = $request->quantity[$key];
                    if ($request->quantity[$key] > 0) {
                        if ($request->unit[$key] == 'pcs') {
                            $remaining_quantity = $request->quantity[$key];
                            $total_kg = Helper::qtyToKgConvertForStock($request->quantity[$key], $request->product_weight[$key]);
                        } else if ($request->unit[$key] == 'gross') {
                            $total_qty = $request->grosse_per_qty[$key] * $request->quantity[$key];
                            $remaining_quantity = $total_qty;
                            $total_kg = Helper::qtyToKgConvertForStock($total_qty, $request->product_weight[$key]);
                        } else if ($request->unit[$key] == 'kg') {
                            $remaining_quantity = Helper::kgToQtyConvertForStock($request->quantity[$key], $request->product_weight[$key]);
                        }
                    }

                    $PurchaseOrderItems = PurchaseOrderItems::create([
                        'company_id'   => $validated['company_id'],
                        'purchase_order_id' => $lastInsertedId,
                        'product_id' => $request->product_id[$key],
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'display_order' => ($key + 1),
                        'product_code' => $request->product_code[$key] ?? null,
                        'product_name' => $product,
                        'product_description' => $request->product_description[$key],
                        'hsn_code' => $request->hsn_code[$key],
                        'outer_quantity' => $request->outer[$key] ?? null,
                        'inner_quantity' => $request->inner[$key] ?? null,
                        'loose_quantity' => $request->loose[$key] ?? null,
                        'unit' => $request->unit[$key],
                        'total_kg' => $total_kg,
                        'quantity' => $request->quantity[$key],
                        'remaining_quantity' => $remaining_quantity,
                        'price_in_amount' => $request->price[$key],
                        'discount_flat' => $request->discount_flat[$key] ?? null,
                        'discount_percentage' => $request->discount_percentage[$key] ?? null,
                        'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                        'amount' => $request->amount[$key] ?? null,
                        'taxable_amount' => $request->taxable_amount[$key] ?? null,
                        'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                        'gst_amount' => $request->gst_amount_array[$key] ?? null,
                        'sub_total' => $request->subtotal[$key],
                    ]);
                }
                SendPurchaseOrderJob::dispatch($lastInsertedId, 'create');
            }

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            dd($e->getMessage());
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

        $dataDelete = PurchaseOrder::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
                    if ($isAjax) {
                        SendPurchaseOrderJob::dispatch($id, 'delete');
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
            $salesOrder = PurchaseOrder::withTrashed()->findOrFail($request?->id);
            if ($salesOrder) {
                $salesOrder->status = $request->update_status;
                $salesOrder->save();
                if ($isAjax) {
                    return $this->sendResponse($salesOrder, $modules['title'] . ' status update successfully.');
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
            $salesOrder = PurchaseOrder::withTrashed()->findOrFail($id);
            $salesOrder->restore();
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
            $edit = PurchaseOrder::with('vendor')->findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(PurchaseOrderRequest $request, string $id)
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
                    if (is_null($product) || trim($product) === '') {
                        return back()->withErrors(["product_name.$key" => "Product name is required."]);
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        return back()->withErrors(["quantity.$key" => "Quantity must be greater than 0."]);
                    }

                    /*
                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
                        return back()->withErrors(["price.$key" => "Price must be greater than 0."]);
                    }
                        */
                }
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = PurchaseOrder::findOrFail($id);

            if (!empty($validated['vendor_id'])) {
                $vendor = Vendor::find($validated['vendor_id']);
                if ($vendor) {
                    $vendor->update([
                        'address' => $request->address ?? $vendor->address,
                        'mobile_number' => $request->phone ?? $vendor->mobile_number,
                    ]);
                }
            }

            // if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int)($validated['total_taxable']) <= 0) {
            //     return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            // }
            if ($updateData) {
                unset($validated['id']);

                $validated['master_taxable_amount'] = $request->master_taxable_amount ?? 0;
                $validated['master_gst_amount'] = $request->master_gst_amount ?? 0;
                $updateData->update($validated);

                if ($request->has('product_name')) {
                    PurchaseOrderItems::where('purchase_order_id', $id)->delete();

                    foreach ($request->product_name as $key => $product) {

                        $total_kg = $request->quantity[$key];
                        $remaining_quantity = $request->quantity[$key];
                        if ($request->quantity[$key] > 0) {
                            if ($request->unit[$key] == 'pcs') {
                                $remaining_quantity = $request->quantity[$key];
                                $total_kg = Helper::qtyToKgConvertForStock($request->quantity[$key], $request->product_weight[$key]);
                            } else if ($request->unit[$key] == 'gross') {
                                $total_qty = $request->grosse_per_qty[$key] * $request->quantity[$key];
                                $remaining_quantity = $total_qty;
                                $total_kg = Helper::qtyToKgConvertForStock($total_qty, $request->product_weight[$key]);
                            } else if ($request->unit[$key] == 'kg') {
                                $remaining_quantity = Helper::kgToQtyConvertForStock($request->quantity[$key], $request->product_weight[$key]);
                            }
                        }

                        PurchaseOrderItems::create([
                            'company_id'   => $validated['company_id'],
                            'purchase_order_id' => $id,
                            'product_id' => $request->product_id[$key],
                            'product_variant_price_id' => $request->product_variant_price_id[$key],
                            'display_order' => ($key + 1),
                            'product_code' => $request->product_code[$key] ?? null,
                            'product_name' => $product,
                            'product_description' => $request->product_description[$key] ?? null,
                            'hsn_code' => $request->hsn_code[$key] ?? null,
                            'outer_quantity' => $request->outer[$key] ?? null,
                            'inner_quantity' => $request->inner[$key] ?? null,
                            'loose_quantity' => $request->loose[$key] ?? null,
                            'unit' => $request->unit[$key] ?? null,
                            'total_kg' => $total_kg,
                            'quantity' => $request->quantity[$key] ?? null,
                            'remaining_quantity' => $remaining_quantity ?? null,
                            'price_in_amount' => $request->price[$key] ?? null,
                            'discount_flat' => $request->discount_flat[$key] ?? null,
                            'discount_percentage' => $request->discount_percentage[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'amount' => $request->amount[$key] ?? null,
                            'taxable_amount' => $request->taxable_amount[$key] ?? null,
                            'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                            'gst_amount' => $request->gst_amount_array[$key] ?? null,
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

    public function change_purchase_order_status(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
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
            $purchaseOrder = PurchaseOrder::withTrashed()->findOrFail($request?->id);
            if ($purchaseOrder) {
                $old_salesorder_status_id = $purchaseOrder->sales_status_id;
                $purchaseOrder->sales_status_id = $request->next_status_id;
                $purchaseOrder->save();
                if ($isAjax) {
                    SendPurchaseOrderJob::dispatch($request?->id, 'update', $old_salesorder_status_id, $request->next_status_id);
                    return $this->sendResponse($purchaseOrder, $modules['title'] . ' status update successfully.');
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

    public function convert_to_inward(string $id)
    {
        $modules = $this->modules;
        try {
            View::share('modules', $modules);
            View::share('purchase_order_id', $id);
            $edit = PurchaseOrder::with('vendor')->findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function exportExcel(Request $request)
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
        if (!$modules['excel_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(new PurchaseOrderExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'PurchaseOrder-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
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

            $data = PurchaseOrder::select('*')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
            $data = $data->with(['vendor', 'company']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('vendor_id') && $request->vendor_id) {
                $data->where('vendor_id', $request->vendor_id);
            }

            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $data->where('sales_status_id', $request->parent_status_id);
            }

            if ($request->has('purchase_order_date') && $request->purchase_order_date) {

                $purchase_order_date = $request->purchase_order_date;
                $purchase_order_date = explode(" to ", $purchase_order_date);
                $fromDate = null;
                $toDate = null;

                if (count($purchase_order_date) == 2) {
                    $fromDate = Helper::convert_date($purchase_order_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($purchase_order_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', ">=", $fromDate);
                    $data->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('purchase_order_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%");
                        });
                });
            }
            $purchaseOrder = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('purchaseOrder', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function purchaseOrderShow($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = $this->currentGuard ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        try {
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            $purchaseOrder = PurchaseOrder::with([
                'company',
                'vendor',
                'term_condition',
                'transport_by',
                'transport_detail',
                'purchase_order_items', // related items if applicable
                'purchase_order_items.product', // related items if applicable
            ])->findOrFail($id);
            // Ensure sales_order_items is always at least an empty collection
            $purchaseOrder->purchase_order_items = $purchaseOrder->purchase_order_items ?? collect();
            return view($modules['folder_path'] . '.' . $purchaseOrder->company->order_view_format, compact('purchaseOrder', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function purchaseOrderPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        try {
            // Share modules with all views
            View::share('modules', $modules);

            $purchaseOrder = PurchaseOrder::with([
                'company',
                'vendor',
                'purchase_order_items',
                'transport_by',
                'transport_detail',
                'term_condition',
                'team_person',
                'admin_person',
                'sales_status'
            ])->findOrFail($id);

            return view($modules['folder_path'] . '.single_print', compact('purchaseOrder', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function sendPurchaseOrderPdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $isWithoutPrice = $request->get('without_price') == '1';

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        // Load PurchaseOrder with relationships
        $purchaseOrder = PurchaseOrder::with([
            'company',
            'vendor',
            'team_person',
            'admin_person',
            'sales_status',
            'purchase_order_items',
            'term_condition',
            'transport_by',
            'transport_detail'
        ])->findOrFail($id);

        // Default logos
        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // Override logos if company exists
        if ($purchaseOrder->company_id) {
            $company = Helper::getCompanyDetailById($purchaseOrder->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        // Convert logos to base64
        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);
        // dd($sidebarMainLogo);
        // PDF view
        $viewPath = $modules['folder_path'] . '.single_pdf' ?? 'default_view';

        // Render HTML
        $html = View::make($viewPath, compact(
            'purchaseOrder',
            'modules',
            'sidebarMainLogo',
            'sidebarHeaderLogo',
            'isWithoutPrice'
        ))->render();

        // Define storage path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($purchaseOrder->company->company_name);
        $folder = "uploads/{$company_id}-{$company_slug}/purchaseorder/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'PurchaseOrder_' . $purchaseOrder->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        // Prepare email
        $data = [
            'customer_name' => $purchaseOrder->vendor?->supplier_name ?? '-',
            'company_name' => $purchaseOrder->company->company_name,
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
        // dd($purchaseOrder->vendor->email_id);
        // Send Email
        Helper::mail_new(
            $purchaseOrder->company_id,
            'Purchase Orders',
            'purchaseorder_send_pdf',
            $purchaseOrder->vendor->email_id,
            // "riddhibutaniocean@gmail.com",
            optional($purchaseOrder->vendor)->id,
            'Purchase Orders/sendPurchaseOrderPdf',
            $data,
            $attachments
        );

        return Redirect::route($modules['route'] . '.purchaseOrderShow', [$purchaseOrder->id])
            ->with('success', 'Purchase Order PDF sent via email.');
    }
}
