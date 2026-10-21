<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Exports\SalesOrderExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesStatus;
use App\Models\QuotationItems;
use App\Models\SalesOrderItems;
use App\Models\SalesOrderReminder;
use App\Models\TeamPerson;
use App\Models\Product;
use App\Models\Company;
use App\Http\Requests\SalesOrderRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendQuotationJob;
use App\Jobs\SendSalesOrderJob;
use App\Models\AccountLedger;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;


use Illuminate\Support\Facades\Gate;


class SalesOrderController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Sales Order',
            'folder_path' => 'software.modules.sales_management.sales_order',
            'route' => 'sales-order',
            'table_name' => (new SalesOrder())->getTable(),
            'permisstion_prefix' => 'sales-order',
            'module_name' => 'Sales Orders',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];

        if (count(config('constants.order_type'))) {
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

                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'action-col text-center'],
                (object) ['data' => "sales_order_no", 'name' => 'sales_order_no', 'td_label' => 'Sales Order'],
                (object) ['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "sales_order_status", 'name' => 'sales_order_status', 'td_label' => 'Sales Order Status', 'className' => ''],
                (object) ['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Code', 'className' => 'customer-col'],
                (object) ['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' => ''],
                (object) ['data' => "product_detail", 'name' => 'product_detail', 'td_label' => 'Product Detail', 'className' => ''],
            ];

            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });
                $columns = array_values($columns);
            }

            $conditionForGrandTotal = (
                $modules['currentGuard'] === 'admin_software' ||
                ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))
            );
            if (!$conditionForGrandTotal) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'grand_total';
                });
                $columns = array_values($columns); // reindex array
            }

            View::share("columns", $columns);

            if ($request->ajax()) {
                $data = SalesOrder::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['company', 'customer', 'team_person', 'quotation', 'admin_person', 'sales_order_items.product']);
                $data = $data->orderBy('id', 'desc');
                //$data = $data->withTrashed();
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

                        if ($request->has('filter_team_person') && $request->filter_team_person) {
                            $query->where('created_by', $request->filter_team_person)->where('created_type', 'team');
                        }

                        if ($request->has('filter_sales_order_date') && $request->filter_sales_order_date) {

                            $filter_sales_order_date = $request->filter_sales_order_date;
                            $filter_sales_order_date = explode(" to ", $filter_sales_order_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_sales_order_date) == 2) {
                                $fromDate = Helper::convert_date($filter_sales_order_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_sales_order_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new SalesOrder())->getTable() . '.sales_order_date', ">=", $fromDate);
                                $query->whereDate((new SalesOrder())->getTable() . '.sales_order_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new SalesOrder())->getTable() . '.sales_order_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('sales_order_no', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($q2) use ($search) {
                                        $q2->where('customer_name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('quotation', function ($q2) use ($search) {
                                        $q2->where('quotation_no', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->addColumn('sales_order_no', function ($row) {

                        $salesOrderDate = $row->sales_order_date
                            ? \Carbon\Carbon::parse($row->sales_order_date)->format('d-m-Y')
                            : '-';

                        $reviseOrRepeat = '';
                        if (!empty($row->sales_order_repeat)) {
                            $reviseOrRepeat .= '<br><small><strong>Repeat:</strong> ' . $row->parent_sales_order_id . '</small>';
                        }
                        return
                            $row->sales_order_no .
                            '<br><strong>Order Date:</strong> ' . $salesOrderDate .
                            '<br><strong>Total:</strong> ' . $row->grand_total .
                            $reviseOrRepeat;
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row?->admin_person?->name;
                        } else {
                            return $row?->team_person?->name;
                        }
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode = optional($row->customer)->client_code;
                        $customerName = $row->customer_name ?? optional($row->customer)->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return '<span style="white-space:nowrap;">' . $clientCode . '<br>' . $customerName . '</span>';
                        }

                        return $clientCode;
                    })
                    ->addColumn('company_name', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('product_detail', function ($row) {
                        if ($row->sales_order_items->count() == 0) {
                            return '-';
                        }

                        $html = '<table class="table table-bordered table-sm mb-0 product-detail-table">';
                        $html .= '<tr><th>Product</th><th>R. Qty</th><th>Current Stock</th></tr>';

                        foreach ($row->sales_order_items as $item) {
                            // Show all products including KG ones
                            $unit = strtolower($item->unit ?? '');

                            // Format quantity based on unit
                            $formattedQty = '';
                            if ($unit == 'kg') {
                                // For KG products, use kg_quantity if available, otherwise use remaining_quantity
                                $kgQty = $item->remaining_quantity;

                                $wt = (float) ($item->product->net_wt ?? 0);
                                if ($wt == 0.0) {
                                    $wt = (float) ($item->product->gross_wt ?? 0);
                                }
                                $formattedQty = number_format((float) $item?->remaining_quantity, 2, '.', '');
                                if ($item->product && $item->product->gross_wt) {
                                    $wt = (float) ($item->product->net_wt ?? 0);
                                    if ($wt == 0.0) {
                                        $wt = (float) ($item->product->gross_wt ?? 0);
                                    }

                                    $formattedQty .= ' KG<br><small class="text-muted">(' . Helper::kgToQtyConvertForStock($kgQty, $wt) . ' PCS)</small>';
                                } else {
                                    $formattedQty .= ' KG';
                                }
                            } elseif ($unit == 'gross') {
                                // GROSS: Show with 2 decimals
                                $remainingQty = $item->remaining_quantity ?? 0;
                                $formattedQty = number_format((float) $remainingQty / $item->product->grosse_per_qty, 2, '.', '');
                                $formattedQty .= ' GROSS<br><small class="text-muted">(' . number_format((float) $remainingQty, 2, '.', '') . ' PCS)</small>';
                            } else {
                                // PCS or default: Show with 2 decimals
                                $remainingQty = $item->remaining_quantity ?? 0;
                                $formattedQty = number_format((int) $remainingQty) . ($unit ? ' ' . strtoupper($unit) : '');
                            }

                            // Get current stock
                            $currentStock = '-';
                            $companyId = $row->company_id;
                            $productCategoryId = $item->product->product_category_id ?? null;
                            $productVariantPriceId = $item->product_variant_price_id ?? $item->product_id ?? null;
                            $isCombo = (int) $item->is_combo === 1;

                            if ($productCategoryId && $productVariantPriceId) {
                                $warehouse = \App\Models\MasterWarehouse::where('company_id', $companyId)
                                    ->where('status', 'active')
                                    ->where(function ($query) {
                                        $query->where('name', 'like', '%Finished Goods%')
                                            ->orWhere('name', 'like', '%FG%');
                                    })
                                    ->first();

                                if ($warehouse) {
                                    $stockQty = Helper::getProductStockTotalQuantity($companyId, $warehouse->id, $productCategoryId, $productVariantPriceId, $isCombo);
                                    $stockKg = Helper::getProductStockTotalKG($companyId, $warehouse->id, $productCategoryId, $productVariantPriceId, $isCombo);

                                    if ($unit == 'kg') {
                                        $currentStock = number_format((float) $stockKg, 2) . ' KG';
                                    } elseif ($unit == 'gross') {
                                        $currentStock = number_format((float) $stockQty, 2) . ' GROSS';
                                    } else {
                                        $currentStock = round($stockQty);
                                    }
                                }
                            }

                            $html .= '<tr>
                                    <td><span style="white-space:normal;">' . str_replace('-', '<br>', $item->product_name) . '</span></td>
                                    <td>' . $formattedQty . '</td>
                                    <td>' . $currentStock . '</td>
                                </tr>';
                        }
                        $html .= '</table>';

                        return $html;
                    })
                    ->addColumn('sales_order_status', function ($row) {
                        if (empty($row?->sales_status)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row?->sales_status?->status_color . '">' . $row?->sales_status?->statusname . '</span>';
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';
                        $action .= '
                            <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            if ($modules['update_permission'] == true) {

                                $salesStatusesLast = SalesStatus::where('company_id', $row->company_id)
                                    ->where('salestype', 'Sales Orders')
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

                                $salesStatusesLast = SalesStatus::where('company_id', $row->company_id)
                                    ->where('salestype', 'Sales Orders')
                                    ->select("id")
                                    ->orderBy('display_order', 'desc')
                                    ->first();
                                if (!empty($salesStatusesLast->id) && $salesStatusesLast->id > 0 && $row->sales_status_id != $salesStatusesLast->id) {
                                    $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </li>';
                                }
                            }


                            if ($row?->sales_status?->is_repeat == 1) {
                                $action .= '<li>
                                        <a href="javascript:void(0)" data-clone="' . route($modules["route"] . ".clone") . '" class="dropdown-item record-clone m-1" data-id="' . $row->id . '" data-quatation-txt="repeat" data-type="repeat" data-bs-original-title="Repeat Data">
                                            <i class="fa-solid fa fa-repeat"></i> Repeat
                                        </a>
                                    </li>';
                            }

                            $action .= '<li>
                                            <a href="' . route($modules["route"] . ".salesOrderShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1"><i class="fa-solid fa fa-eye"></i> View </a>
                                        </li>';

                            $customerPhone = preg_replace('/[^0-9]/', '', $row?->customer?->mobile_number ?? '');
                            if ($customerPhone) {
                                ///$pdfUrl = route('sales-order.download-pdf', $row->id); // or public PDF URL
                                $message = urlencode(
                                    "Hi *{$row?->customer?->customer_name}*,\n\n" .
                                    "*Your Sales Order* {$row->sales_order_no}\n" .
                                    "*Order Date*:" . \Carbon\Carbon::parse($row->sales_order_date)->format('d-m-Y') . "\n" .
                                    "*Total*: ₹{$row->grand_total}\n\n"
                                );


                                // $whatsappUrl = "https://web.whatsapp.com/send?phone=91{$customerPhone}&text={$message}";
                                $whatsappUrl = route('whatsapp.send', ['id' => $row->id, 'type' => 'sales_order']);

                                $action .= '<li>
                                    <a href="' . $whatsappUrl . '" class="dropdown-item mx-1">
                                        <i class="fa-brands fa-whatsapp text-success"></i> Send WhatsApp
                                    </a>
                                </li>';
                            }

                            if (!empty($row?->sales_status_id)) {
                                $currentStatus = SalesStatus::find($row?->sales_status_id);

                                if ($currentStatus?->statusslug == 'ps') {

                                    // 1) Create Packing Slip (redirect)
                                    $createPackingSlip = SalesStatus::where('statusslug', 'cp')
                                        ->where('salestype', 'Sales Orders')
                                        ->first();

                                    // 2) Full Packing (normal change status)
                                    $fullPacking = SalesStatus::where('statusslug', 'fs')
                                        ->where('salestype', 'Sales Orders')
                                        ->first();

                                    if ($createPackingSlip) {
                                        $url = route('packing_slip.insert', $row->id);
                                        $action .= '<li>
            <a href="' . $url . '" data-id="' . $row->id . '"
               data-next-status-id="' . $createPackingSlip->id . '"
               class="change_status dropdown-item mx-1">
               Create Packing Slip
            </a>
        </li>';
                                    }

                                    if ($fullPacking) {
                                        $url = route($modules["route"] . ".change-sales-order-status");
                                        $action .= '<li>
        <a href="javascript:void(0)" data-url="' . $url . '"
           data-id="' . $row->id . '"
           data-next-status-id="' . $fullPacking->id . '"
           data-full-packing="1"
           class="change_status dropdown-item mx-1">
           Change to Full Packing
        </a>
    </li>';
                                    }
                                } else {
                                    $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                        ->where('salestype', 'Sales Orders')
                                        ->where('parent_status_id', $currentStatus->id)
                                        ->where('id', '>', $currentStatus->id)
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                }

                                if (!empty($nextStatus)) {
                                    $action .= '<li class="dropdown-divider"></li>';
                                    if ($nextStatus->is_redirect == 1) {
                                        $url = route('packing_slip.insert', $row->id);
                                        if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                            $action .= '<li>
                                                    <a href="' . $url . '" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">' . $nextStatus->statusname . '</a>
                                                </li>';
                                        }
                                    } else {
                                        $url = route($modules["route"] . ".change-sales-order-status");

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
                                        $action .= '<li class="dropdown-divider"></li>
                                                <li>
                                                    <a data-url="' . route($modules["route"] . ".change-sales-order-status") . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
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
                    ->rawColumns(['status', 'action', 'sales_order_status', 'sales_order_no', 'product_detail', 'customer_name'])
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
            $generateSalesOrderNo = true;
            View::share('generateSalesOrderNo', $generateSalesOrderNo);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(SalesOrderRequest $request)
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
        // dd($request->all(),$validated);
        try {

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    if (is_null($product) || trim($product) === '') {
                        if ($request->ajax()) {
                            return $this->sendError("Product name is required.", ["product_name.$key" => "Product name is required."]);
                        }
                        return back()->withErrors(["product_name.$key" => "Product name is required."]);
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        if ($request->ajax()) {
                            return $this->sendError("Quantity must be greater than 0.", ["quantity.$key" => "Quantity must be greater than 0."]);
                        }
                        return back()->withErrors(["quantity.$key" => "Quantity must be greater than 0."]);
                    }

                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
                        if ($request->ajax()) {
                            return $this->sendError("Price must be greater than 0.", ["price.$key" => "Price must be greater than 0."]);
                        }
                        return back()->withErrors(["price.$key" => "Price must be greater than 0."]);
                    }
                }
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;
            if ($validated['quotation_id'] != '') {
                $quotation = $validated['quotation_id'];
                $salesOrder = SalesOrder::where('quotation_id', $quotation)->first();
                if ($salesOrder) {
                    if ($request->ajax()) {
                        return $this->sendError("Quotation already converted to Sales Order.", ["quotation_id" => "Quotation already converted to Sales Order."]);
                    }
                    return back()->withErrors(["quotation_id" => "Quotation already converted to Sales Order."]);
                }
            }

            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Sales Orders')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }
            if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int) ($validated['total_taxable']) <= 0) {
                if ($request->ajax()) {
                    return $this->sendError('Total Taxable must be greater than 0.');
                }
                return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            }

            if ($request->has('same_as_billing') || (empty($validated['shipping_customer_id']) && (empty($validated['shipping_address']) || trim($validated['shipping_address'] ?? '') === trim($validated['billing_address'] ?? '')))) {
                $validated['shipping_address'] = $validated['billing_address'] ?? null;
                $validated['shipping_gst_no'] = $validated['gst_no'] ?? null;
                $validated['shipping_customer_id'] = null;
            }

            $salesOrder = SalesOrder::create($validated);
            $lastInsertedId = $salesOrder->id;


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
                SalesOrderReminder::create([
                    'sales_order_id' => $lastInsertedId,
                    'user_id' => $member->id,
                    'user_type' => 'team',
                    'last_sent_at' => null,
                    'is_completed' => 0,
                ]);
            }

            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->update([
                        'address' => $request->address ?? $customer->address,
                        'mobile_number' => $request->phone ?? $customer->mobile_number,
                    ]);
                }
            }

            if (isset($request->quotation_id) && $request->quotation_id != '') {
                $quotation = Quotation::find($request->quotation_id);
                if ($quotation) {
                    $old_quotation_status_id = $quotation->sales_status_id;
                    $currentStatus = SalesStatus::find($quotation->sales_status_id);
                    $nextStatus = SalesStatus::where('company_id', $quotation->company_id)
                        ->where('salestype', 'Quotation')
                        ->where('display_order', '>', $currentStatus->display_order)
                        ->where('parent_status_id', $currentStatus->id)
                        ->orderBy('display_order', 'asc')
                        ->first();

                    // $status = SalesStatus::where('company_id', $validated['company_id'])
                    //     ->where('salestype', 'Quotation')
                    //     ->orderBy('display_order', 'desc')
                    //     ->first();
                    $quotation->sales_order_id = $lastInsertedId;
                    $quotation->sales_status_id = $nextStatus->id;
                    $quotation->save();

                    //for send mail
                    SendQuotationJob::dispatch($request->quotation_id, 'update', $old_quotation_status_id, $nextStatus->id);
                }
            }

            $company = Company::where('id', $validated['company_id'])->select('quantity', 'tax_type')->first();

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {

                    $productData = Product::find($request->product_id[$key]);

                    $total_remaining_qty = 0;
                    if ($request->unit[$key] == 'kg') {
                        $total_remaining_qty = $request->quantity[$key] ?? 0;
                    } else if ($request->unit[$key] == 'pcs') {
                        $total_remaining_qty = $request->quantity[$key] ?? 0;
                    } else if ($request->unit[$key] == 'gross') {
                        $total_remaining_qty = $productData->grosse_per_qty * $request->quantity[$key];
                    }

                    $data = [
                        'company_id' => $validated['company_id'],
                        'sales_order_id' => $lastInsertedId,
                        'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                        'product_id' => $request->product_id[$key],
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'price_list_product_id' => $request->price_list_product_id[$key] ?? null,
                        'display_order' => ($key + 1),
                        'product_name' => $product,
                        'product_code' => $request->product_code[$key] ?? null,
                        'product_description' => $request->product_description[$key],
                        'hsn_code' => $request->hsn_code[$key],
                        'outer_quantity' => $request->outer[$key] ?? null,
                        'inner_quantity' => $request->inner[$key] ?? null,
                        'loose_quantity' => $request->loose[$key] ?? null,
                        'kg_quantity' => $request->kg[$key] ?? null,
                        'unit' => $request->unit[$key],
                        'quantity' => $request->quantity[$key],
                        'remaining_quantity' => $total_remaining_qty,
                        'price_in_amount' => $request->price[$key],
                        'discount_flat' => $request->discount_flat[$key] ?? null,
                        'discount_percentage' => $request->discount_percentage[$key] ?? null,
                        'rate_in_amount' => $request->rate_in_amount[$key],
                        'amount' => $request->amount[$key],
                        ...(!empty($request->item_id[$key]) ? ['quotation_item_id' => $request->item_id[$key]] : []),
                    ];

                    if ($company->tax_type == 'product_wise') {
                        $data += [
                            'taxable_amount' => $request->taxable_amount[$key] ?? null,
                            'sub_total' => $request->subtotal[$key] ?? null,
                        ];

                        if ($request->is_igst == 1) {
                            $data += [
                                'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                                'gst_amount' => $request->gst_amount_array[$key] ?? null,
                                'cgst_in_percentage' => null,
                                'cgst_amount' => null,
                                'sgst_in_percentage' => null,
                                'sgst_amount' => null,
                            ];
                        } else {
                            $data += [
                                'gst_in_percentage' => null,
                                'gst_amount' => null,
                                'cgst_in_percentage' => $request->cgst_in_percentage[$key] ?? null,
                                'cgst_amount' => $request->cgst_amount_array[$key] ?? null,
                                'sgst_in_percentage' => $request->sgst_in_percentage[$key] ?? null,
                                'sgst_amount' => $request->sgst_amount_array[$key] ?? null,
                            ];
                        }
                    } else {
                        $data += [
                            'taxable_amount' => '0.00',
                            'gst_in_percentage' => '0.00',
                            'gst_amount' => '0.00',
                            'cgst_in_percentage' => '0.00',
                            'cgst_amount' => '0.00',
                            'sgst_in_percentage' => '0.00',
                            'sgst_amount' => '0.00',
                            'sub_total' => '0.00',
                        ];
                    }

                    $salesorderItems = SalesOrderItems::create($data);

                    if (isset($request->quotation_id) && $request->quotation_id != '' && isset($request->item_id[$key]) && $request->item_id[$key] != '') {
                        $quotation = QuotationItems::find($request->item_id[$key]);
                        if ($quotation) {
                            $quotation->sales_order_item_id = $salesorderItems->id;
                            $quotation->save();
                        }
                    }
                }
                //for send mail
                SendSalesOrderJob::dispatch($lastInsertedId, 'create');
            }

            if ($request->ajax()) {
                return $this->sendResponse($salesOrder, $modules['title'] . ' create successfully');
            }
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            // dd($e->getMessage());
            if ($request->ajax()) {
                return $this->sendError($e->getMessage());
            }
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

        $dataDelete = SalesOrder::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                SalesOrderReminder::where('sales_order_id', $id)->forceDelete();


                if ($dataDelete->delete()) {
                    AccountLedger::where('company_id', $dataDelete->company_id)->where('sales_order_id', $id)->whereNull('receipt_id')->delete();

                    $quotationData = Quotation::where("sales_order_id", $id)->first();
                    if ($quotationData) {

                        $sales_approve_id = Helper::getSalesStatusNameById($dataDelete->company_id, "app", 'Purchase Order');

                        $quotationData->update([
                            'sales_status_id' => $sales_approve_id,
                            'updated_by' => $loginUserId,
                        ]);
                    }

                    if ($isAjax) {
                        SendSalesOrderJob::dispatch($id, 'delete');
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
            $salesOrder = SalesOrder::withTrashed()->findOrFail($request?->id);
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
            $salesOrder = SalesOrder::with(['customer'])->withTrashed()->findOrFail($id);
            $salesOrder->restore();

            $dataAccountLeadger = [
                'company_id' => $salesOrder->company_id ?? null,
                'sales_order_no' => $salesOrder->sales_order_no ?? null,
                'customer_type_id' => optional($salesOrder->customer)->customer_type_id ?? null,
                'customer_id' => $salesOrder->customer_id ?? null,
                'reference_type' => 'order',
                'sales_order_id' => $id,
                'payment_mode' => 'debit',
                'amount' => $salesOrder->grand_total ?? null,
                'debit_amount' => $salesOrder->grand_total ?? null,
            ];
            Helper::storeAccountLedger($dataAccountLeadger);

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
            $edit = SalesOrder::with('customer.city', 'customer.state', 'customer.country', 'price_list', 'sales_order_items.product')->findOrFail($id);
            // dd($edit);
            View::share('edit', $edit);
            $generateSalesOrderNo = false;
            View::share('generateSalesOrderNo', $generateSalesOrderNo);
            $salesOrderRepeatCount = SalesOrder::withTrashed()->where('parent_sales_order_id', $edit->sales_order_no)->where('sales_order_repeat', 'repeat')->count();
            View::share('salesOrderRepeatCount', $salesOrderRepeatCount);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(SalesOrderRequest $request, string $id)
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
        // dd($request->all(), $validated);
        // DB::beginTransaction();
        try {
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    if (is_null($product) || trim($product) === '') {
                        if ($request->ajax()) {
                            return $this->sendError("Product name is required.", ["product_name.$key" => "Product name is required."]);
                        }
                        return back()->withErrors(["product_name.$key" => "Product name is required."]);
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        if ($request->ajax()) {
                            return $this->sendError("Quantity must be greater than 0.", ["quantity.$key" => "Quantity must be greater than 0."]);
                        }
                        return back()->withErrors(["quantity.$key" => "Quantity must be greater than 0."]);
                    }

                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
                        if ($request->ajax()) {
                            return $this->sendError("Price must be greater than 0.", ["price.$key" => "Price must be greater than 0."]);
                        }
                        return back()->withErrors(["price.$key" => "Price must be greater than 0."]);
                    }
                }
            }

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = SalesOrder::findOrFail($id);
            if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int) ($validated['total_taxable']) <= 0) {
                if ($request->ajax()) {
                    return $this->sendError('Total Taxable must be greater than 0.');
                }
                return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            }


            $company = Company::where('id', $validated['company_id'])->select('quantity', 'tax_type')->first();

            if ($updateData) {
                unset($validated['id']);

                if ($updateData->grand_total != $validated['grand_total']) {
                    $salesStatus = SalesStatus::where('salestype', 'Sales Orders')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
                    $validated['sales_status_id'] = $salesStatus->id ?? null;
                    AccountLedger::where('company_id', $validated['company_id'])->where('sales_order_id', $id)->whereNull('receipt_id')->delete();
                }

                if ($request->has('same_as_billing') || (empty($validated['shipping_customer_id']) && (empty($validated['shipping_address']) || trim($validated['shipping_address'] ?? '') === trim($validated['billing_address'] ?? '')))) {
                    $validated['shipping_address'] = $validated['billing_address'] ?? null;
                    $validated['shipping_gst_no'] = $validated['gst_no'] ?? null;
                    $validated['shipping_customer_id'] = null;
                }

                $updateData->update($validated);

                if (!empty($validated['customer_id'])) {
                    $customer = Customer::find($validated['customer_id']);
                    if ($customer) {
                        $customer->update([
                            'address' => $request->address ?? $customer->address,
                            'mobile_number' => $request->phone ?? $customer->mobile_number,
                        ]);
                    }
                }

                if ($request->has('product_name')) {
                    SalesOrderItems::where('sales_order_id', $id)->forceDelete();

                    foreach ($request->product_name as $key => $product) {


                        $productData = Product::find($request->product_id[$key]);

                        $total_remaining_qty = 0;
                        if ($request->unit[$key] == 'kg') {
                            $total_remaining_qty = $request->quantity[$key] ?? 0;
                        } else if ($request->unit[$key] == 'pcs') {
                            $total_remaining_qty = $request->quantity[$key] ?? 0;
                        } else if ($request->unit[$key] == 'gross') {
                            $total_remaining_qty = $productData->grosse_per_qty * $request->quantity[$key];
                        }


                        $data = [
                            'company_id' => $validated['company_id'],
                            'sales_order_id' => $id,
                            'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                            'product_id' => $request->product_id[$key],
                            'product_variant_price_id' => $request->product_variant_price_id[$key],
                            'price_list_product_id' => $request->price_list_product_id[$key] ?? null,
                            'display_order' => ($key + 1),
                            'product_name' => $product,
                            'product_code' => $request->product_code[$key] ?? null,
                            'product_description' => $request->product_description[$key] ?? null,
                            'hsn_code' => $request->hsn_code[$key] ?? null,
                            'outer_quantity' => $request->outer[$key] ?? null,
                            'inner_quantity' => $request->inner[$key] ?? null,
                            'loose_quantity' => $request->loose[$key] ?? null,
                            'kg_quantity' => $request->kg[$key] ?? null,
                            'unit' => $request->unit[$key] ?? null,
                            'quantity' => $request->quantity[$key] ?? null,
                            'remaining_quantity' => $total_remaining_qty,
                            'price_in_amount' => $request->price[$key] ?? null,
                            'discount_flat' => $request->discount_flat[$key] ?? null,
                            'discount_percentage' => $request->discount_percentage[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'amount' => $request->amount[$key] ?? null,
                            ...(!empty($request->item_id[$key]) ? ['quotation_item_id' => $request->item_id[$key]] : []),
                        ];

                        if ($company->tax_type == 'product_wise') {
                            $data += [
                                'taxable_amount' => $request->taxable_amount[$key] ?? null,
                                'sub_total' => $request->subtotal[$key] ?? null,
                            ];


                            if ($request->is_igst == 1) {
                                $data += [
                                    'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                                    'gst_amount' => $request->gst_amount_array[$key] ?? null,
                                    'cgst_in_percentage' => null,
                                    'cgst_amount' => null,
                                    'sgst_in_percentage' => null,
                                    'sgst_amount' => null,
                                ];
                            } else {
                                $data += [
                                    'gst_in_percentage' => null,
                                    'gst_amount' => null,
                                    'cgst_in_percentage' => $request->cgst_in_percentage[$key] ?? null,
                                    'cgst_amount' => $request->cgst_amount_array[$key] ?? null,
                                    'sgst_in_percentage' => $request->sgst_in_percentage[$key] ?? null,
                                    'sgst_amount' => $request->sgst_amount_array[$key] ?? null,
                                ];
                            }
                        } else {
                            $data += [
                                'taxable_amount' => '0.00',
                                'gst_in_percentage' => '0.00',
                                'gst_amount' => '0.00',
                                'cgst_in_percentage' => '0.00',
                                'cgst_amount' => '0.00',
                                'sgst_in_percentage' => '0.00',
                                'sgst_amount' => '0.00',
                                'sub_total' => '0.00',
                            ];
                        }

                        SalesOrderItems::create($data);
                    }

                    if (!empty($validated['quotation_id'])) {
                        $QuotationData = Quotation::findOrFail($validated['quotation_id']);
                        $currentStatus = SalesStatus::find($QuotationData->sales_status_id);
                        if ($currentStatus) {
                            $nextStatus = SalesStatus::where('company_id', $validated['company_id'])
                                ->where('salestype', 'Quotation')
                                ->where('display_order', '>', $currentStatus->display_order)
                                ->where('parent_status_id', $currentStatus->id)
                                ->orderBy('display_order', 'asc')
                                ->first();

                            // Update only if next status exists
                            if ($nextStatus) {
                                $QuotationData->update([
                                    'sales_status_id' => $nextStatus->id,
                                    'updated_by' => $loginUserId
                                ]);
                            }
                        }
                    }
                }
                if ($request->ajax()) {
                    return $this->sendResponse($updateData, $modules['title'] . ' update successfully');
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            if ($request->ajax()) {
                return $this->sendError('something went wrong please try again later');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return $this->sendError($e->getMessage());
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function change_sales_order_status(Request $request)
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
            $salesOrder = SalesOrder::with(['customer'])->withTrashed()->findOrFail($request?->id);
            if ($salesOrder) {
                $old_salesorder_status_id = $salesOrder->sales_status_id;
                if ($request->has('is_full_packing') && $request->is_full_packing == 1) {
                    $salesOrder->is_full_packing = 1;
                }
                $salesOrder->sales_status_id = $request->next_status_id;
                $salesOrder->save();

                $dataAccountLeadger = [
                    'company_id' => $salesOrder->company_id ?? null,
                    'sales_order_no' => $salesOrder->sales_order_no ?? null,
                    'customer_type_id' => optional($salesOrder->customer)->customer_type_id ?? null,
                    'customer_id' => $salesOrder->customer_id ?? null,
                    'reference_type' => 'order',
                    'sales_order_id' => $request?->id,
                    'payment_mode' => 'debit',
                    'amount' => $salesOrder->grand_total ?? null,
                    'debit_amount' => $salesOrder->grand_total ?? null,
                ];
                Helper::storeAccountLedger($dataAccountLeadger);

                if ($isAjax) {
                    SendSalesOrderJob::dispatch($request?->id, 'update', $old_salesorder_status_id, $request->next_status_id);
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

    public function convert_to_sales_order(string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            View::share('modules', $modules);
            View::share('quotation_id', $id);
            $edit = Quotation::with('customer', 'price_list', 'quotation_items.product', 'quotation_items.product_variant_price')->findOrFail($id);
            if (!empty($edit->customer_id)) {

                $customer = Customer::find($edit->customer_id);

                if ($customer && $customer->is_prospect == 1) {
                    $customer->update([
                        'is_prospect' => 0
                    ]);
                }
            }
            if (isset($edit) && isset($edit->sales_order_id) && !empty($edit->sales_order_id) && ($edit->sales_order_id > 0)) {
                $edit = SalesOrder::where('quotation_id', $id)->first();
            }
            View::share('edit', $edit);
            $generateSalesOrderNo = true;
            View::share('generateSalesOrderNo', $generateSalesOrderNo);
            return view($modules['folder_path'] . '.form');
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

        return Excel::download(new SalesOrderExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'SalesOrder-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $data = SalesOrder::select('*')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
            $data = $data->with(['company', 'customer', 'team_person', 'quotation', 'admin_person']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $data->where('sales_status_id', $request->parent_status_id);
            }

            if ($request->has('team_person') && $request->team_person) {
                $data->where('created_by', $request->team_person)->where('created_type', 'team');
            }

            if ($request->has('sales_order_date') && $request->sales_order_date) {

                $sales_order_date = $request->sales_order_date;
                $sales_order_date = explode(" to ", $sales_order_date);
                $fromDate = null;
                $toDate = null;

                if (count($sales_order_date) == 2) {
                    $fromDate = Helper::convert_date($sales_order_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($sales_order_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new SalesOrder())->getTable() . '.sales_order_date', ">=", $fromDate);
                    $data->whereDate((new SalesOrder())->getTable() . '.sales_order_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new SalesOrder())->getTable() . '.sales_order_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('sales_order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('quotation', function ($q2) use ($search) {
                            $q2->where('quotation_no', 'like', "%{$search}%");
                        });
                });
            }
            $SalesOrder = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('SalesOrder', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function clone(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $originalSalesOrder = SalesOrder::with('sales_order_items')->findOrFail($request?->id);

            if ($originalSalesOrder) {
                //get last quotation no
                $quotationCount = SalesOrder::withTrashed()->where('company_id', $originalSalesOrder->company_id)->count();
                $nextNumber = $quotationCount + 1;
                $salesOrderNo = 'SO-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);


                $status_data = SalesStatus::where('company_id', $company_id)
                    ->where('salestype', 'Sales Orders')
                    ->where('display_order', 1)
                    ->first();

                $newSalesOrder = $originalSalesOrder->replicate();
                $newSalesOrder->parent_sales_order_id = $originalSalesOrder->sales_order_no;
                $newSalesOrder->sales_order_no = $salesOrderNo;
                $newSalesOrder->quotation_id = '';
                $newSalesOrder->sales_order_repeat = $request->type === 'repeat' ? 'repeat' : '';
                $newSalesOrder->sales_status_id = $status_data->id;
                $newSalesOrder->created_by = $loginUserId;
                $newSalesOrder->created_at = now();
                $newSalesOrder->save();

                foreach ($originalSalesOrder->sales_order_items as $item) {
                    $newItem = $item->replicate();
                    $newItem->sales_order_id = $newSalesOrder->id;
                    $newItem->quotation_item_id = '';
                    $newItem->created_at = now();
                    $newItem->save();
                }

                return response()->json([
                    'status' => true,
                    'message' => $modules['title'] . ' repeat successfully!',
                    'quotation_id' => $newSalesOrder->id,
                ]);
            }
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function salesOrderShow($id)
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

            $salesOrder = SalesOrder::with(['company', 'customer.city', 'customer.state', 'customer.country', 'price_list', 'team_person', 'team_person.team_role', 'sales_order_items.product', 'term_condition', 'transport_by', 'transport_detail', 'shipping_customer'])->findOrFail($id);

            // Load team_role for authenticated user if team_person
            if ($modules['currentGuard'] === 'team_person' && $modules['authLoginUserDetail']) {
                $modules['authLoginUserDetail']->load('team_role');
            }
            return view($modules['folder_path'] . '.' . $salesOrder->company->order_view_format, compact('salesOrder', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function salesOrderPrint($id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;

        try {

            View::share('modules', $modules);
            $salesOrder = SalesOrder::with(['company', 'customer.city', 'customer.state', 'customer.country', 'team_person', 'sales_order_items.product', 'term_condition', 'transport_by', 'transport_detail', 'shipping_customer'])->findOrFail($id);

            return view($modules['folder_path'] . '.single_print', compact('salesOrder', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function sendSalesOrderPdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $isWithoutPrice = $request->get('without_price') == '1';


        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        try {
            View::share('modules', $modules);
            $salesOrder = SalesOrder::with(['company', 'customer.city', 'customer.state', 'customer.country', 'team_person', 'sales_order_items.product', 'term_condition', 'transport_by', 'transport_detail', 'shipping_customer'])->findOrFail($id);

            $sidebarMainLogo = public_path('software/img/logo.png');
            $sidebarSmallLogo = public_path('software/img/ring.png');
            $sidebarHeaderLogo = '';

            // If company-specific logos exist, use them
            if ($salesOrder->company_id) {
                $company = Helper::getCompanyDetailById($salesOrder->company_id);

                if ($company && $company?->id) {
                    $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                    $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                    $sidebarHeaderLogo = $company?->order_header_logo ?? '';
                }
            }

            $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
            $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

            $company_id = $salesOrder->company_id;

            $viewPath = $modules['folder_path'] . '.single_pdf' ?? 'default_view';
            //return view($modules['folder_path'] . '.single_pdf', compact('quotation', 'company_id'));

            // Render Blade view to HTML
            $html = View::make($viewPath, compact('salesOrder', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo', 'isWithoutPrice', 'company_id'))->render();

            // Define folder path
            $year = now()->format('Y');
            $month = now()->format('m');
            $company_slug = Str::slug($salesOrder->company->company_name);
            $folder = "uploads/{$company_id}-{$company_slug}/salesorder/{$year}-{$month}/";
            $uploadedPath = public_path($folder);

            if (!file_exists($uploadedPath)) {
                mkdir($uploadedPath, 0755, true);
            }

            $filename = 'SalesOrder_' . $salesOrder->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            $filepath = $uploadedPath . $filename;

            // Generate PDF using DOMPDF
            Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->save($filepath);

            $data = [
                'customer_name' => $salesOrder->customer?->customer_name ?? '-',
                'company_name' => $salesOrder->company->company_name,
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
                $salesOrder->company_id,
                'Sales Orders',
                'salesorder_send_pdf',
                optional($salesOrder->customer)->email,
                optional($salesOrder->customer)->id,
                'Sales Orders/sendQuotationPdf',
                $data,
                $attachments
            );

            return Redirect::route($modules['route'] . '.salesOrderShow', [$salesOrder->id])->with('success', 'salesOrder PDF sent via email.');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function pendingSalesOrderNotifications(Request $request)
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

            $fullPacking = SalesStatus::where('statusslug', 'fs')
                ->where('salestype', 'Sales Orders')
                ->first();

            if (!$fullPacking) {
                return $this->sendResponse([], 'No pending packing slip reminders.');
            }

            $fullyStatusId = $fullPacking->id;
            $reminders = SalesOrderReminder::where('user_id', $loginUserId)
                ->where('user_type', $userType)
                ->where('is_completed', 0)
                ->whereNull('last_sent_at')
                ->whereHas('salesOrder', function ($q) use ($fullyStatusId, $loginUserId) {
                    $q->where('sales_status_id', '!=', $fullyStatusId)->where('created_by', '!=', $loginUserId);
                })
                ->with([
                    'salesOrder' => function ($query) {
                        $query->with(['company', 'customer'])
                            ->select('id', 'sales_order_no', 'company_id', 'customer_id', 'sales_status_id', 'created_by', 'created_type');
                    }
                ])
                ->select('id', 'sales_order_id', 'last_sent_at')->get();


            if ($reminders->isEmpty()) {
                return $this->sendResponse([], 'No pending order reminders.');
            }

            // Transform into frontend-friendly format
            $response = $reminders->map(function ($reminder) {
                $slip = $reminder->salesOrder;


                $name = '';
                if ($reminder?->salesOrder?->created_type == 'admin') {
                    $name = $reminder?->salesOrder?->admin_person?->name;
                } else {
                    $name = $reminder?->salesOrder?->team_person?->name;
                }

                return [
                    'id' => $reminder->id, // reminder ID (for marking completed/ignored)
                    'sales_order_id' => $reminder->sales_order_id,
                    'sales_order_no' => $slip?->sales_order_no ?? 'Unknown',
                    'title' => 'Pending Sales Order Review',
                    'body' => "Sales Order #{$slip?->sales_order_no} requires your attention.",
                    'module_name' => 'Sales Orders',
                    'module_id' => $reminder->sales_order_id,
                    'customer_name' => $slip?->customer?->customer_name ?? null,
                    'company_name' => $slip?->company?->company_name ?? null,
                    'name' => $name ?? null,
                ];
            });

            return $this->sendResponse($response, 'Pending order reminders retrieved successfully.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function markAsReadOrderNotifications(Request $request)
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
        $sales_order_id = $request->input('sales_order_id');
        try {
            $userType = $modules['currentGuard'] === 'team_person' ? 'team' : 'admin'; // Adjust based on your guard

            $reminder = SalesOrderReminder::where('id', $reminderId)
                ->where('sales_order_id', $sales_order_id)
                ->where('user_id', $loginUserId)
                ->where('user_type', $userType)
                ->firstOrFail();

            $reminder->is_completed = 1;
            $reminder->save();

            return $this->sendResponse(['success' => true], 'Order marked as completed.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
            }
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
