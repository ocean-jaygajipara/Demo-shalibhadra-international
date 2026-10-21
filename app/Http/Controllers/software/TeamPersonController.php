<?php

namespace App\Http\Controllers\software;

use App\Exports\TeamPersonExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionAppRequest;
use App\Http\Requests\TeamPersonRequest;
use App\Imports\TeamPersonImport;
use App\Models\MasterArea;
use App\Models\MasterCity;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\TeamPersonAssignArea;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Designation;
use App\Models\TeamPerson;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Area;
use App\Models\Company;
use App\Models\RolePermission;
use App\Models\RolePermissionsApp;
use App\Models\TeamPersonImportFile;
use App\Models\TeamRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class TeamPersonController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Team Person',
            'folder_path' => 'software.modules.team-person',
            'route' => 'team-person',
            'table_name' => (new TeamPerson())->getTable(),
            'permisstion_prefix' => 'team-person',
            'module_name' => 'Team Person',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }

    public function index(Request $request)
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
        View::share('modules', $modules);

        try {

            $columns = [
                // (object)[ 'data' => "id", 'name' => 'id', 'td_label' => 'Id' ],

                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],

                (object)['data' => "contact_info", 'name' => 'contact_info', 'td_label' => 'Person Details', 'className' =>  ''],
                (object)['data' => "user_info", 'name' => 'user_info', 'td_label' => 'User Info', 'className' =>  ''],
                (object)['data' => "country.name", 'name' => 'country_id', 'td_label' => 'Country', 'className' =>  ''],
                (object)['data' => "state.name", 'name' => 'state_id', 'td_label' => 'State', 'className' =>  ''],
                (object)['data' => "city.name", 'name' => 'city_id', 'td_label' => 'City', 'className' =>  ''],
                (object)['data' => "designation", 'name' => 'designation', 'td_label' => 'Designation', 'className' =>  ''],
                (object)['data' => "role", 'name' => 'role', 'td_label' => 'Role', 'className' =>  ''],
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
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                // dd($modules, Auth::guard('team_person')->check(), $modules['personal_data_permission'], $modules['all_data_permission'], $loginUserId);
                $data = TeamPerson::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check()) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('id', 'DESC');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                $data = $data->with(['designation', 'company', 'team_role', 'country', 'state', 'city']);
                $data = $data->orderBy('team_persons.id', 'desc');


                return DataTables::of($data)
                    ->addIndexColumn()

                    ->filter(function ($query) use ($request) {

                        if ((isset($modules['company_id']))) {
                            $query->where('company_id', $modules['company_id']);
                        }

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('filter_country') && $request->filter_country) {
                            $query->where('country_id', $request->filter_country);
                        }
                        if ($request->has('filter_state') && $request->filter_state) {
                            $query->where('state_id', $request->filter_state);
                        }
                        if ($request->has('filter_city') && $request->filter_city) {
                            $query->where('city_id', $request->filter_city);
                        }
                        if ($request->has('filter_team_role') && $request->filter_team_role) {
                            $query->where('team_role_id', $request->filter_team_role);
                        }
                        if ($request->has('filter_designation') && $request->filter_designation) {
                            $query->where('designation_id', $request->filter_designation);
                        }
                        // if ($request->has('search') && $request->search != '') {
                        //     $query->where('username', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('name', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('employee_code', 'like', "%" . $request->search . "%");
                        //     $query->orwhere('mobile_no', 'like', "%" . $request->search . "%");
                        // }

                        if ($request->has('search') && $request->search != '') {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('username', 'like', "%" . $search . "%")
                                    ->orWhere('name', 'like', "%" . $search . "%")
                                    ->orWhere('employee_code', 'like', "%" . $search . "%")
                                    ->orWhere('mobile_no', 'like', "%" . $search . "%");
                            });
                        }

                        // if ($request->has('search') && $request->search != '') {
                        //     $search = $request->search;
                        //     $query->where(function ($q) use ($search) {
                        //         $q->where('name', 'like', "%{$search}%")
                        //             ->orWhere('company_id', 'like', "%{$search}%")
                        //             ->orWhereHas('company', function ($q2) use ($search) {
                        //                 $q2->where('company_name', 'like', "%{$search}%");
                        //             })
                        //             ->orWhereHas('team_role', function ($q3) use ($search) {
                        //                 $q3->where('name', 'like', "%{$search}%");
                        //             })
                        //             ->orWhereHas('designation', function ($q4) use ($search) {
                        //                 $q4->where('name', 'like', "%{$search}%");
                        //             });
                        //     });
                        // }
                    })

                    ->addColumn('company_name', function ($row) {
                        return $row->company?->company_name;
                    })
                    ->addColumn('role', function ($row) {
                        return $row->team_role->name;
                    })
                    ->addColumn('designation', function ($row) {
                        return $row->designation->name;
                    })

                    // Ccontact Info
                    ->addColumn('contact_info', function ($row) {
                        $name = $row->name ?? '-';
                        $mobile = $row->mobile_no ?? '-';
                        $email = $row->email ?? '-';

                        $html = '<div class="text-start">';
                        $html .= '<div class="fw-semibold mb-1"><i class="fa fa-user me-1 text-primary"></i>' . htmlspecialchars($name) . '</div>';

                        if ($mobile && $mobile !== '-') {
                            $html .= '<div class="mb-1"><i class="fa fa-phone text-success me-1"></i><a href="tel:' . htmlspecialchars($mobile) . '">' . htmlspecialchars($mobile) . '</a></div>';
                        }

                        if ($email && $email !== '-') {
                            $html .= '<div><i class="fa fa-envelope text-primary me-1"></i><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></div>';
                        }

                        $html .= '</div>';
                        return $html;
                    })

                    // User Info
                    ->addColumn('user_info', function ($row) use ($modules) {
                        $storedPassword = $row->sp;

                        $originalPassword = substr($storedPassword, 4, -3);
                        $html = '<strong>' . ($row->username ?? '-') . '</strong><br>';
                        $html .= 'Code: ' . ($row->employee_code ?? '-').'<br>';
                        if ($modules['currentGuard'] === 'admin_software'){
                             $html .= '<strong> SP : </strong>' . ($storedPassword ?? '-').'<br>';
                             $html .= '<strong> App Key : </strong>' . ($row->company?->app_key ?? '-');
                        }

                        return $html;
                    })
                    ->addColumn('designation_role', function ($row) {
                        $designation = $row->designation ?? '-';
                        $role = $row->role ?? '-';

                        return '<strong>' . $designation . '</strong><br>' . $role;
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
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
                        $btn .= '
                                <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">';


                        if ($modules['update_permission']) {
                            $btn .= '<li>
                                <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item">
                                    <i class="fa-solid fa-pen-to-square me-2"></i> Edit
                                </a>
                            </li>';
                        }

                        if ($modules['delete_permission']) {
                            // Uncomment the below code to enable delete option
                            // $btn .= '<li>
                            //             <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton">
                            //                 <i class="fa-solid fa-trash me-2"></i> Delete
                            //             </a>
                            //         </li>';
                        }
                        if ($modules['add_permission']) {
                            $btn .= '<li>
                            <a href="' . route('team-person.assign.area', [$row->id]) . '"
                                class="dropdown-item mx-1"  target="_blank">
                                <i class="fa-solid fa-location-dot me-2"></i> Add Area
                            </a>
                        </li>';
                        }

                        if ($modules['approval_permission']) {
                            $btn .= '<li>
                                <a href="' . route('team-person.assign-permission', [$row->id]) . '"
                                    class="dropdown-item mx-1">
                                    <i class="menu-icon ti ti-user"></i> Assign Permission
                                </a>
                            </li>';
                        }

                        $btn .= '</ul></div>';

                        // if ($modules['update_permission'] === false && $modules['delete_permission'] === false) {
                        //     $btn = '-';
                        // }

                        return $btn;
                    })

                    ->rawColumns(['status', 'action', 'contact_info', 'user_info', 'designation_role'])
                    ->make(true);
            }
            $currentTeamPersonCount = $modules['restore_permission']
                ? TeamPerson::withTrashed()
                ->where('company_id', $modules['company_id'])
                ->where('created_by', $loginUserId)
                ->count()
                : TeamPerson::where('company_id', $modules['company_id'])
                ->where('created_by', $loginUserId)
                ->count();
            View::share('modules', $modules);

            View::share('currentTeamPersonCount', $currentTeamPersonCount);
            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return Redirect::route('software.dashboard')->withErrors($e->getMessage());
        }
    }

    public function create()
    {
        $modules = $this->modules;
        $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
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
            $currentTeamPersonCount = -1;
            if ($modules['company_id']) {
                $company = \App\Models\Company::find($modules['company_id']);

                $currentTeamPersonCount = $modules['restore_permission']
                    ? TeamPerson::withTrashed()
                    ->where('company_id', $company->id)
                    ->where('created_by', $loginUserId)
                    ->count()
                    : TeamPerson::where('company_id', $company->id)
                    ->where('created_by', $loginUserId)
                    ->count();
                // dd($currentTeamPersonCount);
                if ($company && $currentTeamPersonCount >= $company->max_team_user_count) {
                    return redirect()->route($modules['route'] . '.index')
                        ->withErrors('Team Person limit reached as per your plan. Please upgrade to add more.');
                }
            }
            // Auto-generate next employee code
            $lastEmployee = \App\Models\TeamPerson::where('company_id', $modules['company_id'])
                ->orderByDesc('id')
                ->first();

            if ($lastEmployee && preg_match('/EMP-(\d+)/', $lastEmployee->employee_code, $matches)) {
                $lastNumber = (int) $matches[1];
                $nextEmployeeCode = 'EMP-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextEmployeeCode = 'EMP-001';
            }

            $modules['nextEmployeeCode'] = $nextEmployeeCode;
            View::share('nextEmployeeCode', $nextEmployeeCode);



            View::share("currentTeamPersonCount", $currentTeamPersonCount);
            View::share('modules', $modules);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(TeamPersonRequest $request)
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

        try {
            $company_id = $modules['company_id'] ?? $request->company_id;

            $company = Company::find($company_id);
            // if (!$company) {
            //     return Redirect::back()->withErrors('Company not found.');
            // }

            $modules['company_id'] = $company->id;

            $currentTeamPersonCount = TeamPerson::where('company_id', $company->id)
                ->where('created_by', $loginUserId)
                ->count();


            if ($currentTeamPersonCount >= $company->max_team_user_count) {
                return Redirect::back()->withErrors('Team Person limit reached as per your plan. Please upgrade to add more.');
            }

            $validated = $request->validated();
            $validated['sp'] = Str::random(4) . $request->password . Str::random(3);
            $request['password'] = Hash::make($request->password);


            $validated['created_by'] = $loginUserId;

            $validated['team_role_id'] = $request->team_role_id ?: 0;
            $teamPerson = TeamPerson::create($validated);
            TeamPersonAssignArea::create([
                'company_id'   => $modules['company_id'],
                'team_person_id' => $teamPerson->id,
                'country_id'   => $request->country_id,
                'state_id'     => $request->state_id,
                'city_id'      => $request->city_id,
                'area_id'      => $request->area_id,
                'created_type' => ($modules['currentGuard'] == 'admin_software') ? 'Admin' : 'Team',
                'created_by'   => $loginUserId,
            ]);
            // dd($validated);
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function show(string $id)
    {
        if ($id = "print") {
            // return self::print();
        }
        dd($id);
    }
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
            $edit = TeamPerson::findOrFail($id);
            View::share('edit', $edit);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(TeamPersonRequest $request, string $id)
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
            if (($validated['password'] ?? null) === null) {
                unset($validated['password']);
            }


            $validated['updated_by'] = $loginUserId;
            $updateData = TeamPerson::findOrFail($id);
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

        $dataDelete = TeamPerson::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

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
            $teamPerson = TeamPerson::withTrashed()->findOrFail($request?->id);
            if ($teamPerson) {
                $teamPerson->status = $request->update_status;
                $teamPerson->save();
                if ($isAjax) {
                    return $this->sendResponse($teamPerson, $modules['title'] . ' status update successfully.');
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
        return Excel::download(new TeamPersonExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'TeamPerson-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }


    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
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


            $query = TeamPerson::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->withTrashed()
                ->with(['company'])
                ->orderBy('id', 'DESC');


            // Company filter
            if ($request->has('company') && !empty($request->company)) {
                $query->whereHas('company', function ($subQuery) use ($request) {
                    $subQuery->where('company_id', 'like', '%' . $request->company . '%');
                });
            }

            // Country filter
            if ($request->has('country_id') && !empty($request->country_id)) {
                $query->where('country_id', $request->country_id);
            }

            // State filter
            if ($request->has('state_id') && !empty($request->state_id)) {
                $query->where('state_id', $request->state_id);
            }

            // City filter
            if ($request->has('city_id') && !empty($request->city_id)) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->has('designation_id') && !empty($request->designation_id)) {
                $query->where('designation_id', $request->designation_id);
            }

            if ($request->has('team_role_id') && !empty($request->team_role_id)) {
                $query->where('team_role_id', $request->team_role_id);
            }

            //Search
            if ($request->has('search') && $request->search != '') {
                $query->where(function ($q) use ($request) {
                    $q->where('username', 'like', "%" . $request->search . "%")
                        ->orWhere('name', 'like', "%" . $request->search . "%")
                        ->orWhere('employee_code', 'like', "%" . $request->search . "%")
                        ->orWhere('mobile_no', 'like', "%" . $request->search . "%");
                });
            }

            $TeamPerson = $query->get();

            return view($modules['folder_path'] . '.print', compact('TeamPerson', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function import()
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        View::share('modules', $modules);

        return view($modules['folder_path'] . '.import');
    }
    public function import_store(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $authLoginUserDetail = $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_Id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        View::share('modules', $modules);
        View::share('company_id', $modules['company_id']);

        $validator = Validator::make($request->all(), [
            'company_id'   => 'required',
            'import_file'  => 'required|mimes:xls,xlsx|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'errors' => $validator->errors()
            ]);
        }

        try {
            if ($request->hasFile('import_file')) {
                $company = Company::find($request->company_id);
                $company_name = $company?->company_name;
                $company_slug = Str::slug($company_name);

                $file = $request->file('import_file');
                $extension = $file->getClientOriginalExtension();
                $filename = now()->format('dmYHis') . rand() . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $request->company_id . "-" . $company_slug . "/team-person/{$year}-{$month}/import-team-person-file";
                $path = $file->storeAs($folder, $filename, 'public');

                $filename_path = "{$folder}/{$filename}";
            }

            $teamPersonImportFileId = TeamPersonImportFile::insertGetId([
                'company_id' => $request->company_id,
                'filename' => $filename_path,
                'total_team_persons' => 0,
                'total_success_team_persons' => 0,
                'total_failed_team_persons' => 0,
                'errors' => '',
                'created_by' => $loginUserId,
            ]);

            $company_id = $request->company_id;

            Excel::import(
                new TeamPersonImport($company_id, $loginUserId, $modules['currentGuard'], $teamPersonImportFileId, $authLoginUserDetail),
                $request->file('import_file')
            );

            $teamPersonImportFile = TeamPersonImportFile::find($teamPersonImportFileId);


            session()->flash('team_person_summary', [
                'total_team_persons' => $teamPersonImportFile->total_team_persons,
                'total_success_team_persons' => $teamPersonImportFile->total_success_team_persons,
                'total_failed_team_persons' => $teamPersonImportFile->total_failed_team_persons,
            ]);


            if (!empty($teamPersonImportFile->errors)) {
                if ($teamPersonImportFile->total_success_team_persons > 0) {

                    return Redirect::back()
                        ->with('teampersonImportFileMessages', json_decode($teamPersonImportFile->errors))
                        ->with('success', 'Team Persons imported successfully with some errors!');
                } else {
                    // all failed
                    return Redirect::back()
                        ->with('teampersonImportFileMessages', json_decode($teamPersonImportFile->errors));
                }
            }


            return back()->with('success', 'Team Persons imported successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function assignArea(Request $request, $id)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = $this->currentGuard ?? null;
        $authLoginUserDetail = $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_Id = $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        $modules['breadcrumb'] = [
            ['title' => 'Team Person', 'url' => route('team-person.index')],
            ['title' => 'Assign Area', 'url' => ''],
        ];

        View::share('modules', $modules);

        try {
            $teamperson = TeamPerson::with(['company', 'country', 'state', 'city'])->findOrFail($id);

            if (!$modules['company_id']) {
                $modules['company_id'] = $teamperson->company_id ?? null;
            }

            View::share('company_id', $modules['company_id']);
            $action = $request->input('action');

            if ($action === 'delete') {
                // Validate only required for delete
                $request->validate([
                    'company_id' => ['required', 'integer'],
                    'country_id' => ['required', 'integer'],
                    'state_id'   => ['required', 'integer'],
                ]);

                // Build delete query
                $query = TeamPersonAssignArea::where([
                    'company_id'     => $request->company_id,
                    'team_person_id' => $id,
                    'country_id'     => $request->country_id,
                    'state_id'       => $request->state_id,
                ]);

                if ($request->filled('city_id')) {
                    $query->where('city_id', $request->city_id);
                }

                if ($request->filled('area_id')) {
                    $query->where('area_id', $request->area_id);
                }

                $deletedCount = $query->delete();

                if ($deletedCount > 0) {
                    return redirect()->back()->with('success', "$deletedCount area(s) deleted successfully.");
                } else {
                    return redirect()->back()->with('error', 'No records found to delete.');
                }
            }

            if ($request->isMethod('post')) {
                // dd($request->all());
                $request->validate([
                    'company_id' => ['required', 'integer', 'exists:' . (new Company())->getTable() . ',id'],
                    'country_id' => ['required', 'integer', 'exists:' . (new MasterCountry())->getTable() . ',id'],
                    'state_id'   => ['required', 'integer', 'exists:' . (new MasterState())->getTable() . ',id'],
                    'city_id'    => ['nullable', 'integer', 'exists:' . (new MasterCity())->getTable() . ',id'],
                    'area_id'    => ['nullable', 'integer', 'exists:' . (new MasterArea())->getTable() . ',id'],
                ]);

                $assigned = 0;

                if ($request->city_id && $request->area_id) {
                    // CASE: Specific area selected
                    $exists = TeamPersonAssignArea::where([
                        'company_id'     => $request->company_id,
                        'team_person_id' => $id,
                        'country_id'     => $request->country_id,
                        'state_id'       => $request->state_id,
                        'city_id'        => $request->city_id,
                        'area_id'        => $request->area_id,
                    ])->exists();

                    if (!$exists) {
                        TeamPersonAssignArea::create([
                            'company_id'     => $request->company_id,
                            'team_person_id' => $id,
                            'country_id'     => $request->country_id,
                            'state_id'       => $request->state_id,
                            'city_id'        => $request->city_id,
                            'area_id'        => $request->area_id,
                            'created_type'   => ($modules['currentGuard'] == 'admin_software') ? 'Admin' : 'Team',
                            'created_by'     => $loginUserId,
                        ]);
                        $assigned++;
                    }
                } elseif ($request->city_id && !$request->area_id) {
                    // CASE: All areas from selected city
                    $areas = MasterArea::where('city_id', $request->city_id)
                        ->where('company_id', $request->company_id)
                        ->get();
                    foreach ($areas as $area) {
                        $exists = TeamPersonAssignArea::where([
                            'company_id'     => $request->company_id,
                            'team_person_id' => $id,
                            'country_id'     => $request->country_id,
                            'state_id'       => $request->state_id,
                            'city_id'        => $request->city_id,
                            'area_id'        => $area->id,
                        ])->exists();

                        if (!$exists) {
                            TeamPersonAssignArea::create([
                                'company_id'     => $request->company_id,
                                'team_person_id' => $id,
                                'country_id'     => $request->country_id,
                                'state_id'       => $request->state_id,
                                'city_id'        => $request->city_id,
                                'area_id'        => $area->id,
                                'created_type'   => ($modules['currentGuard'] == 'admin_software') ? 'Admin' : 'Team',
                                'created_by'     => $loginUserId,
                            ]);
                            $assigned++;
                        }
                    }
                } elseif (!$request->city_id && !$request->area_id) {
                    // CASE: All cities and their areas from selected state
                    $cities = MasterCity::where('state_id', $request->state_id)->get();
                    foreach ($cities as $city) {
                        $areas = MasterArea::where('city_id', $city->id)
                            ->where('company_id', $request->company_id)
                            ->get();
                        foreach ($areas as $area) {
                            $exists = TeamPersonAssignArea::where([
                                'company_id'     => $request->company_id,
                                'team_person_id' => $id,
                                'country_id'     => $request->country_id,
                                'state_id'       => $request->state_id,
                                'city_id'        => $city->id,
                                'area_id'        => $area->id,
                            ])->exists();

                            if (!$exists) {
                                TeamPersonAssignArea::create([
                                    'company_id'     => $request->company_id,
                                    'team_person_id' => $id,
                                    'country_id'     => $request->country_id,
                                    'state_id'       => $request->state_id,
                                    'city_id'        => $city->id,
                                    'area_id'        => $area->id,
                                    'created_type'   => ($modules['currentGuard'] == 'admin_software') ? 'Admin' : 'Team',
                                    'created_by'     => $loginUserId,
                                ]);
                                $assigned++;
                            }
                        }
                    }
                }


                if ($assigned > 0) {
                    return redirect()->back()->with('success', "$assigned area(s) assigned successfully.");
                } else {
                    return redirect()->back()->with('error', 'No new areas were assigned. Possibly all already assigned.');
                }
            }


            $assignedAreas = TeamPersonAssignArea::with(['country', 'state', 'city', 'area'])
                ->where('company_id', $modules['company_id'])
                ->where('team_person_id', $teamperson->id)
                ->get();

            View::share('assignedAreas', $assignedAreas);

            return view($modules['folder_path'] . '.area', compact('teamperson'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    public function assignedAreasData(Request $request, $id)

    {
        $query = TeamPersonAssignArea::with(['country', 'state', 'city', 'area'])
            ->where('team_person_id', $id);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('country', fn($row) => $row->country->name ?? '-')
            ->addColumn('state', fn($row) => $row->state->name ?? '-')
            ->addColumn('city', fn($row) => $row->city->name ?? '-')
            ->addColumn('area', fn($row) => $row->area->area_name ?? '-')
            ->addColumn('action', function ($row) use ($request) {
                $deleteRoute = route('team-person-assign-area.destroy', $row->id);
                $btn = '<a href="javascript:void(0)"
                            data-id="' . $row->id . '"
                            data-did="' . $deleteRoute . '"
                            class="btn btn-danger btn-icon deletebutton mx-1"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>';
                return $btn;
            })
            ->rawColumns(['country', 'state', 'city', 'area', 'action'])
            ->make(true);
    }
    public function destroyAssignedArea(Request $request, string $id)
    {
        try {
            $dataDelete = TeamPersonAssignArea::findOrFail($id);
            $dataDelete->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assigned area deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
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
            $team_person = TeamPerson::with(['company', 'team_role'])->findOrFail($id);
            //if ($modules['currentGuard'] != 'admin_software') {

            if ($team_person?->team_role_id == 0) {
                $message = "You can not assign permission. Please contact to Company.";
                if (isset($request) && $request->ajax()) {
                    return $this->sendError($message, [], [], 403);
                }
                return Redirect::route($modules['route'] . '.index')->withErrors($message);
            }
            //}

            View::share('team_person', $team_person);
            if (!$team_person) {
                return Redirect::back()->withErrors(["Team Person not found."]);
            }

            $company = Company::where('status', 'active')->where('id', $team_person?->company_id)->first();

            $panel_sub_modules = Helper::getSubMenu(['platform' => 'app', 'id' => ['type' => 'in', 'values' => $company?->app_right]], "active");
            if (count($panel_sub_modules)) {
                $panel_sub_modules = $panel_sub_modules->sortBy(fn($item) => $item['main_menu']['order_by'] ?? 0);
                $panel_sub_modules = $panel_sub_modules->groupBy('main_menu_id');

                $panel_sub_modules = $panel_sub_modules->map(function ($items) use ($modules, $team_person, $loginUserId) {
                    $mainMenu = $items->first()?->mainMenu;

                    // $allowedSubMenus = [];
                    // //if ($modules['currentGuard'] != 'admin_software') {
                    //     $allowedSubMenus = RolePermission::where('company_id', $team_person?->company_id)
                    //     ->where('team_role_id', $team_person?->team_role?->id)
                    //     ->where(function ($q) {
                    //         $q->where('view_flag', 1)
                    //           ->orWhere('add_flag', 1)
                    //           ->orWhere('update_flag', 1)
                    //           ->orWhere('delete_flag', 1)
                    //           ->orWhere('approval_flag', 1)
                    //           ->orWhere('all_data_flag', 1)
                    //           ->orWhere('personal_data_flag', 1);
                    //     })
                    //     ->pluck('sub_menu_id')
                    //     ->toArray();
                    //    $items = $items->filter(fn($item) => in_array($item['id'], $allowedSubMenus));
                    // //}


                    $mainMenu['sub_menus'] = $items->map(function ($item) {
                        unset($item['main_menu']);
                        return $item->toArray();
                    })->values();
                    return $mainMenu;
                });
                $panel_sub_modules = $panel_sub_modules->values();

                $panel_sub_modules = $panel_sub_modules->sortBy(fn($item) => $item['order_by'] ?? 0);
                $panel_sub_modules = $panel_sub_modules->values();
                //dd($panel_sub_modules);
            }
            View::share('panel_sub_modules', $panel_sub_modules);

            // dd($panel_sub_modules);

            /** Assigned Permission */
            $assignedPermission = RolePermissionsApp::query();
            $assignedPermission = $assignedPermission?->where('company_id', $team_person?->company_id);
            $assignedPermission = $assignedPermission?->where('team_role_id', $team_person?->team_role?->id);
            $assignedPermission = $assignedPermission?->where('team_id', $team_person?->id);
            if ($panel_sub_modules->count()) {
                $assignedPermission = $assignedPermission?->whereIn('main_menu_id', $panel_sub_modules->pluck('id'));
            }
            $assignedPermission = $assignedPermission?->get();
            $assignedPermission = $assignedPermission?->keyBy('role_permission_app_uuid');
            View::share('assignedPermission', $assignedPermission);


            return view($modules['folder_path'] . '.permission');
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store_permission(PermissionAppRequest $request)
    {
        //dd($request->all());
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

        // dd($request->all(), phpinfo());
        try {

            $company_id = $request['company_id'];
            $team_role_id = $request['team_role_id'];
            $team_id = $request['team_id'];
            $permissions = $request['permissions'];
            $config_permissions = ['view', 'add', 'update', 'delete'];
            if (count(config('constants.app_permissions'))) {
                $config_permissions = config('constants.app_permissions');
            }
            sort($permissions, SORT_NUMERIC);

            $role_permission_ids = [];
            foreach ($permissions as $permission_key => $permission) {

                $data = [];
                $main_menu_id = null;
                $sub_menu_id = null;
                if (isset($permission['uuid']) && count(explode("-", $permission['uuid'])) == 5) {
                    $uuid = explode("-", $permission['uuid']);
                    $main_menu_id = (isset($uuid[3])) ? $uuid[3] : null;
                    $sub_menu_id = (isset($uuid[4])) ? $uuid[4] : null;

                    // if($permission['uuid'] == "4-11-14-52"){  dd("L-547", $permission['uuid'], $uuid, $main_menu_id, $sub_menu_id, $permission); }
                }

                foreach ($config_permissions as $key => $config_permission) {
                    $data[$config_permission . '_flag'] = $permission[$config_permission] ?? 0;
                }


                $data['created_by'] = $loginUserId;
                $data['updated_by'] = $loginUserId;

                if ($company_id && $team_role_id && $main_menu_id && $sub_menu_id && $data) {
                    DB::table('role_permissions_app')->updateOrInsert(
                        [
                            'company_id' => $company_id,
                            'team_role_id' => $team_role_id,
                            'team_id' => $team_id,
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

            if ($request?->btn_submit == 'submit_and_exit') {
                return Redirect::route($modules['route'] . '.index')->withSuccess('Assign permission update successfully.');
            }
            return Redirect::route($modules['route'] . '.assign-permission', $team_id)->withSuccess('Assign permission update successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
