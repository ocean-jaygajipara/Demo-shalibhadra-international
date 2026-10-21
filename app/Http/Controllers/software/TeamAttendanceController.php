<?php

namespace App\Http\Controllers\software;

use App\Exports\TeamAttendanceExport;
use App\Exports\TermsAndConditionExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\TeamAttendance;
use App\Models\Company;
use App\Models\TeamPerson;
use App\Models\SaleExecutiveTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class TeamAttendanceController extends Controller
{
    public $modules = [];

    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Team Attendance',
            'folder_path' => 'software.modules.team-attendance',
            'route' => 'team-attendance',
            'table_name' => (new TeamAttendance())->getTable(),
            'permisstion_prefix' => 'team-attendance',
            'module_name' => 'Team Attendance',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];
        View::share("InOut", TeamAttendance::$InOut);
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
        //  dd($modules);
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
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => "team_person_detail", 'name' => 'team_person_detail', 'td_label' => 'Team Person Detail', 'className' =>  ''],
                (object)['data' => "punch_in_data", 'name' => 'punch_in_time', 'td_label' => 'Punch In Detail', 'className' =>  ''],
                (object)['data' => "punch_out_data", 'name' => 'punch_out_time', 'td_label' => 'Punch Out Detail', 'className' =>  ''],
                (object)['data' => "working_time", 'name' => 'working_time', 'td_label' => 'Total Working Time', 'className' =>  ''],
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

            View::share("columns", $columns);


            if ($request->ajax()) {
                $data = TeamAttendance::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $query->where('company_id', $teamPersonCompanyId);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company', 'team_person']);
                $editPermisstion = true;
                $deletePermisstion = true;

                if ($request->has('company_id') && $request->company_id) {
                    $data->where('company_id', 'LIKE', '%' . $request->company_id . '%');
                }
                // if ($request->has('in_out') && $request->in_out != '') {
                //     $data->where('in_out', $request->in_out);
                // }

                if ($request->filled('search')) {
                    $search = $request->search;

                    $data->where(function ($query) use ($search) {
                        $query->whereHas('team_person', function ($query) use ($search) {
                            $query->where('name', 'like', "%" . $search . "%")
                                ->orWhere('parent_type_id', 'like', "%" . $search . "%")
                                ->orWhere('mobile_no', 'like', "%" . $search . "%")
                                ->orWhere('address', 'like', "%" . $search . "%")
                                ->orWhereHas('company', function ($query) use ($search) {
                                    $query->where('company_name', 'like', "%" . $search . "%");
                                });
                        });
                    });
                }
                // if ($request->in_out === 'in' && $request->filled('punch_in_start') && $request->filled('punch_in_end')) {
                //     $data->whereBetween('punch_in_time', [$request->punch_in_start, $request->punch_in_end]);
                // }

                // if ($request->in_out === 'out' && $request->filled('punch_out_start') && $request->filled('punch_out_end')) {
                //     $data->whereBetween('punch_out_time', [$request->punch_out_start, $request->punch_out_end]);
                // }

                if ($request->in_out === 'in') {
                    if ($request->punch_in_start && $request->punch_in_end) {
                        $data->whereBetween('punch_in_time', [
                            $request->punch_in_start,
                            $request->punch_in_end
                        ]);
                    }
                } elseif ($request->in_out === 'out') {
                    if ($request->punch_out_start && $request->punch_out_end) {
                        $data->whereBetween('punch_out_time', [
                            $request->punch_out_start,
                            $request->punch_out_end
                        ]);
                    }
                } else {
                    if ($request->punch_in_start && $request->punch_in_end) {
                        $data->where(function ($q) use ($request) {
                            $q->whereBetween('punch_in_time', [
                                $request->punch_in_start,
                                $request->punch_in_end
                            ])->orWhereBetween('punch_out_time', [
                                $request->punch_out_start,
                                $request->punch_out_end
                            ]);
                        });
                    }
                }
                 $data->orderBy('id', 'DESC');
                $returnData = Datatables::of($data)
                    ->addIndexColumn()


                    ->editColumn('team_person_detail', function ($row) {
                        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;

                        $team_person_detail = '';

                        if (!$modules['company_id']) {
                            $team_person_detail = "<b>Company : </b>" . $row?->company?->company_name;
                        }

                        $team_person_detail .= "<br>";
                        $team_person_detail .= "<b>Team Person : </b>" . $row?->team_person?->name;
                        $team_person_detail .= "<br>";
                        $team_person_detail .= "<b>Mobile No : </b>" . $row?->team_person?->mobile_no;

                        return $team_person_detail;
                    })
                    ->editColumn('punch_in_data', function ($row) {
                        $punch_in_data = '';
                        $punch_in_data .= "<div class='d-flex'>";
                        $punch_in_data .= "<div ><img src='" . $row?->punch_in_image_url . "' alt='" . $row?->team_person?->name . " Punch-In' class='w-100' style='max-width:250px'   onclick=\"showImageModal('" . $row?->punch_in_image_url . "')\" data-src='" . $row?->punch_in_image_path . "' /></div>";
                        $punch_in_data .= "<div class='ms-auto'>";
                        $punch_in_data .= "<div class='row'><div class='col-md-12 text-wrap  text-success'><b>Punch In : </b>" . Helper::convert_date($row?->punch_in_time, "Y-m-d H:i:s", "h:i A d-m-Y") . "</div></div>";
                        $punch_in_data .= "<div class='row'><div class='col-md-12 text-wrap'><b>Address : </b>" . $row?->punch_in_address . "</div>";
                        $punch_in_data .= "<div class='col-md-12 text-wrap'><b>Login From : </b>" . ucfirst($row?->punch_in_flag) . "</div></div>";
                        $punch_in_data .= "</div";
                        $punch_in_data .= "</div>";

                        return $punch_in_data;
                    })
                    ->editColumn('punch_out_data', function ($row) {
                        $punch_out_data = '-';
                        if ($row?->punch_out_time) {
                            $punch_out_data = '';
                            $punch_out_data .= "<div class='d-flex'>";

                            $punch_out_data .= "<div class='mr-auto'><img src='" . $row?->punch_out_image_url . "' alt='" . $row?->team_person?->name . " Punch-out' class='w-100' style='max-width:250px '  onclick=\"showImageModal('" . $row?->punch_out_image_url . "')\"  data-src='" . $row?->punch_out_image_path . "' /></div>";
                            $punch_out_data .= "<div>";
                            $punch_out_data .= "<div class='row'><div class='col-md-12 text-wrap text-danger '><b>Punch Out : </b>" . Helper::convert_date($row?->punch_out_time, "Y-m-d H:i:s", "h:i A d-m-Y") . "</div></div>";
                            $punch_out_data .= "<div class='row'><div class='col-md-12 text-wrap'><b>Address : </b>" . $row?->punch_out_address . "</div>";
                            $punch_out_data .= "<div class='col-md-12 text-wrap'><b>Login From : </b>" . ucfirst($row?->punch_out_flag) . "</div></div>";
                            $punch_out_data .= "</div";
                            $punch_out_data .= "</div>";
                        }

                        return $punch_out_data;
                    })
                    ->editColumn('status', function ($row) {
                        $btn = '';
                        $btn = ucfirst($row->status);
                        if ($row->status == "active") {
                            $btn = '<span class="badge bg-success bg-glow">Active</span>';
                        } elseif ($row->status == "inactive") {
                            $btn = '<span class="badge bg-danger bg-glow">In-Active</span>';
                        }
                        return $btn;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        // if ($editPermisstion) {
                        //     $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                        // }
                        // if ($deletePermisstion) {
                        //     $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                        // }
                        // if ($btn == '') {
                        //     $btn = '-';
                        // }
                        return $btn;
                    })
                    ->rawColumns(['team_person_detail', 'punch_in_data', 'punch_out_data', 'status', 'action'])
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
        // dd($this->modules);

        if (!$modules['add_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);
        $isAjax = ($request->ajax()) ? true : false;
        try {
            $loginUser = Auth::user();
            if (!$loginUser && !$request?->company_id) {
                return $this->sendError("Unauthorization", [], [], 401);
            }
            $company_id = $request?->company_id ?? $loginUser?->company_id;

            $teamPerson = TeamPerson::where('company_id', $request->company_id)
                ->where('id', $loginUser?->id)
                ->first();
            $minStartTime = $teamPerson?->min_working_start_time;
            $maxStartTime = $teamPerson?->max_working_start_time;
            $now = Carbon::now()->format('H:i:s');
            if ($now > $maxStartTime && $request?->punch_type == 'in') {
                return response()->json([
                    'status' => 'error',
                    'message' => "You can't punch in after your shift start time: {$maxStartTime}."
                ], 403);
            }

            $attendance = TeamAttendance::where('company_id', $company_id)
                ->where('team_person_id', $loginUser?->id)
                ->whereNull('punch_out_time')
                ->orderByDesc('id')
                ->first();


            $validator =  Validator::make($request->all(), [
                'company_id' => [
                    'nullable',
                    Rule::exists((new Company())->getTable(), 'id')
                ],
                // 'team_person_id' => [
                //     'nullable',
                //     Rule::exists((new TeamPerson())->getTable(), 'id')
                // ],

                'punch_type' => ['required', 'in:in,out'],
                'punch_in_time' => ['required_if:punch_type,in|date|after_or_equal:' . \Carbon\Carbon::today()->toDateString()],
                //'punch_in_address' => ['required_if:punch_type,in'],
                'punch_in_latitude' => ['required_if:punch_type,in'],
                'punch_in_longitude' => ['required_if:punch_type,in'],

                'punch_out_time' => ['required_if:punch_type,outdate|after_or_equal:' . \Carbon\Carbon::today()->toDateString()],
                //'punch_out_address' => ['required_if:punch_type,out'],
                'punch_out_latitude' => ['required_if:punch_type,out'],
                'punch_out_longitude' => ['required_if:punch_type,out'],
            ]);



            if ($validator->fails()) {
                return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
            }

            if ($request?->punch_type == 'in') {

                $input = [];
                $input['company_id'] = $company_id;
                $input['team_person_id'] = $loginUser?->id;
                $input['punch_in_time'] =  $request?->punch_in_time;
                $input['punch_in_address'] =  $request?->punch_in_address;
                $input['punch_in_latitude'] =  $request?->punch_in_latitude;
                $input['punch_in_longitude'] =  $request?->punch_in_longitude;
                $input['punch_in_flag'] =  'web';
                $input['created_by'] =  $loginUser?->id;

                $attendance = TeamAttendance::create($input);

                SaleExecutiveTracking::create([
                    'company_id' => $company_id,
                    'type' => SaleExecutiveTracking::ATTENDANCE_IN,
                    'latitude' => $request?->punch_in_latitude,
                    'longitude' => $request?->punch_in_longitude,
                    'address' => $request?->punch_in_address,
                    'current_datetime' => $request?->punch_in_time,
                    'created_by' => $loginUser?->id,
                ]);

                if ($isAjax) {
                    return $this->sendResponse([], $request?->punch_text . '  successfully');
                }
                return true;
            } else if ($request?->punch_type == 'out') {
                $attendance->update([
                    'punch_out_time' => $request?->punch_out_time,
                    'punch_out_address' => $request?->punch_out_address,
                    'punch_out_latitude' => $request?->punch_out_latitude,
                    'punch_out_longitude' => $request?->punch_out_longitude,
                    'working_time' => Helper::getTimeDifference($attendance->punch_in_time, $request?->punch_out_time),
                    'punch_out_flag' => 'web',
                    'updated_by' => $loginUser?->id,
                ]);

                SaleExecutiveTracking::create([
                    'company_id' => $company_id,
                    'type' => SaleExecutiveTracking::ATTENDANCE_OUT,
                    'latitude' => $request?->punch_out_latitude,
                    'longitude' => $request?->punch_out_longitude,
                    'address' => $request?->punch_out_address,
                    'current_datetime' => $request?->punch_out_time,
                    'created_by' => $loginUser?->id,
                ]);

                if ($isAjax) {
                    return $this->sendResponse([], $request?->punch_text . '  successfully');
                }
                return true;
            } else {
                return $this->sendError("Punch type missing.");
            }
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TeamAttendance $request, string $id)
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


            $validated['updated_by'] = $loginUserId;
            // return $validated;
            $updateData = TeamAttendance::findOrFail($id);
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
    public function destroy(string $id, Request $request)
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
        $dataDelete = TeamAttendance::findOrFail($id);
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
        return Excel::download(new TermsAndConditionExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'TermsAndConditions-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function print()
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        try {
            View::share('modules', $modules);
            $teamattandace = TeamAttendance::with('company')->withTrashed()->orderBy('id', 'DESC')->get();

            return view($modules['folder_path'] . '.print', compact('teamattandace'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
