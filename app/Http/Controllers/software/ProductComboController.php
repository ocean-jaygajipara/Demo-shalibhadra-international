<?php

namespace App\Http\Controllers\software;

use App\Exports\ProductComboExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductComboRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCombo;
use App\Models\ProductComboItems;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\Helper;

class ProductComboController extends Controller
{
    public $modules = [];

    public function __construct()
    {

        parent::__construct();

        $this->modules = [
            'title' => 'Product Combo',
            'folder_path' => 'software.modules.product.product-combo',
            'route' => 'product-combo',
            'table_name' => (new ProductCombo())->getTable(),
            'permisstion_prefix' => 'products',
            'module_name' => 'Product Combo',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];

        if (count(config('constants.order_type'))) {
            $this->modules['order_type'] = config('constants.order_type');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Load permissions
        foreach (config('constants.permissions') as $permission) {
            $modules[$permission . '_permission'] = !$modules['company_id']
                ? true
                : Gate::check('hasPermission', [$permission, $modules['module_name']]);
        }

        if (!$modules['view_permission']) {
            return $request->ajax()
                ? $this->sendError('Unauthorized', [], [], 403)
                : abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);

        try {
            // Define table columns
            $columns = [
                (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
                (object)['data' => "company.company_name", 'name' => 'company.company_name', 'td_label' => 'Company Name'],
                (object)['data' => "product_combo_name", 'name' => 'product_combo_name', 'td_label' => 'Product Combo Name'],
                (object)['data' => "product_category_name", 'name' => 'product_category_name', 'td_label' => 'Product Category Name'],
                (object)['data' => "display_unit.name", 'name' => 'display_unit.name', 'td_label' => 'Display Unit'],
                // (object)['data' => "price", 'name' => 'price', 'td_label' => 'Price'],
                (object)['data' => "product_variant_label", 'name' => 'product_variant_label', 'td_label' => 'Sub products', 'orderable' => false, 'searchable' => false],
                (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'className' => 'w-5 text-start'],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'className' => 'w-10 text-start', 'orderable' => false, 'searchable' => false],
            ];

            // Hide company name column if company is already filtered
            if ($modules['company_id']) {
                $columns = array_values(array_filter($columns, fn($col) => $col->name !== 'company.company_name'));
            }

            View::share("columns", $columns);

            // Handle AJAX for DataTable
            if ($request->ajax()) {
                    $data = ProductCombo::with([
                        'company',
                        'displayUnit',
                        'product',
                        'product_category'
                    ])
                    ->when(Auth::guard('team_person')->check() && $modules['company_id'], function ($query) use ($modules, $loginUserId) {
                        $query->where('company_id', $modules['company_id']);
                        if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                            $query->where('created_by', $loginUserId);
                        }
                    })
                    ->when($modules['restore_permission'], fn($q) => $q->withTrashed())
                    ->orderByDesc('id');

                return DataTables::of($data)
                    ->addIndexColumn()
                    ->filter(function ($query) use ($request) {
                        if ($request->filled('filter_company')) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if (!empty($request->product_as_variant_price_id)) {
                            $query->whereHas('combo_items', function ($q) use ($request) {
                                $q->where('product_variant_price_id', $request->product_as_variant_price_id);
                            });
                        }

                        if ($request->filled('display_unit_id')) {
                            $query->whereHas('displayUnit', function ($q) use ($request) {
                                $q->where('id', $request->display_unit_id);
                            });
                        }

                        if ($request->filled('status') && $request->status !== 'all') {
                            $query->where('status', $request->status);
                        }

                        if ($request->filled('search')) {
                            $search = $request->search;
                            $query->where(function ($subQuery) use ($search) {
                                $subQuery->where('product_combo_name', 'like', "%{$search}%")
                                    ->orWhere('price', 'like', "%{$search}%");
                            });
                        }
                    })


                    ->editColumn('status', function ($row) use ($modules) {
                        $btn = '';

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
                    ->addColumn('product_category_name', function ($row) {
                        return $row?->product_category?->name;
                    })
                    ->addColumn('product_variant_label', function ($row) {
                        $product_variant_label = [];
                        foreach ($row->combo_items as $combo_item) {
                            $tempName =
                                $tempName = $combo_item?->product?->part_no ?? '';
                            $tempName .= ' - ' . $combo_item?->product?->product_name ?? '';
                            $product_variant_label[] = $tempName;
                        }
                        return implode(', <br> ', $product_variant_label);
                        return $product_variant_label;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row->deleted_at) {
                            if ($modules['update_permission']) {
                                $btn .= '<a href="' . route($modules["route"] . ".edit", [$row->id]) . '" class="btn btn-light btn-icon mx-1"><i class="fa-solid fa-pen-to-square"></i></a>';
                            }
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row->id]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></a>';
                            }
                        } else {
                            $btn .= '<a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row->id]) . '" class="btn btn-light mx-1 record-restore"><i class="ti ti-history"></i> Restore</a>';
                        }
                        return $btn ?: '-';
                    })
                    ->addColumn('company.company_name', fn($row) => $row->company->company_name ?? '-')
                    ->addColumn('display_unit.name', fn($row) => $row->displayUnit->name ?? '-')
                    ->rawColumns(['product_variant_label', 'status', 'action'])
                    ->make(true);
            }

            return view($modules['folder_path'] . '.index');
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['error' => $e->getMessage()], 500)
                : Redirect::route('software.dashboard')->withErrors($e->getMessage());
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
    public function store(ProductComboRequest $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?: null;
        $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;

        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id']) ? true : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['add_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
         //dd("L-288", $request->all());
        $validated = $request->validated();

        DB::beginTransaction();
        try {

            $validated['created_by'] = $loginUserId;
            $productCombo = ProductCombo::create($validated);

            // Handle dynamic fields (combo items)
            if ($productCombo && $productCombo?->id && isset($request?->dynamic_fields) && is_array($request?->dynamic_fields)) {
                // dd("L-291", $request?->dynamic_fields);
                foreach ($request?->dynamic_fields as $dynamicField) {
                    $dynamicField = (object)$dynamicField;
                    // dd("L-293", $request?->dynamic_fields, $dynamicField, $dynamicField?->product_variant_price_id, $dynamicField->product_variant_label);
                    if ($dynamicField->product_variant_label) {
                        ProductComboItems::updateOrCreate([
                            'product_combo_id' => $productCombo->id,
                            'product_variant_price_id' => $dynamicField->product_variant_price_id,
                            'price' => $dynamicField->display_price,
                        ], [
                            'quantity' => $dynamicField->quantity,
                            'order_type' => $dynamicField->order_type,
                        ]);
                    }
                }
            }
            DB::commit();
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
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

            $edit = ProductCombo::with(['combo_items','combo_items.product'])->findOrFail($id);
            View::share('edit', $edit);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function update(ProductComboRequest $request, string $id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['company_id'] = $modules['authLoginUserDetail']->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']->id ?? null;

        // Check permission
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $key => $value) {
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }

        if (!$modules['update_permission']) {
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }

        View::share('modules', $modules);
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $validated['updated_by'] = $loginUserId;
            $productCombo = ProductCombo::findOrFail($id);

            unset($validated['id']);
            $productCombo->update($validated);

            $existingItemIds = [];

            // Handle dynamic fields (combo items)
            if ($productCombo && $productCombo?->id && isset($request?->dynamic_fields) && is_array($request?->dynamic_fields)) {
                // dd("L-423", $request?->dynamic_fields);
                foreach ($request?->dynamic_fields as $dynamicField) {
                    $dynamicField = (object)$dynamicField;
                    // dd("L-427", $request?->dynamic_fields, $dynamicField, $dynamicField?->product_variant_price_id, $dynamicField->product_variant_label);
                    if ($dynamicField->product_variant_label) {
                        $productComboItem  = ProductComboItems::updateOrCreate([
                            'product_combo_id' => $productCombo->id,
                            'product_variant_price_id' => $dynamicField->product_variant_price_id,
                            'price' => $dynamicField->display_price,
                        ], [
                            'quantity' => $dynamicField->quantity,
                            'order_type' => $dynamicField->order_type,
                        ]);
                        $existingItemIds[] = $productComboItem?->id;
                        // dd("L-437", $productComboItem->toArray(), $dynamicField, $existingItemIds);
                    }
                }
                // return $existingItemIds;

                // Delete items that were removed
                ProductComboItems::where('product_combo_id', $productCombo?->id)
                    ->whereNotIn('id', $existingItemIds)
                    ->delete();
            }

            DB::commit();

            return Redirect::route($modules['route'] . '.index')
                ->withSuccess($modules['title'] . ' updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route($modules['route'] . '.index')
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

        $dataDelete = ProductCombo::findOrFail($id);
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

            $country = ProductCombo::withTrashed()->findOrFail($id);
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
            $country = ProductCombo::withTrashed()->findOrFail($request?->id);
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

        return Excel::download(new ProductComboExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'ProductCombo-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }



    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        // Permissions
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
            $query =  ProductCombo::with([
                    'company',
                    'displayUnit',
                    'product',
                    'product_category'
                ])->orderBy('id', 'DESC')
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        $query->where('company_id', $modules['company_id']);

                        if (!empty($modules['personal_data_permission']) && empty($modules['all_data_permission'])) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });

            // Company filter
            if ($request->filled('company')) {
                $query->where('company_id', $request->company);
            }

            // Status filter
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Product (HSN Code + Variant) filter
            if (!empty($request->product_as_variant_price_id)) {
                $query->whereHas('combo_items', function ($q) use ($request) {
                    $q->where('product_variant_price_id', $request->product_as_variant_price_id);
                });
            }

            // Display Unit filter
            if ($request->filled('display_unit_id')) {
                $query->whereHas('displayUnit', function ($q) use ($request) {
                    $q->where('id', $request->display_unit_id);
                });
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('product_combo_name', 'like', "%{$search}%")
                        ->orWhere('price', 'like', "%{$search}%");
                });
            }

            $productcombo = $query->get();

            return view($modules['folder_path'] . '.print', compact('productcombo', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
