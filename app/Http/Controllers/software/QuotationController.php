<?php

namespace App\Http\Controllers\software;

use App\Exports\QuotationExport;
use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Company;
use App\Models\QuotationItems;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SalesStatus;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Jobs\SendQuotationJob;


use App\Http\Requests\QuotationRequest;
use App\Models\AccountLedger;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

class QuotationController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Quotation',
            'folder_path' => 'software.modules.sales_management.quotation',
            'route' => 'quotation',
            'table_name' => (new Quotation())->getTable(),
            'permisstion_prefix' => 'quotation',
            'module_name' => 'Quotation',
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
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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
        //dd($request->all());

        try {

            $columns = [

                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Sr No.'],
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "quotation_no", 'name' => 'quotation_no', 'td_label' => 'Quotation No', 'className' =>  ''],
                (object)['data' => "quotation_date", 'name' => 'quotation_date', 'td_label' => 'Quotation Date', 'className' =>  ''],
                (object)['data' => "quotation_status", 'name' => 'quotation_status', 'td_label' => 'Quotation Status', 'className' =>  ''],
                (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  ''],
                (object)['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "grand_total", 'name' => 'grand_total', 'td_label' => 'Grand Total', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-center'],
            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            // dd(!$modules['company_id'], $columns);

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

            View::share('modules', $modules);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = Quotation::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $query->where('company_id', $teamPersonCompanyId);

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
                    ->addIndexColumn()
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

                        if ($request->has('filter_quotation_date') && $request->filter_quotation_date) {

                            $filter_quotation_date = $request->filter_quotation_date;
                            $filter_quotation_date = explode(" to ", $filter_quotation_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_quotation_date) == 2) {
                                $fromDate = Helper::convert_date($filter_quotation_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_quotation_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Quotation())->getTable() . '.quotation_date', ">=", $fromDate);
                                $query->whereDate((new Quotation())->getTable() . '.quotation_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Quotation())->getTable() . '.quotation_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('quotation_no', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($q2) use ($search) {
                                        $q2->where('customer_name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->addColumn('quotation_no', function ($row) {
                        $reviseOrRepeat = '';
                        if (!empty($row->quotation_revise)) {
                            $reviseOrRepeat .= '<br><small><strong>Revise:</strong> ' . $row->parent_quotation_id . '</small>';
                        }
                        if (!empty($row->quotation_repeat)) {
                            $reviseOrRepeat .= '<br><small><strong>Repeat:</strong> ' . $row->parent_quotation_id . '</small>';
                        }
                        return $row->quotation_no . ' ' . $reviseOrRepeat;
                    })
                    ->editColumn('quotation_date', function ($row) {
                        return $row->quotation_date ? \Carbon\Carbon::parse($row->quotation_date)->format('d-m-Y') : '';
                    })

                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row->admin_person->name;
                        } else {
                            return $row->team_person->name;
                        }
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode   = optional($row->customer)->client_code;
                        $customerName = $row->customer_name ?? optional($row->customer)->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return trim($clientCode . ' - ' . $customerName);
                        }

                        return $clientCode;
                    })

                    ->addColumn('company_name', function ($row) {
                        return $row->company->company_name;
                    })
                    ->addColumn('quotation_status', function ($row) {
                        if (empty($row->sales_status)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row->sales_status->status_color . '">' . $row->sales_status->statusname . '</span>';
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';
                        $action .= '
                            <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            //dd("gfjhgjghj");
                            if ($modules['update_permission']) {

                                $salesStatusesLast = SalesStatus::where('company_id', $row->company_id)
                                    ->where('salestype', 'Quotation')
                                    ->select("id")
                                    ->orderBy('display_order', 'desc')
                                    ->first();
                                //dd($salesStatusesLast->id,'------',$row->sales_status_id);
                                if (!empty($salesStatusesLast->id) && !empty($row->sales_status_id) && $salesStatusesLast->id > 0 && ((int)$row->sales_status_id != (int)$salesStatusesLast->id)) {

                                    $action .= '<li>
                                            <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                        </li>';
                                }
                            }

                            if ($modules['delete_permission']) {
                                $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </li>';
                            }

                            if (empty($row->sales_order_id)) {
                                if ($row?->sales_status?->is_revise == 1) {
                                    $action .= '<li>
                                            <a href="javascript:void(0)" data-clone="' . route($modules["route"] . ".clone") . '" class="dropdown-item record-clone m-1" data-id="' . $row->id . '" data-quatation-txt="revise" data-type="revise" data-bs-original-title="Revise Data">
                                                <i class="fa-solid fa fa-sync-alt"></i> Revise
                                            </a>
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
                                            <a href="' . route($modules["route"] . ".quotationShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1"><i class="fa-solid fa fa-eye"></i> View </a>
                                        </li>';

                            $whatsappUrl = route('whatsapp.send', ['id' => $row->id, 'type' => 'quotation']);
                            $action .= '<li>
                                            <a href="' . $whatsappUrl . '" class="dropdown-item mx-1">
                                                <i class="fa-brands fa-whatsapp text-success"></i> Send WhatsApp
                                            </a>
                                        </li>';

                            $modelData = [];

                            if (!$modules['company_id']) {
                                $modelData['company_name'] = $row->company->company_name;
                            }

                            $modelData['quotation_no'] = $row?->quotation_no;
                            $modelData['quotation_date'] = $row?->quotation_date;
                            $modelData['customer_name'] = $row?->customer?->customer_name;
                            $modelData['mobile_no'] = $row?->customer?->mobile_number;
                            $modelData['contact_person_name'] = $row?->customer?->contact_person_name;
                            // dd($modelData['mobile_no']);




                            $action .= '<li>
                                            <a href="javascript:void(0)" data-company_id="' . $row->company_id . '" data-company_name="' . $row->company->company_name . '" data-id="' . $row->id . '" class="dropdown-item add-followup mx-1" data-quotation="' . htmlspecialchars(json_encode($modelData), ENT_QUOTES, 'UTF-8') . '"><i class="fa-solid fa-plus"></i> Add Followup</a>
                                        </li>';


                            if (!empty($row?->sales_status)) {
                                $currentStatus = SalesStatus::find($row?->sales_status_id);
                                $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                    ->where('salestype', 'Quotation')
                                    ->where('display_order', '>', $currentStatus->display_order)
                                    ->where('parent_status_id', $currentStatus->id)
                                    ->orderBy('display_order', 'asc')
                                    ->first();

                                if (!empty($nextStatus)) {
                                    $action .= '<li class="dropdown-divider"></li>';

                                    if ($nextStatus->is_redirect == 1) {
                                        $url = route($modules["route"] . ".convert-to-sales-order", ['id' => $row->id]);


                                        if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                            $action .= '<li>
                                                    <a href="' . $url . '" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">' . $nextStatus->statusname . '</a>
                                                </li>';
                                        }
                                    } else {
                                        $url = route($modules["route"] . ".change-quotation-status");

                                        if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                            $action .= '<li>
                                                    <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                </li>';
                                        }
                                    }
                                }
                            } else {
                                $nextStatus = SalesStatus::where('company_id', $row->company_id)->where('salestype', 'Quotation')->orderBy('display_order', 'asc')->first();
                                if (!empty($nextStatus)) {
                                    if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                        $action .= '<li class="dropdown-divider"></li>
                                                <li>
                                                    <a data-url="' . route($modules["route"] . ".change-quotation-status") . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
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
                    ->rawColumns(['status', 'action', 'quotation_status', 'quotation_no', 'customer_name'])
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
            $selectedCustomerId = session('new_customer_id');  // read
            session()->forget('new_customer_id');              // one-time use
            $modules['selected_customer_id'] = $selectedCustomerId;

            View::share('modules', $modules);

            // ✅ Check if coming from Inquiry → Quotation
            $convertToQuotation = Session::get('newQuotationRequest');
            if ($convertToQuotation) {
                View::share('inquiry_to_quatation', $convertToQuotation);
                Session::forget('newQuotationRequest'); // remove after first use
            }

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(QuotationRequest $request)
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
        // dd($validated, request()->all());

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

                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
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
            $salesStatus = SalesStatus::where('salestype', 'Quotation')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
            $validated['sales_status_id'] = $salesStatus->id ?? null;
            if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int)($validated['total_taxable']) <= 0) {
                return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            }
            $quotation = Quotation::create($validated);
            $lastInsertedId = $quotation->id;


            if (!empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                if ($customer) {
                    $customer->update([
                        'address'        => $request->address     ?? $customer->address,
                        'mobile_number'  => $request->phone       ?? $customer->mobile_number,
                    ]);
                }
            }

            $company = Company::where('id', $validated['company_id'])->select('quantity', 'tax_type')->first();

            if ($request->has('product_name')) {
                foreach ($request->product_name as $key => $product) {
                    $data = [
                        'company_id'   => $validated['company_id'],
                        'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                        'quotation_id' => $lastInsertedId,
                        'product_id' => $request->product_id[$key],
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'price_list_product_id' => $request->price_list_product_id[$key] ?? null,
                        'display_order' => ($key + 1),
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
                            'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                            'gst_amount' => $request->gst_amount_array[$key] ?? null,
                            'cgst_in_percentage' => $request->cgst_in_percentage[$key] ?? null,
                            'cgst_amount' => $request->cgst_amount_array[$key] ?? null,
                            'sgst_in_percentage' => $request->sgst_in_percentage[$key] ?? null,
                            'sgst_amount' => $request->sgst_amount_array[$key] ?? null,
                            'sub_total' => $request->subtotal[$key] ?? null,
                        ];
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
                    QuotationItems::create($data);
                }
            }
            //for send mail
            SendQuotationJob::dispatch($lastInsertedId, 'create');

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
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

        $isAjax = ($request->ajax()) ? true : false;
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
        $dataDelete = Quotation::findOrFail($id);


        try {
            if ($dataDelete) {
                $validated['deleted_by'] = Auth::guard('admin_software')?->user()?->id;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {

                    AccountLedger::where('company_id', $dataDelete->company_id)->where('quotation_id', $id)->whereNull('receipt_id')->delete();
                    if ($isAjax) {
                        //for send mail
                        SendQuotationJob::dispatch($id, 'delete');
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
            $quotation = Quotation::withTrashed()->findOrFail($request?->id);
            if ($quotation) {
                $quotation->status = $request->update_status;
                $quotation->save();
                if ($isAjax) {
                    return $this->sendResponse($quotation, $modules['title'] . ' status update successfully.');
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
            $quotation = Quotation::with(['customer'])->withTrashed()->findOrFail($id);
            $quotation->restore();

            $dataAccountLeadger = [
                'company_id'       => $quotation->company_id ?? null,
                'quotation_no'     => $quotation->quotation_no ?? null,
                'customer_type_id' => optional($quotation->customer)->customer_type_id ?? null,
                'customer_id'      => $quotation->customer_id ?? null,
                'reference_type'   => 'quotation',
                'quotation_id'     => $id,
                'payment_mode'     => 'debit',
                'amount'           => $quotation->grand_total ?? null,
                'debit_amount'     => $quotation->grand_total ?? null,
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
            $edit = Quotation::with('customer', 'price_list')->findOrFail($id);
            View::share('edit', $edit);

            $quotationReviseCount = Quotation::withTrashed()->where('parent_quotation_id', $edit->quotation_no)->where('quotation_revise', 'revise')->count();
            View::share('quotationReviseCount', $quotationReviseCount);
            $quotationRepeatCount = Quotation::withTrashed()->where('parent_quotation_id', $edit->quotation_no)->where('quotation_repeat', 'repeat')->count();
            View::share('quotationRepeatCount', $quotationRepeatCount);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(QuotationRequest $request, string $id)
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

                    $price = $request->price[$key] ?? null;
                    if (!isset($price) || $price == '' || floatval($price) <= 0) {
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
            if (!isset($validated['total_taxable']) || $validated['total_taxable'] == '' || (int)($validated['total_taxable']) <= 0) {
                return Redirect::back()->withErrors(['Total Taxable must be greater than 0.'])->withInput();
            }
            $updateData = Quotation::findOrFail($id);

            $company = Company::where('id', $validated['company_id'])->select('quantity', 'tax_type')->first();

            if ($updateData) {
                unset($validated['id']);

                if ($updateData->grand_total != $validated['grand_total']) {
                    $salesStatus = SalesStatus::where('salestype', 'Quotation')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
                    $validated['sales_status_id'] = $salesStatus->id ?? null;
                    AccountLedger::where('company_id', $validated['company_id'])->where('quotation_id', $id)->whereNull('receipt_id')->delete();
                }
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
                    QuotationItems::where('quotation_id', $id)->delete();

                    foreach ($request->product_name as $key => $product) {
                        $data = [
                            'company_id'   => $validated['company_id'],
                            'is_combo' => (bool) ($request->is_combo[$key] ?? false),
                            'quotation_id' => $id,
                            'product_id' => $request->product_id[$key],
                            'product_variant_price_id' => $request->product_variant_price_id[$key],
                            'price_list_product_id' => $request->price_list_product_id[$key] ?? null,
                            'display_order' => ($key + 1),
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
                            'remaining_quantity' => $request->remaining_quantity[$key] ?? null,
                            'price_in_amount' => $request->price[$key] ?? null,
                            'discount_flat' => $request->discount_flat[$key] ?? null,
                            'discount_percentage' => $request->discount_percentage[$key] ?? null,
                            'rate_in_amount' => $request->rate_in_amount[$key] ?? null,
                            'amount' => $request->amount[$key] ?? null,
                        ];
                        if ($company->tax_type == 'product_wise') {
                            $data += [
                                'taxable_amount' => $request->taxable_amount[$key] ?? null,
                                'gst_in_percentage' => $request->gst_in_percentage[$key] ?? null,
                                'gst_amount' => $request->gst_amount_array[$key] ?? null,
                                'cgst_in_percentage' => $request->cgst_in_percentage[$key] ?? null,
                                'cgst_amount' => $request->cgst_amount_array[$key] ?? null,
                                'sgst_in_percentage' => $request->sgst_in_percentage[$key] ?? null,
                                'sgst_amount' => $request->sgst_amount_array[$key] ?? null,
                                'sub_total' => $request->subtotal[$key] ?? null,
                            ];
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
                        QuotationItems::create($data);
                    }
                }
                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function change_quotation_status(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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
            $quotation = Quotation::with(['customer'])->withTrashed()->findOrFail($request?->id);
            if ($quotation) {
                $old_quotation_status_id = $quotation->sales_status_id;
                $quotation->sales_status_id = $request->next_status_id;
                $quotation->save();

                $dataAccountLeadger = [
                    'company_id'       => $quotation->company_id ?? null,
                    'quotation_no'     => $quotation->quotation_no ?? null,
                    'customer_type_id' => optional($quotation->customer)->customer_type_id ?? null,
                    'customer_id'      => $quotation->customer_id ?? null,
                    'reference_type'   => 'quotation',
                    'quotation_id'     => $request?->id,
                    'payment_mode'     => 'debit',
                    'amount'           => $quotation->grand_total ?? null,
                    'debit_amount'     => $quotation->grand_total ?? null,
                ];

                Helper::storeAccountLedger($dataAccountLeadger);

                if ($isAjax) {
                    //for send mail
                    SendQuotationJob::dispatch($request?->id, 'update', $old_quotation_status_id, $request->next_status_id);
                    return $this->sendResponse($quotation, $modules['title'] . ' status update successfully.');
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
        // dd($modules);
        if (!$modules['excel_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(new QuotationExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Quotation-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $data = Quotation::select('*');
            $data = $data->where(function ($query) use ($modules, $loginUserId) {
                if (Auth::guard('team_person')->check()) {
                    $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                    $query->where('company_id', $teamPersonCompanyId);

                    if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                        $query->where('created_by', $loginUserId);
                    }
                }
            });
            $data = $data->with(['company', 'customer', 'team_person', 'quotation_items', 'term_condition', 'transport_by', 'transport_detail']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $data->where('sales_status_id', $request->parent_status_id);
            }

            if ($request->has('team_person') && $request->team_person) {
                $data->where('created_by', $request->team_person)->where('created_type', 'team');
            }

            if ($request->has('quotation_date') && $request->quotation_date) {

                $quotation_date = $request->quotation_date;
                $quotation_date = explode(" to ", $quotation_date);
                $fromDate = null;
                $toDate = null;

                if (count($quotation_date) == 2) {
                    $fromDate = Helper::convert_date($quotation_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($quotation_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new Quotation())->getTable() . '.quotation_date', ">=", $fromDate);
                    $data->whereDate((new Quotation())->getTable() . '.quotation_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new Quotation())->getTable() . '.quotation_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('quotation_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%{$search}%");
                        });
                });
            }
            $quotations = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('quotations', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function clone(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            $originalQuotation = Quotation::with('quotation_items')->findOrFail($request?->id);

            if ($originalQuotation) {
                //get last quotation no
                $quotationCount = Quotation::withTrashed()->where('company_id', $originalQuotation->company_id)->count();
                $nextNumber = $quotationCount + 1;
                $quotationNo = 'QUO-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                $newQuotation = $originalQuotation->replicate();
                $newQuotation->parent_quotation_id = $originalQuotation->quotation_no;
                $newQuotation->quotation_no = $quotationNo;
                $newQuotation->quotation_revise = $request->type === 'revise' ? 'revise' : '';
                $newQuotation->quotation_repeat = $request->type === 'repeat' ? 'repeat' : '';
                $newQuotation->created_by = $loginUserId;
                $newQuotation->created_at = now();
                $newQuotation->save();

                foreach ($originalQuotation->quotation_items as $item) {
                    $newItem = $item->replicate();
                    $newItem->quotation_id = $newQuotation->id;
                    $newItem->created_at = now();
                    $newItem->save();
                }

                //return Redirect::route($modules['route'] . '.quotationShow', $newQuotation->id)->withSuccess($modules['title'] . ' revised successfully!');

                return response()->json([
                    'status'       => true,
                    'message'      => $modules['title'] . ' revised successfully!',
                    'quotation_id' => $newQuotation->id,
                ]);
            }
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function quotationShow($id)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            $quotation = Quotation::with(['company', 'customer', 'team_person', 'quotation_items', 'term_condition', 'transport_by', 'transport_detail'])->findOrFail($id);
            return view($modules['folder_path'] . '.' . $quotation?->company?->quotation_view_format ?? 'default_view', compact('quotation', 'company_id', 'modules'));

            //return view($modules['folder_path'] . '.revise', compact('quotation','company_id','modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function quotationPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            View::share('modules', $modules);
            $quotation = Quotation::with(['company', 'customer', 'team_person', 'quotation_items', 'term_condition', 'transport_by', 'transport_detail'])->findOrFail($id);

            return view($modules['folder_path'] . '.single_print', compact('quotation', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function sendQuotationPdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $isWithoutPrice = $request->get('without_price') == '1';

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        $quotation = Quotation::with([
            'company',
            'customer',
            'team_person',
            'quotation_items',
            'term_condition',
            'transport_by',
            'transport_detail'
        ])->findOrFail($id);

        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // If company-specific logos exist, use them
        if ($quotation->company_id) {
            $company = Helper::getCompanyDetailById($quotation->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);

        $company_id = $quotation->company_id;

        $viewPath = $modules['folder_path'] . '.single_pdf' ?? 'default_view';

        //return view($modules['folder_path'] . '.single_pdf', compact('quotation', 'company_id','sidebarMainLogo','sidebarHeaderLogo','isWithoutPrice'));

        // Render Blade view to HTML
        $html = View::make($viewPath, compact('quotation', 'modules', 'sidebarMainLogo', 'sidebarHeaderLogo', 'isWithoutPrice'))->render();

        // Define folder path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($quotation->company->company_name);
        $folder = "uploads/{$company_id}-{$company_slug}/quotations/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'Quotation_' . $quotation->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF using DOMPDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        $data = [
            'customer_name' => $quotation->customer?->customer_name ?? '-',
            'company_name' => $quotation->company->company_name,
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

        Helper::mail_new($quotation->company_id, 'Quotation', 'quotation_send_pdf', optional($quotation->customer)->email, optional($quotation->customer)->id, 'Quotation/sendQuotationPdf', $data, $attachments);
        //Helper::mail_new($quotation->company_id,'Quotation','quotation_send_pdf',optional($quotation->customer)->email,optional($quotation->customer)->id,'Quotation/sendQuotationPdf',$data,$attachments);

        //return response()->download($filepath);
        return Redirect::route($modules['route'] . '.quotationShow', [$quotation->id])->with('success', 'Quotation PDF sent via email.');
    }
}
