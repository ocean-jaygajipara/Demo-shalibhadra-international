<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Redirect;

use const Adminer\DB;

class OutstandingReportController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Outstanding Report',
            'folder_path' => 'software.modules.account.outstanding-report',
            'route' => 'outstanding-report',
            // 'table_name' => (new ())->getTable(),
            'permisstion_prefix' => 'outstanding-report',
            'module_name' => 'Outstanding Report',
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
                // (object)['data' => "company", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                // (object)['data' => "dispatch_no", 'name' => 'dispatch_no', 'td_label' => 'Dispatch No', 'className' =>  ''],
                // (object)['data' => "order_no", 'name' => 'order_no', 'td_label' => 'Order No', 'className' =>  ''],
                // (object)['data' => "dispatch_date", 'name' => 'dispatch_date', 'td_label' => 'Dispatch Date', 'className' =>  ''],
                // (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  ''],
                // (object)['data' => "dispatch_qty", 'name' => 'dispatch_qty', 'td_label' => 'Dispatch Qty', 'className' =>  ''],
                // (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-center'],
            ];

            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });
                $columns = array_values($columns);
            }
            View::share("columns", $columns);



            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function fetch(Request $request)
    {
        $dateRange = $request->input('fromdate_time');
        $companyId = $request->input('company_id');
        $customerTypeId = $request->input('customer_type_id');
        $customerId = $request->input('customer_id');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'html' => '<div class="alert alert-danger">Please select a company.</div>',
            ]);
        }

        // Default financial year range
        $today = Carbon::now();
        $fyStart = Carbon::parse(($today->month < 4 ? $today->year - 1 : $today->year) . '-04-01')->startOfDay();
        $fyEnd = Carbon::parse(($today->month < 4 ? $today->year : $today->year + 1) . '-03-31')->endOfDay();
        [$startDate, $endDate] = [$fyStart, $fyEnd];

        // If user selects date range
        if ($dateRange && str_contains($dateRange, ' to ')) {
            [$start, $end] = explode(' to ', $dateRange);
            try {
                $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'html' => '<div class="alert alert-danger">Invalid date format.</div>',
                ]);
            }
        }

        // Get matching customers
        $customers = DB::table('customers as c')
            ->leftJoin('customer_type as ct', 'c.customer_type_id', '=', 'ct.id')
            ->leftJoin('companies as comp', 'c.company_id', '=', 'comp.id')
            ->select(
                'c.id',
                'c.customer_name',
                'c.contact_person_name',
                'c.mobile_number',
                'ct.name as customer_type_name',
                'comp.company_name'
            )
            ->where('c.company_id', $companyId)
            ->when($customerTypeId, fn($q) => $q->where('c.customer_type_id', $customerTypeId))
            ->when($customerId, fn($q) => $q->where('c.id', $customerId))
            ->get();

        if ($customers->isEmpty()) {
            return response()->json([
                'success' => false,
                'html' => '<div class="alert alert-warning">No customers found for selected filters.</div>',
            ]);
        }

        $html = '
    <div class="card mt-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12"><strong>From:</strong> ' . $startDate->format('d-m-Y') . ' <strong>To:</strong> ' . $endDate->format('d-m-Y') . '</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Customer Type</th>
                            <th>Customer</th>
                            <th>Phone Number</th>

                            <th class="text-end">Closing Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>';

        $rowAdded = false;

        foreach ($customers as $customer) {

            $company = Company::select('id', 'company_name', 'email', 'account_effect_by')->findOrFail($companyId);
            $accountEffect = $company->account_effect_by;


            $receiptSummary = AccountLedger::where('company_id', $companyId)
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->where(function ($query) use ($accountEffect) {
                    $query->where('reference_type', $accountEffect)
                        ->orWhere('reference_type', 'general');
                })
                ->where('customer_id', $customer->id)
                ->selectRaw("
                    SUM(CASE WHEN payment_mode = 'debit' THEN amount ELSE 0 END) as total_debit,
                    SUM(CASE WHEN payment_mode = 'credit' THEN amount ELSE 0 END) as total_credit
                ")
                ->first();

            $totalDebit = (float)($receiptSummary->total_debit ?? 0);
            $totalCredit = (float)($receiptSummary->total_credit ?? 0);

            // ✅ Skip customers with no transactions
            if ($totalDebit == 0 && $totalCredit == 0) {
                continue;
            }

            $closingBalance = $totalDebit - $totalCredit;

            $customerLabel = $customer->customer_name;
            if (!empty($customer->contact_person_name)) {
                $customerLabel .= ' - ' . $customer->contact_person_name;
            }

            $html .= '
            <tr>
                <td>' . ($customer->customer_type_name ?? '-') . '</td>
                <td>' . $customerLabel . '</td>
                <td>' . ($customer->mobile_number ?? '-') . '</td>

                <td class="text-end fw-bold">' . number_format($closingBalance, 2) . '</td>
            </tr>';

            $rowAdded = true;
        }

        $html .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

        if (!$rowAdded) {
            return response()->json([
                'success' => false,
                'html' => '<div class="alert alert-info">No transaction found for any customer in selected filters.</div>',
            ]);
        }

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
