<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\AccountLedger;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PaymentReceipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;

use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Redirect;


class AccountLeagerController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Account Ledger',
            'folder_path' => 'software.modules.account.account-ledger',
            'route' => 'account-ledger',
            // 'table_name' => (new ())->getTable(),
            'permisstion_prefix' => 'account-ledger',
            'module_name' => 'Account Ledger',
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

            $columns = [];

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

    public function getLedgerReport(Request $request)
    {
        try {
            $company_id = $request->company_id;
            $customer_type_id = $request->customer_type_id;
            $customer_id = $request->customer_id;

            $followupDate = $request->input('followup_date');
            [$startDate, $endDate] = [null, null];

            if ($followupDate) {
                [$start, $end] = explode(' to ', $followupDate);
                $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay();
                $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay();
            } else {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfDay();
            }

            $filteredModels = [];
            if ($company_id) {
                $company = Company::select('id', 'company_name', 'email', 'account_effect_by')->findOrFail($company_id);
                $accountEffect = $company->account_effect_by;
                if ($accountEffect == 'quotation') {
                    $filteredModels[] = 'quotation';
                } else if ($accountEffect == 'order') {
                    $filteredModels[] = 'salesOrder';
                }
            }

            $ledgerRecords = AccountLedger::with(['customer'])
                ->where('company_id', $company_id)
                ->where('customer_id', $customer_id)
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->where(function ($query) use ($accountEffect) {
                    $query->where('reference_type', $accountEffect)
                        ->orWhere('reference_type', 'general');
                })
                ->whereHas('customer', function ($query) use ($customer_type_id) {
                    $query->where('customer_type_id', $customer_type_id);
                })
                ->orderBy('entry_date')
                ->get();

            $ledger = [];
            $runningBalance = 0;
            foreach ($ledgerRecords as $record) {

                $runningBalance += ($record->debit_amount - $record->credit_amount);
                $ledger[] = [
                    'date'            => Carbon::parse($record->entry_date)->format('d-m-Y'),
                    'description'     => $record->description,
                    'debit'           => $record->debit_amount,
                    'credit'          => $record->credit_amount,
                    'closing_balance' => $record->closing_balance,
                ];
            }

            // $receipts = PaymentReceipt::with($filteredModels)
            //     ->where('company_id', $company_id)
            //     ->where('customer_type_id', $customer_type_id)
            //     ->where('customer_id', $customer_id)
            //     ->where('status', 'approve')
            //     ->whereBetween('date', [$startDate, $endDate])
            //     ->where(function ($query) use ($accountEffect) {
            //         $query->where('receipt_type', $accountEffect)
            //             ->orWhere('receipt_type', 'general');
            //     })
            //     ->orderBy('date')
            //     ->get();

            // $ledger = [];
            // $runningBalance = 0;

            // foreach ($receipts as $receipt) {
            //     $entryDate = Carbon::parse($receipt->date)->format('Y-m-d');
            //     $description = match ($receipt->receipt_type) {
            //         'order' => 'Payment for Sales Order No. ' . ($receipt->salesOrder?->sales_order_no ?? 'N/A') .
            //             ' via ' . ucfirst($receipt->payment_mode) . '. Receipt No: ' . $receipt->receipt_no . '.',
            //         'quotation' => 'Payment for Quotation No. ' . ($receipt->quotation?->quotation_no ?? 'N/A') .
            //             ' via ' . ucfirst($receipt->payment_mode) . '. Receipt No: ' . $receipt->receipt_no . '.',
            //         default => 'Payment via ' . ucfirst($receipt->payment_mode) . '. Receipt No: ' . $receipt->receipt_no . '.',
            //     };

            //     $debit = $receipt->payment_mode === 'debit' ? $receipt->amount : 0;
            //     $credit = $receipt->payment_mode === 'credit' ? $receipt->amount : 0;

            //     $runningBalance += ($debit - $credit);

            //     AccountLedger::updateOrCreate(
            //         [
            //             'receipt_id' => $receipt->id,
            //             'company_id' => $company_id,
            //         ],
            //         [
            //             'customer_type_id' => $customer_type_id,
            //             'customer_id' => $customer_id,
            //             'entry_date' => $entryDate,
            //             'receipt_type' => $receipt->receipt_type,
            //             'payment_mode' => $receipt->payment_mode,
            //             'amount' => $receipt->amount,
            //             'debit_amount' => $debit,
            //             'credit_amount' => $credit,
            //             'description' => $description,
            //             'closing_balance' => $runningBalance,
            //         ]
            //     );

            //     $ledger[] = [
            //         'date' => Carbon::parse($receipt->date)->format('d-m-Y'),
            //         'description' => $description,
            //         'debit' => $debit,
            //         'credit' => $credit,
            //         'closing_balance' => $runningBalance,
            //     ];
            // }

            return response()->json([
                'ledger' => $ledger,
                'total_debit' => collect($ledger)->sum('debit'),
                'total_credit' => collect($ledger)->sum('credit'),
                'closing_balance' => $runningBalance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Failed to generate ledger report.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }
    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['authLoginUserDetail']?->company_id ?? null;


        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$company_id)
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (empty($modules['print_permission'])) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            // $company_id       = $request->company_id;
            if (!$company_id) {
                $company_id = $request->company_id;
            }
            $customer_id      = $request->input('customer_id');
            $customer_type_id = $request->input('customer_type_id');
            $followupDate     = $request->input('followup_date');
            $startDate = null;
            $endDate   = null;

            if (!empty($followupDate)) {
                if (strpos($followupDate, ' to ') !== false) {
                    [$start, $end] = explode(' to ', $followupDate);
                } elseif (strpos($followupDate, ' - ') !== false) {
                    [$start, $end] = explode(' - ', $followupDate);
                }

                $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay();
                $endDate   = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay();
            } else {

                $startDate = Carbon::now()->startOfMonth();
                $endDate   = Carbon::now()->endOfDay();
            }


            $accountEffect = null;
            if ($company_id) {
                $company = Company::select('id', 'company_name', 'email', 'account_effect_by')
                    ->findOrFail($company_id);
                $accountEffect = $company->account_effect_by;
            }


            $ledgerQuery = AccountLedger::with('customer')
                ->where('company_id', $company_id)
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->where(function ($query) use ($accountEffect) {
                    $query->where('reference_type', $accountEffect)
                        ->orWhere('reference_type', 'general');
                });

            if ($customer_id) {
                $ledgerQuery->where('customer_id', $customer_id);
            }

            if ($customer_type_id) {
                $ledgerQuery->whereHas('customer', function ($query) use ($customer_type_id) {
                    $query->where('customer_type_id', $customer_type_id);
                });
            }


            $ledgerRecords = $ledgerQuery->orderBy('entry_date', 'asc')->get();

            $ledger         = [];
            $runningBalance = 0;
            $total_debit    = 0;
            $total_credit   = 0;

            foreach ($ledgerRecords as $record) {
                $debit  = $record->debit_amount;
                $credit = $record->credit_amount;
                $runningBalance += ($debit - $credit);

                $ledger[] = [
                    'date'            => Carbon::parse($record->entry_date)->format('d-m-Y'),
                    'customer'        => $record->customer->name ?? '-',
                    'description'     => $record->description,
                    'debit'           => $debit,
                    'credit'          => $credit,
                    'closing_balance' => $runningBalance,
                ];

                $total_debit  += $debit;
                $total_credit += $credit;
            }

            $customer = $customer_id ? Customer::findOrFail($customer_id) : null;

            return view($modules['folder_path'] . '.print', compact(
                'ledger',
                'company_id',
                'modules',
                'customer',
                'customer_id',
                'total_debit',
                'total_credit',
                'runningBalance'
            ));
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
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
