<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductBOM;
use App\Http\Requests\ProductBOMRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductBOMController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Product BOM',
            'folder_path' => 'software.modules.product.product-bom',
            'route' => 'product-bom',
            'table_name' => (new ProductBOM())->getTable(),
            'permisstion_prefix' => 'product-bom',
            'module_name' => 'Product BOM',
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
                //$modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
                $modules[$value . '_permission'] = true;
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

            if ($request->ajax()) {

                $columns = [
                    (object)['data' => 'DT_RowIndex','name' => 'DT_RowIndex','td_label' => 'Sr No.','orderable' => false,'searchable' => false],
                    (object)['data' => "product_category", 'name' => 'product_category', 'td_label' => 'Product Category', 'className' =>  ''],
                    (object)['data' => "product_name", 'name' => 'product_name', 'td_label' => 'Product Name', 'className' =>  ''],
                    (object)['data' => "last_process", 'name' => 'last_process', 'td_label' => 'Last Process', 'className' =>  ''],
                    (object)['data' => "required_material_qty", 'name' => 'required_material_qty', 'td_label' => 'Required Material Weight (Gram)', 'className' =>  ''],
                    (object)['data' => "for_product_qty", 'name' => 'for_product_qty', 'td_label' => 'For Product Qty', 'className' =>  ''],
                    (object)['data' => "use_in_operation", 'name' => 'use_in_operation', 'td_label' => 'Use In Operation', 'className' =>  ''],
                    (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],
                ];

                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }

                $data = ProductBOM::with(['product_category', 'product_bom_name'])->select('*')
                    ->where(function ($query) use ($modules, $loginUserId, $request) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);
                            if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                        if ($request->has('product_id') && !empty($request->product_id)) {
                            $query->where('product_id', $request->product_id);
                        }
                    })
                    ->orderBy('id', 'desc');

                if ($modules['restore_permission'] ?? false) {
                    $data = $data->withTrashed();
                }

                $data = $data->get();

                $dataTransformed = $data->map(function ($row, $index) use ($modules) {
                        $action_btn = '';
                        if (!$row?->deleted_at) {
                            if ($modules['update_permission']) {
                                $action_btn .= '<a href="javascript:;" class="edit_product_bom btn btn-light btn-icon mx-1" data-id="'.$row->id. '"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $action_btn .= '<a href="javascript:void(0)" data-fun="loadBomTabData();" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }
                        } else {
                            $action_btn .= '<a href="javascript:void(0)" data-fun="loadBomTabData();" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                        }
                        if ($action_btn == '') {
                            $action_btn = '-';
                        }

                    return [
                        'DT_RowIndex' => $index + 1,
                        'product_category' => $row->product_category->name,
                        'product_name' => $row->product_bom_name->product_name,
                        'last_process' => $row->last_process,
                        'required_material_qty' => $row->required_material_qty,
                        'for_product_qty' => $row->for_product_qty,
                        'use_in_operation' => $row->use_in_operation,
                        'action' => $action_btn,
                    ];
                });

                return response()->json([
                    'columns' => $columns,
                    'data' => $dataTransformed,
                ]);
            }

            if (!$modules['view_permission']) {
                abort(403, 'Unauthorized');
            }

        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 500);
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
    public function store(ProductBOMRequest $request)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                //$modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
                $modules[$value . '_permission'] = true;
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
            ProductBOM::create($validated);

            return $this->sendResponse([], 'Product BOM created successfully');
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), [], [], 500);
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
                //$modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
                $modules[$value . '_permission'] = true;
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {

            $edit = ProductBOM::findOrFail($id);
            return $this->sendResponse($edit, 'Product BOM fetched successfully.');
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), [], [], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductBOMRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                //$modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
                $modules[$value . '_permission'] = true;
            }
        }
        if (!$modules['update_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();

        try {
            $validated['updated_by'] = $loginUserId;
            // return $validated;
            $updateData = ProductBOM::findOrFail($id);
            if ($updateData) {
                unset($validated['id']);
                $updateData->update($validated);

                return $this->sendResponse($updateData, ' Product BOM update successfully.');
            }
            return $this->sendError('something went wrong please try again later', [], [], 500);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], [], 500);
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
                //$modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
                $modules[$value . '_permission'] = true;
            }
        }
        if (!$modules['delete_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        $dataDelete = ProductBOM::findOrFail($id);
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
            return $this->sendError($e->getMessage(), [], [], 500);
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
                //$modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
                $modules[$value . '_permission'] = true;
            }
        }
        if (!$modules['restore_permission']) {
            if (isset($request) && $request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        try {

            $productBOM = ProductBOM::withTrashed()->findOrFail($id);
            $productBOM->restore();

            return $this->sendResponse([], $modules['title'] . ' restored successfully!');

        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


}
