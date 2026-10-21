<?php

namespace App\Http\Controllers\software;

use App\Exports\CustomerTypeExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerTypeRequest;
use App\Http\Requests\PermissionRequest;
use App\Models\Company;
use App\Models\CustomerType;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CustomerTypeController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Customer Type',
            'folder_path' => 'software.modules.customer.customer-type',
            'route' => 'customer-type',
            'table_name' => (new CustomerType())->getTable(),
            'permisstion_prefix' => 'customer-type',
            'module_name' => 'Customer Type',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];

        // Fetch customer types for parent type dropdown (same as create)
        $customerTypes = DB::table('customer_type')
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->get();


        // Share this data with the view
        View::share('customerTypes', $customerTypes);
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
                $modules[$value . '_permission'] = (isset($modules['company_id']) && $modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);

                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
        try {
            $columns = [
                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Sr No.'],
                  (object)['data' => 'DT_RowIndex','name' => 'DT_RowIndex','td_label' => 'Sr No.','orderable' => false,'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "name", 'name' => 'name', 'td_label' => 'Name', 'className' =>  ''],
                (object)['data' => "parent_name", 'name' => 'parent_id', 'td_label' => 'Parent Type Name', 'className' =>  ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  'w-5 text-start'],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],
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
                // dd($request->all());
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = CustomerType::select('*');


                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                })
                    ->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company', 'parentTypeRelation']);

                return Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('company_id') && $request->company_id) {
                            //   /  $query->where('company_id', $request->company_id);  // Exact match for company_id
                            $query->where('company_id', $request->company_id);
                        }
                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                            // $query->orWhere('short_name', 'like', "%" . $request->search . "%");
                        }
                    })
                    ->addColumn('parent_name', function ($row) {
                        return $row->parentTypeRelation->name ?? '-';
                    })

                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';

                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
                        } else {
                            return $btn;
                        }
                        $dropdown .= '</ul>';
                        $btn .= $dropdown;
                        return $btn;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row?->deleted_at) {
                            // $btn .= '<a href="javascript:void(0)" class="edit btn btn-primary btn-sm">View</a>';
                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }

                            if ($modules['approval_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".assign-permission", [$row["id"]]) . '" class="btn btn-primary btn-icon mx-1"><i class="fa-solid fa-cog"></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }
                        // $btn .= '<a href="javascript:void(0)" class="btn btn-info btn-icon mr-2"><i class="fa-solid fa-key"></i></a>';
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->rawColumns(['status', 'action'])
                    ->make(true);
            }
            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
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
    public function store(CustomerTypeRequest $request)
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
            $validated['created_by'] = $loginUserId;

            CustomerType::create($validated);

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' created successfully.');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
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

            // Fetch the existing customer type by ID
            $edit = CustomerType::findOrFail($id);  // Find the customer type by ID, or throw an error if not found
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');  // Return the form view with data
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerTypeRequest $request, string $id)
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

        try {
            // Validation
            $validated = $request->validated();

            $validated['updated_by'] = Auth::guard('admin_software')?->user()?->id;

            $updateData = CustomerType::findOrFail($id);
            $updateData->update($validated);

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
        $isAjax = ($request->ajax());
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

        try {
            $dataDelete = CustomerType::findOrFail($id);

            // Soft delete logic
            // $dataDelete->deleted_by = Auth::guard('admin_software')?->user()?->id;
            $validated['deleted_by'] = $loginUserId;
            $dataDelete->save();

            if ($dataDelete->delete()) {
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $modules['title'] . ' deleted successfully.',
                    ]);
                }

                return Redirect::route($modules['route'] . '.index')
                    ->withSuccess($modules['title'] . ' deleted successfully.');
            }

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong, please try again later.',
                ]);
            }

            return Redirect::route($modules['route'] . '.index')
                ->withErrors('Something went wrong, please try again later.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
            }

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

            $state = CustomerType::withTrashed()->findOrFail($id);
            $state->restore();

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function status_update(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'update_status' => ['required', 'in:active,inactive']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }


        try {
            $state = CustomerType::withTrashed()->findOrFail($request?->id);
            if ($state) {
                $state->status = $request->update_status;
                $state->updated_by = $loginUserId;

                $state->save();
                if ($isAjax) {
                    return $this->sendResponse($state, $modules['title'] . ' status update successfully.');
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

        return Excel::download(new CustomerTypeExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Customer Type-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function print(Request $request)
    {
        $modules = $this->modules;

        // Authenticated user details
        $authUser = $this->authenticateLoginUserDetails;
        $modules['authLoginUserDetail'] = $authUser;
        $modules['company_id'] = $authUser?->company_id ?? null;
        $company_id = $modules['company_id'];
        $loginUserId = $authUser?->id ?? null;

        try {
            // Permissions
            $moduleName = $modules['module_name'];
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $moduleName]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $moduleName]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $moduleName]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $moduleName]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $moduleName]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $moduleName]);

            // Unauthorized check
            if ($request->ajax()) {
                if (!$modules['viewPermission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
            } elseif (!$modules['viewPermission']) {
                abort(403, 'Unauthorized');
            }

            $query = CustomerType::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personalDataPermission'] && !$modules['allDataPermission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->with(['company'])
                ->orderBy('id', 'DESC');

            // Company filter
            if ($request->has('company') && !empty($request->company)) {
                $query->whereHas('company', function ($subQuery) use ($request) {
                    $subQuery->where('company_id', 'like', '%' . $request->company . '%');
                });
            }



            // Search filter
            if ($request->has('search') && !empty($request->search)) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $unit = $query->get();

            return view($modules['folder_path'] . '.print', compact('unit', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function assign_permission($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['approval_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            $customer_role = CustomerType::with(['company'])->findOrFail($id);
            if ($modules['currentGuard'] != 'admin_software') {
                if ($customer_role?->id == 0) {
                    $message = "You can not assign permission. Please contact to Company.";
                    if (isset($request) && $request->ajax()) {
                        return $this->sendError($message, [], [], 403);
                    }
                    return Redirect::route($modules['route'] . '.index')->withErrors($message);
                }
            }

            View::share('customer_role', $customer_role);
            if (!$customer_role) {
                return Redirect::back()->withErrors(["Customer role not found."]);
            }

            $company = Company::where('status', 'active')->where('id', $customer_role?->company_id)->first();


            $panel_right_list = Helper::getMainMenu(['platform' => 'panel', 'id' => ['type' => 'in', 'values' => $company?->customer_panel_right]]);
            $panel_sub_modules = Helper::getSubMenu(['platform' => 'panel', 'id' => ['type' => 'in', 'values' => $company?->customer_panel_right]], "active");
            if (count($panel_sub_modules)) {
                $panel_sub_modules = $panel_sub_modules->sortBy(fn($item) => $item['main_menu']['order_by'] ?? 0);
                $panel_sub_modules = $panel_sub_modules->groupBy('main_menu_id');
                // dd("L-431", $panel_sub_modules->values()?->first());

                $panel_sub_modules = $panel_sub_modules->map(function ($items) use ($modules, $customer_role, $loginUserId) {
                    $mainMenu = $items->first()?->mainMenu;  // One main_menu
                    // dd("L-571", $items->toArray(), $mainMenu->toArray());

                    // $mainMenu = $items->first()->main_menu; // One main_menu
                    $allowedSubMenus = [];
                    if ($modules['currentGuard'] != 'admin_software') {
                        // Get all allowed sub_menu_ids for this customer_role and main_menu
                        $allowedSubMenus = RolePermission::where('company_id', $customer_role?->company_id)
                            // ->where('customer_role_id', $loginUserId)
                            ->where('customer_role_id', $customer_role?->id)
                            // ->where('main_menu_id', $mainMenu?->id)
                            ->where('view_flag', '1');

                        // $allowedSubMenus = $allowedSubMenus->get();

                        // dd("L-592", $customer_role->toArray(), $allowedSubMenus->get(), Helper::interpolateQuery($allowedSubMenus->toSql(), $allowedSubMenus->getBindings()), $customer_role?->toArray(), Auth::user()?->toArray(), $loginUserId, $items->toArray());
                        $allowedSubMenus = $allowedSubMenus->pluck('sub_menu_id')->toArray();

                        // Attach sub_menus (filtered if needed)
                        $items = $items->filter(function ($item) use ($modules, $allowedSubMenus) {
                            // if ($modules['currentGuard'] != 'admin_software') {
                            //     return in_array($item['id'], $allowedSubMenus);
                            // }
                            return true; // Keep all if admin_software
                        });
                        // dd("L-606", $customer_role->toArray(), $allowedSubMenus,  $items->toArray());
                    }

                    // Attach sub_menus by removing 'main_menu' key from each submenu
                    $mainMenu['sub_menus'] = $items->map(function ($item) use ($modules, $customer_role, $mainMenu) {

                        unset($item['main_menu']);

                        // dd("L-586", $item?->toArray());
                        return $item->toArray(); // Convert to array if it's a model
                    })->values();

                    return $mainMenu;
                }); // re-index numerically
                $panel_sub_modules = $panel_sub_modules->values();

                $panel_sub_modules = $panel_sub_modules->sortBy(fn($item) => $item['order_by'] ?? 0);
                $panel_sub_modules = $panel_sub_modules->values();
            }
            // dd("L-626", $panel_sub_modules->toArray());
            // dd("L-600", $company?->panel_right, $panel_right_list, $panel_sub_modules->toArray());
            View::share('panel_sub_modules', $panel_sub_modules);

           // dd($panel_sub_modules);

            /** Assigned Permission */
            $assignedPermission = RolePermission::query();
            $assignedPermission = $assignedPermission?->where('company_id', $customer_role?->company_id);
            $assignedPermission = $assignedPermission?->where('customer_role_id', $customer_role?->id);
            if ($panel_sub_modules->count()) {
                $assignedPermission = $assignedPermission?->whereIn('main_menu_id', $panel_sub_modules->pluck('id'));
            }
            $assignedPermission = $assignedPermission?->get();
            $assignedPermission = $assignedPermission?->keyBy('role_permission_cuid');
            View::share('assignedPermission', $assignedPermission);

            // dd("L-471", $assignedPermission->toArray(), $panel_sub_modules->toArray(), $panel_sub_modules->pluck('id'));


            // $permissions = DB::table('role_permissions')
            //     ->where('main_menu_id', $request->main_menu_id)
            //     ->where('customer_role_id', $request->customer_role_id)
            //     ->where('company_id', $request->company_id)
            //     ->get()
            //     ->keyBy('sub_menu_id');

            // dd("L-481",ini_get('max_input_vars'));
            // dd($request->all());

            return view($modules['folder_path'] . '.permission');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store_permission(PermissionRequest $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        // return $modules;
        if (!$modules['approval_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        // dd($request->all(), phpinfo());
        try {

            $rules = [
                'customer_role_id' => [
                    'required',
                    Rule::exists((new CustomerType())->getTable(), 'id'),
                ],
            ];

            $validator = Validator::make($request->all(), $rules);

            if($validator->fails()) {
                if ($request->ajax()) {
                    return $this->sendError('Validation Failed', $validator->errors(), [], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            $company_id = $request['company_id'];
            $customer_role_id = $request['customer_role_id'];
            $permissions = $request['permissions'];
            $config_permissions = ['view', 'add', 'update', 'delete'];
            if (count(config('constants.permissions'))) {
                $config_permissions = config('constants.permissions');
            }
            sort($permissions, SORT_NUMERIC);
             //dd("L-535 Team Role store permission", $permissions, ini_get('max_input_vars'));

            $role_permission_ids = [];
            foreach ($permissions as $permission_key => $permission) {


                $data = [];
                $main_menu_id = null;
                $sub_menu_id = null;
                if (isset($permission['cuid']) && count(explode("-", $permission['cuid'])) == 4) {
                    $cuid = explode("-", $permission['cuid']);
                    $main_menu_id = (isset($cuid[2])) ? $cuid[2] : null;
                    $sub_menu_id = (isset($cuid[3])) ? $cuid[3] : null;

                    // if($permission['cuid'] == "4-11-14-52"){  dd("L-547", $permission['cuid'], $cuid, $main_menu_id, $sub_menu_id, $permission); }
                }

                foreach ($config_permissions as $key => $config_permission) {
                    $data[$config_permission . '_flag'] = $permission[$config_permission] ?? 0;
                }


                $data['created_by'] = $loginUserId;
                $data['updated_by'] = $loginUserId;

                if ($company_id && $customer_role_id && $main_menu_id && $sub_menu_id && $data) {
                    DB::table('role_permissions')->updateOrInsert(
                        [
                            'company_id' => $company_id,
                            'customer_role_id' => $customer_role_id,
                            'main_menu_id' => $main_menu_id,
                            'sub_menu_id' => $sub_menu_id,
                        ],
                        $data
                    );
                } else {
                    dd("L-565", $cuid, $main_menu_id, $sub_menu_id, $permission, $data);
                }
                // dd("L-564", $permissions, $sub_menu_id, $permission, $permission['assigned_permission_id']);
            }

            if ($request?->btn_submit == 'submit_and_exit') {
                return Redirect::route($modules['route'] . '.index')->withSuccess('Assign permission update successfully.');
            }
            return Redirect::route($modules['route'] . '.assign-permission', $customer_role_id)->withSuccess('Assign permission update successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
