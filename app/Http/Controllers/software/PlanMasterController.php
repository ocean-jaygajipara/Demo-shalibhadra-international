<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlanMasterRequest;
use App\Models\MainMenu;
use App\Models\PlanMaster;
use App\Models\SubMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class PlanMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        $this->modules = [
            'title' => 'Plan Master',
            'folder_path' => 'software.modules.plan-master',
            'route' => 'plan-master',
            'table_name' => (new PlanMaster())->getTable(),
            'permisstion_prefix' => 'plan-master',
            'module_name' => 'PlanMaster',
        ];

        // View::share("PlanForm", config('constants.platform'));
        View::share("PlanType", PlanMaster::$PlanType);

        $app_right_list = SubMenu::with(['mainMenu'])->where('platform', 'app')->where('status', 'active')->orderBy('id', 'asc')->get();
        // $panel_right_list = SubMenu::with(['mainMenu'])->where('platform', 'panel')->where('status', 'active')->orderBy((new SubMenu())->getTable().'.order_by', 'asc')->get();

        /*
        $panel_right_list = SubMenu::whereHas('mainMenu', function ($query) {
            $query->whereNotIn('name', ["Plan Master","Admin Settings", "Manage Email"]);
        })->with(['mainMenu']);
        $panel_right_list = $panel_right_list->where((new SubMenu())->getTable() . '.platform', 'panel')->where((new SubMenu())->getTable() . '.status', 'active');
        $panel_right_list = $panel_right_list->orderBy((new SubMenu())->getTable() . '.order_by', 'asc')->get();
        */

        $panel_right_list = MainMenu::with(['sub_menu'])->whereNotIn('name', ["Plan Master","Admin Settings", "Manage Email"])->where((new MainMenu())->getTable() . '.platform', 'panel')->where((new MainMenu())->getTable() . '.status', 'active')->orderBy((new MainMenu())->getTable() . '.order_by', 'asc')->get();
        // $panel_right_list = SubMenu::with(['mainMenu'])->where('platform', 'panel')->where('status', 'active')->orderBy((new MainMenu())->getTable().'.order_by', 'asc')->orderBy((new SubMenu())->getTable().'.order_by', 'asc')->get();

        View::share('app_right_list', $app_right_list);
        View::share('panel_right_list', $panel_right_list);
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        try {

            if (Auth::guard('admin_software')->check()) {
                $loginUserId = Auth::guard('admin_software')->user()->id;
            }
            if (Auth::guard('team_person')->check()) {
                $loginUserId = Auth::guard('team_person')->user()->id;
            }
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            if ($request->ajax()) {
                if (!$modules['viewPermission']) {

                    return $this->sendError('Unauthorized', [], [], 403);
                }
            }

            if (!$modules['viewPermission']) {
                abort(403, 'Unauthorized');
            }

            View::share('modules', $modules);

            if ($request->ajax()) {
                $data = PlanMaster::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check()) {
                            if ($modules['personalDataPermission'] && $modules['allDataPermission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('id', 'DESC');
                $data = $data->withTrashed();


                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        // if ($request->has('search')) {
                        //     $query->where('name', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('plan_valid_day', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('max_team_user_count', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('max_customer_count', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('max_inquiry_count', 'like', "%" . $request->search . "%");
                        //     $query->orWhere('plan_type ', 'like', "%" . $request->search . "%");
                        // }
                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                            $query->orWhere('plan_type', 'like', "%" . $request->search . "%");
                        }
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';


                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                            // $btn = '<span class="badge bg-success bg-glow">Active</span>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-success update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="active">Active</a></li>';
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
                            if ($modules['editPermission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['deletePermission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
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
                return $returnData;
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
        $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
        if (!$modules['addPermission']) {
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);

        try {

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PlanMasterRequest $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
        if (!$modules['addPermission']) {
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);
        //return $request->all();
        $validated = $request->validated();

        try {
            // return $this->authenticateLoginUserDetailsginUserDetails;
            $validated['created_by'] = $loginUserId;
            // return $validated;
            PlanMaster::create($validated);

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            // return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ($id = "print") {
            // return self::print();
        }
        dd($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $modules = $this->modules;
        $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
        if (!$modules['editPermission']) {
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);

        try {

            $edit = PlanMaster::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PlanMasterRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
        if (!$modules['editPermission']) {
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);

        try {
            // Validation
            $validated = $request->validated();

            $validated['updated_by'] = $loginUserId;
            $updateData = PlanMaster::findOrFail($id);
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

        $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
        $isAjax = $request->ajax();

        if (!$modules['deletePermission']) {

            if ($isAjax) {
                return $this->sendError("Unauthorized");
            } else {
                abort(403, 'Unauthorized');
            }
        }


        try {
            $dataDelete = PlanMaster::findOrFail($id);

            // Soft delete logic
            $loginUserId = null;
            if (Auth::guard('admin_software')->check()) {
                $loginUserId = Auth::guard('admin_software')->user()->id;
            }
            if (Auth::guard('team_person')->check()) {
                $loginUserId = Auth::guard('team_person')->user()->id;
            }
            $dataDelete->deleted_by = $loginUserId;
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

        try {

            $state = PlanMaster::withTrashed()->findOrFail($id);
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
        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'update_status' => ['required', 'in:active,inactive']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }


        try {
            $state = PlanMaster::withTrashed()->findOrFail($request?->id);
            if ($state) {
                $state->status = $request->update_status;
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
}
