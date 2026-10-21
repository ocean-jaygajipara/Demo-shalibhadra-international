<?php

namespace App\Http\Controllers\software;

use App\Exports\LeaveApplicationExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveApplicationRequest;
use App\Models\Company;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;

class LeaveApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Leave Application',
            'folder_path' => 'software.modules.leave.leave-application',
            'route' => 'leave-application',
            'table_name' => (new LeaveApplication())->getTable(),
            'permisstion_prefix' => 'leave-application',
            'module_name' => 'Leave Application',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];

        View::share("leaveForDay", LeaveApplication::$leaveForDay);
        View::share("leaveByDays", LeaveApplication::$leaveByDays);
        View::share("leaveForHalfdays", LeaveApplication::$leaveForHalfdays);
        View::share("leaveTypes", LeaveType::get());
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
        // dd(!$modules['company_id'], $modules);
        try {

            $columns = [
                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Sr No.'],
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],

                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "team_person.name", 'name' => 'team_person_id', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "leave_type.name", 'name' => 'leave_type_id', 'td_label' => 'Leave Type Name', 'className' =>  ''],
                (object)['data' => "fromdate_time", 'name' => 'fromdate_time', 'td_label' => 'From Date', 'className' =>  ''],
                (object)['data' => "todate_time", 'name' => 'todate_time', 'td_label' => 'To Date', 'className' =>  ''],
                (object)['data' => "halfday_fullday", 'name' => 'halfday_fullday', 'td_label' => 'HalfDay / FullDay', 'className' =>  ''],
                (object)['data' => "leave_reason", 'name' => 'leave_reason', 'td_label' => 'Leave Reason', 'className' =>  ''],
                (object)['data' => "rejection_reason", 'name' => 'rejection_reason', 'td_label' => 'Rejection Reason', 'className' =>  ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  ''],
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

                $data = LeaveApplication::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                // $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company', 'leave_type', 'team_person']);

                if ($request->has('company_id') && $request->company_id) {
                    $data->where('company_id', 'LIKE', '%' . $request->company_id . '%');
                }

                if ($request->has('team_person_ids') && $request->team_person_ids) {
                    $data->where('team_person_ids', 'LIKE', '%' . $request->team_person_ids . '%');
                }

                if ($request->has('leave_type') && $request->leave_type) {
                    $data->where('leave_type_id', $request->leave_type);
                }
                if ($request->has('halfday_fullday') && $request->halfday_fullday) {
                    $data->where('halfday_fullday', $request->halfday_fullday);
                }
                if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                    $data->where('status', $request->status);
                }
                if ($request->has('filter_quotation_date') && $request->filter_quotation_date) {
                    if (!empty($request?->filter_quotation_date)) {
                        $dates = explode(' to ', $request?->filter_quotation_date);

                        if (count($dates) === 2) {
                            $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                            $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

                            $data->where(function ($q) use ($from, $to) {
                                $q->whereBetween('fromdate_time', [$from, $to])
                                    ->orWhereBetween('todate_time', [$from, $to]);
                            });
                        } elseif (count($dates) === 1) {
                            $singleDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                            $data->where(function ($q) use ($singleDate) {
                                $q->whereDate('fromdate_time', $singleDate)
                                    ->orWhereDate('todate_time', $singleDate);
                            });
                        }
                    }
                }

                if ($request->filled('search')) {
                    $search = $request->search;

                    $data->where(function ($q) use ($search) {
                        $q->where('leave_reason', 'like', "%{$search}%")
                            ->orWhere('rejection_reason', 'like', "%{$search}%");
                    });
                }

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('rejection_reason', fn($row) => $row->status === 'reject' ? $row->rejection_reason : '-')
                    ->addColumn('day_detail', function ($row) {
                        $type = ucfirst($row->halfday_fullday ?? '-');
                        $detail = '-';

                        if ($row->halfday_fullday == 'fullday') {
                            $detail = !empty($row->singleday_multipleday) ? ucfirst($row->singleday_multipleday) : '-';
                        } elseif ($row->halfday_fullday == 'halfday') {
                            $detail = !empty($row->firsthalf_secondhalf) ? ucfirst($row->firsthalf_secondhalf) : '-';
                        }

                        return "{$type} ({$detail})";
                    })
                    // ->editColumn('leave_reason', fn($row) => $row->leave_reason ?? '-')
                    ->editColumn('firsthalf_secondhalf', fn($row) => $row->firsthalf_secondhalf ?? '-')
                    ->editColumn('singleday_multipleday', fn($row) => $row->singleday_multipleday ?? '-')
                    ->editColumn('fromdate_time', fn($row) => $row->fromdate_time ? Carbon::parse($row->fromdate_time)->format('d-m-Y H:i') : '-')
                    ->editColumn('todate_time', fn($row) => $row->todate_time ? Carbon::parse($row->todate_time)->format('d-m-Y H:i') : '-')
                    ->editColumn('status', function ($row) {
                        return match ($row->status) {
                            'pending' => '<button type="button" class="btn btn-warning btn-sm waves-effect waves-light" style="min-width: 90px;">Pending</button>',
                            'approved' => '<button type="button" class="btn btn-success btn-sm waves-effect waves-light" style="min-width: 90px;">Approve</button>',
                            'rejected' => '<button type="button" class="btn btn-danger btn-sm waves-effect waves-light" style="min-width: 90px;">Reject</button>',
                            default => '-',
                        };
                    })
                    ->editColumn('leave_reason', function ($row) {
                        $returnHtml = $row->leave_reason ?? '-';

                        if ($row->attachment && $row->attachment_url) {
                            $returnHtml .= '
                            <button type="button" class="btn btn-sm btn-info file-preview m-2" data-url="' . $row->attachment_url . '" data-filename="' . $row->attachment . '"> File </button>';
                        }
                        // dd($row->attachment_url);
                        return $returnHtml;
                    })


                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';

                        if ($row->status === 'pending' && $modules['approval_permission'] == true) {
                            $btn .= '<a href="javascript:void(0)"
                                class="btn btn-sm btn-primary d-inline-flex align-items-center action-button mx-1 leaveApplicationAcutionModel"
                                data-id="' . $row->id . '"
                                data-company_id="' . ($row->company_id ?? '') . '"
                                data-company_name="' . ($row->company->company_name ?? '') . '"
                                data-team_person_name="' . ($row->team_person->name ?? '') . '"
                                data-leave_type="' . ($row->leave_type->name ?? '') . '"
                                data-from_date="' . $row->fromdate_time . '"
                                data-to_date="' . $row->todate_time . '"
                                data-leave_for_day="' . (LeaveApplication::$leaveForDay[$row->halfday_fullday] ?? '') . '"
                                data-leave_by_days="' . (LeaveApplication::$leaveByDays[$row->singleday_multipleday] ?? '') . '"
                                data-leave_for_half="' . (LeaveApplication::$leaveForHalfdays[$row->firsthalf_secondhalf] ?? '') . '"
                                data-leave_reason="' . e($row->leave_reason) . '"
                                data-status="' . $row->status . '"
                                data-url="' . route($modules['route'] . '.show', [$row->id]) . '">Action</a>';
                        }

                        if (!$row?->deleted_at && $row->status === 'pending') {
                            if ($modules['update_permission'] == true) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission'] == true) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                            }
                        }

                        if ($row?->deleted_at) {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }

                        return $btn ?: '-';
                    })
                    ->rawColumns(['status', 'action', 'leave_reason'])
                    ->make(true);
            }

            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            // For debugging: return the actual exception during AJAX
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

        $leaveForDay = [
            'halfday' => 'Half Day',
            'fullday' => 'Full Day',
        ];
        $leaveByDays = [
            'singleday' => "Single Day",
            'multipleday' => "Mutiple Day",

        ];
        $leaveForHalfdays = [
            'firsthalf' => "First Half",
            'secondhalf' => "Second Half",


        ];
        try {
            View::share('modules', $modules);
            View::share('leaveForDay', $leaveForDay);
            View::share('leaveByDays', $leaveByDays);
            View::share('leaveForHalfdays', $leaveForHalfdays);


            return view($modules['folder_path'] . '.form', compact('modules', 'leaveForDay', 'leaveByDays', 'leaveForHalfdays'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    // public function create()
    // {
    //     $modules = $this->modules;
    //     $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
    //     $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
    //     $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
    //     $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

    //     // Set permissions
    //     if (count(config('constants.permissions'))) {
    //         foreach (config('constants.permissions') as $key => $value) {
    //             $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id'])
    //                 ? true
    //                 : Gate::check('hasPermission', [$value, $modules['module_name']]);
    //         }
    //     }

    //     // Check add permission
    //     if (!$modules['add_permission']) {
    //         if (isset($request) && $request->ajax()) {
    //             return $this->sendError('Unauthorized', [], [], 403);
    //         }
    //         abort(403, 'Unauthorized');
    //     }

    //     // Dropdown values
    //     $leaveForDay = [
    //         'halfday' => 'Half Day',
    //         'fullday' => 'Full Day',
    //     ];

    //     $leaveByDays = [
    //         'singleday' => "Single Day",
    //         'multipleday' => "Multiple Day",
    //     ];

    //     $leaveForHalfdays = [
    //         'firsthalf' => "First Half",
    //         'secondhalf' => "Second Half",
    //     ];

    //     try {
    //         // Fetch leave types company-wise
    //         $companyId = $modules['company_id'] ?? null;

    //         $leaveTypes = LeaveType::query()
    //             ->when($companyId, function ($query) use ($companyId) {
    //                 $query->where('company_id', $companyId);
    //             })
    //             ->orderBy('name')
    //             ->get();

    //         // Share to view
    //         View::share('modules', $modules);
    //         View::share('leaveForDay', $leaveForDay);
    //         View::share('leaveByDays', $leaveByDays);
    //         View::share('leaveForHalfdays', $leaveForHalfdays);
    //         View::share('leaveTypes', $leaveTypes);

    //         return view($modules['folder_path'] . '.form', compact(
    //             'modules',
    //             'leaveForDay',
    //             'leaveByDays',
    //             'leaveForHalfdays',
    //             'leaveTypes'
    //         ));
    //     } catch (\Exception $e) {
    //         return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
    //     }
    // }


    /**
     * Store a newly created resource in storage.
     */
    public function store(LeaveApplicationRequest $request)
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

            // Convert fromdate_time and todate_time to proper format
            $validated['fromdate_time'] = isset($validated['fromdate_time']) && !empty($validated['fromdate_time'])
                ? Carbon::parse($validated['fromdate_time'])->format("Y-m-d H:i:s")
                : Carbon::now()->format("Y-m-d H:i:s");


            if ($validated['halfday_fullday'] === 'halfday') {
                $validated['todate_time'] = null;  // Set to null for halfday
            } else {
                $validated['todate_time'] = isset($validated['todate_time']) && !empty($validated['todate_time'])
                    ? Carbon::parse($validated['todate_time'])->format("Y-m-d H:i:s")
                    : null;
            }
            // dd( $request->file('attachment'));
            if ($request->hasFile('attachment')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();

                // Generate filename using only company_id and current timestamp
                $image_name = Helper::make_slug(
                    $validated['company_id'] . ' ' . date('Ymd-His')
                );

                $filename = $image_name . '.' . $extension;

                // Create folder path
                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/{$validated['company_id']}-{$company_slug}/Leave-Application/{$year}-{$month}/";

                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);

                // Ensure folder exists
                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }
                // dd($uploadedPath.$filename);
                // Move the file to the destination folder
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;

                    // Optional: Convert to WebP
                    if (in_array(strtolower($extension), ['jpeg', 'jpg', 'png'])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                        }
                    }
                    // dd($uploadedImage);
                    // Save final path to validated array
                    $validated['attachment'] = $uploadedImage;
                }
            }



            $validated['singleday_multipleday'] = $request?->singleday_multipleday;
            $validated['firsthalf_secondhalf'] = $request?->firsthalf_secondhalf;
            // return $validated;

            LeaveApplication::create($validated);

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' created successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */

    // public function show($id)
    // {
    //     $leave = LeaveApplication::with(['company'])->findOrFail($id);

    //     return response()->json([
    //         'id' => $leave->id,
    //         'status' => $leave->status,
    //         'reject' => $leave->reject,
    //         'company_name' => $leave->company->company_name ?? 'N/A',
    //         'leave_type' => $leave->leave_type->name ?? 'N/A',
    //     ]);
    //     return Redirect::route('leave-application.print')->with('info', 'Viewing not available.');
    // }
    public function show($id)
    {
        $leave = LeaveApplication::with(['company', 'leave_type'])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $leave->id,
                'status' => $leave->status,
                'reject' => $leave->reject,
                'company_name' => $leave->company->company_name ?? 'N/A',
                'leave_type' => $leave->leave_type->name ?? 'N/A',
            ]);
        }

        return Redirect::route('leave-application.print')->with('info', 'leave-application  Print');
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
            $leaveTypes = LeaveType::all();
            $edit = LeaveApplication::findOrFail($id);
            View::share('edit', $edit);
            $leaveTypes = LeaveType::where('company_id', $modules['company_id'])->get();

            return view($modules['folder_path'] . '.form', compact('leaveTypes'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LeaveApplicationRequest $request, string $id)
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
            $leaveApplication = LeaveApplication::findOrFail($id);

            $validated['updated_by'] = $loginUserId;

            // Convert fromdate_time and todate_time to proper format
            $validated['fromdate_time'] = isset($validated['fromdate_time']) && !empty($validated['fromdate_time'])
                ? Carbon::parse($validated['fromdate_time'])->format("Y-m-d H:i:s")
                : Carbon::now()->format("Y-m-d H:i:s");

            // Handle todate_time
            if (isset($validated['todate_time']) && !empty($validated['todate_time'])) {
                $validated['todate_time'] = Carbon::parse($validated['todate_time'])->format("Y-m-d H:i:s");
            } else {
                // If no todate_time is provided and the leave is being changed from half-day to full-day, set it to fromdate_time + 1 day.
                if ($validated['halfday_fullday'] === 'fullday' && empty($validated['todate_time'])) {
                    $validated['todate_time'] = Carbon::parse($validated['fromdate_time'])->addDay()->format("Y-m-d H:i:s");
                }
            }

            // For halfday, no todate_time is required
            if ($validated['halfday_fullday'] === 'halfday') {
                $validated['todate_time'] = null;  // Set to null for halfday
            }

            // Check that To Date is not earlier than From Date
            if (
                isset($validated['fromdate_time'], $validated['todate_time']) &&
                $validated['todate_time'] &&
                Carbon::parse($validated['todate_time'])->lt(Carbon::parse($validated['fromdate_time']))
            ) {
                return Redirect::back()->withInput()->withErrors(['todate_time' => 'To Date cannot be earlier than From Date.']);
            }

            if ($request->hasFile('attachment')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();

                $image_name = Helper::make_slug($validated['company_id'] . ' ' . date('Ymd-His'));
                $filename = $image_name . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/{$validated['company_id']}-{$company_slug}/Leave-Application/{$year}-{$month}/";

                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;

                    if (in_array(strtolower($extension), ['jpeg', 'jpg', 'png'])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                        }
                    }

                    // Save final image path
                    $validated['attachment'] = $uploadedImage;
                }
            }

            // If no conflict, proceed to save the leave application update
            $validated['singleday_multipleday'] = $request?->singleday_multipleday;
            $validated['firsthalf_secondhalf'] = $request?->firsthalf_secondhalf;

            // Update the leave application record
            $leaveApplication->update($validated);

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' updated successfully');
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

        $dataDelete = LeaveApplication::findOrFail($id);
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

            $country = LeaveApplication::withTrashed()->findOrFail($id);
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
            $country = LeaveApplication::withTrashed()->findOrFail($request?->id);
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

        return Excel::download(
            new LeaveApplicationExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId),
            'LeaveApplication-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx'
        );
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


            $query = LeaveApplication::select('*')
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

            //Leave Type
            if ($request->has('leave_type') && $request->leave_type) {
                $query->where('leave_type_id', $request->leave_type);
            }
            // days
            if ($request->has('halfday_fullday') && $request->halfday_fullday) {
                $query->where('halfday_fullday', $request->halfday_fullday);
            }
            if ($request->has('filter_quotation_date') && $request->filter_quotation_date) {
                if (!empty($request?->filter_quotation_date)) {
                    $dates = explode(' to ', $request?->filter_quotation_date);

                    if (count($dates) === 2) {
                        $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                        $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

                        $query->where(function ($q) use ($from, $to) {
                            $q->whereBetween('fromdate_time', [$from, $to])
                                ->orWhereBetween('todate_time', [$from, $to]);
                        });
                    } elseif (count($dates) === 1) {
                        $singleDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                        $query->where(function ($q) use ($singleDate) {
                            $q->whereDate('fromdate_time', $singleDate)
                                ->orWhereDate('todate_time', $singleDate);
                        });
                    }
                }
            }


            // dd($request->all(), Helper::interpolateQuery($query?->toSql(), $query?->getBindings()));
            // Status filter
            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Search filter
            if ($request->has('search') && !empty($request->search)) {
                $query->where('leave_reason', 'like', '%' . $request->search . '%');
            }

            $LeaveApplication = $query->get();

            return view($modules['folder_path'] . '.print', compact('LeaveApplication', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function submitLeaveRequest(Request $request)
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
        if (!$modules['approval_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $leave = LeaveApplication::find($request->id);
            if (!$leave) {
                return response()->json(['success' => false, 'message' => 'Leave not found.']);
            }

            $leave->status = $request->status;

            // Validate rejection reason if status is 'reject'
            if ($request->status === 'reject') {
                if (empty($request->reject)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rejection reason is required.'
                    ]);
                }
                $leave->rejection_reason = $request->reject;
            } else {
                $leave->rejection_reason = null;
            }

            $leave->save();

            return response()->json([
                'success' => true,
                'message' => $request->status === 'reject' ? 'Leave request rejected' : 'Leave request approved',
                'leave_request_id' => $leave->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ]);
        }
    }
}
