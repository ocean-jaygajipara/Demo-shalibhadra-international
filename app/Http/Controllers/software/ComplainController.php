<?php

namespace App\Http\Controllers\software;

use App\Exports\ComplainExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComplainRequest;
use App\Models\Company;
use App\Models\Complain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;


class ComplainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Complain',
            'folder_path' => 'software.modules.customer.complain',
            'route' => 'complain',
            'table_name' => (new Complain())->getTable(),
            'permisstion_prefix' => 'complain',
            'module_name' => 'Complain',
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
                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Sr No.'],
                  (object)['data' => 'DT_RowIndex','name' => 'DT_RowIndex','td_label' => 'Sr No.','orderable' => false,'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "sourcecomplain.name", 'name' => 'source_complain_id', 'td_label' => 'Source of Complaint', 'className' =>  ''],
                (object)['data' => "complaintype.name", 'name' => 'complain_type_id', 'td_label' => 'Complain Type', 'className' =>  ''],
                (object)['data' => "customertype.name", 'name' => 'customer_type_id', 'td_label' => 'Customer Type', 'className' =>  ''],
                (object)['data' => "customer.customer_name", 'name' => 'customer_id', 'td_label' => 'Customer', 'className' =>  ''],
                (object)['data' => "remark", 'name' => 'remark', 'td_label' => 'Remark', 'className' =>  ''],
                (object)['data' => "complaincreatedbyteamperson.name", 'name' => 'complain_created_by_id', 'td_label' => 'Complain Created By', 'className' =>  ''],
                (object)['data' => "complainassigntoteamperson.name", 'name' => 'complain_assign_to_id', 'td_label' => 'Complain Assign To', 'className' =>  ''],
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
                $data = Complain::select('complains.*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {

                            $query->where('complains.company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('complains.created_by', $loginUserId);
                            }
                        }
                    })
                    ->orderBy('complains.id', 'DESC');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->leftJoin('customers', 'complains.customer_id', '=', 'customers.id');
                $data = $data->with(['company', 'complainassigntoteamperson', 'complaincreatedbyteamperson', 'customer', 'sourcecomplain', 'complaintype', 'customertype']);



                return Datatables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {

                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('customers.company_id', $request->filter_company);
                        }

                        if ($request->has('complain_assign_to_id') && $request->complain_assign_to_id) {
                            $query->where('complain_assign_to_id', $request->complain_assign_to_id);
                        }
                        if ($request->has('search') && !empty($request->search)) {
                            $query->whereHas('customer', function ($subQuery) use ($request) {
                                $subQuery->where('customer_name', 'like', '%' . $request->search . '%');
                            });
                        }

                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('complains.status', $request->status);
                        }

                        if ($request->has('customer_type_id') && $request->customer_type_id) {
                            $query->where('customers.customer_type_id', $request->customer_type_id);
                        }

                        if ($request->has('source_complain_id') && $request->source_complain_id) {
                            $query->where('source_complain_id', $request->source_complain_id);
                        }

                        if ($request->has('complain_type_id') && $request->complain_type_id) {
                            $query->where('complain_type_id', $request->complain_type_id);
                        }

                        if ($request->has('complain_created_by_id') && $request->complain_created_by_id) {
                            $query->where('complain_created_by_id', $request->complain_created_by_id);
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
                    ->addColumn('image', function ($row) {
                        if ($row->image) {

                            return '<img src="' . $row->image_url . '" width="50" height="50">';
                        }
                        return '-';
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
            // Generate complain number (complain_no) dynamically
            // $lastComplain = Complain::orderBy('id', 'desc')->first();

            // if ($lastComplain) {

            //     $lastNumber = (int) substr($lastComplain->complain_no, 3); //
            //     $newNumber = $lastNumber + 1;
            // } else {

            //     $newNumber = 1;
            // }

            $newNumber = 1;

            $complainNo = 'CP-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT); // Format the complain number


            // Pass the generated complainNo to the view
            return view($modules['folder_path'] . '.form', compact('complainNo', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(ComplainRequest $request)
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


        // dd($validated, $request->all());
        try {

            // $validated['customer_id'] = "1";
            $validated['created_by'] = $loginUserId;
            // return $validated;


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
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/complain/{$year}-{$month}" . "/";
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

            Complain::create($validated);

            return redirect()->route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' created successfully.');
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')
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
        dd($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $value) {
                $modules[$value . '_permission'] = !$modules['company_id'] ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['update_permission']) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);

            $edit = Complain::findOrFail($id);
            View::share('edit', $edit);

            $company = Company::find($edit->company_id);
            $company_slug = Str::slug($company->company_name);

            $image_path = asset("storage/uploads/{$company->id}-{$company_slug}/complain/{$edit->image}");
            View::share('image_path', $image_path);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(ComplainRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $value) {
                $modules[$value . '_permission'] = !$modules['company_id'] ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['update_permission']) {
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            $validated = $request->validated();
            $validated['updated_by'] = $loginUserId;

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
                $folder = "uploads/" . $validated['company_id'] . "-" . $company_slug . "/complain/{$year}-{$month}" . "/";

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

            $updateData = Complain::findOrFail($id);
            $updateData->update($validated);

            return redirect()->route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route($modules['route'] . '.index')
                ->withErrors($e->getMessage());
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
        $isAjax = ($request->ajax()) ? true : false;
        try {
            $dataDelete = Complain::findOrFail($id);


            $dataDelete->deleted_by = $loginUserId;
            $dataDelete->save();

            if ($dataDelete->delete()) {
                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => $modules['title'] . ' deleted successfully.',
                    ]);
                }

                return redirect()->route($modules['route'] . '.index')
                    ->withSuccess($modules['title'] . ' deleted successfully.');
            }

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong, please try again later.',
                ]);
            }

            return redirect()->route($modules['route'] . '.index')
                ->withErrors('Something went wrong, please try again later.');
        } catch (\Exception $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect()->route($modules['route'] . '.index')->withErrors($e->getMessage());
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

            $state = Complain::withTrashed()->findOrFail($id);
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
            $state = Complain::withTrashed()->findOrFail($request?->id);
            if ($state) {
                $state->status = $request->update_status;
                $state->updated_by = $loginUserId;
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
        return Excel::download(new ComplainExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'ComplainExport-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $query = Complain::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personalDataPermission'] && !$modules['allDataPermission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->with(['company','customer'])
                ->orderBy('id', 'DESC');



            // Filters
            if ($request->has('company') && !empty($request->company)) {
                $query->where('company_id', $request->company);
            }

            if ($request->has('complain_assign_to_id') && $request->complain_assign_to_id) {
                $query->where('complain_assign_to_id', $request->complain_assign_to_id);
            }

            if ($request->has('customer_type_id') && $request->customer_type_id) {
                $query->where('customer_type_id', $request->customer_type_id);
            }

            if ($request->has('source_complain_id') && $request->source_complain_id) {
                $query->where('source_complain_id', $request->source_complain_id);
            }

            if ($request->has('complain_type_id') && $request->complain_type_id) {
                $query->where('complain_type_id', $request->complain_type_id);
            }

            if ($request->has('complain_created_by_id') && $request->complain_created_by_id) {
                $query->where('complain_created_by_id', $request->complain_created_by_id);
            }
            if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && !empty($request->search)) {
                $query->whereHas('customer', function ($subQuery) use ($request) {
                    $subQuery->where('customer_name', 'like', '%' . $request->search . '%');
                });
            }

            $complains = $query->orderBy('id', 'DESC')->get();

            return view($modules['folder_path'] . '.print', compact('complains', 'modules', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
