<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpoMarketingSliderRequest;
use App\Models\Company;
use App\Models\ExpoManagement;
use App\Models\ExpoMarketingSlider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExpoMarketingSliderController extends Controller
{
    public $modules = [];

    public function __construct()
    {

        parent::__construct();

        $this->modules = [
            'title' => 'Expo Marketing Slider',
            'folder_path' => 'software.modules.visitor-book.expo-marketing-slider',
            'route' => 'expo-marketing-slider',
            'table_name' => (new ExpoMarketingSlider())->getTable(),
            'permisstion_prefix' => 'expo-marketing-slider',
            'module_name' => 'Expo Marketing Slider',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null

        ];
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
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
                (object)['data' => 'name', 'name' => 'name', 'td_label' => 'Name', 'className' => 'text-center'],
                (object)['data' => 'image', 'name' => 'image', 'td_label' => 'Image', 'className' => 'text-center'],
                (object)['data' => 'redirect_url', 'name' => 'redirect_url', 'td_label' => 'Redirect URL', 'className' => 'text-center'],
                (object)['data' => 'sequence', 'name' => 'sequence', 'td_label' => 'Sequence'],
                (object)['data' => 'status', 'name' => 'status', 'td_label' => 'Status'],
                // (object)['data' => 'company', 'name' => 'company', 'td_label' => 'Company'],
                (object)['data' => 'action', 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
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

            //dd($request->all());
            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = ExpoMarketingSlider::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check()) {
                            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                            $query->where('company_id', $teamPersonCompanyId);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('id', 'DESC');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company']);

                return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn('image', function ($row) {
                        if ($row->attachment) {

                            return '<img src="' . $row->attachment_url . '" width="100" height="100">';
                        }
                        return '-';
                    })


                    ->filter(function ($query) use ($request) {

                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                        }
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


                    ->editColumn('sequence', function ($row) use ($modules) {
                        if ($modules['update_permission']) {
                            return '<input type="number" class="form-control-sequence displayorder-input"
                            value="' . e($row["sequence"]) . '"
                            data-id="' . e($row["id"]) . '">';
                        }
                        return e($row["sequence"]);
                    })



                    ->addColumn('company_name', function ($row) {
                        return $row->company?->company_name ?? '-';
                    })

                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';

                        if (!$row?->deleted_at) {

                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", $row->id) . '"
                                class="btn btn-light btn-icon mx-1">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>';
                            }


                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)"
                                data-id="' . $row->id . '"
                                data-did="' . route($modules["route"] . ".destroy", $row->id) . '"
                                class="btn btn-danger btn-icon deletebutton mx-1">
                                <i class="fa-solid fa-trash"></i>
                            </a>';
                            }
                        } else {

                            $btn .= '<a href="javascript:void(0)"
                            data-restore="' . route($modules["route"] . ".restore", $row->id) . '"
                            class="btn btn-light mx-1 record-restore"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-original-title="Restore Data">
                            <i class="ti ti-history"></i> Restore
                        </a>';
                        }

                        return $btn ?: '-';
                    })

                    ->rawColumns(['status', 'image', 'action', 'sequence'])
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
    public function store(ExpoMarketingSliderRequest $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;


        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (empty($modules['add_permission'])) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);


        $validated = $request->validated();
        $validated['created_by'] = $loginUserId;

        try {

            if ($request->hasFile('attachment')) {
                /*
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name ?? 'default-company';
                $company_slug = Str::slug($company_name);

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/{$validated['company_id']}-{$company_slug}/ExpoMarketingSlider/{$year}-{$month}/";
                */

                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();

                $image_name = Helper::make_slug(
                    $validated['company_id'] . ' ' . $validated['name']
                ) . '-' . now()->format('Ymd-His');

                $filename = $image_name . '.' . $extension;


                $fileUploadPath = Helper::fileUploadPath(null, ExpoMarketingSlider::$folderPath, $validated['company_id']);

                $uploadedPath = public_path($fileUploadPath);

                if (!file_exists($uploadedPath)) {
                    mkdir($uploadedPath, 0777, true);
                }

                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $fileUploadPath . $filename;

                    // Convert to WebP if image
                    if (in_array(strtolower($extension), ['jpeg', 'png', 'jpg'])) {
                        $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                        if ($webP) {
                            $uploadedImage = $fileUploadPath . $webP;
                        }
                    }

                    $validated['attachment'] = $uploadedImage;
                }
            }


            // return $validated;
            ExpoMarketingSlider::create($validated);

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' created successfully');
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
        // dd($id);
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
            $edit = ExpoMarketingSlider::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpoMarketingSliderRequest $request,  string $id)
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
        $request['id'] = $id;

        $validated = $request->validated();
        try {

            $validated['updated_by'] = $loginUserId;
            $updateData = ExpoMarketingSlider::findOrFail($id);

            if ($updateData) {
                unset($validated['id']);

                if ($request->hasFile('attachment')) {
                    /*
                    $company = Company::find($validated['company_id']);
                    $company_name = $company->company_name ?? 'default-company';
                    $company_slug = Str::slug($company_name);
                    $year = now()->format('Y');
                    $month = now()->format('m');
                    $folder = "uploads/{$validated['company_id']}-{$company_slug}/ExpoMarketingSlider/{$year}-{$month}/";
                    */

                    $file = $request->file('attachment');
                    $extension = $file->getClientOriginalExtension();

                    $image_name = Helper::make_slug(
                        $validated['company_id'] . ' ' . $validated['name']
                    ) . '-' . now()->format('Ymd-His');

                    $filename = $image_name . '.' . $extension;

                    $fileUploadPath = Helper::fileUploadPath(null, ExpoMarketingSlider::$folderPath, $validated['company_id']);

                    $uploadedPath = public_path($fileUploadPath);

                    if (!file_exists($uploadedPath)) {
                        mkdir($uploadedPath, 0777, true);
                    }

                    if ($file->move($uploadedPath, $filename)) {
                        $uploadedImage = $fileUploadPath . $filename;

                        // Convert to WebP if image
                        if (in_array(strtolower($extension), ['jpeg', 'png', 'jpg'])) {
                            $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                            if ($webP) {
                                $uploadedImage = $fileUploadPath . $webP;
                            }
                        }

                        $validated['attachment'] = $uploadedImage;
                    }
                }


                // return $validated;
                // Update record
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

        $dataDelete = ExpoMarketingSlider::findOrFail($id);
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
            $expomarketingslider = ExpoMarketingSlider::withTrashed()->findOrFail($id);
            $expomarketingslider->restore();
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
            $expomarketingslider = ExpoMarketingSlider::withTrashed()->findOrFail($request?->id);
            if ($expomarketingslider) {
                $expomarketingslider->status = $request->update_status;
                $expomarketingslider->updated_by = $loginUserId;
                $expomarketingslider->save();
                if ($isAjax) {
                    return $this->sendResponse($expomarketingslider, $modules['title'] . ' status update successfully.');
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
    public function updateDisplayOrder(Request $request)
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

        $request->validate([
            'id' => 'required',
            'sequence' => 'required|integer'
        ]);

        $status = ExpoMarketingSlider::findOrFail($request->id);
        $status->sequence = $request->sequence;
        $status->save();

        $status->save();

        return response()->json(['message' => 'Display order updated successfully.']);
    }
}
