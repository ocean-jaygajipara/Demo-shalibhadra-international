<?php

namespace App\Http\Controllers\software;

use App\Exports\ExpoManagementExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpoManagementRequest;
use App\Models\ExpoManagement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class ExpoManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Expo Management',
            'folder_path' => 'software.modules.visitor-book.expo-management',
            'route' => 'expo-management',
            'table_name' => (new ExpoManagement())->getTable(),
            'permisstion_prefix' => 'expo-management',
            'module_name' => 'Expo Management',
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
        // dd($modules);
        try {
            $columns = [
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],

                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' => ''],
                (object)['data' => "expo_category.name", 'name' => 'expo_category_id', 'td_label' => 'Expo Category', 'className' => ''],
                (object)['data' => "name", 'name' => 'name', 'td_label' => 'Expo Name', 'className' => ''],
                (object)['data' => "start_date", 'name' => 'start_date', 'td_label' => 'Start Date', 'className' => ''],
                (object)['data' => "end_date", 'name' => 'end_date', 'td_label' => 'End Date', 'className' => ''],
                (object)['data' => "location", 'name' => 'location', 'td_label' => 'Location', 'className' => ''],
                (object)['data' => "expo_url", 'name' => 'expo_url', 'td_label' => 'Expo URL', 'className' => ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' => 'w-5 text-start'],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-start'],

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
                $data = ExpoManagement::select('*')
                ->orderBy('id', 'DESC');

                if (isset($modules['company_id']) && $modules['company_id']) {
                    $data = $data->where('company_id', $modules['company_id']);
                }

                $data->where(function ($query) use ($modules, $loginUserId) {
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
                $data = $data->with(['company', 'expo_category']);

                // $editPermisstion = (Auth::user()->can($modules["table_name"].'-edit')) ? true : false;
                // $deletePermisstion = (Auth::user()->can($modules["table_name"].'-delete')) ? true : false;
                $editPermisstion = true;
                $deletePermisstion = true;

                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        // Filter by expo category
                        if ($request->has('expo_category_id') && $request->expo_category_id) {
                            $query->where('expo_category_id', $request->expo_category_id);
                        }

                        // Filter by status
                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                        if ($request->has('filter_expo_date') && $request->filter_expo_date) {
                            if (!empty($request?->filter_expo_date)) {
                                $dates = explode(' to ', $request?->filter_expo_date);

                                if (count($dates) === 2) {
                                    $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                                    $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

                                    $query->where(function ($q) use ($from, $to) {
                                        $q->whereBetween('start_date', [$from, $to])
                                            ->orWhereBetween('end_date', [$from, $to]);
                                    });
                                } elseif (count($dates) === 1) {
                                    $singleDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                                    $query->where(function ($q) use ($singleDate) {
                                        $q->whereDate('start_date', $singleDate)
                                            ->orWhereDate('end_date', $singleDate);
                                    });
                                }
                            }
                        }

                        // Filter by text search (name, expo_url, location)
                        if ($request->has('search') && $request->search != '') {
                            $search = $request->search;
                            $query->where(function ($q) use ($search) {
                                $q->where('name', 'like', "%$search%")
                                    ->orWhere('expo_url', 'like', "%$search%")
                                    ->orWhere('location', 'like', "%$search%");
                            });
                        }
                    })
                    ->editColumn('name', function ($row) {
                        $html = e($row->name); // escape the name to prevent XSS

                        if (!empty($row->attachment_url)) {
                            $html .= '
                        <button type="button"
                            class="btn btn-sm btn-info file-preview m-1"
                            data-url="' . e($row->attachment_url) . '"
                            data-filename="' . e($row->attachment) . '">
                            File
                        </button>';
                        }
                        return $html;
                    })
                    ->editColumn('start_date', function ($row) {
                        return $row->start_date ? Carbon::parse($row->start_date)->format('d-m-Y') : '';
                    })
                    ->editColumn('end_date', function ($row) {
                        return $row->end_date ? Carbon::parse($row->end_date)->format('d-m-Y') : '';
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
                    ->editColumn('date', fn($row) => $row->date ? Carbon::parse($row->date)->format('d-m-Y') : '-')
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
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }
                        // $btn .= '<a href="javascript:void(0)" class="btn btn-info btn-icon mr-2"><i class="fa-solid fa-key"></i></a>';
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })

                    ->rawColumns(['name', 'status', 'action'])
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
            View::share('modules', $modules);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExpoManagementRequest $request)
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

        // dd($request->all(), $validated);


        try {
            // return $this->authenticateLoginUserDetails;

            $validated['created_by'] = $this->authenticateLoginUserDetails?->id;
            if ($request->hasFile('attachment')) {
                $companyId = $validated['company_id'];
                $image_name = Helper::make_slug('company-' . $companyId . '-' . date('Ymd-His'));
                $file = $request->file('attachment'); // must match input name
                $extension = $file->getClientOriginalExtension();
                $filename = $image_name . '.' . $extension;

                $sub_folder_path = 'uploads/company/' . $companyId . '/ExpoManagement/';
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;

                    if (in_array(strtolower($extension), ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                        }
                    }

                    $validated['attachment'] = $uploadedImage;
                }
            }


            // return $validated;
            ExpoManagement::create($validated);

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

            $edit = ExpoManagement::findOrFail($id);
            $edit->expo_category_name = $edit->expoCategory->name ?? '';
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpoManagementRequest $request, string $id)
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
            if ($request->hasFile('attachment')) {
                $companyId = $validated['company_id'];
                $image_name = Helper::make_slug('company-' . $companyId . '-' . date('Ymd-His'));
                $file = $request->file('attachment'); // must match input name
                $extension = $file->getClientOriginalExtension();
                $filename = $image_name . '.' . $extension;

                $sub_folder_path = 'uploads/company/' . $companyId . '/ExpoManagement/';
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;

                    if (in_array(strtolower($extension), ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                        }
                    }

                    $validated['attachment'] = $uploadedImage;
                }
            }
            $updateData = ExpoManagement::findOrFail($id);
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

        $dataDelete = ExpoManagement::findOrFail($id);
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

            $country = ExpoManagement::withTrashed()->findOrFail($id);
            $country->restore();

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function status_update(Request $request)
    {
        $isAjax = ($request->ajax()) ? true : false;

        // $isAjax = ($request->ajax()) ? true : false;

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
            $country = ExpoManagement::withTrashed()->findOrFail($request?->id);
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

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['print_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {
            $query = ExpoManagement::with('company')
                ->orderBy('id', 'DESC')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });


            // Filter by company
            if ($request->filled('filter_company')) {
                $query->where('company_id', $request->filter_company);
            }

            // Filter by expo category
            if ($request->filled('expo_category_id')) {
                $query->where('expo_category_id', $request->expo_category_id);
            }

            // Filter by status
            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('filter_expo_date') && $request->filter_expo_date) {
                if (!empty($request?->filter_expo_date)) {
                    $dates = explode(' to ', $request?->filter_expo_date);

                    if (count($dates) === 2) {
                        $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                        $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

                        $query->where(function ($q) use ($from, $to) {
                            $q->whereBetween('start_date', [$from, $to])
                                ->orWhereBetween('end_date', [$from, $to]);
                        });
                    } elseif (count($dates) === 1) {
                        $singleDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                        $query->where(function ($q) use ($singleDate) {
                            $q->whereDate('start_date', $singleDate)
                                ->orWhereDate('end_date', $singleDate);
                        });
                    }
                }
            }

            // Filter by text search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('expo_url', 'like', "%$search%")
                        ->orWhere('location', 'like', "%$search%");
                });
            }

            $expo_list = $query->get();

            return view($modules['folder_path'] . '.print', compact('expo_list', 'company_id', 'modules'));
        } catch (\Exception $e) {
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

        return Excel::download(new ExpoManagementExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'ExpoManagement-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
}
