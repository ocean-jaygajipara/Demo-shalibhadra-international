<?php

namespace App\Http\Controllers\software;

use App\Exports\ProductStockHistoryExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ProductStockHistory;
use App\Models\ProductVariantPrice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use App\Http\Requests\ProductStockHistoryRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\Redirect;



class ProductStockHistoryController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();

        $this->modules = [
            'title' => 'Product Stock History',
            'folder_path' => 'software.modules.inventory.product-stock-history',
            'route' => 'product-stock-history',
            'table_name' => (new ProductStockHistory())->getTable(),
            'permisstion_prefix' => 'product-stock-history',
            'module_name' => 'Add Product Stock',
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
                (object) ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-start'],
                (object) ['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' => ''],
                (object) ['data' => "warehouse_name", 'name' => 'warehouse_name', 'td_label' => 'Warehouse', 'className' => ''],
                (object) ['data' => "product_category_name", 'name' => 'product_category_name', 'td_label' => 'Product Category', 'className' => 'w-20 text-wrap'],
                (object) ['data' => "product_variant_price", 'name' => 'product_variant_price', 'td_label' => 'Product', 'className' => 'w-100 text-wrap'],
                (object) ['data' => "total_quantity", 'name' => 'total_quantity', 'td_label' => 'Quantity', 'className' => ''],
                (object) ['data' => "total_kg", 'name' => 'total_kg', 'td_label' => 'Kg', 'className' => ''],
                (object) ['data' => "credit", 'name' => 'credit', 'td_label' => 'Credit', 'className' => ''],
                (object) ['data' => "debit", 'name' => 'debit', 'td_label' => 'Debit', 'className' => ''],
                (object) ['data' => "date", 'name' => 'date', 'td_label' => 'Date', 'className' => ''],
                (object) ['data' => "remark", 'name' => 'remark', 'td_label' => 'Remark', 'className' => 'w-50 text-wrap'],
                (object) ['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-wrap'],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_name';
                });
                $columns = array_values($columns);
            }
            View::share("columns", $columns);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = ProductStockHistory::with(['company', 'warehouse', 'product_category', 'product'])
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            $query->where('company_id', $modules['company_id']);

                            if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                                $query->where('created_by', $loginUserId);
                            }
                        }
                    });

                $search = $request->search;
                $data = $data->when($search, function ($q) use ($search) {
                    $q->whereHas('product', function ($p) use ($search) {
                        $p->where('product_name', 'like', "%{$search}%")
                        ->orWhere('part_no', 'like', "%{$search}%")
                        ->orWhere('hsn_code', 'like', "%{$search}%");
                    });
                });

                $data = $data->when($request->filter_warehouse, function ($q) use ($request) {
                        $q->where('warehouse_id', $request->filter_warehouse);
                    });
                $data = $data->when($request->filter_company, function ($q) use ($request) {
                        $q->where('company_id', $request->filter_company);
                    });
                $data = $data->when($request->filter_product_category, function ($q) use ($request) {
                        $q->where('product_category_id', $request->filter_product_category);
                    });
                $data = $data->when($request->filter_product_variant_price_id, function ($q) use ($request) {
                        $q->where('product_variant_price_id', $request->filter_product_variant_price_id);
                    });

                if ($request->has('filter_date') && $request->filter_date) {
                    $filter_date = explode(" to ", $request->filter_date);
                    $fromDate = null;
                    $toDate = null;

                    if (count($filter_date) == 2) {
                        $fromDate = Helper::convert_date($filter_date[0], "d/m/Y", "Y-m-d");
                        $toDate = Helper::convert_date($filter_date[1], "d/m/Y", "Y-m-d");
                    }

                    if ($fromDate && $toDate) {
                        $data->whereDate('date', ">=", $fromDate);
                        $data->whereDate('date', "<=", $toDate);
                    } elseif ($fromDate) {
                        $data->whereDate('date', $fromDate);
                    }
                }

                $data = $data->orderBy('id', 'desc');
                $data = $data->withTrashed();

                return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn('product_variant_price', function ($row) {
                        if (isset($row->product) && !empty($row->product)) {
                            return $row?->product?->part_no . ' - ' . $row?->product?->product_name;
                        }
                        return '-';
                    })
                    ->editColumn('date', function ($row) {
                        return Helper::convert_date($row?->date, "Y-m-d", "d-m-Y");
                    })
                    ->addColumn('product_category_name', function ($row) {
                        return $row?->product_category?->name;
                    })
                    ->addColumn('company_name', function ($row) {
                        return $row?->company?->company_name;
                    })
                    ->addColumn('warehouse_name', function ($row) {
                        return $row?->warehouse?->name;
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $btn = '';
                        if (!$row?->deleted_at) {
                            if ($modules['delete_permission']) {
                                $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="btn btn-danger btn-icon deletebutton mx-1"><i class="fa-solid fa-trash"></i></i></a>';
                            }
                        } else {
                            $deleted_at = \Carbon\Carbon::parse($row->deleted_at)->format('d-m-Y');
                            $btn .= '<span> Deleted at : ' . $deleted_at . '</span>';
                        }
                        if ($btn == '') {
                            $btn = '-';
                        }
                        return $btn;
                    })
                    ->rawColumns(['action'])
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

    public function create(Request $request)
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
            if ($request->ajax()) {
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

    public function store(ProductStockHistoryRequest $request)
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
            $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $request->warehouse_id, $request->product_category_id, $request->product_variant_price_id);

            $product_total_kg = Helper::getProductStockTotalKG($request->company_id, $request->warehouse_id, $request->product_category_id, $request->product_variant_price_id);

            $validated['created_by'] = $loginUserId;

            if (!empty($validated['total_quantity'])) {
                if ($validated['total_quantity'] > 0) {
                    $validated['credit'] = (float) $validated['total_quantity'];
                } else {
                    $validated['debit'] = (float) $validated['total_quantity'];
                }
            }

            if (!empty($validated['total_kg'])) {
                if ($validated['total_kg'] > 0) {
                    $validated['credit'] = (float) $validated['total_kg'];
                } else {
                    $validated['debit'] = (float) $validated['total_kg'];
                }
            }
            $validated['total_quantity'] = (float) $product_total_quantity + (float) $validated['total_quantity'];
            $validated['total_kg'] = (float) $product_total_kg + (float) $validated['total_kg'];
            //dd($validated);
            ProductStockHistory::create($validated);

            if ($request->has('save_and_next') && $request->save_and_next == 1) {
                return $this->sendResponse([], 'Saved successfully. You can add the next entry.');
            }

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }


    public function show(Request $request, string $id)
    {
        if ($id == "print") {
            return $this->print($request);
        }
        dd($id);
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

        $dataDelete = ProductStockHistory::findOrFail($id);
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

    public function restore(Request $request, $id)
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
            if ($request->ajax()) {
                return $this->sendError('Unauthorized', [], [], 403);
            }
            abort(403, 'Unauthorized');
        }
        try {
            $addProductStock = ProductStockHistory::withTrashed()->findOrFail($id);
            $addProductStock->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function add($product_variant_price_id, $warehouse_id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
        if (!$modules['addPermission']) {
            abort(403, 'Unauthorized');
        }

        try {
            View::share('modules', $modules);
            $data = Product::findOrFail($product_variant_price_id);
            $data->company_id = $data->company_id;
            $data->product_category_id = $data->product_category_id;
            $data->warehouse_id = $warehouse_id;
            $data->product_variant_price_id = $product_variant_price_id;

            View::share('data', $data);
            return view($modules['folder_path'] . '.form');
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
        return Excel::download(
            new ProductStockHistoryExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId),
            'ProductStockHistory-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx'
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
            View::share('modules', $modules);

            $AddProductStock = ProductStockHistory::with(['company', 'warehouse', 'product_category', 'product'])
                ->where(function ($q) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
                        $q->where('company_id', $teamPersonCompanyId);

                        if ($modules['personal_data_permission'] && !$modules['all_data_permission']) {
                            $q->where('created_by', $loginUserId);
                        }
                    }
                });

            $filter_search = $request->filter_search;
            $AddProductStock = $AddProductStock->when($filter_search, function ($q) use ($filter_search) {
                $q->whereHas('product', function ($p) use ($filter_search) {
                    $p->where('product_name', 'like', "%{$filter_search}%")
                    ->orWhere('part_no', 'like', "%{$filter_search}%")
                    ->orWhere('hsn_code', 'like', "%{$filter_search}%");
                });
            });

            $AddProductStock = $AddProductStock->when($request->filter_warehouse, function ($q) use ($request) {
                    $q->where('warehouse_id', $request->filter_warehouse);
                });
            $AddProductStock = $AddProductStock->when($request->filter_company, function ($q) use ($request) {
                    $q->where('company_id', $request->filter_company);
                });
            $AddProductStock = $AddProductStock->when($request->filter_product_category, function ($q) use ($request) {
                    $q->where('product_category_id', $request->filter_product_category);
                });
            $AddProductStock = $AddProductStock->when($request->filter_product_variant_price_id, function ($q) use ($request) {
                    $q->where('product_variant_price_id', $request->filter_product_variant_price_id);
                });

            if ($request->has('filter_date') && $request->filter_date) {
                $filter_date = explode(" to ", $request->filter_date);
                $fromDate = null;
                $toDate = null;

                if (count($filter_date) == 2) {
                    $fromDate = Helper::convert_date($filter_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($filter_date[1], "d/m/Y", "Y-m-d");
                }

                if ($fromDate && $toDate) {
                    $AddProductStock->whereDate('date', ">=", $fromDate);
                    $AddProductStock->whereDate('date', "<=", $toDate);
                } elseif ($fromDate) {
                    $AddProductStock->whereDate('date', $fromDate);
                }
            }

            $AddProductStock = $AddProductStock->orderBy('id', 'DESC')->get();
            return view($modules['folder_path'] . '.print', compact('AddProductStock', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
