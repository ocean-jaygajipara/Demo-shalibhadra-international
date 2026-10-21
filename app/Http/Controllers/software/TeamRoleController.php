<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamRoleRequest;
use App\Http\Requests\PermissionRequest;
use App\Models\Company;
use App\Models\TeamRole;
use App\Models\MainMenu;
use App\Models\MasterModules;
use App\Models\RolePermission;
use App\Models\SubMenu;
use App\Models\TeamPerson;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TeamRoleController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Team Role',
            'folder_path' => 'software.modules.team-role',
            'route' => 'team-role',
            'table_name' => (new TeamRole())->getTable(),
            'permisstion_prefix' => 'team-role',
            'module_name' => 'Team Role',
        ];
    }

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
        // return $modules;
        View::share('modules', $modules);

        try {

            $columns = [
                (object)['data' => 'DT_RowIndex','name' => 'DT_RowIndex','td_label' => 'Sr No.','orderable' => false,'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "parent_name", 'name' => 'parent_id', 'td_label' => 'Parent', 'orderable' => false, 'searchable' => false, 'className' =>  ''],
                (object)['data' => "name", 'name' => 'name', 'td_label' => 'Name', 'className' =>  ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  'w-10 text-start'],
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
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                // dd($request->all());
                $data = TeamRole::select('*');

                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard($this->currentGuard)->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);
                        $query->where('parent_id', "!=", 0);

                        if (!$modules['parent_type_id']) {
                            // $query->where(function($query1) {
                            //     $query1->where('created_type', "!=", 'admin_software')
                            //     ->orWhereNull('created_type');
                            // });
                        }

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            // $query->whereNotNull('created_type');
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
                // dd($data->toSql(), $data->get());
                $data = $data->with(['company']);

                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                // dd($this->currentGuard, $data->get()->toArray());
                $data = $data->orderBy('id', 'asc');
                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                        }
                    })
                    ->addColumn('parent_name', function ($row) use ($modules) {
                        return $row?->parent_name;
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        // $btn = ucfirst($row->status);

                        // $btn .= '<ul class="dropdown-menu" style="">
                        // <li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route('master-country.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Active</a></li>
                        // <li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route('master-country.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Inactive</a></li>
                        // </ul>';

                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm
                            dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item
                             waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                            // $btn = '<span class="badge bg-success bg-glow">Active</span>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm
                            dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown"
                            aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item
                            waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
                            // $btn = '<span class="badge bg-danger bg-glow">In-Active</span>';
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
                    ->rawColumns(['status', 'action', 'parent_name'])
                    ->make(true);

                return $returnData;
            }

            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
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
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;

        $modules['product_view'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.view'), 'Product']);
        // dd($modules['currentGuard']);
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

            $roles = TeamRole::whereNull('deleted_at')->get(); // List of available parent roles
            View::share('roles', $roles);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TeamRoleRequest $request)
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
        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
        $validated = $request->validated();
        View::share('modules', $modules);
        $validated = $request->validated();
        $validated['created_type'] = ($modules['currentGuard'] === 'team_person') ? 'Team' : 'Admin';




        try {
            // $validated['created_type'] = ($modules['currentGuard'] === 'team_person') ? 'TeamPerson' : 'Admin';
            $validated['created_by'] = $loginUserId;

            // return $validated;
            TeamRole::create($validated);
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
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

            $edit = TeamRole::findOrFail($id);
            if ($modules['currentGuard'] != 'admin_software' && $edit?->parent_id == 0) {
                $message = "You can not edit this data. contact to Company.";
                abort(403, $message);
            }
            $roles = TeamRole::where('id', '!=', $id)->whereNull('deleted_at')->get(); // Avoid setting self as parent
            View::share([
                'edit' => $edit,
                'roles' => $roles
            ]);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TeamRoleRequest $request, string $id)
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
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
             $validated = $request->validated();
            $validated['updated_by'] = $loginUserId;
            // return $validated;
            $updateData = TeamRole::findOrFail($id);
            if ($modules['currentGuard'] != 'admin_software' && $updateData?->parent_id == 0) {
                $message = "You can not edit this data. contact to Company.";
                abort(403, $message);
            }
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

        $isAjax = ($request->ajax()) ? true : false;


        $dataDelete = TeamRole::findOrFail($id);
        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

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

            $country = TeamRole::withTrashed()->findOrFail($id);
            $country->restore();

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
            $country = TeamRole::withTrashed()->findOrFail($request?->id);
            if ($country) {
                $country->status = $request->update_status;
                $country->save();
                if ($isAjax) {
                    return $this->sendResponse($country, $modules['title'] . ' status update successfully.');
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
        // return $modules;
        if (!$modules['approval_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            $team_role = TeamRole::with(['company'])->findOrFail($id);
            if ($modules['currentGuard'] != 'admin_software') {
                /*
                if($id == $loginUserId){
                    $message = "You can not access this team role assign permission. Please contact to Company.";
                    if (isset($request) && $request->ajax()) {
                        return $this->sendError($message, [], [], 403);
                    }
                    return Redirect::route($modules['route'] . '.index')->withErrors($message);

                }
                */
                if ($team_role?->parent_id == 0) {
                    $message = "You can not assign permission. Please contact to Company.";
                    if (isset($request) && $request->ajax()) {
                        return $this->sendError($message, [], [], 403);
                    }
                    return Redirect::route($modules['route'] . '.index')->withErrors($message);
                }
            }

            View::share('team_role', $team_role);
            // return $team_role;
            if (!$team_role) {
                return Redirect::back()->withErrors(["Team role not found."]);
            }

            $company = Company::where('status', 'active')->where('id', $team_role?->company_id)->first();


            // $main_menu_id  = MasterModules::where('status', 'active')->whereIn('id', $company->panel_right)->where('platform', 'panel')->pluck('main_menu_id')->toArray();
            // $mainMenu = MainMenu::where('status', 'active')->whereIn('id', $main_menu_id)->get();
            // View::share('mainMenu', $mainMenu);
            // return $main_menu_id;

            $panel_right_list = Helper::getMainMenu(['platform' => 'panel', 'id' => ['type' => 'in', 'values' => $company?->panel_right]]);
            $panel_sub_modules = Helper::getSubMenu(['platform' => 'panel', 'id' => ['type' => 'in', 'values' => $company?->panel_right]], "active");
            if (count($panel_sub_modules)) {
                $panel_sub_modules = $panel_sub_modules->sortBy(fn($item) => $item['main_menu']['order_by'] ?? 0);
                $panel_sub_modules = $panel_sub_modules->groupBy('main_menu_id');
                // dd("L-431", $panel_sub_modules->values()?->first());

                $panel_sub_modules = $panel_sub_modules->map(function ($items) use ($modules, $team_role, $loginUserId) {
                    $mainMenu = $items->first()?->mainMenu;  // One main_menu
                    // dd("L-571", $items->toArray(), $mainMenu->toArray());

                    // $mainMenu = $items->first()->main_menu; // One main_menu
                    $allowedSubMenus = [];
                    if ($modules['currentGuard'] != 'admin_software') {
                        // Get all allowed sub_menu_ids for this team_role and main_menu
                        $allowedSubMenus = RolePermission::where('company_id', $team_role?->company_id)
                            // ->where('team_role_id', $loginUserId)
                            ->where('team_role_id', $team_role?->parent_id)
                            // ->where('main_menu_id', $mainMenu?->id)
                            ->where('view_flag', '1');

                        // $allowedSubMenus = $allowedSubMenus->get();

                        // dd("L-592", $team_role->toArray(), $allowedSubMenus->get(), Helper::interpolateQuery($allowedSubMenus->toSql(), $allowedSubMenus->getBindings()), $team_role?->toArray(), Auth::user()?->toArray(), $loginUserId, $items->toArray());
                        $allowedSubMenus = $allowedSubMenus->pluck('sub_menu_id')->toArray();

                        // Attach sub_menus (filtered if needed)
                        $items = $items->filter(function ($item) use ($modules, $allowedSubMenus) {
                            if ($modules['currentGuard'] != 'admin_software') {
                                return in_array($item['id'], $allowedSubMenus);
                            }
                            return true; // Keep all if admin_software
                        });
                        // dd("L-606", $team_role->toArray(), $allowedSubMenus,  $items->toArray());
                    }

                    // Attach sub_menus by removing 'main_menu' key from each submenu
                    $mainMenu['sub_menus'] = $items->map(function ($item) use ($modules, $team_role, $mainMenu) {

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



            /** Assigned Permission */
            $assignedPermission = RolePermission::query();
            $assignedPermission = $assignedPermission?->where('company_id', $team_role?->company_id);
            $assignedPermission = $assignedPermission?->where('team_role_id', $team_role?->id);
            if ($panel_sub_modules->count()) {
                $assignedPermission = $assignedPermission?->whereIn('main_menu_id', $panel_sub_modules->pluck('id'));
            }
            $assignedPermission = $assignedPermission?->get();
            $assignedPermission = $assignedPermission?->keyBy('role_permission_uuid');
            View::share('assignedPermission', $assignedPermission);

            // dd("L-471", $assignedPermission->toArray(), $panel_sub_modules->toArray(), $panel_sub_modules->pluck('id'));


            // $permissions = DB::table('role_permissions')
            //     ->where('main_menu_id', $request->main_menu_id)
            //     ->where('team_role_id', $request->team_role_id)
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

    public function get_submenu(Request $request)
    {
        $modules = $this->modules;
        try {
            View::share('modules', $modules);

            $company = Company::where('status', 'active')->where('id', $request->company_id)->first();

            $sub_menu_ids = MasterModules::where('status', 'active')->whereIn('id', $company->panel_right)->where('platform', 'panel')->pluck('sub_menu_id')->toArray();

            $subMenus = SubMenu::where('main_menu_id', $request->main_menu_id)->whereIn('id', $sub_menu_ids)->get();

            $permissions = DB::table('role_permissions')
                ->where('main_menu_id', $request->main_menu_id)
                ->where('team_role_id', $request->team_role_id)
                ->where('company_id', $request->company_id)
                ->get()
                ->keyBy('sub_menu_id');

            $data = [
                'subMenus' => $subMenus,
                'permissions' => $permissions,
            ];
            return $this->sendResponse($data, $modules['title'] . ' Submenu list');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), $e->getMessage(), [], 401);
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
                'team_role_id' => [
                    'required',
                    Rule::exists((new TeamRole())->getTable(), 'id'),
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
            $team_role_id = $request['team_role_id'];
            $permissions = $request['permissions'];
            $config_permissions = ['view', 'add', 'update', 'delete'];
            if (count(config('constants.permissions'))) {
                $config_permissions = config('constants.permissions');
            }
            sort($permissions, SORT_NUMERIC);
            // dd("L-535 Team Role store permission", $permissions, ini_get('max_input_vars'));

            $role_permission_ids = [];
            foreach ($permissions as $permission_key => $permission) {


                $data = [];
                $main_menu_id = null;
                $sub_menu_id = null;
                if (isset($permission['uuid']) && count(explode("-", $permission['uuid'])) == 4) {
                    $uuid = explode("-", $permission['uuid']);
                    $main_menu_id = (isset($uuid[2])) ? $uuid[2] : null;
                    $sub_menu_id = (isset($uuid[3])) ? $uuid[3] : null;

                    // if($permission['uuid'] == "4-11-14-52"){  dd("L-547", $permission['uuid'], $uuid, $main_menu_id, $sub_menu_id, $permission); }
                }

                foreach ($config_permissions as $key => $config_permission) {
                    $data[$config_permission . '_flag'] = $permission[$config_permission] ?? 0;
                }


                $data['created_by'] = $loginUserId;
                $data['updated_by'] = $loginUserId;

                if ($company_id && $team_role_id && $main_menu_id && $sub_menu_id && $data) {
                    DB::table('role_permissions')->updateOrInsert(
                        [
                            'company_id' => $company_id,
                            'team_role_id' => $team_role_id,
                            'main_menu_id' => $main_menu_id,
                            'sub_menu_id' => $sub_menu_id,
                        ],
                        $data
                    );
                } else {
                    dd("L-565", $uuid, $main_menu_id, $sub_menu_id, $permission, $data);
                }
                // dd("L-564", $permissions, $sub_menu_id, $permission, $permission['assigned_permission_id']);
            }

            /*
            old code before 09-06-2025
            $company_id = $request['company_id'];
            $team_role_id = $request['team_role_id'];
            $main_menu_id = $request['main_menu_id'];
            $config_permissions = ['view', 'add', 'update', 'delete'];
            if (count(config('constants.permissions'))) {
            $config_permissions = config('constants.permissions');
            }
            $permissions = $request['permissions'];
            $validated['updated_by'] = $loginUserId;
            $validated['created_by'] = $loginUserId;

            dd("L-535", $permissions);
            foreach ($permissions as $sub_menu_id => $permission) {
            $data = [];
            foreach ($config_permissions as $key => $config_permission) {
            $data[$config_permission . '_flag'] = $permission[$config_permission] ?? 0;
            }
            $data['created_by'] = $loginUserId;
            $data['updated_by'] = $loginUserId;
            DB::table('role_permissions')->updateOrInsert(
            [
            'company_id' => $company_id,
            'team_role_id' => $team_role_id,
            'main_menu_id' => $main_menu_id,
            'sub_menu_id' => $sub_menu_id,
            ],
            $data
            );
            }
            */


            /*
            old code
            foreach ($permissions as $sub_menu_id => $permission) {
            $data = [
            'view_flag' => $permission['view'] ?? 0,
            'add_flag' => $permission['add'] ?? 0,
            'update_flag' => $permission['update'] ?? 0,
            'delete_flag' => $permission['delete'] ?? 0,
            'print_flag' => $permission['print'] ?? 0,
            'excel_flag' => $permission['excel'] ?? 0,
            'approval_flag' => $permission['approval'] ?? 0,
            'all_data_flag' => $permission['all_data'] ?? 0,
            'personal_data_flag' => $permission['personal_data'] ?? 0,
            ];

            DB::table('role_permissions')->updateOrInsert(
            [
            'company_id' => $company_id,
            'team_role_id' => $team_role_id,
            'main_menu_id' => $main_menu_id,
            'sub_menu_id' => $sub_menu_id,
            'created_by' => $validated['created_by'],
            'updated_by' => $validated['updated_by'],
            ],
            $data
            );
            }
            */

            if ($request?->btn_submit == 'submit_and_exit') {
                return Redirect::route($modules['route'] . '.index')->withSuccess('Assign permission update successfully.');
            }
            return Redirect::route($modules['route'] . '.assign-permission', $team_role_id)->withSuccess('Assign permission update successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
