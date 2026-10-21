<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskStatusLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class TaskController extends Controller
{

    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Task',
            'folder_path' => 'software.modules.task_management.task',
            'route' => 'task',
            'table_name' => (new Task())->getTable(),
            'permisstion_prefix' => 'task',
            'module_name' => 'Task',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
        View::share('task_priority_arr', Task::$task_priority_arr);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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
            $columns = [
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "title", 'name' => 'title', 'td_label' => 'Title', 'className' =>  ''],
                (object)['data' => "department", 'name' => 'department', 'td_label' => 'Department', 'className' =>  ''],
                (object)['data' => "task_assigned_to", 'name' => 'task_assigned_to', 'td_label' => 'Assigned', 'className' =>  'dt-assigned'],
                (object)['data' => "priority", 'name' => 'priority', 'td_label' => 'Priority', 'className' =>  ''],
                (object)['data' => "task_status", 'name' => 'task_status', 'td_label' => 'Task Status', 'className' =>  ''],
                (object)['data' => "task_created_by", 'name' => 'task_created_by', 'td_label' => 'Created by', 'className' =>  ''],
                (object)['data' => "task_time", 'name' => 'task_time', 'td_label' => 'Task Time', 'className' =>  ''],
                (object)['data' => "task_total_time", 'name' => 'task_total_time', 'td_label' => 'Total Working Time', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-center'],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            View::share("columns", $columns);

            View::share('modules', $modules);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = Task::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $query->where('company_id', $teamPersonCompanyId);

                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where(function ($q) use ($loginUserId) {
                                    $q->orWhere('task_created_by', $loginUserId)
                                        ->orWhereRaw('FIND_IN_SET(?, task_assigned_to)', [$loginUserId]);
                            });
                        }
                    }
                });
                $data = $data->with(['company', 'department']);
                $data = $data->orderBy('id', 'desc');
                // if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                //     $data = $data->withTrashed();
                // }

                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('filter_priority') && $request->filter_priority) {
                            $query->where('priority', $request->filter_priority);
                        }
                        if ($request->has('filter_created_by') && $request->filter_created_by) {
                            $query->where('task_created_by', $request->filter_created_by);
                        }
                        if ($request->has('filter_assigned_to') && $request->filter_assigned_to) {
                            $query->whereRaw("FIND_IN_SET(?, task_assigned_to)", [$request->filter_assigned_to]);
                        }
                        if ($request->has('filter_department_id') && $request->filter_department_id) {
                            $query->where('department_id', $request->filter_department_id);
                        }
                        if ($request->has('filter_task_date') && $request->filter_task_date) {
                            $filter_task_date = explode(" to ", $request->filter_task_date);

                            $fromDate = null;
                            $toDate   = null;

                            if (count($filter_task_date) == 2) {
                                $fromDate = Helper::convert_date($filter_task_date[0], "d/m/Y", "Y-m-d");
                                $toDate   = Helper::convert_date($filter_task_date[1], "d/m/Y", "Y-m-d");
                            } elseif (count($filter_task_date) == 1) {
                                $fromDate = Helper::convert_date($filter_task_date[0], "d/m/Y", "Y-m-d");
                            }

                            if ($fromDate && $toDate) {
                                $query->where(function ($q) use ($fromDate, $toDate) {
                                    $table = (new Task())->getTable();
                                    $q->whereBetween(DB::raw("DATE($table.task_start_date_time)"), [$fromDate, $toDate])
                                    ->orWhereBetween(DB::raw("DATE($table.arrival_date_time)"), [$fromDate, $toDate])
                                    ->orWhereBetween(DB::raw("DATE($table.due_date_time)"), [$fromDate, $toDate]);
                                });
                            } elseif ($fromDate) {
                                $query->where(function ($q) use ($fromDate) {
                                    $table = (new Task())->getTable();
                                    $q->whereDate("$table.task_start_date_time", $fromDate)
                                    ->orWhereDate("$table.arrival_date_time", $fromDate)
                                    ->orWhereDate("$table.due_date_time", $fromDate);
                                });
                            }
                        }
                        if ($request->has('search')) {
                            $search = $request->search;
                            $query->where(function ($q) use ($search) {
                                $q->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                            });
                        }
                    })
                    ->addColumn("task_status", function($row){
                        return '<span class="badge" style="background: linear-gradient(90deg, '.$row?->task_status?->background_color.', '.$row?->task_status?->gradient_color.'); color: '.$row?->task_status?->font_color.';">'.$row?->task_status?->status_name.'</span>';
                    })
                    ->addColumn("task_time", function($row){

                        $LastTaskStatus = TaskStatus::where('status_type', 'task')->where('company_id', $row->company_id)->orderby('id', 'desc')->first();

                        $taskStatusLog = TaskStatusLog::where('company_id', $row->company_id)->where('task_id', $row->id)->where('task_status_id',$LastTaskStatus->id)->first();

                        $times = [];
                        if (!empty($row->arrival_date_time)) {
                            $times[] = "<strong>Arrival Time:</strong> " . \Carbon\Carbon::parse($row->arrival_date_time)->format('d-m-Y h:i A');
                        }
                        if (!empty($row->due_date_time)) {
                            $times[] = "<strong>Due Time:</strong> " . \Carbon\Carbon::parse($row->due_date_time)->format('d-m-Y h:i A');
                        }
                        if (!empty($row->task_start_date_time)) {
                            $times[] = "<strong>Start Time:</strong> " . \Carbon\Carbon::parse($row->task_start_date_time)->format('d-m-Y h:i A');
                        }
                        if (!empty($taskStatusLog->status_date)) {
                            $times[] = "<strong>Completed Time:</strong> " . \Carbon\Carbon::parse($taskStatusLog->status_date)->format('d-m-Y h:i A');
                        }
                        return implode('<br>', $times);
                    })
                    ->addColumn('task_total_time', function ($row) {
                        return $row->total_worked_hours;
                    })
                    ->addColumn('company_name', function ($row) {
                        return $row->company->company_name;
                    })
                    ->addColumn('task_assigned_to', function ($row) {
                        $badges = collect($row->task_assigned_to_names)->map(function ($name) {
                            return '<span class="badge bg-primary me-1 mb-2">' . e($name) . '</span><br>';
                        })->implode('');

                        return $badges ?: '-';
                    })
                    ->addColumn('task_created_by', function ($row) {
                        return $row->task_created_by_name;
                    })
                    ->addColumn('department', function ($row) {
                        return $row->department?->name ?? '-';
                    })
                    ->addColumn('action', function ($row) use ($modules, $loginUserId) {

                        $btn = '';
                        $btn .= '
                            <div class="">
                                <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu">';

                        $taskStatusLog = TaskStatusLog::where('company_id', $row->company_id)->where('task_id', $row->id)->exists();
                        if(!empty($taskStatusLog)){
                             $btn .= '<li>
                                        <a href="javascript:void(0)" data-id="' . $row->id . '" data-company_id="' . $row->company_id . '"  class="dropdown-item viewButton mx-1"><i class="fa-solid fa-view"></i>View</a>
                                    </li>';
                        }


                        if (!$row?->deleted_at) {
                            $taskStatusesLast = TaskStatus::where('status_type', 'task')->where('company_id', $row->company_id)
                                    ->select("id")
                                    ->orderBy('display_order', 'desc')
                                    ->first();


                            if (!empty($taskStatusesLast->id) && !empty($row->task_status_id) && $taskStatusesLast->id > 0 && ((int)$row->task_status_id != (int)$taskStatusesLast->id)) {

                                $canEditDelete = false;
                                if ($modules['currentGuard'] === 'admin_software') {
                                    $canEditDelete = true;
                                }
                                if ($modules['currentGuard'] === 'team_person' && (int)$row->task_created_by === (int)auth()->guard('team_person')->id()) {
                                    $canEditDelete = true;
                                }

                                if ($modules['update_permission'] && $canEditDelete) {
                                    $btn .= '<li>
                                        <a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    </li>';
                                }
                                if ($modules['delete_permission'] && $canEditDelete) {
                                    $btn .= '<li>
                                        <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                    </li>';
                                }
                            }
                        } else {

                            $btn .= '<li>
                                <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                    Restore
                                </a>
                            </li>';
                        }


                        $taskStatusStart = TaskStatus::where('status_type', 'task')
                            ->where('company_id', $row->company_id)
                            ->where('display_order', 2) // Start
                            ->first();

                        $LastTaskStatus = TaskStatus::where('status_type', 'task')->where('company_id', $row->company_id)->orderby('id', 'desc')->first();


                        if (!empty($row?->task_status_id) && $modules['currentGuard'] == 'team_person' && ($row?->task_status_id != $LastTaskStatus->id)) {
                            $currentStatus = TaskStatus::find($row?->task_status_id);


                            if (strtolower($currentStatus->status_name) === 'pause' || strtolower($currentStatus->status_name) === 'paused') {
                                $allowedStatuses = TaskStatus::where('company_id', $row->company_id)
                                    ->where('status_type', 'task')
                                    ->whereIn('status_name', ['Start'])
                                    ->orderBy('display_order', 'asc')
                                    ->get();

                                if ($allowedStatuses->isNotEmpty()) {
                                    foreach ($allowedStatuses as $status) {
                                        if (!empty($modules['approval_permission'])) {
                                            $btn .= '<li>
                                                        <a data-url="' . route($modules["route"] . ".change-task-status") . '"
                                                        href="javascript:void(0)"
                                                        data-id="' . $row->id . '"
                                                        data-next-status-id="' . $status->id . '"
                                                        class="change_status dropdown-item mx-1">
                                                        ' . $status->status_name . '
                                                        </a>
                                                    </li>';
                                        }
                                    }
                                }
                            } elseif ($currentStatus->display_order < $taskStatusStart->display_order) {
                                if (!empty($modules['approval_permission'])) {
                                    $btn .= '<li>
                                                <a data-url="' . route($modules["route"] . ".change-task-status") . '"
                                                href="javascript:void(0)"
                                                data-id="' . $row->id . '"
                                                data-next-status-id="' . $taskStatusStart->id . '"
                                                class="change_status dropdown-item mx-1">
                                                ' . $taskStatusStart->status_name . '
                                                </a>
                                            </li>';
                                }
                            } elseif ($currentStatus->display_order >= $taskStatusStart->display_order) {
                                $nextStatuses = TaskStatus::where('company_id', $row->company_id)
                                    ->where('status_type', 'task')
                                    ->where('display_order', '>', $taskStatusStart->display_order)
                                    ->orderBy('display_order', 'asc')
                                    ->get();

                                if ($nextStatuses->isNotEmpty()) {
                                    $btn .= '<li class="dropdown-divider"></li>';
                                    foreach ($nextStatuses as $status) {
                                        if (!empty($modules['approval_permission'])) {
                                            $btn .= '<li>
                                                        <a data-url="' . route($modules["route"] . ".change-task-status") . '"
                                                        href="javascript:void(0)"
                                                        data-id="' . $row->id . '"
                                                        data-next-status-id="' . $status->id . '"
                                                        class="change_status dropdown-item mx-1">
                                                        ' . $status->status_name . '
                                                        </a>
                                                    </li>';
                                        }
                                    }
                                }
                            }
                        }


                        if ($btn == '') {
                            $btn = '-';
                        }
                        $btn .= '</ul>
                                </div>';
                        return $btn;
                    })
                    ->rawColumns(['status', 'action','task_status','task_assigned_to','task_time'])
                    ->make(true);

                if (!$modules['view_permission']) {
                    abort(403, 'Unauthorized');
                }
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
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskRequest $request)
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
        $validated = $request->validated();

        try {
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;
            $validated['task_assigned_to'] = implode(',', $validated['task_assigned_to']);

            $taskStatus = TaskStatus::where('status_type', 'task')->where('company_id', $validated['company_id'])->where('display_order', 1)->first();
            $validated['task_status_id'] = $taskStatus->id ?? null;

            if ($request->arrival_date_time) {
                $arrival_date_time = Helper::convert_date($request?->arrival_date_time, 'd-m-Y h:i a', 'Y-m-d H:i');
                $validated['arrival_date_time'] = $arrival_date_time;
            }

            if ($request->due_date_time) {
                $due_date_time = Helper::convert_date($request?->due_date_time, 'd-m-Y h:i a', 'Y-m-d H:i');
                $validated['due_date_time'] = $due_date_time;
            }

            $task = Task::create($validated);
            $lastInsertedId = $task->id;

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
        // if ($id = "print") {
        //     return self::print();
        // }
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

            $edit = Task::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskRequest $request, string $id)
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
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);
        $request['id'] = $id;
        $validated = $request->validated();

        try {
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['updated_type'] = 'admin';
            } else {
                $validated['updated_type'] = 'team';
            }

            $validated['updated_by'] = $loginUserId;
            $validated['task_assigned_to'] = implode(',', $validated['task_assigned_to']);

            if ($request->arrival_date_time) {
                $arrival_date_time = Helper::convert_date($request?->arrival_date_time, 'd-m-Y h:i a', 'Y-m-d H:i');
                $validated['arrival_date_time'] = $arrival_date_time;
            }

            if ($request->due_date_time) {
                $due_date_time = Helper::convert_date($request?->due_date_time, 'd-m-Y h:i a', 'Y-m-d H:i');
                $validated['due_date_time'] = $due_date_time;
            }
            $updateData = Task::findOrFail($id);
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
        if (!$modules['delete_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $dataDelete = Task::findOrFail($id);
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
            $task = Task::withTrashed()->findOrFail($request?->id);
            if ($task) {
                $task->status = $request->update_status;
                $task->updated_by = $loginUserId;
                $task->save();
                if ($isAjax) {
                    return $this->sendResponse($task, $modules['title'] . ' status update successfully.');
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

    public function restore($id)
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
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $task = Task::withTrashed()->findOrFail($id);
            $task->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function change_task_status(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $validator = Validator::make($request->all(), [
            'id' => ['required', Rule::exists($modules['table_name'], 'id')],
            'next_status_id' => ['required', 'exists:task_statuses,id']
        ]);

        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }

        try {
            $task = Task::with(['company', 'department'])->withTrashed()->findOrFail($request?->id);
            if ($task) {

                $taskStatus = TaskStatus::where('status_type', 'task')->where('company_id', $task->company_id)->where('display_order', 1)->first();
                if ($task->task_status_id == $taskStatus->id) {
                    $task->task_start_date_time = Helper::convert_date(now()->format('d-m-Y h:i a'), 'd-m-Y h:i a', 'Y-m-d H:i');
                }
                $task->task_status_id = $request->next_status_id;
                $task->save();

                TaskStatusLog::create([
                    'company_id'     => $task->company_id,
                    'task_id'        => $request?->id,
                    'team_id'        => $loginUserId,
                    'task_status_id' => $request->next_status_id,
                    'status_date'    => Helper::convert_date(now()->format('d-m-Y h:i a'), 'd-m-Y h:i a', 'Y-m-d H:i'),
                    'remarks'        => $request->remarks,
                ]);
                return $this->sendResponse($task, $modules['title'] . ' status update successfully.');
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


    public function get_task_status_log(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {
            if ($isAjax) {
                $task = Task::with(['company', 'department'])->withTrashed()->findOrFail($request?->task_id);
                if ($task) {
                     $logs = TaskStatusLog::with(['company'])
                        ->where('company_id', $request->company_id)
                        ->where('task_id', $request->task_id)
                        ->orderBy('status_date', 'asc')
                        ->get()
                        ->map(function ($log) {

                            return [
                                'status_name' => $log->task_status->status_name ?? '',
                                'company_name' => $log->company->company_name ?? '',
                                'task_name'   => $log->task->title ?? '',
                                'created_by'  => $log->team_person->name ?? '',
                                'status_date' => $log->status_date->format('d-m-Y h:i A'),
                                'remarks'     => $log->remarks ?? '',
                            ];
                        });

                    return $this->sendResponse($logs, 'Task status log Fetched Successfully');
                }
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
