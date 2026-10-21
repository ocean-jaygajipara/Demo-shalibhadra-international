<?php

namespace App\Http\Controllers\software;

use App\Exports\DocumentListExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentListRequest;
use App\Models\Company;
use App\Models\DocumentList;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;




class DocumentListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Document List',
            'folder_path' => 'software.modules.master.document-list',
            'route' => 'document-list',
            'table_name' => (new DocumentList())->getTable(),
            'permisstion_prefix' => 'document-list',
            'module_name' => 'Document Details',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,


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
                // (object)[ 'data' => "id", 'name' => 'id', 'td_label' => 'Id' ],
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],

                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "document_type.name", 'name' => 'name', 'td_label' => 'Document Type Name', 'className' =>  ''],
                (object)['data' => "name", 'name' => 'name', 'td_label' => 'Name', 'className' =>  ''],
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
                // dd($request->all());
                $data = DocumentList::select('*');
                $data = $data->with(['company', 'document_type']);
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
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


                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }
                        if ($request->has('filter_document_type') && $request->filter_document_type) {
                            $query->where('document_type_id', $request->filter_document_type);
                        }

                        if ($request->has('search')) {
                            $query->where('name', 'like', "%" . $request->search . "%");
                        }
                        if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
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
        // Validate input
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new DocumentList())->getTable())
                    ->where(function ($query) use ($request) {
                        return $query->where('company_id', $request->company_id)
                            ->where('document_type_id', $request->document_type_id)
                            ->whereNull('deleted_at');
                    }),
            ],
            'status' => ['required', 'in:active,inactive'],
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:10240',
        ]);

        // Image handling
        $imagePath = null;
        if ($request->hasFile('image')) {

            $company = Company::find($validated['company_id']);
            $company_name = $company->company_name ?? 'default-company';
            $company_slug = Str::slug($company_name);

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $image_name = Helper::make_slug($validated['company_id'] . ' ') . date('Ymd-His');
            $filename = $image_name . '.' . $extension;


            $year = now()->format('Y');
            $month = now()->format('m');
            $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/document-list/{$year}-{$month}" . "/";
            //    dd($folder);
            // $filename = $image_name . '.' . $extension;

            $sub_folder_path = $folder;
            $uploadedPath = public_path($sub_folder_path);
            if ($file->move($uploadedPath, $filename)) {
                $uploadedImage = $sub_folder_path . $filename;
                // if (in_array($extension, ["jpeg", "png", "jpg"])) {
                //     $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                //     if ($webP) {
                //         // dd($webP);
                //         $uploadedImage = $sub_folder_path . $webP;
                //     }
                // }
                $validated['image'] = $uploadedImage;
                // dd($uploadedImage);
            }
        }

        // Save to database
        // $document = new DocumentList();
        // $document->company_id = $validated['company_id'];
        // $document->document_type_id = $validated['document_type_id'];
        // $document->name = $validated['name'];
        // $document->status = $validated['status'];
        // $document->image = $imagePath;
        // $document->save();

        $validated['created_by'] = $loginUserId;
        DocumentList::create($validated);

        // return Redirect::route('document-list.index')->with('success', 'Document created successfully');
        return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
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

            $edit = DocumentList::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DocumentListRequest $request, string $id)
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

            // Fetch the existing document record
            $document = DocumentList::findOrFail($id);

            // Handle image upload (delete old image if new one is uploaded)
            if ($request->hasFile('image')) {
                $company = Company::find($validated['company_id']);
                $company_name = $company->company_name;
                $company_slug = Str::slug($company_name);

                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $image_name = Helper::make_slug($validated['company_id'] . ' ') . date('Ymd-His');
                $filename = $image_name . '.' . $extension;

                $year = now()->format('Y');
                $month = now()->format('m');
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/document-list/{$year}-{$month}" . "/";

                $sub_folder_path = $folder;
                $uploadedPath = public_path($sub_folder_path);
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    // if (in_array($extension, ["jpeg", "png", "jpg"])) {
                    //     $webP = Helper::existingImageConverToWebp($extension, $uploadedPath, $filename, $image_name, 80, false);
                    //     if ($webP) {
                    //         $uploadedImage = $sub_folder_path . $webP;
                    //     }
                    // }
                    $validated['image'] = $uploadedImage;
                }
            }

            // Update the document with validated data
            $document->update($validated);

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

        $dataDelete = DocumentList::findOrFail($id);

        $isAjax = $request->ajax();

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

            $state = DocumentList::withTrashed()->findOrFail($id);
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
            $state = DocumentList::withTrashed()->findOrFail($request?->id);
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
        return Excel::download(
            new DocumentListExport($request->all(), $this->authenticateLoginUserDetails, $modules),
            'DocumentList-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx'
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
            $documentlist = DocumentList::select('*')
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

            // $documentlist = DocumentList::with(['company']);
            // dd($request->all());


            if ($request->has('company') && !empty($request->company)) {
                $documentlist->whereHas('company', function ($q) use ($request) {
                    $q->where('company_id', 'like', '%' . $request->company . '%');
                });
            }

            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $documentlist->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $documentlist->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->has('documentType') && !empty($request->documentType)) {
                $documentlist->where('document_type_id', $request->documentType);
            }

            $documentlist = $documentlist->orderBy('id', 'DESC')->get();

            return view($modules['folder_path'] . '.print', compact('documentlist', 'modules', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
