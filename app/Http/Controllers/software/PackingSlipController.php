<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Exports\PackingSlipExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\PackingSlipRequest;
use App\Models\PackingSlip;
use App\Models\PackingSlipItem;
use App\Models\ProductCombo;
use App\Models\SalesOrder;
use App\Models\SalesOrderItems;
use App\Models\SalesStatus;
use App\Models\TeamPerson;
use App\Models\MasterWarehouse;
use App\Models\ProductStockHistory;
use App\Models\PackingSlipReminder;
use App\Models\Product;
use App\Models\ProductCustomerItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Jobs\SendPackingSlipReminderNotificationJob;

class PackingSlipController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Packing Slip',
            'folder_path' => 'software.modules.sales_management.packing_slip',
            'route' => 'packing_slip',
            'table_name' => (new PackingSlip())->getTable(),
            'permisstion_prefix' => 'packing_slips',
            'module_name' => 'Packing Slip',
        ];
        // dd($this->modules);
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
                (object) ['data' => "company", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "packing_slip_no", 'name' => 'packing_slip_no', 'td_label' => 'Packing slip No', 'className' => ''],
                (object) ['data' => "sales_order_no", 'name' => 'sales_order_no', 'td_label' => 'Sales Order No', 'className' => ''],
                (object) ['data' => "packing_slip_date", 'name' => 'packing_slip_date', 'td_label' => 'Packing Slip Date', 'className' => ''],
                (object) ['data' => "packing_slip_status", 'name' => 'packing_slip_status', 'td_label' => 'Packing Slip Status', 'className' => ''],
                (object) ['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Code', 'className' => 'w-50 text-wrap'],
                (object) ['data' => "total_qty", 'name' => 'total_qty', 'td_label' => 'Packing Slip Qty', 'className' => ''],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center'],
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
                $data = PackingSlip::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $query->where('company_id', $modules['company_id']);
                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
                $data = $data->with(['company', 'customer', 'packing_slip_item', 'invoice']);
                $data = $data->orderBy('id', 'desc');

                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('filter_packing_slip_date') && $request->filter_packing_slip_date) {
                            $filter_packing_slip_date = $request->filter_packing_slip_date;
                            $filter_packing_slip_date = explode(" to ", $filter_packing_slip_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_packing_slip_date) == 2) {
                                $fromDate = Helper::convert_date($filter_packing_slip_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_packing_slip_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', ">=", $fromDate);
                                $query->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('packing_slip_no', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($q2) use ($search) {
                                        $q2->where('customer_name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('sales_order', function ($q2) use ($search) {
                                        $q2->where('sales_order_no', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('packing_slip_date', function ($row) {
                        return \Carbon\Carbon::parse($row?->packing_slip_date)->format('d-m-Y');
                    })
                    ->addColumn('company', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode = optional($row->customer)->client_code;
                        $customerName = $row->customer_name ?? optional($row->customer)->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return trim($clientCode . ' - ' . $customerName);
                        }

                        return $clientCode;
                    })
                    ->addColumn('packing_slip_status', function ($row) {
                        if (empty($row?->sales_status)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row?->sales_status?->status_color . '">' . $row?->sales_status?->statusname . '</span>';
                    })
                    ->addColumn('sales_order_no', function ($row) {
                        return $row?->sales_order_no;
                    })
                    ->addColumn('total_qty', function ($row) {
                        if ($row?->packing_slip_item && $row?->packing_slip_item->count() > 0) {
                            $totalQtyPc = $row?->packing_slip_item->sum('total_qty');
                            return number_format($totalQtyPc, 2);
                        }
                        return '0.00';
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';
                        $hasInvoice = $row->invoice ? true : false;

                        $action .= '<div class="">';

                        $action .= '<button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                    <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            // Add Show/Detail link
                            $action .= '<li>
                                    <a href="' . route($modules["route"] . ".show", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-eye mt-1"></i> Show Detail</a>
                                </li>';
                            $action .= '<li>
                                <a href="' . route($modules["route"] . ".lable_view", [$row["id"]]) . '" class="dropdown-item mx-1">
                                    <i class="fa-solid fa-receipt me-2 mt-1"></i> Label Print
                                </a>
                            </li>';

                            if ($hasInvoice) {
                                $cartonTotal = $row->total_no_of_carton ?? 0;

                                $action .= '<li>
                                    <a href="' . route($modules["route"] . ".cartoon_view", [$row["id"]]) . '"
                                    class="dropdown-item mx-1 carton-label-btn"
                                    data-total-carton="' . $cartonTotal . '">
                                    <i class="fa-solid fa-box-open me-2 mt-1"></i> Carton Label
                                    </a>
                                </li>';
                            }

                            $action .= '<li class="dropdown-divider"></li>';

                            if ($hasInvoice) {
                                $action .= '<li>
                                    <a href="#" class="dropdown-item mx-1 text-warning"> After Invoice Can\'t Modify.</a>
                                </li>';
                            } else {
                                if ($modules['update_permission'] == true && !$hasInvoice) {

                                    $salesStatusesLast = SalesStatus::where('company_id', $row->company_id)
                                        ->where('salestype', 'Packing Slip')
                                        ->select("id")
                                        ->orderBy('display_order', 'desc')
                                        ->first();
                                    if (!empty($salesStatusesLast->id) && $salesStatusesLast->id > 0 && $row->sales_status_id != $salesStatusesLast->id) {
                                        $action .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square mt-1"></i> Edit</a>
                                        </li>';
                                    }
                                }

                                if ($modules['delete_permission'] == true && !$hasInvoice) {
                                    $action .= '<li>
                                                    <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1" invoice="' . $row?->id . '"><i class="fa-solid fa-trash mt-1"></i> Delete</a>
                                                </li>';
                                }


                                if (!empty($row?->sales_status_id)) {
                                    $currentStatus = SalesStatus::find($row?->sales_status_id);

                                    if ($currentStatus?->statusslug == 'pd') {
                                        $nextStatus = SalesStatus::where('statusslug', 'cd')
                                            ->where('salestype', 'Packing Slip')
                                            ->orderBy('display_order', 'asc')
                                            ->first();
                                    } else {
                                        $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                            ->where('salestype', 'Packing Slip')
                                            ->where('parent_status_id', $currentStatus->id)
                                            ->where('id', '>', $currentStatus->id)
                                            ->orderBy('display_order', 'asc')
                                            ->first();
                                    }

                                    if (!empty($nextStatus)) {
                                        $action .= '<li class="dropdown-divider"></li>';
                                        if ($nextStatus->is_redirect == 1) {

                                            $url = route($modules["route"] . ".convert-to-invoice", ['id' => $row->id]);
                                            if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                                $action .= '<li>
                                                        <a href="' . $url . '" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">' . $nextStatus->statusname . '</a>
                                                    </li>';
                                            }
                                        } else {
                                            $url = "";
                                            if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                                $action .= '<li>
                                                                <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                            </li>';
                                            }
                                        }
                                    }
                                } else {
                                    $nextStatus = SalesStatus::where('company_id', $row->company->id)->where('parent_status_id', null)->where('salestype', 'Sales Orders')->orderBy('display_order', 'asc')->first();
                                    if (!empty($nextStatus)) {
                                        if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                            $url = "";
                                            $action .= '<li class="dropdown-divider"></li>
                                                        <li>
                                                            <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                        </li>';
                                        }
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
                    ->rawColumns(['action', 'packing_slip_status', 'customer_name'])
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
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            $generatePackingSlipNo = true;
            View::share('generatePackingSlipNo', $generatePackingSlipNo);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function insert($id)
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
            View::share('sales_order_id', $id);
            View::share('modules', $modules);
            $salesOrder = SalesOrder::with('customer', 'company', 'price_list')->findOrFail($id);
            View::share('edit', $salesOrder);

            $generatePackingSlipNo = true;
            View::share('generatePackingSlipNo', $generatePackingSlipNo);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(PackingSlipRequest $request)
    {
        //dd("L-239", $request->all());
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
        $validated['total_no_of_carton'] = $request->total_no_of_carton;
        // dd($request->total_no_of_carton);

        DB::beginTransaction();
        try {

            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Packing Slip')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }

            $validated['created_by'] = $loginUserId;
            // dd($request->all());
            $packingslip = PackingSlip::create($validated);
            $lastInsertedId = $packingslip->id;


            $companyId = $validated['company_id'];
            $creatorId = $loginUserId;
            $creatorType = $validated['created_type']; // team / admin

            $teamMembers = TeamPerson::where('company_id', $companyId)
                ->where('status', 'active')
                ->when($creatorType === 'team', function ($query) use ($creatorId) {
                    $query->where('id', '!=', $creatorId);
                })
                ->get();

            foreach ($teamMembers as $member) {
                PackingSlipReminder::create([
                    'packing_slip_id' => $lastInsertedId,
                    'user_id' => $member->id,
                    'user_type' => 'team',
                    'last_sent_at' => null,
                    'is_completed' => 0,
                ]);
            }

            if ($request->has('product_name') && is_array($request->product_name)) {
                foreach ($request->product_name as $key => $productItem) {

                    if ($request->is_combo[$key] == 1) {
                        $warehouse_id = $request?->warehouse_id;

                        $combo = ProductCombo::with([
                            'combo_items.product'
                        ])->where('id', $request->product_id[$key])->first();

                        $gross = $combo->gross_wt ?? 0;
                        $net = $combo->net_wt ?? 0;
                        $grosse_per_qty = $combo->grosse_per_qty ?? 0;

                        foreach ($combo->combo_items as $comboItem) {
                            $product = Product::find($comboItem->product_variant_price_id);
                            if ($warehouse_id > 0) {

                                $productName = $product?->product_name ?? 'Unknown Product';

                                if ($comboItem->order_type == 'kg') {
                                    $product_total_quantity = Helper::getProductStockTotalKG($request->company_id, $warehouse_id, $product->product_category_id, $comboItem->product_variant_price_id);
                                } else {
                                    $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $warehouse_id, $product->product_category_id, $comboItem->product_variant_price_id);
                                }

                                if ((float) $product_total_quantity <= 0) {
                                    DB::rollBack();
                                    return Redirect::back()->withErrors(["No stock available for product '{$productName}'."])->withInput();
                                }

                                if ($comboItem->order_type == 'gross') {
                                    $total_qty_main = (((float) $comboItem->quantity * (float) $request->total_qty[$key]) * (float) ($product->grosse_per_qty ?? 144));
                                } else {
                                    $total_qty_main = (float) $comboItem->quantity * (float) $request->total_qty[$key];
                                }

                                // Round to 2 decimal places for comparison to avoid precision issues
                                if (round((float) $product_total_quantity, 2) < round((float) $total_qty_main, 2)) {
                                    $available = $product_total_quantity;
                                    $required = $request->total_qty[$key];
                                    DB::rollBack();
                                    return Redirect::back()->withErrors(["Insufficient stock for product '{$productName}'. Available: {$available}, Required: {$total_qty_main}."])->withInput();
                                }

                                $total_remaining_qty = (float) $product_total_quantity - (float) $total_qty_main;
                                $total_kg = 0;
                                if ($comboItem->order_type == 'kg') {
                                    if ($total_remaining_qty > 0) {
                                        $wt = (float) $product->net_wt;
                                        if ($wt == 0.0) {
                                            $wt = (float) $product->gross_wt;
                                        }
                                        $total_kg = Helper::kgToQtyConvertForStock($total_remaining_qty, $wt);
                                    }
                                } else {
                                    if ($total_remaining_qty > 0) {
                                        $wt = (float) $product->net_wt;
                                        if ($wt == 0.0) {
                                            $wt = (float) $product->gross_wt;
                                        }
                                        $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                                    }
                                }

                                if ($comboItem->order_type == 'kg') {
                                    $addProductStock = ProductStockHistory::create([
                                        'company_id' => $request->company_id,
                                        'warehouse_id' => $warehouse_id,
                                        'product_category_id' => $product->product_category_id,
                                        'product_subcategory_id' => '',
                                        'product_variant_price_id' => $comboItem->product_variant_price_id,
                                        'packing_slip_id' => $lastInsertedId,
                                        'total_quantity' => $total_kg,
                                        'total_kg' => (float) $product_total_quantity - (float) $total_qty_main,
                                        'debit' => (float) $total_qty_main,
                                        'date' => $request->packing_slip_date,
                                        'stock_type' => 'kg',
                                        'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                        'created_by' => $loginUserId
                                    ]);
                                } else {
                                    $addProductStock = ProductStockHistory::create([
                                        'company_id' => $request->company_id,
                                        'warehouse_id' => $warehouse_id,
                                        'product_category_id' => $product->product_category_id,
                                        'product_subcategory_id' => '',
                                        'product_variant_price_id' => $comboItem->product_variant_price_id,
                                        'packing_slip_id' => $lastInsertedId,
                                        'total_quantity' => (float) $product_total_quantity - (float) $total_qty_main,
                                        'total_kg' => $total_kg,
                                        'debit' => (float) $total_qty_main,
                                        'date' => $request->packing_slip_date,
                                        'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                        'created_by' => $loginUserId
                                    ]);
                                }
                            }

                        }
                    } else {
                        $warehouse_id = $request?->warehouse_id;
                        $product = Product::find($request->product_id[$key]);
                        if ($warehouse_id > 0) {

                            $productName = $product?->product_name ?? 'Unknown Product';

                            if ($request->unit[$key] == 'kg') {
                                $product_total_quantity = Helper::getProductStockTotalKG($request->company_id, $warehouse_id, $product->product_category_id, $request->product_id[$key]);
                            } else {
                                $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $warehouse_id, $product->product_category_id, $request->product_id[$key]);
                            }

                            if ((float) $product_total_quantity <= 0) {
                                DB::rollBack();
                                return Redirect::back()->withErrors(["No stock available for product '{$productName}'."])->withInput();
                            }

                            // Round to 2 decimal places for comparison to avoid precision issues
                            if (round((float) $product_total_quantity, 2) < round((float) $request->total_qty[$key], 2)) {
                                $available = $product_total_quantity;
                                $required = $request->total_qty[$key];
                                DB::rollBack();
                                return Redirect::back()->withErrors(["Insufficient stock for product '{$productName}'. Available: {$available}, Required: {$required}."])->withInput();
                            }

                            $total_remaining_qty = (float) $product_total_quantity - (float) $request->total_qty[$key];
                            $total_kg = 0;
                            if ($request->unit[$key] == 'kg') {
                                if ($total_remaining_qty > 0) {
                                    $wt = (float) $product->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float) $product->gross_wt;
                                    }
                                    $total_kg = Helper::kgToQtyConvertForStock($total_remaining_qty, $wt);
                                }
                            } else {
                                if ($total_remaining_qty > 0) {
                                    $wt = (float) $product->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float) $product->gross_wt;
                                    }
                                    $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                                }
                            }

                            if ($request->unit[$key] == 'kg') {
                                $addProductStock = ProductStockHistory::create([
                                    'company_id' => $request->company_id,
                                    'warehouse_id' => $warehouse_id,
                                    'product_category_id' => $product->product_category_id,
                                    'product_subcategory_id' => '',
                                    'product_variant_price_id' => $request->product_id[$key],
                                    'packing_slip_id' => $lastInsertedId,
                                    'total_quantity' => $total_kg,
                                    'total_kg' => (float) $product_total_quantity - (float) $request->total_qty[$key],
                                    'debit' => (float) $request->total_qty[$key],
                                    'date' => $request->packing_slip_date,
                                    'stock_type' => 'kg',
                                    'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                    'created_by' => $loginUserId
                                ]);
                            } else {
                                $addProductStock = ProductStockHistory::create([
                                    'company_id' => $request->company_id,
                                    'warehouse_id' => $warehouse_id,
                                    'product_category_id' => $product->product_category_id,
                                    'product_subcategory_id' => '',
                                    'product_variant_price_id' => $request->product_id[$key],
                                    'packing_slip_id' => $lastInsertedId,
                                    'total_quantity' => (float) $product_total_quantity - (float) $request->total_qty[$key],
                                    'total_kg' => $total_kg,
                                    'debit' => (float) $request->total_qty[$key],
                                    'date' => $request->packing_slip_date,
                                    'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                    'created_by' => $loginUserId
                                ]);
                            }
                        }
                    }

                    $org_qty_pc = 0;
                    if ($request->unit[$key] == 'kg') {
                        $org_qty_pc = $request->total_qty[$key] ?? 0;
                    } else if ($request->unit[$key] == 'pcs') {
                        $org_qty_pc = $request->total_qty[$key] ?? 0;
                    } else if ($request->unit[$key] == 'gross') {
                        $total_gross = $product->grosse_per_qty * $request->total_qty[$key];
                        $remaining_qty = $total_gross - $request->total_qty[$key];
                        $org_qty_pc = $remaining_qty / $product->grosse_per_qty;
                    }

                    $packingSlipItem = PackingSlipItem::create([
                        'packing_slip_id' => $lastInsertedId,
                        'sales_order_item_id' => $request->sales_order_item_id[$key] ?? null,
                        'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                        'product_id' => $request->product_id[$key] ?? null,
                        'product_variant_price_id' => $request->product_variant_price_id[$key] ?? null,
                        'product_name' => $productItem,
                        'product_code' => $request->product_code[$key] ?? null,
                        'product_description' => $request->product_description[$key] ?? null,
                        'box_no' => $request->box_no[$key] ?? null,
                        'hsn_code' => $request->hsn_code[$key] ?? null,
                        'unit' => $request->unit[$key] ?? null,
                        'no_of_carton' => $request->no_of_carton[$key] ?? null,
                        'sales_order_no' => $request->sales_order_no ?? null,
                        'sales_order_date' => $request->sales_order_date ? date('d-m-Y', strtotime($request->sales_order_date)) : null,
                        'wt_pc' => $request->wt_pc[$key] ?? null,
                        'qty_pc' => $request->qty_pc[$key] ?? null,
                        'org_qty_pc' => $org_qty_pc ?? null,
                        'total_packet' => $request->total_packet[$key] ?? null,
                        'qty_ctn' => $request->qty_ctn[$key] ?? null,
                        'actual_wt_ctn' => $request->actual_wt_ctn[$key] ?? null,
                        'total_qty' => $request->total_qty[$key] ?? null,
                        'total_weight' => $request->total_weight[$key] ?? null,
                        'created_by' => $loginUserId,
                    ]);
                    if ($request->sales_order_item_id[$key]) {
                        $salesOrderItem = SalesOrderItems::withTrashed()->find($request->sales_order_item_id[$key]);
                        if ($salesOrderItem) {

                            if (isset($request->packing_selected[$key]) && $request->packing_selected[$key] > 0) {
                                $salesOrderItem->update([
                                    'remaining_quantity' => 0,
                                    'completation_time_adjustment_quantity' => $salesOrderItem->remaining_quantity - $request->total_qty[$key],
                                ]);
                            } else {
                                $newRemainingQty = $salesOrderItem->remaining_quantity - $request->total_qty[$key];
                                $salesOrderItem->update([
                                    'remaining_quantity' => max(0, $newRemainingQty),
                                ]);
                            }

                            // Update sales order status if sales_order_id is provided
                            if ($salesOrderItem->sales_order_id) {
                                $salesOrderItems = SalesOrderItems::where('sales_order_id', $salesOrderItem->sales_order_id)->get();

                                $isFullyPacking = $salesOrderItems->every(function ($item) {
                                    return $item->remaining_quantity == 0;
                                });

                                $salesOrder = SalesOrder::find($salesOrderItem->sales_order_id);
                                if ($salesOrder) {
                                    if ($isFullyPacking) {
                                        $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "fs", 'Sales Orders');
                                    } else {
                                        $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "ps", 'Sales Orders');
                                    }
                                    if ($sales_status_id) {
                                        $salesOrder->update([
                                            'sales_status_id' => $sales_status_id,
                                            'updated_by' => $loginUserId
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            // Helper::sendPackingSlipReminderNotifications(
            //     $lastInsertedId,
            //     $validated['company_id'],
            //     $loginUserId,
            //     $validated['created_type'],
            //     $validated['packing_slip_no']
            // );

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('PackingSlip store failed: ' . $e->getMessage());
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
            $edit = PackingSlip::with(['customer', 'packing_slip_item', 'company', 'invoice'])->findOrFail($id);

            // Check if invoice exists for this packing slip
            if ($edit->invoice) {
                return Redirect::route($modules['route'] . '.index')->withErrors('Cannot edit packing slip. An invoice has been generated for this packing slip. Please delete the invoice first.');
            }

            View::share('packing_slip_id', $id);
            View::share('edit', $edit);

            $packingItems = $edit->packing_slip_item;
            $totalGrossWt = 0;
            $totalNetWt = 0;
            $totalPendingQty = 0;
            $grosse_per_qty = 0;

            foreach ($packingItems as $item) {
                $gross = $item->product?->gross_wt ?? 0;
                $net = $item->product?->net_wt ?? 0;
                $remainingQty = $item->sales_order_item->remaining_quantity ?? 0;

                $totalGrossWt += $gross;
                $totalNetWt += $net;
                $totalPendingQty += $remainingQty;

                $grosse_per_qty = $item->product?->grosse_per_qty ?? 0;
                $warehouse_id = $edit->warehouse_id;

                $available_stock = 0;
                if ($item->sales_order_item->unit == 'kg') {
                    if (!empty($warehouse_id)) {
                        $available_stock = $remainingQty;
                        $available_stock = $available_stock + $item->total_qty;
                    }
                } else if ($item->sales_order_item->unit == 'pcs') {
                    if (!empty($warehouse_id)) {
                        $available_stock = $remainingQty;
                        $available_stock = $available_stock + $item->total_qty;
                    }
                } else if ($item->sales_order_item->unit == 'gross') {
                    if (!empty($warehouse_id)) {
                        $available_stock = $remainingQty;
                        $available_stock = $available_stock + $item->total_qty;
                    }
                }
                $item->available_stock = $available_stock;
            }

            View::share('total_gross_wt', $totalGrossWt);
            View::share('total_net_wt', $totalNetWt);
            View::share('total_pending_qty', $totalPendingQty);
            View::share('grosse_per_qty', $grosse_per_qty);

            $generatePackingSlipNo = false;
            View::share('generatePackingSlipNo', $generatePackingSlipNo);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function update(PackingSlipRequest $request, string $id)
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

        // Check if invoice exists for this packing slip
        $packingSlip = PackingSlip::with('invoice')->findOrFail($id);
        if ($packingSlip->invoice) {
            return Redirect::back()->withErrors('Cannot update packing slip. An invoice has been generated for this packing slip. Please delete the invoice first.');
        }

        $validated = $request->validated();
        //dd($request->all());
        DB::beginTransaction();
        try {
            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Packing Slip')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }


            $validated['created_by'] = $loginUserId;
            $updateData = PackingSlip::findOrFail($id);


            if ($updateData) {
                unset($validated['id']);

                $updateData->update($validated);
                if ($request->has('product_name') && is_array($request->product_name)) {

                    PackingSlipItem::where('packing_slip_id', $id)->delete();
                    $deletedItems = PackingSlipItem::onlyTrashed()
                        ->where('packing_slip_id', $id)
                        ->get();

                    foreach ($deletedItems as $old) {
                        $salesOrderItem = SalesOrderItems::withTrashed()->find($old->sales_order_item_id);
                        if ($salesOrderItem) {
                            if (isset($old->total_qty)) {
                                $ProductData = Product::findOrFail($salesOrderItem->product_id);
                                $newRemainingQty = $salesOrderItem->remaining_quantity + $old->total_qty;
                                $salesOrderItem->update([
                                    'remaining_quantity' => max(0, $newRemainingQty),
                                ]);
                            }
                        }
                    }


                    PackingSlipItem::where('packing_slip_id', $id)->forceDelete();
                    $warehouse_id = $request?->warehouse_id;

                    $product_stock_history_list = ProductStockHistory::where('packing_slip_id', $id)->orderBy('id', 'DESC')->get()->toArray();
                    if (is_array($product_stock_history_list) && !empty($product_stock_history_list)) {
                        foreach ($product_stock_history_list as $k => $value) {

                            if ($value['stock_type'] == 'kg') {
                                $product_total_quantity = Helper::getProductStockTotalKG($value['company_id'], $warehouse_id, $value['product_category_id'], $value['product_variant_price_id']);
                            } else {
                                $product_total_quantity = Helper::getProductStockTotalQuantity($value['company_id'], $warehouse_id, $value['product_category_id'], $value['product_variant_price_id']);
                            }

                            $ProductData = Product::findOrFail($value['product_variant_price_id']);
                            $total_remaining_qty = (float) $product_total_quantity + (float) $value['debit'];
                            $total_kg = 0;
                            if ($value['stock_type'] == 'kg') {
                                if ($total_remaining_qty > 0) {
                                    $wt = (float) $ProductData->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float) $ProductData->gross_wt;
                                    }
                                    $total_kg = Helper::kgToQtyConvertForStock($total_remaining_qty, $wt);
                                }
                            } else {
                                if ($total_remaining_qty > 0) {
                                    $wt = (float) $ProductData->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float) $ProductData->gross_wt;
                                    }
                                    $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                                }
                            }


                            ProductStockHistory::where('packing_slip_id', $id)->where("id", $value['id'])->delete();

                            if ($value['stock_type'] == 'kg') {
                                ProductStockHistory::create([
                                    'company_id' => $value['company_id'],
                                    'warehouse_id' => $warehouse_id,
                                    'product_category_id' => $value['product_category_id'],
                                    'product_subcategory_id' => '',
                                    'product_variant_price_id' => $value['product_variant_price_id'],
                                    'packing_slip_id' => $id,
                                    'total_quantity' => (float) $total_kg,
                                    'total_kg' => (float) $product_total_quantity + (float) $value['debit'],
                                    'credit' => (float) $value['debit'],
                                    'date' => $request->packing_slip_date,
                                    'remark' => 'update PackingSlip No. - ' . $validated['packing_slip_no'],
                                    'created_by' => $loginUserId
                                ]);
                            } else {
                                ProductStockHistory::create([
                                    'company_id' => $value['company_id'],
                                    'warehouse_id' => $warehouse_id,
                                    'product_category_id' => $value['product_category_id'],
                                    'product_subcategory_id' => '',
                                    'product_variant_price_id' => $value['product_variant_price_id'],
                                    'packing_slip_id' => $id,
                                    'total_quantity' => (float) $product_total_quantity + (float) $value['debit'],
                                    'total_kg' => (float) $total_kg,
                                    'credit' => (float) $value['debit'],
                                    'date' => $request->packing_slip_date,
                                    'remark' => 'update PackingSlip No. - ' . $validated['packing_slip_no'],
                                    'created_by' => $loginUserId
                                ]);
                            }
                        }
                    }

                    foreach ($request->product_name as $key => $productItem) {
                        $ProductData = null;
                        if ($request->is_combo[$key] == 1) {
                            //dd($request->all());
                            $warehouse_id = $request?->warehouse_id;
                            $combo = ProductCombo::with([
                                'combo_items.product'
                            ])->where('id', $request->product_id[$key])->first();

                            $gross = $combo->gross_wt ?? 0;
                            $net = $combo->net_wt ?? 0;
                            $grosse_per_qty = $combo->grosse_per_qty ?? 0;

                            foreach ($combo->combo_items as $comboItem) {
                                $product = Product::find($comboItem->product_variant_price_id);
                                if ($warehouse_id > 0) {

                                    $productName = $product?->product_name ?? 'Unknown Product';

                                    if ($comboItem->order_type == 'kg') {
                                        $product_total_quantity = Helper::getProductStockTotalKG($request->company_id, $warehouse_id, $product->product_category_id, $comboItem->product_variant_price_id);
                                    } else {
                                        $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $warehouse_id, $product->product_category_id, $comboItem->product_variant_price_id);
                                    }

                                    if ((float) $product_total_quantity <= 0) {
                                        DB::rollBack();
                                        return Redirect::back()->withErrors(["No stock available for product '{$productName}'."])->withInput();
                                    }

                                    if ($comboItem->order_type == 'gross') {
                                        $total_qty_main = (((float) $comboItem->quantity * (float) $request->total_qty[$key]) * (float) $grosse_per_qty);
                                    } else {
                                        $total_qty_main = (float) $comboItem->quantity * (float) $request->total_qty[$key];
                                    }

                                    // Round to 2 decimal places for comparison to avoid precision issues
                                    if (round((float) $product_total_quantity, 2) < round((float) $total_qty_main, 2)) {
                                        $available = $product_total_quantity;
                                        $required = $request->total_qty[$key];
                                        DB::rollBack();
                                        return Redirect::back()->withErrors(["Insufficient stock for product '{$productName}'. Available: {$available}, Required: {$total_qty_main}."])->withInput();
                                    }

                                    $total_remaining_qty = (float) $product_total_quantity - (float) $total_qty_main;
                                    $total_kg = 0;
                                    if ($comboItem->order_type == 'kg') {
                                        if ($total_remaining_qty > 0) {
                                            $wt = (float) $product->net_wt;
                                            if ($wt == 0.0) {
                                                $wt = (float) $product->gross_wt;
                                            }
                                            $total_kg = Helper::kgToQtyConvertForStock($total_remaining_qty, $wt);
                                        }
                                    } else {
                                        if ($total_remaining_qty > 0) {
                                            $wt = (float) $product->net_wt;
                                            if ($wt == 0.0) {
                                                $wt = (float) $product->gross_wt;
                                            }
                                            $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                                        }
                                    }

                                    if ($comboItem->order_type == 'kg') {
                                        $addProductStock = ProductStockHistory::create([
                                            'company_id' => $request->company_id,
                                            'warehouse_id' => $warehouse_id,
                                            'product_category_id' => $product->product_category_id,
                                            'product_subcategory_id' => '',
                                            'product_variant_price_id' => $comboItem->product_variant_price_id,
                                            'packing_slip_id' => $id,
                                            'total_quantity' => $total_kg,
                                            'total_kg' => (float) $product_total_quantity - (float) $total_qty_main,
                                            'debit' => (float) $total_qty_main,
                                            'date' => $request->packing_slip_date,
                                            'stock_type' => 'kg',
                                            'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                            'created_by' => $loginUserId
                                        ]);
                                    } else {
                                        $addProductStock = ProductStockHistory::create([
                                            'company_id' => $request->company_id,
                                            'warehouse_id' => $warehouse_id,
                                            'product_category_id' => $product->product_category_id,
                                            'product_subcategory_id' => '',
                                            'product_variant_price_id' => $comboItem->product_variant_price_id,
                                            'packing_slip_id' => $id,
                                            'total_quantity' => (float) $product_total_quantity - (float) $total_qty_main,
                                            'total_kg' => $total_kg,
                                            'debit' => (float) $total_qty_main,
                                            'date' => $request->packing_slip_date,
                                            'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                            'created_by' => $loginUserId
                                        ]);
                                    }
                                }
                            }
                        } else {
                            $ProductData = Product::find($request->product_id[$key]);
                            $warehouse_id = $request?->warehouse_id;
                            if ($warehouse_id > 0) {

                                $productName = $ProductData?->product_name ?? 'Unknown Product';
                                if ($request->unit[$key] == 'kg') {
                                    $product_total_quantity = Helper::getProductStockTotalKG($request->company_id, $warehouse_id, $ProductData->product_category_id, $request->product_id[$key]);
                                } else {
                                    $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $warehouse_id, $ProductData->product_category_id, $request->product_id[$key]);
                                }

                                if ((float) $product_total_quantity <= 0) {
                                    DB::rollBack();
                                    return Redirect::back()->withErrors(["No stock available for product '{$productName}'."])->withInput();
                                }

                                // Round to 2 decimal places for comparison to avoid precision issues
                                if (round((float) $product_total_quantity, 2) < round((float) $request->total_qty[$key], 2)) {
                                    $available = $product_total_quantity;
                                    $required = $request->total_qty[$key];
                                    DB::rollBack();
                                    return Redirect::back()->withErrors(["Insufficient stock for product '{$productName}'. Available: {$available}, Required: {$required}."])->withInput();
                                }

                                $total_remaining_qty = (float) $product_total_quantity - (float) $request->total_qty[$key];
                                if ($request->unit[$key] == 'kg') {
                                    if ($total_remaining_qty > 0) {
                                        $wt = (float) $ProductData->net_wt;
                                        if ($wt == 0.0) {
                                            $wt = (float) $ProductData->gross_wt;
                                        }
                                        $total_kg = Helper::kgToQtyConvertForStock($total_remaining_qty, $wt);
                                    }
                                } else {
                                    if ($total_remaining_qty > 0) {
                                        $wt = (float) $ProductData->net_wt;
                                        if ($wt == 0.0) {
                                            $wt = (float) $ProductData->gross_wt;
                                        }
                                        $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                                    }
                                }

                                if ($request->unit[$key] == 'kg') {
                                    $addProductStock = ProductStockHistory::create([
                                        'company_id' => $request->company_id,
                                        'warehouse_id' => $warehouse_id,
                                        'product_category_id' => $ProductData->product_category_id,
                                        'product_subcategory_id' => '',
                                        'product_variant_price_id' => $request->product_id[$key],
                                        'packing_slip_id' => $id,
                                        'total_quantity' => (float) $total_kg,
                                        'total_kg' => (float) $product_total_quantity - (float) $request->total_qty[$key],
                                        'debit' => (float) $request->total_qty[$key],
                                        'date' => $request->packing_slip_date,
                                        'stock_type' => 'kg',
                                        'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                        'created_by' => $loginUserId
                                    ]);
                                } else {
                                    $addProductStock = ProductStockHistory::create([
                                        'company_id' => $request->company_id,
                                        'warehouse_id' => $warehouse_id,
                                        'product_category_id' => $ProductData->product_category_id,
                                        'product_subcategory_id' => '',
                                        'product_variant_price_id' => $request->product_id[$key],
                                        'packing_slip_id' => $id,
                                        'total_quantity' => (float) $product_total_quantity - (float) $request->total_qty[$key],
                                        'total_kg' => (float) $total_kg,
                                        'debit' => (float) $request->total_qty[$key],
                                        'date' => $request->packing_slip_date,
                                        'remark' => 'PackingSlip No. - ' . $request->packing_slip_no,
                                        'created_by' => $loginUserId
                                    ]);
                                }
                            }

                        }

                        $org_qty_pc = 0;
                        if ($request->unit[$key] == 'kg') {
                            $org_qty_pc = $request->total_qty[$key];
                        } else if ($request->unit[$key] == 'pcs') {
                            $org_qty_pc = $request->total_qty[$key] ?? 0;
                        } else if ($request->unit[$key] == 'gross') {
                            if (!$ProductData) {
                                $ProductData = Product::find($request->product_id[$key]);
                            }
                            $grossePerQty = (float) ($ProductData?->grosse_per_qty ?? 0);
                            if ($grossePerQty > 0) {
                                $total_gross = $grossePerQty * (float) $request->total_qty[$key];
                                $remaining_qty = $total_gross - (float) $request->total_qty[$key];
                                $org_qty_pc = $remaining_qty / $grossePerQty;
                            }
                        }

                        $packingSlipItem = PackingSlipItem::create([
                            'packing_slip_id' => $id,
                            'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                            'sales_order_item_id' => $request->sales_order_item_id[$key] ?? null,
                            'product_id' => $request->product_id[$key] ?? null,
                            'product_variant_price_id' => $request->product_variant_price_id[$key] ?? null,
                            'product_name' => $productItem,
                            'product_code' => $request->product_code[$key] ?? null,
                            'product_description' => $request->product_description[$key] ?? null,
                            'box_no' => $request->box_no[$key] ?? null,
                            'hsn_code' => $request->hsn_code[$key] ?? null,
                            'unit' => $request->unit[$key] ?? null,
                            'no_of_carton' => $request->no_of_carton[$key] ?? null,
                            'sales_order_no' => $request->sales_order_no ?? null,
                            'sales_order_date' => $request->sales_order_date ? date('d-m-Y', strtotime($request->sales_order_date)) : null,
                            'wt_pc' => $request->wt_pc[$key] ?? null,
                            'qty_pc' => $request->qty_pc[$key] ?? null,
                            'org_qty_pc' => $org_qty_pc ?? null,
                            'total_packet' => $request->total_packet[$key] ?? null,
                            'qty_ctn' => $request->qty_ctn[$key] ?? null,
                            'actual_wt_ctn' => $request->actual_wt_ctn[$key] ?? null,
                            'total_qty' => $request->total_qty[$key] ?? null,
                            'total_weight' => $request->total_weight[$key] ?? null,
                            'created_by' => $loginUserId,
                        ]);
                        $salesOrderItem = SalesOrderItems::withTrashed()->findOrFail($request->sales_order_item_id[$key]);

                        // $salesOrderItem = SalesOrderItems::withTrashed()->findOrFail($request->sales_order_item_id[$key]);
                        // if(isset($request->db_total_qty[$key])){
                        //     $newRemainingQty = $salesOrderItem->remaining_quantity + $request->db_total_qty[$key];
                        //     $salesOrderItem->update([
                        //         'remaining_quantity' => max(0, $newRemainingQty),
                        //     ]);
                        // }

                        $newRemainingQty = $salesOrderItem->remaining_quantity - $request->total_qty[$key];
                        $salesOrderItem->update([
                            'remaining_quantity' => max(0, $newRemainingQty),
                        ]);
                    }
                }


                $salesOrderItems = SalesOrderItems::where('sales_order_id', $request->sales_order_id)->get();
                $isFullyPacking = $salesOrderItems->every(function ($item) {
                    return $item->remaining_quantity == 0;
                });

                $salesOrder = SalesOrder::findOrFail($request->sales_order_id);
                if ($isFullyPacking) {
                    $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "fs", 'Sales Orders');
                } else {
                    $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "ps", 'Sales Orders');
                }
                if (!$sales_status_id) {
                    return "";
                }

                $salesOrder->update([
                    'sales_status_id' => $sales_status_id,
                    'updated_by' => $loginUserId
                ]);
                DB::commit();
                // Helper::sendPackingSlipReminderNotifications(
                //     $id,
                //     $validated['company_id'],
                //     $loginUserId,
                //     $validated['created_type'],
                //     $validated['packing_slip_no']
                // );
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            DB::rollBack();
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


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


        $dataDelete = PackingSlip::with('invoice')->findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        // Check if invoice exists for this packing slip
        if ($dataDelete->invoice) {
            if ($isAjax) {
                return $this->sendError('Cannot delete packing slip. An invoice has been generated for this packing slip. Please delete the invoice first.', [], [], 400);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors('Cannot delete packing slip. An invoice has been generated for this packing slip. Please delete the invoice first.');
        }

        try {
            if ($dataDelete) {
                $dataDelete->update(['deleted_by' => $loginUserId]);

                PackingSlipReminder::where('packing_slip_id', $id)->forceDelete();

                $packingSlipItem = PackingSlipItem::where('packing_slip_id', $id)->get();
                if ($packingSlipItem->isNotEmpty()) {
                    foreach ($packingSlipItem as $item) {
                        $item->update(['deleted_by' => $loginUserId]);
                        $item->delete();
                    }
                }

                $product_stock_history_data = ProductStockHistory::where('packing_slip_id', $id)->get()->toArray();
                if (is_array($product_stock_history_data) && !empty($product_stock_history_data)) {
                    foreach ($product_stock_history_data as $key => $value) {

                        if ($value['stock_type'] == 'kg') {
                            $product_total_quantity = Helper::getProductStockTotalKG($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);
                        } else {
                            $product_total_quantity = Helper::getProductStockTotalQuantity($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);
                        }

                        $ProductData = Product::findOrFail($value['product_variant_price_id']);

                        $total_remaining_qty = (int) $product_total_quantity + (int) $value['debit'];
                        $total_kg = 0;
                        if ($value['stock_type'] == 'kg') {
                            if ($total_remaining_qty > 0) {
                                $wt = (float) $ProductData->net_wt;
                                if ($wt == 0.0) {
                                    $wt = (float) $ProductData->gross_wt;
                                }
                                $total_kg = Helper::kgToQtyConvertForStock($total_remaining_qty, $wt);
                            }
                        } else {
                            if ($total_remaining_qty > 0) {
                                $wt = (float) $ProductData->net_wt;
                                if ($wt == 0.0) {
                                    $wt = (float) $ProductData->gross_wt;
                                }
                                $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                            }
                        }

                        ProductStockHistory::where('packing_slip_id', $id)->where("id", $value['id'])->delete();
                        if ($value['stock_type'] == 'kg') {
                            ProductStockHistory::create([
                                'company_id' => $value['company_id'],
                                'warehouse_id' => $value['warehouse_id'],
                                'product_category_id' => $value['product_category_id'],
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $value['product_variant_price_id'],
                                'packing_slip_id' => $id,
                                'total_quantity' => (float) $total_kg,
                                'total_kg' => (int) $product_total_quantity + (int) $value['debit'],
                                'credit' => (int) $value['debit'],
                                'date' => $value['date'],
                                'remark' => 'deleted of PackingSlip No. - ' . $dataDelete->packing_slip_no,
                                'created_by' => $loginUserId
                            ]);
                        } else {
                            ProductStockHistory::create([
                                'company_id' => $value['company_id'],
                                'warehouse_id' => $value['warehouse_id'],
                                'product_category_id' => $value['product_category_id'],
                                'product_subcategory_id' => '',
                                'product_variant_price_id' => $value['product_variant_price_id'],
                                'packing_slip_id' => $id,
                                'total_quantity' => (int) $product_total_quantity + (int) $value['debit'],
                                'total_kg' => $total_kg,
                                'credit' => (int) $value['debit'],
                                'date' => $value['date'],
                                'remark' => 'deleted of PackingSlip No. - ' . $dataDelete->packing_slip_no,
                                'created_by' => $loginUserId
                            ]);
                        }
                    }
                }

                if ($packingSlipItem->isNotEmpty()) {
                    foreach ($packingSlipItem as $item) {
                        $dataSalesOrderItem = SalesOrderItems::find($item->sales_order_item_id);
                        if ($dataSalesOrderItem) {
                            $packingItemsCount = PackingSlipItem::where('sales_order_item_id', $item->sales_order_item_id)
                                ->whereNull('deleted_at')
                                ->count();

                            $salesOrder = SalesOrder::find($dataSalesOrderItem->sales_order_id);

                            if ($salesOrder) {
                                $sales_approve_id = Helper::getSalesStatusNameById($dataDelete->company_id, "app", 'Sales Orders');
                                $sales_partially_id = Helper::getSalesStatusNameById($dataDelete->company_id, "ps", 'Sales Orders');

                                $salesOrder->update([
                                    'sales_status_id' => $packingItemsCount == 0 ? $sales_approve_id : $sales_partially_id,
                                    'updated_by' => $loginUserId,
                                ]);
                            }

                            // update remaining qty
                            $packing_quantity = PackingSlipItem::where('sales_order_item_id', $dataSalesOrderItem->id)
                                ->whereNull('deleted_at')
                                ->sum('total_qty');

                            // Add ordered quantity to item
                            $org_qty_pc = 0;
                            if ($dataSalesOrderItem->unit == 'kg') {
                                $org_qty_pc = $dataSalesOrderItem->quantity ?? 0;
                            } else if ($dataSalesOrderItem->unit == 'pcs') {
                                $org_qty_pc = $dataSalesOrderItem->quantity ?? 0;
                            } else if ($dataSalesOrderItem->unit == 'gross') {
                                $org_qty_pc = $dataSalesOrderItem->product?->grosse_per_qty * $dataSalesOrderItem->quantity;
                            }


                            $dataSalesOrderItem->update([
                                'remaining_quantity' => ($packing_quantity == 0)
                                    ? $org_qty_pc
                                    : ($org_qty_pc - $packing_quantity)
                            ]);
                        }
                    }
                }



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

    public function getSalesOrderItems(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        try {
            $sales_order_id = $request->sales_order_id;

            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            $data = SalesOrderItems::with(['product', 'sales_order'])
                ->where('sales_order_id', $sales_order_id)
                ->where('remaining_quantity', '!=', 0)
                ->orderBy('id', 'ASC')
                ->get();

            $totalGrossWt = 0;
            $totalNetWt = 0;
            $totalPendingQty = 0;

            foreach ($data as $item) {
                $gross = $item->product?->gross_wt ?? 0;
                $net = $item->product?->net_wt ?? 0;
                $grosse_per_qty = $item->product?->grosse_per_qty ?? 0;
                $remainingQty = $item->remaining_quantity ?? 0;

                $totalGrossWt += $gross;
                $totalNetWt += $net;
                $totalPendingQty += $remainingQty;

                if ($item->sales_order && $item->sales_order->sales_order_date) {
                    $item->sales_order->formatted_sales_order_date = date('d-m-Y', strtotime($item->sales_order->sales_order_date));
                } else {
                    $item->sales_order->formatted_sales_order_date = null;
                }

                $customerName = $item->sales_order?->customer?->customer_name;
                $isCombo = (int) $item->is_combo === 1;
                $partNo = $isCombo ? ($item->product_code ?? '') : ($item->product?->part_no ?? $item->product_code);
                $custItemInfo = '';
                if ($customerName) {
                    $cItem = ProductCustomerItem::where('company_id', $item->company_id)
                        ->where('product_id', $item->product_id)
                        ->where('customer_name', $customerName)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($cItem) {
                        $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                        $custDesc = trim($cItem->customer_item_description ?? '');
                        $custParts = array_filter([$custCode, $custDesc]);
                        $custItemInfo = implode(' ', $custParts);
                    }
                }
                $displayCode = $partNo;
                if (!empty($custItemInfo) && $custItemInfo !== $partNo) {
                    $displayCode .= ' (' . $custItemInfo . ')';
                }
                $item->display_product_code = $displayCode;
                $item->customer_item_code = $custItemInfo;
                if (!$isCombo && $item->product) {
                    $item->product_name = $item->product->product_name ?: $item->product->product_description;
                }
            }

            return response()->json([
                'items' => $data,
                'total_gross_wt' => $totalGrossWt,
                'total_net_wt' => $totalNetWt,
                'total_pending_qty' => number_format($totalPendingQty, 2),
                'grosse_per_qty' => $grosse_per_qty,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCustomerOrderItems(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $customer_id = $request->customer_id;
            $warehouse_id = $request->warehouse_id;
            $company_id = $request->company_id ?? $modules['company_id'];

            if (!$customer_id) {
                return response()->json(['error' => 'Customer ID is required'], 400);
            }

            if (empty($warehouse_id)) {
                $defaultWarehouse = MasterWarehouse::where('company_id', $company_id)
                    ->whereRaw('LOWER(name) = ?', ['finished goods'])
                    ->first();
                $warehouse_id = $defaultWarehouse->id ?? null;
            }

            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            // Get all sales orders for the customer with remaining quantity items
            $data = SalesOrderItems::with(['product', 'sales_order.customer'])
                ->whereHas('sales_order', function ($query) use ($customer_id, $company_id) {
                    $query->where('customer_id', $customer_id);
                    if ($company_id) {
                        $query->where('company_id', $company_id);
                    }
                })
                ->where('remaining_quantity', '>', 0)
                ->orderBy('id', 'ASC')
                ->get();

            $totalGrossWt = 0;
            $totalNetWt = 0;
            $totalPendingQty = 0;
            $grosse_per_qty = 0;

            foreach ($data as $item) {

                if ((int) $item->is_combo === 0) {
                    $gross = $item->product?->gross_wt ?? 0;
                    $net = $item->product?->net_wt ?? 0;
                    $grosse_per_qty = $item->product?->grosse_per_qty ?? 0;
                    $remainingQty = $item->remaining_quantity ?? 0;
                    $orderedQty = $item->quantity ?? 0;

                    $totalGrossWt += $gross;
                    $totalNetWt += $net;
                    $totalPendingQty += $remainingQty;

                    if ($item->sales_order && $item->sales_order->sales_order_date) {
                        $item->sales_order->formatted_sales_order_date = date('d-m-Y', strtotime($item->sales_order->sales_order_date));
                    } else {
                        $item->sales_order->formatted_sales_order_date = null;
                    }

                    // Add ordered quantity to item
                    $org_qty_pc = 0;
                    $available_stock_kg = 0;
                    $available_stock_qty = 0;

                    if ($item->unit == 'kg') {
                        $org_qty_pc = $remainingQty ?? 0;
                        if (!empty($warehouse_id)) {
                            $available_stock_kg = Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $item->product->product_category_id, $item->product_id);
                            $available_stock_qty = (int) Helper::getProductStockTotalQuantity($item->company_id, $warehouse_id, $item->product->product_category_id, $item->product_id);
                        }
                    } else if ($item->unit == 'pcs') {
                        $org_qty_pc = $remainingQty ?? 0;

                        if (!empty($warehouse_id)) {
                            $available_stock_kg = Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $item->product->product_category_id, $item->product_id);
                            $available_stock_qty = (int) Helper::getProductStockTotalQuantity(
                                $item->company_id,
                                $warehouse_id,
                                $item->product->product_category_id,
                                $item->product_id
                            );
                        }
                    } else if ($item->unit == 'gross') {
                        $org_qty_pc = $remainingQty;

                        if (!empty($warehouse_id)) {
                            $available_stock_kg = Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $item->product->product_category_id, $item->product_id);
                            $available_stock_qty = (int) Helper::getProductStockTotalQuantity($item->company_id, $warehouse_id, $item->product->product_category_id, $item->product_id);
                        }
                    }
                    $item->ordered_quantity = $org_qty_pc;
                    $item->available_stock_kg = $available_stock_kg;
                    $item->available_stock_qty = $available_stock_qty;
                }

                if ((int) $item->is_combo === 1) {
                    $combo = ProductCombo::with([
                        'combo_items.product'
                    ])->where('id', $item->product_id)->first();

                    if (!$combo) {
                        continue;
                    }


                    if ($item->sales_order && $item->sales_order->sales_order_date) {
                        $item->sales_order->formatted_sales_order_date = date('d-m-Y', strtotime($item->sales_order->sales_order_date));
                    } else {
                        $item->sales_order->formatted_sales_order_date = null;
                    }

                    $remainingQty = $item->remaining_quantity ?? 0;

                    $gross = $combo->gross_wt ?? 0;
                    $net = $combo->net_wt ?? 0;
                    $grosse_per_qty = $combo->grosse_per_qty ?? 0;

                    $totalGrossWt += $gross;
                    $totalNetWt += $net;
                    $totalPendingQty += $remainingQty;

                    $comboProducts = [];
                    foreach ($combo->combo_items as $comboItem) {
                        $product = $comboItem->product;
                        $productName = $product
                            ? ($product->part_no ?? '') . ' - ' . ($product->product_name ?? '')
                            : '';
                        $comboProducts[] = [
                            'product_name' => $productName,
                            'required_qty' => $comboItem->quantity,
                        ];
                    }

                    // Combo available stock = MIN possible combo qty
                    $item->ordered_quantity = $remainingQty;

                    $org_qty_pc = 0;
                    $available_stock_kg = 0;
                    $available_stock_qty = 0;

                    if ($item->unit == 'kg') {
                        $org_qty_pc = $remainingQty ?? 0;
                        if (!empty($warehouse_id)) {
                            $available_stock_kg = Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, (bool) $item->is_combo);
                            $available_stock_qty = (int) Helper::getProductStockTotalQuantity($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, (bool) $item->is_combo);
                        }
                    } else if ($item->unit == 'pcs') {
                        $org_qty_pc = $remainingQty ?? 0;

                        if (!empty($warehouse_id)) {
                            $available_stock_kg = Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, (bool) $item->is_combo);
                            $available_stock_qty = (int) Helper::getProductStockTotalQuantity(
                                $item->company_id,
                                $warehouse_id,
                                $combo->product_category_id,
                                $item->product_id,
                                (bool) $item->is_combo
                            );
                        }
                    } else if ($item->unit == 'gross') {
                        $org_qty_pc = $remainingQty;

                        if (!empty($warehouse_id)) {
                            $available_stock_kg = Helper::getProductStockTotalKG($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, (bool) $item->is_combo);
                            $available_stock_qty = (int) Helper::getProductStockTotalQuantity($item->company_id, $warehouse_id, $combo->product_category_id, $item->product_id, (bool) $item->is_combo);
                        }
                    }
                    $item->ordered_quantity = $org_qty_pc;
                    $item->available_stock_kg = $available_stock_kg;
                    $item->available_stock_qty = $available_stock_qty;

                    $item->combo_products = $comboProducts;

                    // Override display fields
                    $item->product_name = $combo->product_combo_name;
                    $item->product_code = $combo->part_no;
                    $item->hsn_code = $combo->h_s_n_code;
                    $item->product_description = $combo->product_description;
                    $item->gross_wt = $combo->gross_wt;
                    $item->net_wt = $combo->net_wt;
                    $item->grosse_per_qty = $combo->grosse_per_qty;
                }

                $customerName = $item->sales_order?->customer?->customer_name;
                $isCombo = (int) $item->is_combo === 1;
                $partNo = $isCombo ? ($combo->part_no ?? $item->product_code) : ($item->product?->part_no ?? $item->product_code);
                $custItemInfo = '';
                if ($customerName) {
                    $cItem = ProductCustomerItem::where('company_id', $item->company_id)
                        ->where('product_id', $item->product_id)
                        ->where('customer_name', $customerName)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($cItem) {
                        $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                        $custDesc = trim($cItem->customer_item_description ?? '');
                        $custParts = array_filter([$custCode, $custDesc]);
                        $custItemInfo = implode(' ', $custParts);
                    }
                }
                $displayCode = $partNo;
                if (!empty($custItemInfo) && $custItemInfo !== $partNo) {
                    $displayCode .= ' (' . $custItemInfo . ')';
                }
                $item->display_product_code = $displayCode;
                $item->customer_item_code = $custItemInfo;
                if (!$isCombo && $item->product) {
                    $item->product_name = $item->product->product_name ?: $item->product->product_description;
                }
            }

            return response()->json([
                'items' => $data,
                'total_gross_wt' => $totalGrossWt,
                'total_net_wt' => $totalNetWt,
                'total_pending_qty' => number_format($totalPendingQty, 2),
                'grosse_per_qty' => $grosse_per_qty,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
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
        if (!$modules['view_permission']) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $packingSlip = PackingSlip::with([
                'company',
                'customer',
                'sales_order',
                'sales_order.customer',
                'sales_status',
                'warehouse',
                'packing_slip_item.product',
                'packing_slip_item.product_variant_price',
                'packing_slip_item.sales_order_item',
                'invoice',
                'invoice.customer'
            ])->findOrFail($id);

            return view($modules['folder_path'] . '.detail', compact('packingSlip', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function PackingSlipShow($id)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            $packingSlip = PackingSlip::with(['company', 'customer', 'packing_slip_item.product'])->findOrFail($id);
            //    dd(
            //     $packingSlip
            //    );
            return view($modules['folder_path'] . '.' . $packingSlip->company->order_view_format, compact('packingSlip', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function packingSlipPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            View::share('modules', $modules);
            $packingSlip = PackingSlip::with(['company', 'customer', 'packing_slip_item.product'])->findOrFail($id);


            return view($modules['folder_path'] . '.single_print', compact('packingSlip', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function sendPackingSlipPdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        $packingSlip = PackingSlip::with(['company', 'customer', 'packing_slip_item.product'])->findOrFail($id);

        // Set default logos
        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // Use company logos if available
        if ($packingSlip->company_id) {
            $company = Helper::getCompanyDetailById($packingSlip->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        // Convert logos to base64 if needed
        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

        $company_id = $packingSlip->company_id;

        $viewPath = $modules['folder_path'] . '.single_pdf' ?? 'default_view';

        // Render Blade view to HTML
        $html = View::make($viewPath, compact('packingSlip', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

        // Define folder path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = str::slug($packingSlip->company->company_name);
        $folder = "uploads/{$company_id}-{$company_slug}/packingslip/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'PackingSlip_' . $packingSlip->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        $data = [
            'customer_name' => optional($packingSlip->customer)->customer_name ?? '-',
            'company_name' => $packingSlip->company->company_name,
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
            $packingSlip->company_id,
            'Packing Slip',
            'packing_slip_send_pdf',
            // optional($packingSlip->customer)->email,
            'riddhibutaniocean@gmail.com',
            optional($packingSlip->customer)->id,
            'Packing Slip/sendPackingSlipPdf',
            $data,
            $attachments
        );

        // return Redirect::route($modules['route'] . '.PackingSlipShow', [$packingSlip->id])->with('success', 'Packing Slip PDF sent via email.');
        return Redirect::route($modules['route'] . '.PackingSlipShow', [$packingSlip->id])->with('success', 'Packing Slip PDF sent via email.');
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

        return Excel::download(new PackingSlipExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'PackingSlip-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $data = PackingSlip::select('*')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
            $data = $data->with(['company', 'customer', 'packing_slip_item']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('packing_slip_date') && $request->packing_slip_date) {

                $packing_slip_date = $request->packing_slip_date;
                $packing_slip_date = explode(" to ", $packing_slip_date);
                $fromDate = null;
                $toDate = null;

                if (count($packing_slip_date) == 2) {
                    $fromDate = Helper::convert_date($packing_slip_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($packing_slip_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', ">=", $fromDate);
                    $data->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('packing_slip_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('sales_order', function ($q2) use ($search) {
                            $q2->where('sales_order_no', 'like', "%{$search}%");
                        });
                });
            }
            $packingSlip = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('packingSlip', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function lable_view($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = $this->currentGuard ?? null;

        $company_id = $modules['company_id'] =
            $this->authenticateLoginUserDetails?->company_id ?? null;

        $modules['parent_type_id'] =
            $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        // Permission check (same as show)
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] =
                    (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['view_permission']) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $packingSlip = PackingSlip::with([
                'company',
                'customer',
                'packing_slip_item.product',
                'packing_slip_item.customerItem'
            ])->findOrFail($id);

            //    dd($packingSlip);
            return view(
                $modules['folder_path'] . '.lable_view',
                compact('packingSlip', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
    }
    public function lable_print($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            View::share('modules', $modules);
            $packingSlip = PackingSlip::with(['company', 'customer', 'packing_slip_item.product'])->findOrFail($id);


            return view($modules['folder_path'] . '.lable_print', compact('packingSlip', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function cartoon_view($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = $this->currentGuard ?? null;

        $company_id = $modules['company_id'] =
            $this->authenticateLoginUserDetails?->company_id ?? null;

        $modules['parent_type_id'] =
            $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        // Permission check (same as show)
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] =
                    (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['view_permission']) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $packingSlip = PackingSlip::with([
                'company',
                'customer',
                'invoice',
                'sales_order.customer',
                'packing_slip_item.product',
                'packing_slip_item.customerItem'
            ])->findOrFail($id);

            // Check if total_no_of_carton is defined
            if (!$packingSlip->total_no_of_carton || $packingSlip->total_no_of_carton <= 0) {
                return redirect()
                    ->route($modules['route'] . '.index')
                    ->with('error', 'Total number of cartons must be greater than 0. Please edit the Packing Slip first.');
            }

            return view(
                $modules['folder_path'] . '.cartoon_view',
                compact('packingSlip', 'company_id', 'modules')
            );
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
    }

    // public function cartoon_view($id)
    // {
    //     $modules = $this->modules;
    //     $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
    //     $modules['currentGuard'] = $this->currentGuard ?? null;

    //     $company_id = $modules['company_id'] =
    //         $this->authenticateLoginUserDetails?->company_id ?? null;

    //     $modules['parent_type_id'] =
    //         $this->authenticateLoginUserDetails?->parent_type_id ?? null;

    //     // Permission check (same as show)
    //     if (count(config('constants.permissions'))) {
    //         foreach (config('constants.permissions') as $key => $value) {
    //             $modules[$value . '_permission'] =
    //                 (isset($modules['company_id']) && !$modules['company_id'])
    //                 ? true
    //                 : Gate::check('hasPermission', [$value, $modules['module_name']]);
    //         }
    //     }

    //     if (!$modules['view_permission']) {
    //         abort(403, 'Unauthorized');
    //     }

    //     try {
    //         View::share('modules', $modules);

    //         $packingSlip = PackingSlip::with([
    //             'company',
    //             'customer',
    //             'packing_slip_item.product',
    //             'packing_slip_item.customerItem'
    //         ])->findOrFail($id);

    //         //    dd($packingSlip);
    //         return view(
    //             $modules['folder_path'] . '.cartoon_view',
    //             compact('packingSlip', 'company_id', 'modules')
    //         );
    //     } catch (\Exception $e) {
    //         return Redirect::route($modules['route'] . '.index')
    //             ->withErrors($e->getMessage());
    //     }
    // }
    public function cartoon_print($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            View::share('modules', $modules);
            $packingSlip = PackingSlip::with([
                'company',
                'customer',
                'invoice',
                'sales_order.customer',
                'packing_slip_item.product',
                'packing_slip_item.customerItem'
            ])->findOrFail($id);


            return view($modules['folder_path'] . '.cartoon_print', compact('packingSlip', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function pendingPackingNotifications(Request $request)
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

        if (!$loginUserId) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 401);
            }
            abort(401, 'Unauthorized');
        }

        $isAjax = ($request->ajax()) ? true : false;

        try {

            $userType = $modules['currentGuard'] === 'team_person' ? 'team' : 'admin'; // Adjust based on your guard

            $statusData = SalesStatus::where('company_id', $modules['company_id'])
                ->where('salestype', 'Packing Slip')
                ->where('display_order', 1)
                ->first();

            if (!$statusData) {
                return $this->sendResponse([], 'No pending packing slip reminders.');
            }

            $pendingStatusId = $statusData->id;
            $reminders = PackingSlipReminder::where('user_id', $loginUserId)
                ->where('user_type', $userType)
                ->where('is_completed', 0)
                ->whereNull('last_sent_at')
                ->whereHas('packingSlip', function ($q) use ($pendingStatusId, $loginUserId) {
                    $q->where('sales_status_id', $pendingStatusId)
                        ->where('created_by', '!=', $loginUserId)
                        ->whereHas('sales_order', function ($sq) use ($loginUserId) {
                            $sq->where('created_by', $loginUserId);
                        });
                })
                ->with([
                    'packingSlip' => function ($query) {
                        $query->with([
                            'company',
                            'customer',
                            'sales_order' => function ($sq) {
                                $sq->select('id', 'sales_order_no', 'created_by');
                            }
                        ])->select(
                                'id',
                                'packing_slip_no',
                                'company_id',
                                'customer_id',
                                'sales_status_id',
                                'created_by',
                                'sales_order_id',
                                'created_type',
                            );
                    }
                ])
                ->select('id', 'packing_slip_id', 'last_sent_at')->get();


            if ($reminders->isEmpty()) {
                return $this->sendResponse([], 'No pending packing slip reminders.');
            }

            // Transform into frontend-friendly format
            $response = $reminders->map(function ($reminder) {
                $slip = $reminder->packingSlip;

                $name = '';
                if ($reminder?->packingSlip?->created_type == 'admin') {
                    $name = $reminder?->packingSlip?->admin_person?->name;
                } else {
                    $name = $reminder?->packingSlip?->team_person?->name;
                }

                return [
                    'id' => $reminder->id, // reminder ID (for marking completed/ignored)
                    'packing_slip_id' => $reminder->packing_slip_id,
                    'packing_slip_no' => $slip?->packing_slip_no ?? 'Unknown',
                    'title' => 'Pending Packing Slip Review',
                    'body' => "Packing Slip #{$slip?->packing_slip_no} requires your attention.",
                    'module_name' => 'Packing Slip',
                    'module_id' => $reminder->packing_slip_id,
                    'customer_name' => $slip?->customer?->customer_name ?? null,
                    'company_name' => $slip?->company?->company_name ?? null,
                    'name' => $name ?? null,
                ];
            });

            return $this->sendResponse($response, 'Pending packing slip reminders retrieved successfully.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function markAsReadNotifications(Request $request)
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

        if (!$loginUserId) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 401);
            }
            abort(401, 'Unauthorized');
        }

        $isAjax = ($request->ajax()) ? true : false;
        $reminderId = $request->input('id');
        $packing_slip_id = $request->input('packing_slip_id');
        try {
            $userType = $modules['currentGuard'] === 'team_person' ? 'team' : 'admin'; // Adjust based on your guard

            $reminder = PackingSlipReminder::where('id', $reminderId)
                ->where('packing_slip_id', $packing_slip_id)
                ->where('user_id', $loginUserId)
                ->where('user_type', $userType)
                ->firstOrFail();

            $reminder->is_completed = 1;
            $reminder->save();

            return $this->sendResponse(['success' => true], 'Packing slip marked as completed.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
