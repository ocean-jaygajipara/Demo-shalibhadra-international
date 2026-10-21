<?php

namespace App\Http\Controllers\software;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductCustomerItem;
use App\Http\Requests\ProductCustomerItemRequest;
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

class ProductCustomerItemController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Product Customer Item',
            'folder_path' => 'software.modules.product.product-customer-item',
            'route' => 'product-customer-item',
            'table_name' => (new ProductCustomerItem())->getTable(),
            'permisstion_prefix' => 'product-customer-item',
            'module_name' => 'Product Customer Item',
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
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            if ($request->ajax()) {
                $columns = [
                    (object)['data' => 'DT_RowIndex','name' => 'DT_RowIndex','td_label' => 'Sr No.','orderable' => false,'searchable' => false],
                    (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  ''],
                    (object)['data' => "customer_code", 'name' => 'customer_code', 'td_label' => 'Customer Code', 'className' =>  ''],
                    (object)['data' => "customer_item_code", 'name' => 'customer_item_code', 'td_label' => 'Customer Item Code', 'className' =>  ''],
                    (object)['data' => "customer_item_description", 'name' => 'customer_item_description', 'td_label' => 'Customer Item Description', 'className' =>  ''],
                    (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],
                ];

                $data = ProductCustomerItem::select('*')
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
                            $action_btn .= '<a href="javascript:;" class="edit_product_customer_item btn btn-light btn-icon mx-1" data-id="' . $row->id . '"><i class="fa-solid fa-pen-to-square"></i></a>';
                        }
                        if ($modules['delete_permission']) {
                            $action_btn .= '<a href="javascript:void(0)" data-fun="loadCustomerItemDescriptionTabData();" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                        }
                    } else {
                        $action_btn .= '<a href="javascript:void(0)" data-fun="loadCustomerItemDescriptionTabData();" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="btn btn-light mx-1 record-restore" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Restore Data"><i class="ti ti-history"></i> Restore</a>';
                    }

                    if ($action_btn == '') {
                        $action_btn = '-';
                    }

                    return [
                        'DT_RowIndex' => $index + 1,
                        'customer_name' => $row->customer_name,
                        'customer_code' => $row->customer_code,
                        'customer_item_code' => $row->customer_item_code,
                        'customer_item_description' => $row->customer_item_description,
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
    public function store(ProductCustomerItemRequest $request)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
               // $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
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
            ProductCustomerItem::create($validated);

            return $this->sendResponse([], 'Product customer item created successfully');
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

            $edit = ProductCustomerItem::findOrFail($id);
            return $this->sendResponse($edit, 'Product customer item fetched successfully.');
        } catch (\Exception $e) {
           return $this->sendError($e->getMessage(), [], [], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductCustomerItemRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
               // $modules[$value . '_permission'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
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
            $updateData = ProductCustomerItem::findOrFail($id);
            if ($updateData) {
                unset($validated['id']);
                $updateData->update($validated);

                return $this->sendResponse($updateData, ' Product customer item update successfully.');
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
        $dataDelete = ProductCustomerItem::findOrFail($id);
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

            $ProductCustomerItem = ProductCustomerItem::withTrashed()->findOrFail($id);
            $ProductCustomerItem->restore();

            return $this->sendResponse([], $modules['title'] . ' restored successfully!');

        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


}
