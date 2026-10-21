<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Services\EwayBillService;
use App\Services\EInvoiceService;
use App\Models\EwayBill;
use App\Exports\InvoiceExport;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Invoice;
use App\Models\SalesStatus;
use App\Models\InvoiceItems;
use App\Models\PackingSlip;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Company;
use App\Models\CompanySocial;
use App\Models\EwayBillVehicleHistory;
use App\Http\Requests\InvoiceRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\AccountLedger;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use ZipArchive;
use Illuminate\Support\Facades\File;
use App\Jobs\GenerateInvoicePdfJob;


use Illuminate\Support\Facades\Gate;


class InvoiceController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Invoice',
            'folder_path' => 'software.modules.sales_management.invoice',
            'route' => 'invoice',
            'table_name' => (new Invoice())->getTable(),
            'permisstion_prefix' => 'invoice',
            'module_name' => 'Invoice',
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

                (object) ['data' => "select_all", 'name' => 'select_all', 'td_label' => '<input type="checkbox" class="form-check-input" id="select_all">', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
                (object) ['data' => "invoice_order_no", 'name' => 'invoice_order_no', 'td_label' => 'Invoice No'],
                (object) ['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "invoice_order_date", 'name' => 'invoice_order_date', 'td_label' => 'Invoice Date', 'className' => ''],
                (object) ['data' => "invoice_status", 'name' => 'invoice_status', 'td_label' => 'Invoice Status', 'className' => ''],
                (object) ['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' => ''],
                (object) ['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' => ''],
                (object) ['data' => "grand_total", 'name' => 'grand_total', 'td_label' => 'Total Amount', 'className' => ''],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center'],
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
                $data = Invoice::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->with(['company', 'customer', 'team_person', 'admin_person']);
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

                        if ($request->has('filter_parent_status_id') && $request->filter_parent_status_id) {
                            $query->where('sales_status_id', $request->filter_parent_status_id);
                        }

                        if ($request->has('filter_team_person') && $request->filter_team_person) {
                            $query->where('created_by', $request->filter_team_person)->where('created_type', 'team');
                        }

                        if ($request->has('filter_invoice_order_date') && $request->filter_invoice_order_date) {

                            $filter_invoice_order_date = $request->filter_invoice_order_date;
                            $filter_invoice_order_date = explode(" to ", $filter_invoice_order_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_invoice_order_date) == 2) {
                                $fromDate = Helper::convert_date($filter_invoice_order_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_invoice_order_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Invoice())->getTable() . '.invoice_order_date', ">=", $fromDate);
                                $query->whereDate((new Invoice())->getTable() . '.invoice_order_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Invoice())->getTable() . '.invoice_order_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('invoice_order_no', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($q2) use ($search) {
                                        $q2->where('customer_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('invoice_order_date', function ($row) {
                        return \Carbon\Carbon::parse($row->invoice_order_date)->format('d-m-Y');
                    })
                    ->addColumn('invoice_order_no', function ($row) {
                        $reviseOrRepeat = '';
                        if (!empty($row->sales_order_repeat)) {
                            $reviseOrRepeat .= '<br><small><strong>Repeat:</strong> ' . $row->parent_invoice_id . '</small>';
                        }
                        return $row->invoice_order_no . ' ' . $reviseOrRepeat;
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
                    ->addColumn('invoice_status', function ($row) {
                        if (empty($row->sales_status)) {
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

                                $action .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                        </li>';


                                if ($row["irn"]) {
                                    // Check if cancelled or can be cancelled
                                    if ($row["irn_status"] === 'CNL' || $row["e_invoice_status"] === 'CANCELLED') {

                                    } else {
                                        // Check if within 24 hours
                                        $irnGeneratedAt = \Carbon\Carbon::parse($row["irn_generated_at"]);
                                        $canCancel = now()->diffInHours($irnGeneratedAt) <= 24;

                                        if ($canCancel) {
                                            $action .= '<li>
                                                <form action="' . route($modules["route"] . ".cancel_einvoice", [$row["id"]]) . '" method="POST" class="d-inline">
                                                    ' . csrf_field() . '
                                                    <input type="hidden" name="cancel_reason" value="2">
                                                    <input type="hidden" name="cancel_remarks" value="Wrong entry">
                                                    <button type="submit" class="dropdown-item mx-1">
                                                        <i class="fa-solid fa-xmark"></i> Cancel E-Invoice
                                                    </button>
                                                </form>
                                            </li>';
                                        } else {
                                            $action .= '<li>
                                                <a href="javascript:;" class="dropdown-item mx-1 disabled" title="Can only cancel within 24 hours">
                                                    <i class="fa-solid fa-xmark"></i> Cancel E-Invoice (Expired)
                                                </a>
                                            </li>';
                                        }
                                    }
                                }

                                if (!empty($row['eway_bill_no'])) {
                                    $action .= '<li>
                                        <a href="javascript:void(0);"
                                        class="dropdown-item mx-1 update-vehicle-btn"
                                        data-invoice-id="' . $row['id'] . '"
                                        data-eway-bill-no="' . $row['eway_bill_no'] . '"
                                        data-current-vehicle="' . ($row?->ewayBill?->currentVehicle?->vehicle_no ?? $row?->currentVehicle?->vehicle_no) . '"
                                        data-bs-toggle="modal"
                                        data-bs-target="#updateVehicleModal">
                                            <i class="fa-solid fa-truck"></i> Update Vehicle For E-Way Bill
                                        </a>
                                    </li>';
                                }

                                if (!empty($row['eway_bill_no'])) {
                                    $action .= '<li>
                                        <a href="javascript:void(0);"
                                        class="dropdown-item mx-1 view-vehicle-history-btn"
                                        data-invoice-id="' . $row['id'] . '"
                                        data-eway-bill-no="' . $row['eway_bill_no'] . '"
                                        data-bs-toggle="modal"
                                        data-bs-target="#vehicleHistoryModal">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Vehicle History
                                        </a>
                                    </li>';
                                }
                            }

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </li>';
                            }

                            if (!empty($row['e_invoice_irn']) || !empty($row['irn']) || ($row['is_e_invoiced'] ?? false)) {
                                $action .= '<li class="text-center">
                                    <a href="javascript:void(0);"
                                    onclick="window.open(\'' . route($modules['route'] . '.eInvoicePrint', [$row['id']]) . '\', \'_blank\', \'width=1200,height=800\');"
                                    class="dropdown-item text-center">
                                    <i class="fa-solid fa fa-print"></i> Einvoice Print
                                    </a>
                                </li>';
                            }

                            if (!empty($row['eway_bill_no']) || !empty($row['eway_bill_id']) || ($row['generate_eway'] ?? false)) {
                                $action .= '<li class="text-center">
                                    <a href="javascript:void(0);"
                                    onclick="window.open(\'' . route($modules['route'] . '.ewayBillPrint', [$row['id']]) . '\', \'_blank\', \'width=1200,height=800\');"
                                    class="dropdown-item text-center">
                                        <i class="fa-solid fa-truck"></i> E-Way Bill Print
                                    </a>
                                </li>';
                            }

                            $action .= '<li>
                                            <a href="' . route($modules["route"] . ".invoiceShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1"><i class="fa-solid fa fa-eye"></i> View </a>
                                        </li>';
                            $customerPhone = preg_replace('/[^0-9]/', '', $row?->customer?->mobile_number ?? '');

                            if ($customerPhone) {
                                $whatsappUrl = route('whatsapp.send', ['id' => $row->id, 'type' => 'invoice']);

                                $action .= '<li>
                                    <a href="' . $whatsappUrl . '" class="dropdown-item mx-1">
                                        <i class="fa-brands fa-whatsapp text-success"></i> Send WhatsApp
                                    </a>
                                </li>';
                            }

                            if (!empty($row?->sales_status_id)) {
                                $currentStatus = SalesStatus::find($row?->sales_status_id);

                                if ($currentStatus?->statusslug == 'pd') {
                                    $nextStatus = SalesStatus::where('statusslug', 'cd')
                                        ->where('salestype', 'Invoice')
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                } else {
                                    $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                        ->where('salestype', 'Invoice')
                                        ->where('parent_status_id', $currentStatus->id)
                                        ->where('id', '>', $currentStatus->id)
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                }

                                if (!empty($nextStatus)) {
                                    $action .= '<li class="dropdown-divider"></li>';
                                    $url = route($modules["route"] . ".change-invoice-status");
                                    if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                        $action .= '<li>
                                                    <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                </li>';
                                    }
                                }
                            } else {
                                $nextStatus = SalesStatus::where('company_id', $row->company->id)->where('parent_status_id', null)->where('salestype', 'Invoice')->orderBy('display_order', 'asc')->first();
                                if (!empty($nextStatus)) {
                                    if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                        $action .= '<li class="dropdown-divider"></li>
                                                <li>
                                                    <a data-url="' . route($modules["route"] . ".change-invoice-status") . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
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
                    ->rawColumns(['status', 'action', 'invoice_status', 'invoice_order_no', 'customer_name', 'select_all'])
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
            $is_edit_invoice = true;
            View::share('is_edit_invoice', $is_edit_invoice);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(InvoiceRequest $request, EwayBillService $ewayService)
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

        DB::beginTransaction();
        try {

            $nextStatus = SalesStatus::where('statusslug', 'ig')
                ->where('salestype', 'Packing Slip')
                ->orderBy('display_order', 'asc')
                ->first();

            $PackingSlip = PackingSlip::where('id', $request->packing_slip_id)->first();
            if ($PackingSlip && $nextStatus) {
                $PackingSlip->sales_status_id = $nextStatus->id;
                $PackingSlip->save();
            }

            // Validate and filter products - only process valid products
            $validProducts = [];
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    // Skip empty products
                    if (is_null($product) || trim($product) === '' || empty(trim($product))) {
                        continue;
                    }

                    // Check if product_id exists (required for valid product)
                    $productId = $request->product_id[$key] ?? null;
                    if (empty($productId)) {
                        continue; // Skip if no product_id
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        return back()->withErrors(["quantity.$key" => "Quantity must be greater than 0."]);
                    }

                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
                        return back()->withErrors(["price.$key" => "Price must be greater than 0."]);
                    }

                    // Store valid product key
                    $validProducts[] = $key;
                }
            }

            // Ensure at least one valid product exists
            if (empty($validProducts)) {
                return Redirect::back()->withErrors(['At least one product is required.'])->withInput();
            }
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;

            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Invoice')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }
            if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int) ($validated['total_taxable']) <= 0) {
                return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            }

            if ($request->has('same_as_billing') || (empty($validated['shipping_customer_id']) && (empty($validated['shipping_address']) || trim($validated['shipping_address'] ?? '') === trim($validated['billing_address'] ?? '')))) {
                $validated['shipping_address'] = $validated['billing_address'] ?? null;
                $validated['shipping_gst_no'] = $validated['gst_no'] ?? null;
                $validated['shipping_customer_id'] = null;
            }

            $Invoice = Invoice::create($validated);
            $lastInsertedId = $Invoice->id;

            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->update([
                        'address' => $request->address ?? $customer->address,
                        'mobile_number' => $request->phone ?? $customer->mobile_number,
                    ]);
                }
            }

            $company = Company::where('id', $validated['company_id'])->select('quantity', 'tax_type')->first();
            if (!empty($validProducts)) {
                $displayOrder = 1;
                foreach ($validProducts as $key) {
                    $product = $request->product_name[$key];
                    // If product does not exist, skip to next iteration
                    if (!$product) {
                        continue;
                    }
                    $data = [
                        'company_id' => $validated['company_id'],
                        'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                        'invoice_id' => $lastInsertedId,
                        'product_id' => $request->product_id[$key],
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'price_list_product_id' => $request->price_list_product_id[$key] ?? null,
                        'display_order' => $displayOrder++,
                        'product_code' => $request->product_code[$key] ?? null,
                        'product_name' => $product,
                        'product_description' => $request->product_description[$key],
                        'hsn_code' => $request->hsn_code[$key],
                        'outer_quantity' => $request->outer[$key] ?? null,
                        'inner_quantity' => $request->inner[$key] ?? null,
                        'loose_quantity' => $request->loose[$key] ?? null,
                        'kg_quantity' => $request->kg[$key] ?? null,
                        'unit' => $request->unit[$key],
                        'quantity' => $request->quantity[$key],
                        'remaining_quantity' => $request->quantity[$key],
                        'price_in_amount' => $request->price[$key],
                        'discount_flat' => $request->discount_flat[$key] ?? null,
                        'discount_percentage' => $request->discount_percentage[$key] ?? null,
                        'rate_in_amount' => $request->rate_in_amount[$key],
                        'amount' => $request->amount[$key],
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

                    // dd("Invoice Controller 499",$data);
                    $InvoiceItems = InvoiceItems::create($data);
                }
            }

            $updateData = Invoice::with(['company', 'customer', 'invoice_items'])->findOrFail($lastInsertedId);

            //Einvoice generate code
            // if ($request->submit_type === 'save_and_eway_bill') {
            // } else {
            //     $credential = CompanySocial::where('company_id', $updateData->company_id)->where('platform', 'einvoice')->firstOrFail();
            //     $eInvoiceService = new EInvoiceService();
            //     $eInvoiceService->authenticate($credential);
            //     if (!$updateData->irn) {
            //         try {
            //             $eInvoiceData = $eInvoiceService->generateIRN($updateData);
            //             // return back()->withErrors(['einvoice' => $eInvoiceData]);
            //         } catch (\Exception $e) {
            //             // $errorMessage = $e->getMessage();
            //             DB::rollBack();
            //             return Redirect::back()->withErrors(['IRN generation failed: ' . $e->getMessage()])->withInput();
            //         }
            //     }
            // }


            // if ($request->submit_type === 'save_and_eway_bill' && $request->grand_total >= 50000) {
            //     $this->generateEwayBill($updateData, $ewayService, $request->only(['lr_no', 'lr_date', 'vehicle_no', 'vehicle_type']));
            // }


            DB::commit();

            // Dispatch PDF generation job
            GenerateInvoicePdfJob::dispatch($lastInsertedId);

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function generateEwayBill(Invoice $invoice, EwayBillService $service, array $transportData = [])
    {

        $eway = EwayBill::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'vehicle_no' => $transportData['vehicle_no'] ?? '',
            'vehicle_type' => $transportData['vehicle_type'] ?? '',
            'lr_no' => $transportData['lr_no'] ?? '',
            'lr_date' => $transportData['lr_date'] ?? '',
            'transporter_id' => $invoice->transporter_id,
            'transporter_name' => $invoice->transporter_name,
            'transporter_gstin' => $invoice->transporter_gstin,
            'status' => 'PENDING',
            'created_type' => $invoice->created_type,
            'created_by' => $invoice->created_by,
        ]);
        $lastEwayInsertedId = $eway->id;

        $credential = CompanySocial::where('company_id', $invoice->company_id)
            ->where('platform', 'eway')
            ->whereNotNull('platform')
            ->firstOrFail();

        $service->authenticate($credential);
        $response = $service->generate($invoice, $transportData);


        if (empty($response['data']['ewayBillNo'])) {
            throw new \Exception($response['error'] ?? 'E-Way Bill generation failed');
        }

        $validityDays = max(1, ceil($invoice->distance / 100));
        $validUpto = now()->addDays($validityDays);

        $eway->update([
            'eway_bill_no' => $response['data']['ewayBillNo'],
            'eway_bill_date' => now(),
            'valid_upto' => $validUpto,
            'status' => 'GENERATED',
            'generated_at' => now(),
            'api_response' => $response,
            'transporter_id' => $invoice?->transport_details_id,
            'transporter_name' => $invoice?->transport_detail?->name,
            'transporter_gstin' => $invoice?->transport_detail?->transport_gst_id,
        ]);

        $invoice->update([
            'eway_bill_id' => $lastEwayInsertedId,
            'eway_bill_no' => $response['data']['ewayBillNo'],
            'eway_bill_status' => 'GENERATED',
            'generate_eway' => 1,
            'transporter_id' => $invoice->transport_details_id,
            'transporter_name' => $invoice?->transport_detail?->name,
            'transporter_gstin' => $invoice?->transport_detail?->transport_gst_id,
        ]);
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

        $dataDelete = Invoice::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
                    $packingSlipData = PackingSlip::where("id", $dataDelete->packing_slip_id)->first();
                    if ($packingSlipData) {
                        $sales_pending_id = Helper::getSalesStatusNameById($dataDelete->company_id, "pen", 'Packing Slip');
                        $packingSlipData->update([
                            'sales_status_id' => $sales_pending_id,
                            'updated_by' => $loginUserId,
                        ]);
                    }
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
            $Invoice = Invoice::withTrashed()->findOrFail($request?->id);
            if ($Invoice) {
                $Invoice->status = $request->update_status;
                $Invoice->save();
                if ($isAjax) {
                    return $this->sendResponse($Invoice, $modules['title'] . ' status update successfully.');
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
            $Invoice = Invoice::with(['customer'])->withTrashed()->findOrFail($id);
            $Invoice->restore();

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
            // Load invoice with invoice_items relationship to show only created products
            $edit = Invoice::with(['customer', 'price_list', 'invoice_items.product', 'invoice_items.product_variant_price', 'ewayBill', 'shipping_customer'])->findOrFail($id);

            // return $edit->invoice_items;
            View::share('edit', $edit);
            $is_edit_invoice = false;
            View::share('is_edit_invoice', $is_edit_invoice);
            $InvoiceRepeatCount = Invoice::withTrashed()->where('parent_invoice_id', $edit->invoice_order_no)->where('invoice_repeat', 'repeat')->count();
            View::share('InvoiceRepeatCount', $InvoiceRepeatCount);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function convert_to_invoice(string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            View::share('modules', $modules);
            View::share('packing_slip_id', $id);
            $edit = PackingSlip::with(['company', 'customer', 'sales_order.shipping_customer', 'packing_slip_item.sales_order_item', 'packing_slip_item.product', 'packing_slip_item.product_variant_price'])->findOrFail($id);

            // Grouping packing slip items by product_variant_price_id and unit
            $groupedItems = $edit->packing_slip_item->groupBy(function ($item) {
                return $item->product_id . '-' . $item->unit;
            })->map(function ($group) {
                $firstItem = $group->first();
                $totalQty = $group->sum('total_qty');

                // Recalculate financial fields based on the summed quantity
                $price = $firstItem->sales_order_item->price_in_amount ?? 0;
                $amount = $totalQty * $price;

                // Note: Other fields like taxable_amount, gst_amount etc. will be recalculated by the JS on the form
                // but we update the object here for the initial display
                $firstItem->total_qty = $totalQty;
                $firstItem->amount = $amount;

                // Sum other quantity related fields if needed for display
                $firstItem->no_of_carton = $group->sum('no_of_carton');
                $firstItem->total_packet = $group->sum('total_packet');

                return $firstItem;
            })->values();

            $edit->setRelation('packing_slip_item', $groupedItems);

            $edit->is_gst_applicable = $edit->sales_order?->is_gst_applicable ?? 0;
            $edit->gst_no = $edit->sales_order?->gst_no ?? $edit->customer?->gst_number ?? null;

            $soBilling = trim($edit->sales_order?->billing_address ?? ($edit->billing_address ?? ''));
            $soShipping = trim($edit->sales_order?->shipping_address ?? ($edit->shipping_address ?? ''));
            $soCustomerId = $edit->sales_order?->shipping_customer_id ?? null;

            if (empty($soCustomerId) && ($soBilling === $soShipping || empty($soShipping))) {
                $edit->billing_address = $soBilling;
                $edit->shipping_address = $soBilling;
                $edit->shipping_customer_id = null;
                $edit->shipping_gst_no = $edit->gst_no;
            } else {
                $edit->billing_address = $soBilling;
                $edit->shipping_address = $soShipping;
                $edit->shipping_customer_id = $soCustomerId;
                $edit->shipping_gst_no = $edit->sales_order?->shipping_gst_no ?? null;
            }
            View::share('edit', $edit);
            $is_edit_invoice = true;
            View::share('is_edit_invoice', $is_edit_invoice);


            $transportData = Helper::getTransportDetailsByCustomer($edit->customer_id);

            $transport_by_id = $transportData['transport_by_id'];
            $transport_details_id = $transportData['transport_details_id'];

            View::share('transport_by_id', $transport_by_id);
            View::share('transport_details_id', $transport_details_id);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(InvoiceRequest $request, string $id, EwayBillService $ewayService)
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
        // dd($request->all(),$validated);
        try {
            // Validate and filter products - only process valid products
            $validProducts = [];
            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    // Skip empty products
                    if (is_null($product) || trim($product) === '' || empty(trim($product))) {
                        continue;
                    }

                    // Check if product_id exists (required for valid product)
                    $productId = $request->product_id[$key] ?? null;
                    if (empty($productId)) {
                        continue; // Skip if no product_id
                    }

                    $quantity = $request->quantity[$key] ?? null;
                    if (!isset($quantity) || $quantity == '' || floatval($quantity) <= 0) {
                        return back()->withErrors(["quantity.$key" => "Quantity must be greater than 0."]);
                    }

                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
                        return back()->withErrors(["price.$key" => "Price must be greater than 0."]);
                    }

                    // Store valid product key
                    $validProducts[] = $key;
                }
            }

            // Ensure at least one valid product exists
            if (empty($validProducts)) {
                return Redirect::back()->withErrors(['At least one product is required.'])->withInput();
            }

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }
            $validated['updated_by'] = $loginUserId;
            $updateData = Invoice::with('ewayBill')->findOrFail($id);
            $old_vehicle_no = $updateData?->ewayBill?->vehicle_no;

            if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int) ($validated['total_taxable']) <= 0) {
                return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            }


            $company = Company::where('id', $validated['company_id'])->select('quantity', 'tax_type')->first();

            if ($updateData) {
                unset($validated['id']);

                // if($updateData->grand_total != $validated['grand_total']){
                //     $salesStatus = SalesStatus::where('salestype', 'Invoice')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
                //     $validated['sales_status_id'] = $salesStatus->id ?? null;
                // }

                $salesStatus = SalesStatus::where('salestype', 'Invoice')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
                $validated['sales_status_id'] = $salesStatus->id ?? null;

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


                if (!empty($validProducts)) {
                    // Delete existing invoice items before creating new ones
                    InvoiceItems::where('invoice_id', $id)->forceDelete();

                    $displayOrder = 1;
                    foreach ($validProducts as $key) {
                        $product = $request->product_name[$key];
                        // If product does not exist, skip to next iteration
                        if (!$product) {
                            continue;
                        }

                        $data = [
                            'company_id' => $validated['company_id'],
                            'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                            'invoice_id' => $id,
                            'product_id' => $request->product_id[$key],
                            'product_variant_price_id' => $request->product_variant_price_id[$key],
                            'price_list_product_id' => $request->price_list_product_id[$key] ?? null,
                            'display_order' => $displayOrder++,
                            'product_code' => $request->product_code[$key] ?? null,
                            'product_name' => $product,
                            'product_description' => $request->product_description[$key] ?? null,
                            'hsn_code' => $request->hsn_code[$key] ?? null,
                            'outer_quantity' => $request->outer[$key] ?? null,
                            'inner_quantity' => $request->inner[$key] ?? null,
                            'loose_quantity' => $request->loose[$key] ?? null,
                            'kg_quantity' => $request->kg[$key] ?? null,
                            'unit' => $request->unit[$key] ?? null,
                            'quantity' => $request->quantity[$key] ?? null,
                            'remaining_quantity' => $request->quantity[$key] ?? null,
                            'price_in_amount' => $request->price[$key] ?? null,
                            'discount_flat' => $request->discount_flat[$key] ?? null,
                            'discount_percentage' => $request->discount_percentage[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'amount' => $request->amount[$key] ?? null,
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

                        InvoiceItems::create($data);
                    }
                }

                $updateData = Invoice::with(['company', 'customer', 'invoice_items'])->findOrFail($id);

                // if ($request->submit_type === 'save_and_eway_bill') {

                // } else {
                //     $credential = CompanySocial::where('company_id', $updateData->company_id)->where('platform', 'einvoice')->firstOrFail();
                //     $eInvoiceService = new EInvoiceService();
                //     $eInvoiceService->authenticate($credential);

                //     if (!$updateData->irn) {
                //         try {
                //             $eInvoiceData = $eInvoiceService->generateIRN($updateData);
                //         } catch (\Exception $e) {
                //             return Redirect::back()->withErrors(['IRN generation failed: ' . $e->getMessage()])->withInput();
                //         }
                //     }
                // }


                // // Eway bill generate code
                // if ($request->submit_type === 'save_and_eway_bill' && $request->grand_total >= 50000) {

                //     if (!empty($updateData->eway_bill_no)) {


                //         try {

                //             $credential = CompanySocial::where('company_id', $updateData->company_id)
                //                 ->where('platform', 'eway')
                //                 ->whereNotNull('platform')
                //                 ->firstOrFail();

                //             $ewayBill = EwayBill::where('invoice_id', $updateData->id)
                //                 ->where('eway_bill_no', $updateData->eway_bill_no)
                //                 ->first();

                //             // CHECK 24-HOUR LIMIT BEFORE CANCELLATION
                //             if ($ewayBill && $ewayBill->created_at->diffInHours(now()) > 24) {
                //                 return Redirect::back()->withErrors('E-Way Bill can only be cancelled within 24 hours of generation. Current E-Way Bill was generated on ' . $ewayBill->created_at->format('d-m-Y h:i A'))->withInput();
                //             }

                //             $ewayService->authenticate($credential);
                //             $cancelResult = $ewayService->cancel(
                //                 $updateData->eway_bill_no,
                //                 '2', // Cancel reason code (2 = Data Entry Mistake)
                //                 'Regenerating E-Way Bill due to invoice update'
                //             );

                //             $ewayBill = EwayBill::where('invoice_id', $updateData->id)
                //                 ->where('eway_bill_no', $updateData->eway_bill_no)
                //                 ->first();

                //             // Update EwayBill table - set status to CANCELLED
                //             if ($ewayBill) {
                //                 $ewayBill->update([
                //                     'status' => 'CANCELLED',
                //                     'cancelled_at' => now(),
                //                     'cancelled_by' => $loginUserId,
                //                     'updated_by' => $loginUserId,
                //                     'cancel_reason' => 'Data Entry Mistake',
                //                     'cancel_remarks' => 'Regenerating E-Way Bill due to invoice update',
                //                     'cancel_response' => $cancelResult,
                //                     'deleted_by' => $loginUserId,
                //                 ]);

                //                 $ewayBill->delete();
                //             }

                //             // Clear the old E-Way Bill number
                //             $updateData->update([
                //                 'eway_bill_no' => null,
                //                 'eway_bill_status' => 'CANCELLED',
                //                 'generate_eway' => 0,
                //             ]);

                //             $this->generateEwayBill($updateData, $ewayService, $request->only(['lr_no', 'lr_date', 'vehicle_no', 'vehicle_type']));
                //         } catch (\Exception $cancelException) {
                //             return Redirect::route($modules['route'] . '.index')->withErrors($cancelException->getMessage());
                //         }
                //         //}

                //     } else {
                //         $this->generateEwayBill($updateData, $ewayService, $request->only(['lr_no', 'lr_date', 'vehicle_no', 'vehicle_type']));
                //     }
                // }
                // Dispatch PDF generation job
                GenerateInvoicePdfJob::dispatch($id);

                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    /**
     * Update Vehicle Number in E-Way Bill
     */
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
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoice,id',
            'vehicle_no' => 'required|string|max:20',
            'vehicle_type' => 'nullable|in:R,RW,O',
            'reason_code' => 'required|in:1,2,3,4',
            'reason_remark' => 'nullable|string|max:100',
            'lr_no' => 'nullable|string|max:50',
            'lr_date' => 'nullable|date',
        ], [
            'invoice_id.required' => 'Invoice ID is required',
            'invoice_id.exists' => 'Invoice not found',
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
            $invoice = Invoice::with(['company.city', 'ewayBill', 'transport_detail'])
                ->findOrFail($request->invoice_id);

            // E-Way Bill check
            if (empty($invoice->eway_bill_no)) {
                $msg = 'E-Way Bill not found for this invoice';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 404)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Same vehicle check
            if ($invoice->vehicle_no === $request->vehicle_no) {
                $msg = 'New vehicle number is same as current vehicle number';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 422)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Credentials
            $credential = CompanySocial::where('company_id', $invoice->company_id)
                ->where('platform', 'eway')
                ->whereNotNull('platform')
                ->firstOrFail();

            if (!$credential) {
                $msg = 'E-Way Bill credentials not found. Please configure E-Way Bill settings first.';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 404)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Authenticate E-Way API
            $ewayService = new EwayBillService();
            $ewayService->authenticate($credential);

            $reasonRemarks = $request->reason_remark
                ?? $this->getReasonRemarkText($request->reason_code);

            // API call
            try {
                $updateResult = $ewayService->updateVehicle(
                    $invoice->eway_bill_no,
                    $request->vehicle_no,
                    $invoice->transport_detail->name ?? '',
                    $request->vehicle_type ?? 'R',
                    $request->reason_code,
                    substr($invoice->company->city->name ?? '', 0, 50),
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

            // Fetch DB record
            $ewayBill = EwayBill::where('invoice_id', $invoice->id)
                ->where('eway_bill_no', $invoice->eway_bill_no)
                ->first();

            if (!$ewayBill) {
                DB::rollBack();
                $msg = 'E-Way Bill record not found in database';
                return $request->ajax()
                    ? $this->sendError($msg, [], [], 404)
                    : Redirect::back()->withErrors([$msg])->withInput();
            }

            // Update history
            EwayBillVehicleHistory::where('eway_bill_id', $ewayBill->id)
                ->update(['is_current' => false]);

            $updateResponseJson = json_encode($updateResult, JSON_UNESCAPED_UNICODE);


            $vehicleHistory = EwayBillVehicleHistory::create([
                'eway_bill_id' => $ewayBill->id,
                'eway_bill_no' => $invoice->eway_bill_no,
                'vehicle_no' => $request->vehicle_no,
                'vehicle_type' => $request->vehicle_type ?? 'R',
                'transport_mode' => '1',
                'transporter_name' => $invoice->transport_detail->name ?? '',
                'lr_no' => $request->lr_no,
                'lr_date' => $request->lr_date,
                'from_place' => substr($invoice->company->city->name ?? '', 0, 50),
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
            $ewayBill->update([
                'vehicle_no' => $request->vehicle_no,
                'vehicle_type' => $request->vehicle_type ?? 'R',
                'lr_no' => $request->lr_no,
                'lr_date' => $request->lr_date,
                'updated_by' => $loginUserId,
                'vehicle_update_response' => $updateResponseJson,
            ]);


            DB::commit();

            $successMessage = 'Vehicle updated successfully for E-Way Bill: ' . $invoice->eway_bill_no;

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => $successMessage,
                    'data' => [
                        'eway_bill_no' => $invoice->eway_bill_no,
                        'old_vehicle_no' => $invoice->getOriginal('vehicle_no'),
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



    /**
     * Get default reason remark text based on code
     */
    private function getReasonRemarkText($code)
    {
        $remarks = [
            '1' => 'Vehicle breakdown - Changed to another vehicle',
            '2' => 'Goods transhipment to another vehicle',
            '3' => 'Vehicle number updated',
            '4' => 'First time vehicle entry',
        ];

        return $remarks[$code] ?? 'Vehicle number updated';
    }

    /**
     * Get Vehicle History for E-Way Bill
     */
    public function getVehicleHistory($invoiceId)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);

            if (empty($invoice->eway_bill_no)) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-Way Bill not found'
                ], 404);
            }

            $ewayBill = EwayBill::where('invoice_id', $invoice->id)->orderBy('id', 'desc')->first();

            if (!$ewayBill) {
                return response()->json([
                    'success' => false,
                    'message' => 'E-Way Bill record not found'
                ], 404);
            }

            $vehicleHistory = EwayBillVehicleHistory::where('eway_bill_id', $ewayBill->id)
                ->orderBy('id', 'desc')
                ->get()
                ->map(function ($history) {
                    return [
                        'vehicle_no' => $history->vehicle_no,
                        'vehicle_type' => $history->vehicle_type === 'R' ? 'Regular' : 'Over Dimensional',
                        'reason' => $this->getReasonCodeText($history->reason_code),
                        'reason_remark' => $history->reason_remark,
                        'lr_no' => $history->lr_no,
                        'lr_date' => \Carbon\Carbon::parse($history->lr_date)->format('d-m-Y'),
                        'updated_by' => $history->updatedBy?->name ?? 'System',
                        'updated_at' => \Carbon\Carbon::parse($history->updated_at)->format('d-m-Y'),
                        'is_current' => $history->is_current,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'eway_bill_no' => $invoice->eway_bill_no,
                    'total_changes' => $vehicleHistory->count(),
                    'history' => $vehicleHistory,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    private function getReasonCodeText($code)
    {
        $reasons = [
            '1' => 'Break down',
            '2' => 'Transhipment',
            '3' => 'Others',
            '4' => 'First Time',
        ];

        return $reasons[$code] ?? 'Others';
    }

    public function change_invoice_status(Request $request)
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
            $Invoice = Invoice::with(['customer', 'sales_status', 'company'])->withTrashed()->findOrFail($request?->id);
            if ($Invoice) {
                // Update the status
                // The InvoiceObserver will automatically generate invoice number
                // when status changes to "Approve" (statusslug = 'app')
                $Invoice->sales_status_id = $request->next_status_id;
                $Invoice->save();

                if ($isAjax) {
                    return $this->sendResponse($Invoice, $modules['title'] . ' status update successfully.');
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

        return Excel::download(new InvoiceExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Invoice-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $data = Invoice::select('*')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
            $data = $data->with(['company', 'customer', 'team_person', 'admin_person', 'ewayBill']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $data->where('sales_status_id', $request->parent_status_id);
            }

            if ($request->has('team_person') && $request->team_person) {
                $data->where('created_by', $request->team_person)->where('created_type', 'team');
            }

            if ($request->has('invoice_order_date') && $request->invoice_order_date) {

                $invoice_order_date = $request->invoice_order_date;
                $invoice_order_date = explode(" to ", $invoice_order_date);
                $fromDate = null;
                $toDate = null;

                if (count($invoice_order_date) == 2) {
                    $fromDate = Helper::convert_date($invoice_order_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($invoice_order_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new Invoice())->getTable() . '.invoice_order_date', ">=", $fromDate);
                    $data->whereDate((new Invoice())->getTable() . '.invoice_order_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new Invoice())->getTable() . '.invoice_order_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('invoice_order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%{$search}%");
                        });
                });
            }
            $Invoice = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('Invoice', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function bulkDownload(Request $request)
    {
        ini_set('max_execution_time', 600);
        $ids = $request->ids;

        if (empty($ids)) {
            return Redirect::back()->withErrors(['No items selected.'])->withInput();
        }

        $invoices = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'sales_order'])->whereIn('id', $ids)->get();

        if ($invoices->isEmpty()) {
            return Redirect::back()->withErrors(['No matching records found.'])->withInput();
        }

        $zip = new ZipArchive;
        $zipFileName = 'Invoices_' . now()->format('Ymd_His') . '.zip';
        $zipFilePath = public_path('uploads/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {

            $modules = $this->modules;
            $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;

            foreach ($invoices as $Invoice) {

                $company_id = $Invoice->company_id;
                $company_slug = Str::slug($Invoice->company->company_name ?? 'company');
                $year = $Invoice->created_at->format('Y');
                $month = $Invoice->created_at->format('m');
                $folder = "uploads/{$company_id}-{$company_slug}/Invoice_pdf/{$year}-{$month}/";
                $uploadedPath = public_path($folder);

                if (!File::exists($uploadedPath)) {
                    File::makeDirectory($uploadedPath, 0777, true);
                }

                $safeInvoiceNo = str_replace(['/', '\\'], '-', $Invoice->invoice_order_no);
                $filename = 'Invoice_' . $safeInvoiceNo . '_' . $Invoice->id . '.pdf';
                $filepath = $uploadedPath . $filename;

                if (!File::exists($filepath)) {

                    $sidebarMainLogo = public_path('software/img/logo.png');
                    $sidebarSmallLogo = public_path('software/img/ring.png');
                    $sidebarHeaderLogo = '';

                    if ($Invoice->company_id) {
                        $company = Helper::getCompanyDetailById($Invoice->company_id);
                        if ($company && $company?->id) {
                            $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                            $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                            $sidebarHeaderLogo = $company?->order_header_logo ?? '';
                        }
                    }

                    $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
                    $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

                    $viewPath = $modules['folder_path'] . '.default_single_pdf';

                    try {
                        $html = View::make($viewPath, compact('Invoice', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo'))->render();

                        Pdf::loadHTML($html)
                            ->setPaper('A4', 'portrait')
                            ->save($filepath);
                    } catch (\Exception $e) {
                        // Continue to next invoice if PDF generation fails
                        continue;
                    }
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

    public function invoiceShow($id)
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

            $Invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'sales_order', 'ewayBill', 'shipping_customer'])->findOrFail($id);
            // return $Invoice->company->invoice_view_format;
            return view($modules['folder_path'] . '.' . $Invoice->company->invoice_view_format, compact('Invoice', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function invoicePrint($id)
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
            $Invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'sales_order', 'shipping_customer'])->findOrFail($id);

            $invoice_letter_head = '';
            if ($Invoice->company_id) {
                $company = Helper::getCompanyDetailById($Invoice->company_id);
                if ($company && $company?->id) {
                    $invoice_letter_head = $company?->invoice_letter_head ?? '';
                }
            }

            $invoice_letter_head = Helper::webpToBase64($invoice_letter_head);

            return view($modules['folder_path'] . '.default_single_print', compact('Invoice', 'modules', 'invoice_letter_head'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function sendInvoicePdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        $Invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'sales_order', 'ewayBill', 'shipping_customer'])->findOrFail($id);


        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarheaderLogo = '';

        // If company-specific logos exist, use them
        if ($Invoice->company_id) {
            $company = Helper::getCompanyDetailById($Invoice->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarheaderLogo = $company?->order_header_logo ?? '';
            }
        }

        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarheaderLogo = Helper::webpToBase64($sidebarheaderLogo);

        $authorisedSignature = $Invoice->company->authorised_signature ?? '';
        $authorisedSignature = Helper::webpToBase64($authorisedSignature);

        $company_id = $Invoice->company_id;

        $viewPath = $modules['folder_path'] . '.default_single_pdf' ?? 'default_view';

        // Render Blade view to HTML (falls back to .php)
        $html = View::make($viewPath, compact('Invoice', 'modules', 'sidebarMainLogo', 'sidebarheaderLogo', 'authorisedSignature'))->render();

        // Define folder path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($Invoice->company->company_name);
        $folder = "uploads/{$company_id}-{$company_slug}/Invoice/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'Invoice_' . $Invoice->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        $data = [
            'customer_name' => $Invoice->customer?->customer_name ?? '-',
            'company_name' => $Invoice->company->company_name,
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
            $Invoice->company_id,
            'Sales Orders',
            'Invoice_send_pdf',
            optional($Invoice->customer)->email,
            optional($Invoice->customer)->id,
            'Sales Orders/sendQuotationPdf',
            $data,
            $attachments
        );

        return Redirect::route($modules['route'] . '.invoiceShow', [$Invoice->id])->with('success', 'Invoice PDF sent via email.');
    }

    /**
     * Cancel E-Invoice IRN
     */
    public function cancelEInvoice(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        if (empty($invoice->irn)) {
            return redirect()->back()->with('error', 'No IRN found for this invoice');
        }

        if ($invoice->irn_status === 'CNL' || $invoice->e_invoice_status === 'CANCELLED') {
            return redirect()->back()->with('error', 'Invoice IRN is already cancelled');
        }

        if ($invoice->irn_generated_at && now()->diffInHours($invoice->irn_generated_at) > 24) {
            return redirect()->back()->with('error', 'IRN can only be cancelled within 24 hours of generation');
        }

        try {

            $credential = CompanySocial::where('company_id', $invoice->company_id)
                ->where('platform', 'einvoice')
                ->whereNotNull('platform')
                ->firstOrFail();

            $eInvoiceService = new EInvoiceService();
            $eInvoiceService->authenticate($credential);

            $cancelReason = $request->input('cancel_reason', '2');
            $cancelRemarks = $request->input('cancel_remarks', 'Wrong entry');

            $result = $eInvoiceService->cancelIRN($invoice, $cancelReason, $cancelRemarks);

            return redirect()->back()->with('success', 'E-Invoice cancelled successfully. You can now regenerate it.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel E-Invoice: ' . $e->getMessage());
        }
    }

    public function eInvoicePrint($id)
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
            $Invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail'])->findOrFail($id);

            $qrCodeDataUri = null;
            if (!empty($Invoice->signed_qr_code)) {
                $result = Builder::create()
                    ->writer(new PngWriter())
                    ->data($Invoice->signed_qr_code)
                    ->encoding(new Encoding('UTF-8'))
                    ->size(450)
                    ->margin(10)
                    ->build();

                $qrCodeDataUri = $result->getDataUri();
            }

            return view($modules['folder_path'] . '.einvoice_print', compact('Invoice', 'qrCodeDataUri'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
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
            $Invoice = Invoice::with(['company', 'customer', 'team_person', 'invoice_items', 'term_condition', 'transport_by', 'transport_detail', 'ewayBill'])->findOrFail($id);

            // After getting $response and $ewayBillNo, $ewayBillDate
            $ewayBillNo = $Invoice->ewayBill->eway_bill_no;
            $supplierGstin = $Invoice->company->gst_no;
            $genDateTime = \Carbon\Carbon::parse($Invoice->ewayBill->generated_at ?? now());

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

            return view($modules['folder_path'] . '.ewaybill_print', compact('Invoice', 'qrCodeDataUri'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

}
