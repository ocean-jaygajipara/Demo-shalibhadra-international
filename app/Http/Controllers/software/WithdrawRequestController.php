<?php

namespace App\Http\Controllers\software;

use App\Exports\WithdrawalRequestExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\RedemptionItem;
use App\Models\SetPointToRupees;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Str;
use Yajra\DataTables\Facades\DataTables;

class WithdrawRequestController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Withdraw Request',
            'folder_path' => 'software.modules.withdraw-request',
            'route' => 'withdraw-request',
            'table_name' => (new WithdrawRequest())->getTable(),
            'permisstion_prefix' => 'withdraw-request',
            'module_name' => 'QR Withdrawal Management',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null

        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;

        // Set authenticated user details
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Set permissions
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $value) {
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        // View permission check
        if (!$modules['view_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            $columns = [
                (object)['data' => "company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => 'customer_name', 'name' => 'customer_id', 'td_label' => 'Customer'],
                (object)['data' => "request_points", 'name' => 'request_points', 'td_label' => 'Request Points'],
                (object)['data' => "amount", 'name' => 'amount', 'td_label' => 'Amount'],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  'w-5 text-start'],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],

            ];
            View::share("columns", $columns);

            if ($request->ajax()) {
                $data = WithdrawRequest::with(['company', 'customer'])
                    ->select('withdraw_requests.*')
                    ->when(Auth::guard('team_person')->check() && $modules['company_id'], function ($query) use ($modules, $loginUserId) {
                        $query->where('withdraw_requests.company_id', $modules['company_id']);
                        if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                            $query->where('withdraw_requests.created_by', $loginUserId);
                        }
                    })
                    ->orderByDesc('id');

                if (!empty($modules['restore_permission'])) {
                    $data = $data->withTrashed();
                }

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('company_name', function ($row) {
                        return $row->company->company_name ?? '-';
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode   = optional($row->customer)->client_code;
                        $customerName = optional($row->customer)->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return '<span style="white-space:nowrap;">'.$clientCode.'<br>'.$customerName.'</span>';
                        }

                        return $clientCode;
                    })
                    ->filter(function ($query) use ($request) {
                        if ($request->has('search') && $request->search) {
                            $query->whereHas('customer', function ($q) use ($request) {
                                $q->where('customer_name', 'like', '%' . $request->search . '%');
                            });
                        }

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->filled('filter_status') && $request->filter_status !== 'all') {
                            $query->where('status', $request->filter_status);
                        }
                    })
                    ->editColumn('withdraw_request_date', function ($record) {
                        return Helper::convert_date($record?->withdraw_request_date, 'Y-m-d', "d-m-Y");
                    })
                    // ->editColumn('status', function ($record) {
                    //     return match ($record->status) {
                    //         'Pending' => '<span class="badge bg-secondary-subtle text-secondary text-uppercase">Pending</span>',
                    //         'Approve' => '<span class="badge bg-success-subtle text-success text-uppercase">Approve</span>',
                    //         'Reject' => '<span class="badge bg-danger-subtle text-danger text-uppercase">Reject</span>',
                    //         default => '-',
                    //     };
                    // })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "Pending") {
                            $btn .= '<button type="button" class="btn btn-warning btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="Approve">Approve</a></li>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="Reject">Reject</a></li>';
                        } elseif ($row->status == "Approve") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm  waves-light" disable aria-expanded="false">' . ucfirst($row->status) . '</button>';
                        } elseif ($row->status == "Reject") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm  waves-light" disable  aria-expanded="false">' . ucfirst($row->status) . '</button>';
                        } else {
                            return $btn;
                        }
                        $dropdown .= '</ul>';
                        $btn .= $dropdown;


                        return $btn;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '<div class="d-flex gap-2 justify-content-center">';
                        $hasButton = false;

                        if (!$row?->deleted_at && $modules['update_permission'] && $row->status == "Pending") {
                            $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-primary btn-icon mx-1" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';
                            $hasButton = true;
                        }

                        if (!$row?->deleted_at) {
                            if ($modules['delete_permission'] && $row->status == "Pending") {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                                $hasButton = true;
                            }
                        } else {
                            if ($modules['restore_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", [$row["id"]]) . '" class="btn btn-light mx-1 record-restore"><i class="ti ti-history"></i> Restore</a>';
                                $hasButton = true;
                            }
                        }

                        $btn .= '</div>';
                        return $hasButton ? $btn : '-';
                    })

                    ->rawColumns(['status', 'action', 'customer_name'])
                    ->make(true);
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
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
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permissions
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['view_permission']) {
            return $request->ajax()
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        // Validation
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $rule = SetPointToRupees::where('company_id', $validated['company_id'])->first();

        if ($rule) {
            $minAmount = $rule->min_amount;
            $maxAmount = $rule->max_amount;
            $amount = $validated['amount'];

            if ($amount < $minAmount) {
                return redirect()->back()->withInput()->with('error', "Minimum withdrawal amount is ₹$minAmount");
            }

            if ($amount > $maxAmount) {
                return redirect()->back()->withInput()->with('error', "Maximum withdrawal amount is ₹$maxAmount");
            }
        }

        try {
            $attachmentPath = null;

            // Handle file upload
            if ($request->hasFile('attachment')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $image_name = Helper::make_slug('withdraw-request') . '-pending-' . date('Ymd-His');
                $filename = $image_name . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/withdraw-request/{$year}-{$month}/";

                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $attachmentPath = $sub_folder_path . $filename;

                    if (in_array(strtolower($extension), ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                        if ($webP) {
                            $attachmentPath = $sub_folder_path . $webP;
                        }
                    }
                }
            }

            $point = $rule->point;
            $rupees = $rule->rupees;
            $rupeesPerPoint = $rupees / $point;

            $requestPoints = ceil($validated['amount'] / $rupeesPerPoint);

            $totalCollectedPoints = RedemptionItem::where('company_id', $validated['company_id'])
                ->where('scanned_user_id', $validated['customer_id'])
                ->sum('collect_points');

            $usedPoints = WithdrawRequest::where('company_id', $validated['company_id'])
                ->where('customer_id', $validated['customer_id'])
                ->whereIn('status', ['Pending', 'Approved'])
                ->sum('request_points');

            $availablePoints = $totalCollectedPoints - $usedPoints;

            if ($availablePoints < $requestPoints) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "You have only {$availablePoints} points available for withdrawal. You need {$requestPoints} points for ₹{$validated['amount']}. Please check existing withdrawals.");
            }

            if ($totalCollectedPoints < $requestPoints) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Insufficient scanned points. You have {$totalCollectedPoints} scanned points, but need {$requestPoints} points to withdraw ₹{$validated['amount']}.");
            }

            $totalScannedPoints = RedemptionItem::where('company_id', $validated['company_id'])
                ->where('scanned_user_id', $validated['customer_id'])
                ->sum('collect_points');


            if ($totalScannedPoints < $requestPoints) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Insufficient scanned points. You have {$totalScannedPoints} points, but ₹{$validated['amount']} requires {$requestPoints} points.");
            }

            WithdrawRequest::create([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'amount' => $validated['amount'],
                'request_points' => $requestPoints,
                'withdraw_request_date' => now(),
                'attachment' => $attachmentPath,
                'status' => 'Pending',
                'created_by' => $loginUserId,
            ]);
            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' created successfully.');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
        }
    }



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
            $edit = WithdrawRequest::findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['update_permission']) {
            return $request->ajax()
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $rule = SetPointToRupees::where('company_id', $validated['company_id'])->first();

        if ($rule) {
            $minAmount = $rule->min_amount;
            $maxAmount = $rule->max_amount;
            $amount = $validated['amount'];

            if ($amount < $minAmount) {
                return redirect()->back()->withInput()->with('error', "Minimum withdrawal amount is ₹$minAmount");
            }

            if ($amount > $maxAmount) {
                return redirect()->back()->withInput()->with('error', "Maximum withdrawal amount is ₹$maxAmount");
            }
        }

        try {
            $withdrawRequest = WithdrawRequest::findOrFail($id);
            $attachmentPath = $withdrawRequest->attachment;

            if ($request->hasFile('attachment')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $image_name = Helper::make_slug('withdraw-request') . '-pending-' . date('Ymd-His');
                $filename = $image_name . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/withdraw-request/{$year}-{$month}/";
                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $attachmentPath = $sub_folder_path . $filename;

                    if (in_array(strtolower($extension), ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                        if ($webP) {
                            $attachmentPath = $sub_folder_path . $webP;
                        }
                    }
                }
            }

            $point = $rule->point;
            $rupees = $rule->rupees;
            $rupeesPerPoint = $rupees / $point;
            $requestPoints = ceil($validated['amount'] / $rupeesPerPoint);

            $totalCollectedPoints = RedemptionItem::where('company_id', $validated['company_id'])
                ->where('scanned_user_id', $validated['customer_id'])
                ->sum('collect_points');

            $usedPoints = WithdrawRequest::where('company_id', $validated['company_id'])
                ->where('customer_id', $validated['customer_id'])
                ->where('id', '!=', $id)
                ->whereIn('status', ['Pending', 'Approved'])
                ->sum('request_points');

            $availablePoints = $totalCollectedPoints - $usedPoints;

            if ($availablePoints < $requestPoints) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Only {$availablePoints} points available. ₹{$validated['amount']} requires {$requestPoints} points.");
            }

            $withdrawRequest->update([
                'company_id' => $validated['company_id'],
                'customer_id' => $validated['customer_id'],
                'amount' => $validated['amount'],
                'request_points' => $requestPoints,
                'withdraw_request_date' => now(),
                'attachment' => $attachmentPath,
                'status' => 'Pending',
                'updated_by' => $loginUserId,
            ]);

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' updated successfully.');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
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

        $isAjax = ($request->ajax()) ? true : false;
        $dataDelete = WithdrawRequest::findOrFail($id);

        try {
            if ($dataDelete) {
                $dataDelete->forceDelete();
                // $validated['deleted_by'] = $loginUserId;
                // $dataDelete->update($validated);

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

    public function restore($id)
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
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $vendor = WithdrawRequest::withTrashed()->findOrFail($id);
            $vendor->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function status_update(Request $request)
    {
        $isAjax = $request->ajax();

        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        // Permission check
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['update_permission']) {
            return $isAjax
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }

        // Validate base fields
        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
        ]);

        if ($validator->fails()) {
            return $isAjax
                ? $this->sendError($validator->messages()->first(), $validator->messages(), [], 401)
                : Redirect::back()->withErrors($validator->messages())->withInput();
        }

        try {
            $withdraw = WithdrawRequest::withTrashed()->findOrFail($request->id);
            $withdraw->status = $request->update_status; // <-- add this line
            $withdraw->updated_by = $loginUserId;
            $withdraw->save();


            return $isAjax
                ? $this->sendResponse($withdraw, $modules['title'] . ' status updated successfully.')
                : Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' status updated successfully.');
        } catch (\Exception $e) {
            return $isAjax
                ? $this->sendError($e->getMessage(), $e->getMessage(), [], 401)
                : Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
     public function exportExcel(Request $request)
    {
        // dd($request->all());
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
        if (!$modules['excel_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        // dd("L-1309", $request?->all(), $modules);
        return Excel::download(new WithdrawalRequestExport($request->all(), $this->authenticateLoginUserDetails), 'Withdrawal-Request ' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id =   $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['print_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {
            $withdraw_request = WithdrawRequest::with(['company', 'customer'])
                ->when(Auth::guard('team_person')->check() && $modules['company_id'], function ($query) use ($modules, $loginUserId) {
                    $query->where('company_id', $modules['company_id']);

                    if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                        $query->where('created_by', $loginUserId);
                    }
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($query) use ($request) {
                    $query->where('status', $request->status);
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $query->whereHas('customer', function ($q) use ($request) {
                        $q->where('customer_name', 'like', '%' . $request->search . '%');
                    });
                })
                ->when($request->filled('filter_company'), function ($query) use ($request) {
                    $query->where('company_id', $request->filter_company);
                })
                ->orderByDesc('id')
                ->get();

            return view($modules['folder_path'] . '.print', compact('withdraw_request', 'modules', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
