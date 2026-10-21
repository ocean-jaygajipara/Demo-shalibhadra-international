<?php

namespace App\Http\Controllers\software;

use App\Exports\PaymentReceiptExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentReceiptRequest;
use App\Models\AccountLedger;
use App\Models\Company;
use App\Models\PaymentReceipt;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

use const Adminer\DB;

class PaymentReceiptController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Payment Receipt',
            'folder_path' => 'software.modules.account.payment-receipt',
            'route' => 'payment-receipt',
            'table_name' => (new PaymentReceipt())->getTable(),
            'permisstion_prefix' => 'payment-receipt',
            'module_name' => 'Payment Receipt',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,

        ];
        View::share("receipt_type", PaymentReceipt::$receipt_type);
        View::share("payment_by", PaymentReceipt::$payment_by);
        View::share("payment_mode", PaymentReceipt::$payment_mode);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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
        // DD($modules);
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);


        try {
            $columns = [
                // (object)['data' => 'id', 'name' => 'id', 'td_label' => 'Sr No.'],
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],

                (object)['data' => 'company.company_name', 'name' => 'company_id', 'td_label' => 'Company'],
                (object)['data' => 'receipt_no', 'name' => 'receipt_no', 'td_label' => 'Receipt No'],
                (object)['data' => 'date', 'name' => 'date', 'td_label' => 'Date'],
                (object)['data' => 'customer_type.name', 'name' => 'customer_type_id', 'td_label' => 'Customer Type'],
                (object)['data' => 'customer.customer_name', 'name' => 'customer_id', 'td_label' => 'Customer'],
                (object)['data' => 'receipt_type', 'name' => 'receipt_type', 'td_label' => 'Receipt Type'],
                (object)['data' => 'payment_by', 'name' => 'payment_by', 'td_label' => 'Payment By'],
                (object)['data' => 'payment_mode', 'name' => 'payment_mode', 'td_label' => 'Payment Mode'],
                (object)['data' => 'amount', 'name' => 'amount', 'td_label' => 'Amount'],
                (object)['data' => 'status', 'name' => 'status', 'td_label' => 'Status', 'className' => 'text-start'],
                (object)['data' => 'action', 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-start'],
            ];

            // Remove company column for team person/company user
            if ($modules['company_id']) {
                $columns = array_filter($columns, fn($col) => $col->name !== 'company_id');
                $columns = array_values($columns);
            }

            View::share('columns', $columns);
            View::share('route', $modules['route']);

            if ($request->ajax()) {
                $data = PaymentReceipt::with(['company', 'customer', 'customerType'])
                    ->when($modules['company_id'], function ($query) use ($modules, $loginUserId) {
                        $query->where('company_id', $modules['company_id']);
                        if (!($modules['all_data_permission'] ?? false) && ($modules['personal_data_permission'] ?? false)) {
                            $query->where('created_by', $loginUserId);
                        }
                    });

                // if ($modules['restore_permission']) {
                //     $data->withTrashed();
                // }

                if ($request->has('filter_company') && $request->filter_company) {
                    $company = Company::select('id', 'company_name', 'email', 'account_effect_by')->findOrFail($request->filter_company);
                    $accountEffect = $company->account_effect_by;

                    $data = $data->where(function ($query) use ($accountEffect) {
                        $query->where('receipt_type', $accountEffect)
                            ->orWhere('receipt_type', 'general');
                    });
                }

                $data = $data->orderByDesc('id');

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('search') && $request->search != '') {
                            $query->where(function ($q) use ($request) {
                                $q->where('receipt_no', 'like', "%" . $request->search . "%")
                                    ->orWhereHas('customer', function ($q2) use ($request) {
                                        $q2->where('customer_name', 'like', "%" . $request->search . "%");
                                    });
                            });
                        }
                        if ($request->has('customer_type_id') && $request->customer_type_id != '') {
                            $query->whereHas('customerType', function ($q) use ($request) {
                                $q->where('customer_type_id', $request->customer_type_id);
                            });
                        }
                        if ($request->receipt_type) {
                            $query->where('receipt_type', $request->receipt_type);
                        }

                        if ($request->status && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                        if ($request->filled('date')) {
                            $dateRange = explode(' to ', $request->date);

                            if (count($dateRange) === 2) {
                                $fromDate = \Carbon\Carbon::createFromFormat('d-m-Y', trim($dateRange[0]))->startOfDay();
                                $toDate = \Carbon\Carbon::createFromFormat('d-m-Y', trim($dateRange[1]))->endOfDay();

                                $query->whereBetween('date', [$fromDate, $toDate]);
                            }
                        }
                    })
                    ->editColumn('status', function ($row) {
                        $status = strtolower($row->status);
                        if ($status === 'pending') {
                            return '<button class="btn btn-warning btn-sm change-status-btn" data-id="' . $row->id . '" data-company_id="' . $row->company_id . '">Pending</button>';
                        } elseif ($status === 'approve') {
                            return '<span class="btn btn-success btn-sm">Approve</span>';
                        } else {
                            return '<span class="btn btn-secondary btn-sm">' . ucfirst($status) . '</span>';
                        }
                    })


                    ->editColumn('date', function ($row) {
                        return \Carbon\Carbon::parse($row->date)->format('d/m/Y'); // dd/mm/yy format
                    })


                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';

                        if (!$row?->deleted_at) {

                            if ($modules['update_permission']) {
                                if (strtolower($row->status) !== 'approve') {
                                    $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                                } else {
                                    // $btn .= '<span class="btn btn-light btn-icon mx-1 disabled">-</span>';
                                }
                            }


                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                            }
                        } else {

                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }

                        return $btn ?: '-';
                    })

                    ->rawColumns(['status', 'action'])
                    ->make(true);
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['error' => $e->getMessage()], 500)
                : Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
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
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }


        try {
            View::share('modules', $modules);
            $company_id = $modules['company_id'];

            $quotations = Quotation::select('id', 'quotation_no')->get();
            $orders = SalesOrder::select('id', 'sales_order_no')->get();
            $receipt_type = PaymentReceipt::$receipt_type;

            return view($modules['folder_path'] . '.form', compact('modules', 'quotations', 'orders', 'receipt_type'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentReceiptRequest $request)
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
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);



        $validated = $request->validated();

        try {
            $validated['status'] = 'pending';
            $validated['date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');
            $validated['created_by'] = $loginUserId;

            // return $validated;
            PaymentReceipt::create($validated);

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ($id = "print") {
            return self::print();
        }
        dd($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
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
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {
            View::share('modules', $modules);
            $company_id = $modules['company_id'];

            $quotations = Quotation::where('company_id', $company_id)->get();
            $orders = SalesOrder::where('company_id', $company_id)->get();
            $receipt_type = PaymentReceipt::$receipt_type;

            $edit = PaymentReceipt::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form', compact('edit', 'quotations', 'orders', 'receipt_type'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentReceiptRequest $request, string $id)
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
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);

        $validated = $request->validated();

        try {
            $validated['date'] = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date)->format('Y-m-d');

            $validated['updated_by'] = $loginUserId;
            // return $validated;
            $updateData = PaymentReceipt::findOrFail($id);
            if ($updateData) {
                unset($validated['id']);
                $updateData->update($validated);

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

        $dataDelete = PaymentReceipt::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;
        try {
            // dd('hiii');
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {

                    AccountLedger::where('receipt_id', $id)->where('company_id', $dataDelete->company_id)->where('quotation_id', $dataDelete->quotation_id)->delete();
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
    public function restore($id)
    {
        // dd()
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']->id ?? null;

        // Check permission
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = isset($modules['company_id']) && !$modules['company_id']
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!($modules['restore_permission'] ?? false)) {
            abort(403, 'Unauthorized');
        }

        try {
            $paymentReceipt = PaymentReceipt::withTrashed()->findOrFail($id);
            $paymentReceipt->restore();

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function generateReceiptNo()
    {
        try {
            $latest = PaymentReceipt::withTrashed()
                ->orderByDesc('id')
                ->first();

            $number = 1;

            if ($latest && $latest->receipt_no && preg_match('/(\d+)$/', $latest->receipt_no, $matches)) {
                $number = (int)$matches[1] + 1;
            }

            $receiptNo = 'REC-' . str_pad($number, 5, '0', STR_PAD_LEFT);

            return response()->json(['receipt_no' => $receiptNo]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }
    public function status_update(Request $request)
    {
        $isAjax = $request->ajax();

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['update_permission']) {
            if ($isAjax) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {

            $filteredModels = [];
            if ($request->company_id) {
                $company = Company::select('id', 'company_name', 'email', 'account_effect_by')->findOrFail($request->company_id);
                $accountEffect = $company->account_effect_by;
                if ($accountEffect == 'quotation') {
                    $filteredModels[] = 'quotation';
                } else if ($accountEffect == 'order') {
                    $filteredModels[] = 'salesOrder';
                }
            }

            $receipt = PaymentReceipt::with($filteredModels)->withTrashed()->findOrFail($request->id);
            if ($receipt->status === 'pending') {
                $receipt->status = 'approve';
                $receipt->updated_by = $loginUserId;
                $receipt->save();

                $entryDate = Carbon::now()->format('Y-m-d');
                $description = match ($receipt->receipt_type) {
                    'order' => 'Payment for Sales Order No. ' . ($receipt->salesOrder?->sales_order_no ?? 'N/A') .
                        ' via ' . ucfirst($receipt->payment_mode) . '. Receipt No: ' . $receipt->receipt_no . '.',
                    'quotation' => 'Payment for Quotation No. ' . ($receipt->quotation?->quotation_no ?? 'N/A') .
                        ' via ' . ucfirst($receipt->payment_mode) . '. Receipt No: ' . $receipt->receipt_no . '.',
                    default => 'Payment via ' . ucfirst($receipt->payment_mode) . '. Receipt No: ' . $receipt->receipt_no . '.',
                };

                $debit = $receipt->payment_mode === 'debit' ? $receipt->amount : 0;
                $credit = $receipt->payment_mode === 'credit' ? $receipt->amount : 0;
                $runningBalance = ($debit - $credit);

                AccountLedger::updateOrCreate(
                    [
                        'receipt_id' => $receipt->id,
                        'company_id' => $request->company_id,
                    ],
                    [
                        'customer_type_id' => $receipt->customer_type_id,
                        'customer_id' => $receipt->customer_id,
                        'entry_date' => $entryDate,
                        'reference_type' => $receipt->receipt_type,
                        'quotation_id' => $receipt->quotation_id ?? null,
                        'sales_order_id' => $receipt->sales_order_id ?? null,
                        'payment_mode' => $receipt->payment_mode,
                        'amount' => $receipt->amount,
                        'debit_amount' => $debit,
                        'credit_amount' => $credit,
                        'description' => $description,
                        'closing_balance' => $runningBalance,
                    ]
                );

                $message = $modules['title'] . ' status updated successfully.';
                if ($isAjax) {
                    return $this->sendResponse($receipt, $message);
                }

                return Redirect::route($modules['route'] . '.index')->withSuccess($message);
            } else {
                // Do not allow changing anything except pending
                $message = 'Only pending status can be changed.';
                if ($isAjax) {
                    return $this->sendError($message, $message, [], 400);
                }

                return Redirect::back()->withErrors($message)->withInput();
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            if ($isAjax) {
                return $this->sendError($message, $message, [], 401);
            }
            return Redirect::back()->withErrors($message)->withInput();
        }
    }

    public function get_quotation_dropdown(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists('companies', 'id')
                ],
                'customer_id' => [
                    'required',
                    Rule::exists('customers', 'id')
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->messages()->first(),
                    'data' => []
                ], 401);
            }

            $quotationSelectedId = $request->quotation_selected_id;
            $payment_receipt_id = $request->payment_receipt_id;

            $quotations = Quotation::select('id', 'quotation_no', 'grand_total')
                ->where('company_id', $request->company_id)
                ->where('customer_id', $request->customer_id)
                // ->when($quotationSelectedId, function ($query, $quotationSelectedId) {
                //     $query->orWhere('id', $quotationSelectedId);
                // })
                ->get()
                ->map(function ($quotation) use ($quotationSelectedId, $payment_receipt_id) {
                    $paymentReceiptsQuery = $quotation->paymentReceipts();

                    if ($quotation->id == $quotationSelectedId && $payment_receipt_id) {
                        $paymentReceiptsQuery->where('id', '=', $payment_receipt_id);
                    }

                    $paidAmount = $paymentReceiptsQuery->sum('amount');

                    if ($quotation->id == $quotationSelectedId && $payment_receipt_id) {

                        $paymentReceiptsQuery_sub = $quotation->paymentReceipts();
                        $paidAmount_sub = $paymentReceiptsQuery_sub->sum('amount');

                        $remaining = $quotation->grand_total - $paidAmount_sub;
                    }else{
                        $remaining = $quotation->grand_total - $paidAmount;
                    }

                    return [
                        'id' => $quotation->id,
                        'text' => $quotation->quotation_no . ' (' . number_format($remaining, 2) . ')',
                        'remaining_amount' => ($quotation->id == $quotationSelectedId && $payment_receipt_id) ? $paidAmount : $remaining,
                    ];
                })
                //->filter(fn($o) => $o['remaining_amount'] > 0)
                ->filter(function ($q) use ($quotationSelectedId) {
                    // Keep if remaining > 0 OR it's the selected quotation
                    return $q['remaining_amount'] > 0 || $q['id'] == $quotationSelectedId;
                }) // show only pending ones
                ->values();

            return response()->json([
                'status' => true,
                'data' => $quotations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }


    public function get_sales_order_dropdown(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => [
                    'required',
                    Rule::exists('companies', 'id')
                ],
                'customer_id' => [
                    'required',
                    Rule::exists('customers', 'id')
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->messages()->first(),
                    'data' => []
                ], 401);
            }

            $orderSelectedId = $request->order_selected_id;
            $payment_receipt_id = $request->payment_receipt_id;

            $salesOrders = SalesOrder::select('id', 'sales_order_no', 'grand_total')
                ->where('company_id', $request->company_id)
                ->where('customer_id', $request->customer_id)
                // ->when($orderSelectedId, function ($query, $orderSelectedId) {
                //     $query->orWhere('id', $orderSelectedId);
                // })
                ->get()
                ->map(function ($order) use ($orderSelectedId, $payment_receipt_id) {

                    $paymentReceiptsQuery = $order->paymentReceipts();

                    if ($order->id == $orderSelectedId && $payment_receipt_id) {
                        $paymentReceiptsQuery->where('id', '=', $payment_receipt_id);
                    }

                    $paidAmount = $paymentReceiptsQuery->sum('amount');

                    if ($order->id == $orderSelectedId && $payment_receipt_id) {

                        $paymentReceiptsQuery_sub = $order->paymentReceipts();
                        $paidAmount_sub = $paymentReceiptsQuery_sub->sum('amount');

                        $remaining = $order->grand_total - $paidAmount_sub;
                    }else{
                        $remaining = $order->grand_total - $paidAmount;
                    }

                    return [
                        'id' => $order->id,
                        'text' => $order->sales_order_no . ' (' . number_format($remaining, 2) . ')',
                        'remaining_amount' => ($order->id == $orderSelectedId && $payment_receipt_id) ? $paidAmount : $remaining,
                    ];

                    // $paid = $order->paymentReceipts()->sum('amount');
                    // $remaining = $order->grand_total - $paid;

                    // return [
                    //     'id' => $order->id,
                    //     'text' => $order->sales_order_no . ' (' . number_format($remaining, 2) . ')',
                    //     'remaining_amount' => $remaining,
                    // ];
                })
                //->filter(fn($o) => $o['remaining_amount'] > 0)
                ->filter(function ($q) use ($orderSelectedId) {
                    // Keep if remaining > 0 OR it's the selected quotation
                    return $q['remaining_amount'] > 0 || $q['id'] == $orderSelectedId;
                })
                ->values();

            return response()->json([
                'status' => true,
                'data' => $salesOrders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }


    public function getQuotationAmount(Request $request)
    {
        $quotation = Quotation::find($request->id);

        if ($quotation) {
            return response()->json([
                'success' => true,
                'amount' => $quotation->grand_total
            ]);
        }

        return response()->json(['success' => false]);
    }



    public function getSalesOrderAmount(Request $request)
    {
        $salesOrder = SalesOrder::find($request->id);

        if ($salesOrder) {
            return response()->json([
                'success' => true,
                'amount' => $salesOrder->grand_total
            ]);
        }

        return response()->json(['success' => false]);
    }
    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $hasPermission = (!isset($modules['company_id']) || !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);

                $modules[$value . '_permission'] = $hasPermission;

                if ($value === 'personal_data') {
                    $modules['personal_data_permission'] = $hasPermission;
                }
                if ($value === 'all_data') {
                    $modules['all_data_permission'] = $hasPermission;
                }
            }
        }

        if (empty($modules['excel_permission'])) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        return Excel::download(new PaymentReceiptExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'PaymentReceipt-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$company_id) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['print_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $query = PaymentReceipt::with(['company', 'customer', 'customerType', 'quotation', 'salesOrder'])
                ->orderBy('id', 'DESC')
                ->whereNull('deleted_at');

            if ($company_id) {
                $query->where('company_id', $company_id);
                if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                    $query->where('created_by', $loginUserId);
                }
            }

            if ($request->filled('filter_company')) {
                $query->where('company_id', $request->filter_company);
            }

            if ($request->filled('customer_type_id')) {
                $query->where('customer_type_id', $request->customer_type_id);
            }

            if ($request->filled('receipt_type')) {
                $query->where('receipt_type', $request->receipt_type);
            }

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('date')) {
                $dateRange = explode(' to ', $request->date);
                if (count($dateRange) === 2) {
                    $from = Carbon::createFromFormat('d/m/Y', trim($dateRange[0]))->startOfDay();
                    $to = Carbon::createFromFormat('d/m/Y', trim($dateRange[1]))->endOfDay();
                    $query->whereBetween('date', [$from, $to]);
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('receipt_no', 'like', "%$search%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%$search%");
                        });
                });
            }

            $receipt_list = $query->get();

            return view($modules['folder_path'] . '.print', compact('receipt_list', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
