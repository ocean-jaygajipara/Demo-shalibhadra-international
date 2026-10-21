<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentExport;
use App\Exports\VisitorGatepassExport;
use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\VisitorGatepassRequest;
use App\Models\Company;
use App\Models\VisitorGatepass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class VisitorGatepassController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Visitor Gatepass',
            'folder_path' => 'software.modules.gate-pass.visitor-gatepass',
            'route' => 'visitor-gatepass',
            'table_name' => (new VisitorGatepass())->getTable(),
            'permisstion_prefix' => 'visitor-gatepass',
            'module_name' => 'Visitor Gatepass',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];
    }



    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permission setup
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        // If view permission is missing
        if (!$modules['view_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            $columns = [
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => "company", 'name' => 'company', 'td_label' => 'Company name', 'className' =>  'text-wrap'],
                (object)['data' => 'visitor_details', 'name' => 'visitor_details', 'td_label' => 'Visitor Details'],
                (object)['data' => 'visit_deatils', 'name' => 'visit_deatils', 'td_label' => 'Visit Deatils'],
                (object)['data' => 'meeting_with', 'name' => 'meeting_with', 'td_label' => 'Meeting With'],
                (object)['data' => 'entry_details', 'name' => 'entry_details', 'td_label' => 'Entry Details'],
                (object)['data' => 'status', 'name' => 'status', 'td_label' => 'Status', 'className' => 'w-5 text-start'],
                (object)['data' => 'action', 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-start'],
            ];

            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company';
                });

                $columns = array_values($columns);
            }
            View::share('columns', $columns);

            if ($request->ajax()) {
                $data = VisitorGatepass::with('company', 'purposeOfVisit', 'visitorType', 'teamPerson', 'department',);

                // Filter by company if needed
                if ($modules['company_id']) {
                    $data->where('company_id', $modules['company_id']);
                }

                // Show deleted records if permission exists
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                $data = $data->orderBy('id', 'DESC');

                $returnData = DataTables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {

                        if ($request->has('search')) {
                            $query->where(function ($q) use ($request) {
                                $q->where('visitor_name', 'like', "%" . $request->search . "%")
                                    ->orWhere('contact_number', 'like', "%" . $request->search . "%")
                                    ->orWhere('email', 'like', "%" . $request->search . "%");
                            });
                        }
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                        if ($request->filled('visitor_type_id')) {
                            $query->where('visitor_type_id', $request->visitor_type_id);
                        }

                        if ($request->filled('purpose_visit_id')) {
                            $query->where('purpose_of_visit_id', $request->purpose_visit_id);
                        }

                        if ($request->filled('person_to_meet')) {
                            $query->where('team_person_id', $request->person_to_meet);
                        }

                        if ($request->filled('department_id')) {
                            $query->where('department_id', $request->department_id);
                        }
                    })

                    ->addColumn('visitor_details', function ($row) {
                        $name = '<i class="fa fa-user text-primary me-1"></i> ' . ($row->visitor_name ? e($row->visitor_name) : '-');

                        $phone = $row->contact_number
                            ? '<a href="tel:' . e($row->contact_number) . '" class="text-decoration-none me-2"><i class="fa fa-phone text-success"></i> ' . e($row->contact_number) . '</a>'
                            : '';

                        $email = $row->email
                            ? '<a href="https://mail.google.com/mail/?view=cm&fs=1&to=' . urlencode($row->email) . '" target="_blank" class="text-decoration-none">
                                <i class="fa fa-envelope text-primary"></i> ' . e($row->email) . '
                            </a>'
                            : '';


                        return implode('<br>', array_filter([$name, $phone, $email]));
                    })



                    ->addColumn('visit_deatils', function ($row) {
                        return "<strong>Type:</strong> " . e($row->visitorType?->name) . "<br>" .
                            "<strong>Purpose:</strong> " . ($row->purposeOfVisit?->name ?? '-');
                    })
                    ->addColumn('entry_details', function ($row) {
                        $html = '';

                        if (!$row->check_in_time) {
                            $html .= "<span id='entry-box-{$row->id}'>
                    <button type='button' class='btn btn-sm btn-success mark-in-out-btn' data-id='{$row->id}' data-type='in'>In</button>
                  </span>";
                        } else {
                            $html .= "<span id='entry-box-{$row->id}'>
                    <strong>In:</strong> " . \Carbon\Carbon::parse($row->check_in_time)->format('d-m-Y h:i A') . "<br>";
                            if (!$row->check_out_time) {
                                $html .= "<button type='button' class='btn btn-sm btn-danger mt-1 mark-in-out-btn' data-id='{$row->id}' data-type='out'>Out</button>";
                            } else {
                                $html .= "<strong>Out:</strong> " . \Carbon\Carbon::parse($row->check_out_time)->format('d-m-Y h:i A');
                            }
                            $html .= "</span>";
                        }

                        return $html;
                    })



                    ->addColumn('meeting_with', function ($row) {
                        return "<strong>Name:</strong> " . ($row->teamPerson?->name ?? '-') . "<br>" .
                            "<strong>Dept:</strong> " . ($row->department?->name ?? '-');
                    })



                    ->addColumn('company', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('purpose', function ($row) {
                        return $row->purposeOfVisit?->name ?? '-';
                    })
                    ->addColumn('person_to_meet', function ($row) {
                        return $row->teamPerson?->name ?? '-';
                    })
                    ->addColumn('department', function ($row) {
                        return $row->department?->name ?? '-';
                    })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = '<ul class="dropdown-menu">';
                        if ($row->status == "active") {
                            $btn .= '<button type="button" class="btn btn-success btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-url="' . route($modules["route"] . '.status-update') . '" data-id="' . $row["id"] . '" data-update_status="inactive">Inactive</a></li>';
                        } elseif ($row->status == "inactive") {
                            $btn .= '<button type="button" class="btn btn-danger btn-sm dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown">' . ucfirst($row->status) . '</button>';
                            $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-url="' . route($modules["route"] . '.status-update') . '" data-id="' . $row["id"] . '" data-update_status="active">Active</a></li>';
                        } else {
                            return '-';
                        }
                        $dropdown .= '</ul>';
                        return $btn . $dropdown;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row?->deleted_at) {
                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore"><i class="ti ti-history"></i> Restore</a>';
                        }

                        return $btn ?: '-';
                    })
                    ->rawColumns(['status', 'entry_details', 'action', 'visitor_details', 'visit_deatils', 'meeting_with'])
                    ->make(true);

                return $returnData;
            }

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
        $company = Company::find($modules['company_id']);
        $mobile_min = $company?->mobile_min;
        $mobile_max = $company?->mobile_max;

        try {


            View::share('modules', $modules);
            View::share('mobile_min', $mobile_min);
            View::share('mobile_max', $mobile_max);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function store(VisitorGatepassRequest $request)
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
        // return $request->all();
        $validated = $request->validate([
            'company_id' => 'required',
            // 'name' => 'required',
            // 'status' => 'required',
        ]);
        try {
            // return $this->authenticateLoginUserDetails;
            $validated = $request->validated();
            $validated['created_by'] = $loginUserId;

            $company = Company::find($validated['company_id']);
            $company_name = $company->company_name ?? 'default-company';
            $company_slug = Str::slug($company_name);

            if ($request->filled('signature') && Str::startsWith($request->input('signature'), 'data:image')) {
                $signatureData = $request->input('signature');
                $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
                $signatureData = str_replace(' ', '+', $signatureData);
                $signatureImage = base64_decode($signatureData);

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/signature/{$year}-{$month}/";
                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $image_name = Helper::make_slug($validated['company_id'] . ' signature') . date('Ymd-His');
                $filename = $image_name . '.png';
                $fullPath = $uploadedPath . $filename;

                file_put_contents($fullPath, $signatureImage);

                // Optional: convert to WebP like attachment handling
                $webP = Helper::existingImageConverToWebp('png', $uploadedPath, $filename, $image_name, 80, false);
                if ($webP) {
                    $validated['signature'] = $sub_folder_path . $webP;
                } else {
                    $validated['signature'] = $sub_folder_path . $filename;
                }
            }
            if ($request->filled('photo') && Str::startsWith($request->input('photo'), 'data:image')) {
                $photoData = $request->input('photo');
                $photoData = str_replace('data:image/png;base64,', '', $photoData);
                $photoData = str_replace(' ', '+', $photoData);
                $photoImage = base64_decode($photoData);

                $year = now()->format('Y');
                $month = now()->format('m');
                $photoFolder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/photo/{$year}-{$month}/";
                $photoPath = public_path($photoFolder);

                if (!file_exists($photoPath)) {
                    mkdir($photoPath, 0755, true);
                }

                $photoName = Helper::make_slug($validated['company_id'] . ' photo') . date('Ymd-His');
                $photoFilename = $photoName . '.png';
                $fullPhotoPath = $photoPath . $photoFilename;

                file_put_contents($fullPhotoPath, $photoImage);

                $photoWebP = Helper::existingImageConverToWebp('png', $photoPath, $photoFilename, $photoName, 80, false);
                if ($photoWebP) {
                    $validated['photo'] = $photoFolder . $photoWebP;
                } else {
                    $validated['photo'] = $photoFolder . $photoFilename;
                }
            }

            // return $validated;

            VisitorGatepass::create($validated);
            // dd($request->all(), $validated);

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function show(string $id) {}



    public function edit(string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permission check
        foreach (config('constants.permissions') as $permission) {
            $modules["{$permission}_permission"] = !$modules['company_id']
                ? true
                : Gate::check('hasPermission', [$permission, $modules['module_name']]);
        }

        if (!$modules['update_permission']) {
            abort(403, 'Unauthorized');
        }
        $company = Company::find($modules['company_id']);
        $mobile_min = $company?->mobile_min;
        $mobile_max = $company?->mobile_max;
        try {
            View::share('modules', $modules);
            View::share('mobile_min', $mobile_min);
            View::share('mobile_max', $mobile_max);

            $edit = VisitorGatepass::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function update(VisitorGatepassRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permission check
        foreach (config('constants.permissions') as $permission) {
            $modules["{$permission}_permission"] = !$modules['company_id']
                ? true
                : Gate::check('hasPermission', [$permission, $modules['module_name']]);
        }

        if (!$modules['update_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            $validated = $request->validated();
            $company = Company::find($validated['company_id']);
            $company_name = $company->company_name ?? 'default-company';
            $company_slug = \Illuminate\Support\Str::slug($company_name);

            if ($request->filled('signature') && \Illuminate\Support\Str::startsWith($request->input('signature'), 'data:image')) {
                $signatureData = $request->input('signature');
                $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
                $signatureData = str_replace(' ', '+', $signatureData);
                $signatureImage = base64_decode($signatureData);

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/signature/{$year}-{$month}/";
                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0755, true);
                }

                $image_name = Helper::make_slug($validated['company_id'] . ' signature') . date('Ymd-His');
                $filename = $image_name . '.png';
                $fullPath = $uploadedPath . $filename;

                file_put_contents($fullPath, $signatureImage);

                // Convert to webp
                $webP = Helper::existingImageConverToWebp('png', $uploadedPath, $filename, $image_name, 80, false);
                if ($webP) {
                    $validated['signature'] = $sub_folder_path . $webP;
                } else {
                    $validated['signature'] = $sub_folder_path . $filename;
                }
            }

            $validated['updated_by'] = $loginUserId;

            $updateData = VisitorGatepass::findOrFail($id);
            $updateData->update($validated);
            // dd($updateData,$validated);
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' updated successfully');
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

        $dataDelete = VisitorGatepass::findOrFail($id);
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

            $restore_data = VisitorGatepass::withTrashed()->findOrFail($id);
            $restore_data->restore();

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
            $country = VisitorGatepass::withTrashed()->findOrFail($request?->id);
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

            $query = VisitorGatepass::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personalDataPermission'] && !$modules['allDataPermission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->with(['company', 'purposeOfVisit', 'visitorType', 'teamPerson', 'department'])
                ->orderBy('id', 'DESC');

            // Company filter
            if ($request->has('company') && !empty($request->company)) {
                $query->whereHas('company', function ($subQuery) use ($request) {
                    $subQuery->where('company_id', 'like', '%' . $request->company . '%');
                });
            }

            // Status filter
            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Search filter
            if ($request->has('search') && !empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('visitor_name', 'like', '%' . $request->search . '%')
                        ->orWhere('contact_number', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->filled('visitor_type_id')) {
                $query->where('visitor_type_id', $request->visitor_type_id);
            }

            if ($request->filled('purpose_visit_id')) {
                $query->where('purpose_of_visit_id', $request->purpose_visit_id);
            }

            if ($request->filled('person_to_meet')) {
                $query->where('team_person_id', $request->person_to_meet);
            }

            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            $VisitorGatepass = $query->get();

            return view($modules['folder_path'] . '.print', compact('VisitorGatepass', 'company_id', 'modules'));
        } catch (\Exception $e) {
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

        return Excel::download(new VisitorGatepassExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Visitor Gatepass-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }
    public function markTime(Request $request)
    {
        $id = $request->id;
        $type = $request->type;

        $visitor = VisitorGatepass::find($id);

        if (!$visitor) {
            return response()->json(['success' => false, 'message' => 'Record not found.']);
        }

        if ($type == 'in' && !$visitor->check_in_time) {
            $visitor->check_in_time = now();
        } elseif ($type == 'out' && !$visitor->check_out_time) {
            $visitor->check_out_time = now();
        } else {
            return response()->json(['success' => false, 'message' => 'Already marked.']);
        }

        $visitor->save();

        return response()->json([
            'success' => true,
            'type' => $type,
            'time' => now()->format('d-m-Y h:i A'),
        ]);
    }
}
