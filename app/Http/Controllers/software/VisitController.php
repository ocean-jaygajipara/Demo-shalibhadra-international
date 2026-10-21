<?php

namespace App\Http\Controllers\software;

use App\Exports\VisitExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\TeamPerson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;





class VisitController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Visit',
            'folder_path' => 'software.modules.visit',
            'route' => 'visit',
            'table_name' => (new Visit())->getTable(),
            'permisstion_prefix' => 'visit',
            'module_name' => 'Visit',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null

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
        View::share('modules', $modules);
        try {
            $columns = [
                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Id'],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  'w-5 text-wrap'],
                (object)['data' => "teamperson.name", 'name' => 'team_person_id', 'td_label' => 'Team Person Name', 'className' =>  'w-10 text-center', 'orderable' => false, 'searchable' => false],
                (object)['data' => "model", 'name' => 'model', 'td_label' => 'Visit From', 'className' =>  'text-wrap', 'orderable' => false, 'searchable' => false],
                (object)['data' => "meet_person_name", 'name' => 'meet_person_name', 'td_label' => 'Meet Person Name', 'className' =>  'text-wrap'],
                // (object)['data' => "created_at", 'name' => 'created_at', 'td_label' => 'Date & Time', 'className' =>  ''],
                // (object)['data' => "visit_start_remark", 'name' => 'visit_start_remark', 'td_label' => 'Start Remark', 'className' =>  ''],
                (object)['data' => "visit_start", 'name' => 'visit_start', 'td_label' => 'Start Detail', 'className' =>  'text-wrap', 'orderable' => false, 'searchable' => false],
                (object)['data' => "visit_start_address", 'name' => 'visit_start_address', 'td_label' => 'Start Address', 'className' =>  'text-wrap'],
                (object)['data' => "start_image_url", 'name' => 'start_image', 'td_label' => 'Start Image   ', 'className' =>  ''],
                // (object)['data' => "customer.customer_name", 'name' => 'customer_id', 'td_label' => 'Customer Name', 'className' =>  ''],
                (object)['data' => "visit_stop", 'name' => 'visit_stop', 'td_label' => 'Stop Detail', 'className' =>  'text-wrap', 'orderable' => false, 'searchable' => false],
                // (object)['data' => "visit_stop_remark", 'name' => 'visit_stop_remark', 'td_label' => 'Stop Remark', 'className' =>  ''],
                (object)['data' => "visit_stop_address", 'name' => 'visit_stop_address', 'td_label' => 'Stop Address', 'className' =>  'text-wrap'],
                (object)['data' => "stop_image_url", 'name' => 'stop_image', 'td_label' => 'Stop Image', 'className' =>  ''],
                (object)['data' => "total_time_duration", 'name' => 'total_time_duration', 'td_label' => 'Duration of Meeting', 'className' =>  'text-wrap', 'orderable' => false, 'searchable' => false],
                // (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  'w-5 text-center'],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'className' =>  'w-10 text-start', 'orderable' => false, 'searchable' => false],

            ];
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            if ($modules['parent_type_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'team_person_id';
                });

                $columns = array_values($columns);
            }
            // dd($modules, $columns);
            View::share("columns", $columns);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = Visit::select('visits.*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('visits.company_id', $modules['company_id']);
                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('visits.created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('visits.id', 'DESC');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                // $data = $data->leftJoin('customers', 'visits.customer_id', '=', 'customers.id');
                $data = $data->with(['company', 'teamperson', 'customer']);
                // return $data?->get();

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {


                        if ($request->has('search') && $request->search) {
                            $query->where(function ($q) use ($request) {
                                $q->whereHas('customer', function ($subQuery) use ($request) {
                                    $subQuery->where('customer_name', 'like', "%" . $request->search . "%");
                                })->orWhere('meet_person_name', 'like', "%" . $request->search . "%");
                            });
                        }

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('visits.company_id', $request->filter_company);
                        }
                        if ($request->has('filter_team_person_name') && $request->filter_team_person_name) {
                            $query->where('team_person_id', $request->filter_team_person_name);
                        }
                        // if ($request->has('filter_date') && $request->filter_date) {
                        //     $query->whereDate('created_at', $request->filter_date);
                        // }
                        if ($request->has('filter_date') && $request->filter_date) {

                            $filter_date = $request->filter_date;
                            $filter_date = explode(" to ", $filter_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_date) == 2) {
                                $fromDate = Helper::convert_date($filter_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Visit())->getTable() . '.visit_start_date', ">=", $fromDate);
                                $query->whereDate((new Visit())->getTable() . '.visit_start_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Visit())->getTable() . '.visit_start_date', $fromDate);
                            }
                        }
                    })

                    ->editColumn('model', function ($row) {
                        $model = "";
                        if ($row?->model) {
                            $model .= "<b>From : </b>" . ucfirst($row?->model);
                        }
                        $ref = null;
                        if (Helper::checkTableExist($row?->model)) {
                            $model_data = DB::table($row?->model)->where('id', $row?->model_id)->first();
                            if ($model_data) {
                                if ($row?->model == "inquiries") {
                                    $ref = $model_data?->customer_name . " ~ " . $model_data?->contact_person_name;
                                } elseif ($row?->model == "customers") {
                                    $ref = $model_data?->customer_name . " ~ " . $model_data?->contact_person_name;
                                }
                            }
                        }
                        if ($row?->model_id && $ref) {
                            $model .= "<br><b>Ref Name : </b>" . $ref;
                        }
                        return $model;
                    })
                    ->editColumn('visit_start', function ($row) {
                        $visit_start = "";
                        if ($row?->visit_start_date) {
                            $visit_start .= "<b>Visit Date : </b>" . Helper::convert_date($row?->visit_start_date, "Y-m-d H:i:s", "d-m-Y H:i");
                        }
                        if ($row?->visit_start_remark) {
                            $visit_start .= "<br><b>Remark : </b>" . $row?->visit_start_remark;
                        }
                        return $visit_start;
                    })
                    ->editColumn('created_at', function ($row) {
                        return $row->created_at->format('d/m/Y H:i');
                    })
                    ->editColumn('start_image_url', function ($row) {
                        return "<img src='" . $row->start_image_url . "' class='w-50' onclick=\"showImageModal('" . $row?->start_image_url . "')\" data-src='" . $row?->start_image_url . "'>";
                    })
                    ->editColumn('visit_stop', function ($row) {
                        $visit_stop = "";
                        if ($row?->visit_stop_date) {
                            $visit_stop .= "<b>Visit Date : </b>" . Helper::convert_date($row?->visit_stop_date, "Y-m-d H:i:s", "d-m-Y H:i");
                        }
                        if ($row?->visit_stop_remark) {
                            $visit_stop .= "<br><b>Remark : </b>" . $row?->visit_stop_remark;
                        }
                        return $visit_stop;
                    })
                    ->editColumn('stop_image_url', function ($row) {
                        return "<img src='" . $row->stop_image_url . "' class='w-50' onclick=\"showImageModal('" . $row?->stop_image_url . "')\" data-src='" . $row?->stop_image_url . "' >";
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';


                        $dropdown = "";
                        $dropdown .= '<ul class="dropdown-menu" style="">';

                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm w-100 dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item waves-effect btn-label-danger update-status" data-url="' . route($modules['route'] . '.status-update') . '" data-id="' . $row["id"] . '"  data-update_status="inactive">Inactive</a></li>';
                            // $btn = '<span class="badge bg-success bg-glow">Active</span>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm w-100 dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">' . ucfirst($row->status) . '</button>';
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
                            // if ($editPermisstion) {
                            //     $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            // }
                            if ($modules['delete_permission']) {
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
                    ->rawColumns(['model', 'visit_start', 'visit_stop', 'start_image_url', 'stop_image_url', 'status', 'action'])
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
        //
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

        $dataDelete = visit::findOrFail($id);
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

            $module = visit::withTrashed()->findOrFail($id);
            $module->restore();

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
            $country = visit::withTrashed()->findOrFail($request?->id);
            if ($country) {
                $country->status = $request->update_status;
                $country->updated_by = $loginUserId;
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

        return Excel::download(new VisitExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Visite-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
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
        if (!$modules['print_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $query = Visit::with(['company', 'teamPerson', 'customer'])->withTrashed()->latest();

            // Filter: Search (customer name or meet person name)
            if (!empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('customer', function ($q2) use ($request) {
                        $q2->where('customer_name', 'like', '%' . $request->search . '%');
                    })->orWhere('meet_person_name', 'like', '%' . $request->search . '%');
                });
            }

            // Filter: Company
            if (!empty($request->filter_company)) {
                $query->where('company_id', $request->filter_company);
            }

            // Filter: Team Person
            if (!empty($request->filter_team_person_name)) {
                $query->where('team_person_id', $request->filter_team_person_name);
            }

            // Filter: Created Date
            if (!empty($request->filter_date)) {
                $query->whereDate('created_at', $request->filter_date);
            }

            $visits = $query->get();

            return view($modules['folder_path'] . '.print', compact('visits'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
