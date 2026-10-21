<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use App\Models\Redemption;
use App\Models\RedemptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use App\Helpers\Helper;
use App\Http\Controllers\CommonController;
use App\Http\Requests\QuickQrGeneratorRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\qrgenerator;
use App\Models\SalesStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class QuickQRGeneratorController extends Controller
{

    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Generate QR Code',
            'folder_path' => 'software.modules.quickgenerator',
            'route' => 'quick-qr-generator',
            'table_name' => (new Redemption())->getTable(),
            'module_name' => 'Quick QR Generator',
            'permisstion_prefix' => 'QuickQR Generator',
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
        // dd($modules);
        if (!$modules['view_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        View::share('modules', $modules);

        try {

            $columns = [
                (object)['data' => "DT_RowIndex", 'name' => 'DT_RowIndex', 'td_label' => 'Sr No', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "created_at", 'name' => 'created_at', 'td_label' => 'Date'],
                (object)['data' => "label", 'name' => 'label', 'td_label' => 'Lable '],
                (object)['data' => "quantity", 'name' => 'quantity', 'td_label' => 'Quantity', 'className' =>  ''],
                (object)['data' => "collect_points", 'name' => 'collect_points', 'td_label' => 'Point Value', 'className' =>  ''],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' =>  'w-5 text-start'],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],
            ];
            if ($modules['company_id']) {
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
                $data = Redemption::select('*')
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });
                $data = $data->orderBy('id', 'DESC');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
                $data = $data->with(['company', 'redemption_items']);


                $returnData = Datatables::of($data)
                    ->addIndexColumn()
                    // ->filter(function ($query) use ($request) {

                    // if ($request->has('filter_company') && $request->filter_company) {
                    //     $query->where('company_id', $request->filter_company);
                    // }
                    // if ($request->has('filter_document_type') && $request->filter_document_type) {
                    //     $query->where('document_type_id', $request->filter_document_type);
                    // }

                    // if ($request->has('search')) {
                    //     $query->where('name', 'like', "%" . $request->search . "%");
                    // }
                    //     if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
                    //         $query->where('status', $request->status);
                    //     }
                    // })
                    ->editColumn('created_at', function ($record) {
                        $response = Helper::convert_date($record?->created_at, 'Y-m-d H:i:s', "d-m-Y");
                        // $response = $record?->created_at;
                        return $response;
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

                        $canDelete = true;

                        // Check if any redemption item is scanned
                        foreach ($row->redemption_items ?? [] as $item) {
                            if (!is_null($item->scanned_user_id) || !is_null($item->scanned_date)) {
                                $canDelete = false;
                                break;
                            }
                        }

                        if (!$row?->deleted_at) {
                            if ($row->status == "active") {
                                $btn .= '<a href="' . route($modules["route"] . ".show", [$row["id"]]) . '" class="btn btn-info btn-icon mx-1"><i class="fa-solid fa-eye"></i></a>';
                            }
                            // if ($modules['update_permission']) {
                            //     $btn .= '<a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            //     }

                            if ($modules['delete_permission'] && $canDelete) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->rawColumns(['status', 'action'])
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
    public function store(QuickQrGeneratorRequest $request)
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

            $companyName = '';

            if (!empty($validated['company_id'])) {
                $company = Company::find($validated['company_id']);
                $companyName = ($company && $company->company_name) ? strtoupper(substr($company->company_name, 0, 2)) : 'CO';
            }

            $autoIncrementId = Helper::getTableWiseGetAutoIncrementId((new RedemptionItem())->getTable());

            $validated['created_by'] = $loginUserId;

            $redemption = Redemption::create($validated);

            DB::commit();
            if ($redemption) {
                $redemption = Redemption::find($redemption?->id);
                for ($i = 0; $i < (int)$redemption?->quantity; $i++) {
                    $redemptionItemInput = [];
                    $redemptionItemInput['company_id'] = $validated['company_id'];
                    $redemptionItemInput['redemption_id'] = $redemption?->id;
                    // $redemptionItemInput['redemption_code_label'] = $validated['label'] . Helper::getTableWiseGetAutoIncrementId((new RedemptionItem())->getTable()) . "-" . sprintf("%04d", rand(0, 9999)) . Carbon::now()->format('YmdHis');
                    $redemptionItemInput['redemption_code_label'] =
                        $validated['label'] .
                        $autoIncrementId . '-' .
                        Carbon::now()->format('YH') .
                        $companyName .
                        Carbon::now()->format('im') .
                        sprintf("%04d", rand(0, 9999)) .
                        Carbon::now()->format('ds');
                    $redemptionItemInput['collect_points'] = $redemption?->collect_points;
                    $redemptionItemInput['status'] = $redemption?->status;
                    $redemptionItemInput['created_by'] = $loginUserId;
                    RedemptionItem::create($redemptionItemInput);
                }
            }

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' created successfully.');
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

            $show = Redemption::with(['redemption_items'])->findOrFail($id);
            $company_slug = $show?->comapny_id;
            if ($show?->company) {
                $company_slug = Helper::make_slug($show?->company?->company_name . ' ' . $show?->comapny_id);
            }
            View::share("company_slug", $company_slug);

            return view($modules['folder_path'] . '.show', compact('show'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
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

            $edit = Redemption::findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
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

        $dataDelete = Redemption::with('items')->findOrFail($id);

        $isAjax = $request->ajax();

        try {
            if ($dataDelete) {

                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                // Update and soft delete all related items
                foreach ($dataDelete->items as $item) {
                    $item->update(['deleted_by' => $loginUserId]);
                    $item->delete();
                }

                // Soft delete redemption
                $dataDelete->delete();

                if ($isAjax) {
                    return $this->sendResponse([], $modules['title'] . ' delete successfully');
                }
                return true;
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

            $redemption = Redemption::withTrashed()->with(['items' => function ($query) {
                $query->withTrashed();
            }])->findOrFail($id);

            // Restore redemption
            $redemption->restore();

            // Restore related items
            foreach ($redemption->items as $item) {
                $item->restore();
            }

            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function redemption_items_destroy(Request $request, string $id)
    {
        try {
            $dataDelete = RedemptionItem::findOrFail($id);
            $dataDelete->delete();

            return response()->json([
                'success' => true,
                'message' => 'Qr Code Deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
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
            $state = Redemption::withTrashed()->findOrFail($request?->id);
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

    public function redemptionItemStatuUpdate(Request $request)
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
            'id' => 'required|exists:' . ((new RedemptionItem())->getTable()) . ',id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return $this->sendError('Validation Error.', $validator->errors()->first(), $validator->errors(), 200);
            }
            return Redirect::back()->withErrors($validator->errors());
        }

        try {
            $editStatus = RedemptionItem::findOrFail($request?->id);
            if ($editStatus) {
                $editStatus->status = $request?->status;
                $editStatus->save();

                if ($isAjax) {
                    return $this->sendResponse([], "Status Update successfully.");
                }
                return Redirect::back()->with('success', 'Status Update successfully.');
            }
            return Redirect::back()->withErrors('something went wrong please try again later');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage())->withInput();
        }
    }

    public function guest_scanner(Request $request, $company_slug, $redemption_code_label)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;

        try {
            View::share('modules', $modules);
            View::share('company_slug', $company_slug);

            if ($request->_token) {

                DB::beginTransaction();
                try {
                    $validator = Validator::make($request->all(), [
                        'company_id' => [
                            'required',
                            Rule::exists((new Company())->getTable(), 'id')->where('status', 'active')
                        ],
                        'redemption_code_label'  => [
                            'required',
                            Rule::exists((new RedemptionItem())->getTable(), 'redemption_code_label')->where('status', 'active')
                        ],
                        'guest_name'  => ['required', 'string', 'max:255'],
                        'guest_mobile'  => ['required', 'string', 'max:255'],
                        'guest_email'  => ['required', 'email '],
                    ]);

                    if ($validator->fails()) {
                        return $this->sendError($validator->messages()->first(), $validator->messages(), [], 401);
                    }
                    //

                    $customerType = CustomerType::updateOrCreate([
                        'company_id' => $request?->company_id,
                        'name' => 'redemption',
                    ], [
                        'company_id' => $request?->company_id,
                        'name' => 'redemption',
                        'status' => "active"
                    ]);

                    $checkCustomer = Customer::where('company_id', $request?->company_id)->where('mobile_number', $request?->guest_mobile)->first();
                    if (!$checkCustomer) {
                        $client_code = null;
                        $newRequest = new Request();
                        $newRequest['company_id'] = $request?->company_id;
                        $newRequest['company_id'] = (isset($newRequest['company_id']) && !empty($newRequest['company_id'])) ? $newRequest['company_id'] : $request?->company_id;
                        // $newRequest['directReturn'] = true;

                        $generate_client_code = (new CommonController(new Request()))->generate_client_code($newRequest);
                        // dd("L-643", $newRequest, $generate_client_code);

                        if ($generate_client_code->getStatusCode() == 200) {
                            $dataResponse = $generate_client_code?->getData();
                            if ($dataResponse?->data) {
                                $client_code = $dataResponse?->data;
                            }
                        }

                        $checkCustomer = Customer::create([
                            'company_id' => $request?->company_id,
                            'customer_type_id' => $customerType?->id,
                            'client_code' => $client_code,
                            'customer_name' => $request?->guest_name,
                            'contact_person_name' => $request?->guest_name,
                            'mobile_number' => $request?->guest_mobile,
                            'email' => $request?->guest_email ?? null,
                            'platform' => 'web',
                        ]);
                    }
                    $redemptionItem = RedemptionItem::where('company_id', $request?->company_id)->where('redemption_code_label', $request?->redemption_code_label)->whereNull('scanned_date')->first();

                    /** Data not fund return respons */
                    if (!$redemptionItem) {
                        return Redirect::back()->withErrors($request?->redemption_code_label . " before redemption previously.");
                    }

                    $redemptionItem->scanned_user_id = $checkCustomer?->id;
                    $redemptionItem->scanned_date = Helper::convert_date("", "", "Y-m-d H:i:s");
                //    dd( $redemptionItem->scanned_date = Helper::convert_date("", "", "Y-m-d H:i:s"));
                    $redemptionItem->customer_id = $checkCustomer?->id;
                    $redemptionItem->save();

                    Session::flash('success', "Scanned QR Successfully Submited.");
                    // dd("L-644", $request->all(), $validator, $checkCustomer?->toArray(), $redemptionItem?->toArray());

                    DB::commit();
                    return Redirect::route('geast.thankyou');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return $e->getMessage();
                    return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
                }
            }
            $show = RedemptionItem::where('redemption_code_label', $redemption_code_label)->whereNull('scanned_date')->first();
            // return $modules['folder_path'];
            return view($modules['folder_path'] . '.scanner', compact('show'));
        } catch (\Exception $e) {
            return $e->getMessage();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
