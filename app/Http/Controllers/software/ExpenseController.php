<?php

namespace App\Http\Controllers\software;

use App\Exports\ExpenseExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\TeamPerson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Expense',
            'folder_path' => 'software.modules.expense.expense',
            'route' => 'expense',
            'table_name' => (new Expense())->getTable(),
            'permisstion_prefix' => 'expense',
            'module_name' => 'Expense',
            'authLoginUserDetail' => null,
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
                // (object)['data' => "id", 'name' => 'id', 'td_label' => 'Sr No.'],
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],

                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "team_person.name", 'name' => 'team_person_id', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "expense_category.name", 'name' => 'expense_category_id', 'td_label' => 'Exp. Cat. Name', 'className' =>  ''],
                (object)['data' => "expense_sub_category.name", 'name' => 'expense_subcategory_id', 'td_label' => 'Exp. Sub Cat. Name', 'className' =>  ''],
                (object)['data' => "date", 'name' => 'date', 'td_label' => 'Exp. Date', 'className' =>  ''],
                (object)['data' => "req_amount", 'name' => 'req_amount', 'td_label' => 'REQ. AMT.', 'className' =>  ''],
                (object)['data' => "pass_amount", 'name' => 'pass_amount', 'td_label' => 'PASS AMT.', 'className' =>  ''],
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
                $data = Expense::select('*');

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
                $data = $data->with(['company', 'expense_category', 'expense_sub_category', 'team_person']);
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
                        if ($request->has('filter_expense_category') && $request->filter_expense_category) {
                            $query->where('expense_category_id', $request->filter_expense_category);
                        }
                        if ($request->has('filter_expense_subcategory') && $request->filter_expense_subcategory) {
                            $query->where('expense_subcategory_id', $request->filter_expense_subcategory);
                        }

                        if ($request->has('filter_team_person') && $request->filter_team_person) {
                            $query->where('team_person_id', $request->filter_team_person);
                        }
                        if ($request->filled('from_date') && $request->filled('to_date')) {
                            $query->whereBetween('date', [
                                $request->from_date,
                                $request->to_date
                            ]);
                        }
                        if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }
                    })

                    ->editColumn('req_amount', function ($row) {
                        $returnHtml = $row->req_amount;

                        if ($row->attachment && $row->attachment_url) {
                            $returnHtml .= '
                        <button type="button"
                            class="btn btn-sm btn-info file-preview m-2"
                            data-url="' . $row->attachment_url . '"
                            data-filename="' . $row->attachment . '">
                            File
                        </button>';
                        }

                        return $returnHtml;
                    })


                    // ->editColumn('status', function ($row) use ($modules) {
                    //     $btn = '';


                    //     $dropdown = "";
                    //     $dropdown .= '<ul class="dropdown-menu" style="">';

                    //     if ($row->status == "pass") {
                    //         $btn .= '<span class="badge text-bg-success d-inline-block text-center" style="min-width: 100px;">Approve</span>';
                    //     } else if ($row->status == "reject") {
                    //         $btn .= '<span class="badge text-bg-danger d-inline-block text-center" style="min-width: 100px;">Reject</span>';
                    //     } else {
                    //         if ($modules['approval_permission'] == true) {
                    //             $btn .= '<button type="button" class="btn btn-warning btn-sm  dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">Pending</button>';
                    //             $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-id="' . $row["id"] . '" data-update_status="pass">Pass</a></li>';
                    //             $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-id="' . $row["id"] . '" data-update_status="reject">Reject</a></li>';
                    //         } else {
                    //             $btn .= '<span class="badge text-bg-warning d-inline-block text-center" style="min-width: 100px;">Pending</span>';
                    //         }
                    //     }


                    //     $dropdown .= '</ul>';
                    //     $btn .= $dropdown;
                    //     return $btn;
                    // })
                    // ->editColumn('status', function ($row) use ($modules) {
                    //     $btn = '';
                    //     $dropdown = "";
                    //     $dropdown .= '<ul class="dropdown-menu" style="">';

                    //     if ($row->status == "pass") {
                    //         $btn .= '<span class="badge text-bg-success d-inline-block text-center" style="min-width: 100px;">Approve</span>';
                    //         if (!empty($row->pass_amount)) {
                    //             $btn .= '<div class="small text-success mt-1">Amount: ' . number_format($row->pass_amount, 2) . '</div>';
                    //         }
                    //     } else if ($row->status == "reject") {
                    //         $btn .= '<span class="badge text-bg-danger d-inline-block text-center" style="min-width: 100px;">Reject</span>';
                    //         if (!empty($row->reason)) {
                    //             $btn .= '<div class="text-danger small mt-1">Reason: ' . e($row->reason) . '</div>';
                    //         }
                    //     } else {
                    //         if ($modules['approval_permission'] == true) {
                    //             $btn .= '<button type="button" class="btn btn-warning btn-sm dropdown-toggle waves-effect waves-light" data-bs-toggle="dropdown" aria-expanded="false">Pending</button>';
                    //             $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-id="' . $row["id"] . '" data-request_amount="' . $row["req_amount"] . '" data-update_status="pass">Pass</a></li>';
                    //             $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-id="' . $row["id"] . '" data-update_status="reject">Reject</a></li>';
                    //         } else {
                    //             $btn .= '<span class="badge text-bg-warning d-inline-block text-center" style="min-width: 100px;">Pending</span>';
                    //         }
                    //     }

                    //     $dropdown .= '</ul>';
                    //     $btn .= $dropdown;
                    //     return $btn;
                    // })
                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';
                        $dropdown = '<ul class="dropdown-menu">';

                        if ($row->status == "pass") {
                            $btn .= '<span class="badge text-bg-success d-inline-block text-center" style="min-width: 100px;">Approve</span>';
                        } else if ($row->status == "reject") {
                            $btn .= '<span class="badge text-bg-danger d-inline-block text-center" style="min-width: 100px;">Reject</span>';
                            if (!empty($row->reason)) {
                                $btn .= '<div class="small mt-1"
                                style="white-space: normal; word-break: break-word; max-width:150px;">
                                <span class="text-danger">Reason :</span>
                                <span class="text-dark">' . e($row->reason) . '</span>
                            </div>';
                            }
                        } else {
                            if ($modules['approval_permission']) {
                                $btn .= '<button type="button" class="btn btn-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Pending</button>';
                                $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-id="' . $row->id . '" data-update_status="pass">Pass</a></li>';
                                $dropdown .= '<li><a href="javascript:void(0)" class="dropdown-item update-status" data-id="' . $row->id . '" data-update_status="reject">Reject</a></li>';
                            } else {
                                $btn .= '<span class="badge text-bg-warning d-inline-block text-center" style="min-width: 100px;">Pending</span>';
                            }
                        }

                        $dropdown .= '</ul>';
                        $btn .= $dropdown;

                        return $btn;
                    })


                    ->editColumn('pass_amount', fn($row) => $row->pass_amount ?? '-')
                    ->editColumn('date', fn($row) => $row->date ? Carbon::parse($row->date)->format('d-m-Y') : '-')
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';

                        // Show '-' for status 'pass' or 'reject'
                        if ($row->status === 'pass' || $row->status === 'reject') {
                            return '-';
                        }

                        // Show edit/delete/restore for status 'pending' or any other
                        if (!$row?->deleted_at) {
                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }

                        return $btn !== '' ? $btn : '-';
                    })

                    ->rawColumns(['req_amount', 'status', 'action'])
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


    public function store(ExpenseRequest $request)
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

            if ($request->hasFile('image')) {
                $teamPerson = TeamPerson::where('id', $validated['team_person_id'])->where('company_id', $validated['company_id'])->first();
                // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$teamPerson->id . ' ' . (string)$teamPerson?->name);

                $file = $request->file('image');

                $extenstion = $file->getClientOriginalExtension();

                $filename = $image_name . '.' . $extenstion;

                $sub_folder_path = Helper::fileUploadPath($teamPerson, Expense::$folderPath);
                $uploadedPath = public_path($sub_folder_path);
                // return $uploadedPath;
                if ($file->move($uploadedPath, $filename)) {
                    $uploadedImage = $sub_folder_path . $filename;
                    if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                        $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                        if ($webP) {
                            $uploadedImage = $sub_folder_path . $webP;
                            $extenstion = "webp";
                        }
                    }
                    // $filename = $uploadedImage;
                    // $image_path = $sub_folder_path . '/' . $filename;
                    $validated['attachment'] = $uploadedImage;
                }
            }

            // return $validated;
            Expense::create($validated);

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function show(string $id)
    {
        if ($id = "print") {
            return self::print();
        }
        dd($id);
    }


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

            $edit = Expense::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(ExpenseRequest $request, string $id)
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
        // dd($validated); // Dumping validated data

        try {
            $validated['updated_by'] = $loginUserId;
            // return $validated;
            $updateData = Expense::findOrFail($id);
            if ($updateData) {

                if ($request->hasFile('image')) {
                    $teamPerson = TeamPerson::where('id', $validated['team_person_id'])->where('company_id', $validated['company_id'])->first();
                    // return $image_name = Helper::make_slug('leave-application ' . (string)$loginUser?->id. ' '. (string)$loginUser?->name . ' ' . date('Ymd-His'));
                    $image_name = Helper::make_slug(date('Ymd-His') . ' ' . (string)$teamPerson->id . ' ' . (string)$teamPerson?->name);

                    $file = $request->file('image');

                    $extenstion = $file->getClientOriginalExtension();

                    $filename = $image_name . '.' . $extenstion;

                    $sub_folder_path = Helper::fileUploadPath($teamPerson, Expense::$folderPath);
                    $uploadedPath = public_path($sub_folder_path);
                    // return $uploadedPath;
                    if ($file->move($uploadedPath, $filename)) {
                        $uploadedImage = $sub_folder_path . $filename;
                        if (in_array($extenstion, ["jpeg", "png", "jpg"])) {
                            $webP = Helper::existingImageConverToWebp($extenstion, $uploadedPath, $filename, $image_name, 60, true);
                            if ($webP) {
                                $uploadedImage = $sub_folder_path . $webP;
                                $extenstion = "webp";
                            }
                        }
                        // $filename = $uploadedImage;
                        // $image_path = $sub_folder_path . '/' . $filename;
                        $validated['attachment'] = $uploadedImage;
                    }
                }
                // dd('L-426',$modules, $modules['parent_type'], $validated, $request->all(), $teamPerson);

                unset($validated['id']);
                $updateData->update($validated);

                return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' update successfully');
            }
            return Redirect::back()->withErrors('something went wrong please try again later')->withInput();
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
        $dataDelete = Expense::findOrFail($id);
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

            $country = Expense::withTrashed()->findOrFail($id);
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
            'update_status' => ['in:pass,reject'],
            'pass_amount' => [
                'nullable',
                'numeric',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value < 0) {
                        $fail('The Pass amount cannot be negative.');
                    }

                    $expense = Expense::find($request->id);
                    if ($expense && $value > $expense->req_amount) {
                        $fail('The Pass amount cannot be greater than the Request amount.');
                    }
                }
            ],

            'reason' => ['nullable', 'string'],
        ]);
        // dd($request->all(), $validator);
        if ($validator->fails() && $isAjax) {
            return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
        }


        try {
            $country = Expense::withTrashed()->findOrFail($request?->id);
            $country->status = $request->update_status;
            $country->pass_amount = $request->pass_amount;
            $country->reason = $request->reason;
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

        return Excel::download(new ExpenseExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'Expense-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
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

            $query = Expense::select('*')
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personalDataPermission'] && !$modules['allDataPermission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                })
                ->with(['company'])
                ->orderBy('id', 'DESC');
            if ($request->filled('filter_company')) {
                $query->where('company_id', $request->filter_company);
            }

            if ($request->filled('filter_expense_category')) {
                $query->where('expense_category_id', $request->filter_expense_category);
            }

            if ($request->filled('filter_expense_subcategory_id')) {
                $query->where('expense_subcategory_id', $request->filter_expense_subcategory_id);
            }

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereBetween('date', [
                    $request->from_date,
                    $request->to_date
                ]);
            }


            if ($request->filled('team_person_ids')) {
                $query->where('team_person', $request->team_person_ids);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            $expenses = $query->get();

            return view($modules['folder_path'] . '.print', compact('expenses', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
