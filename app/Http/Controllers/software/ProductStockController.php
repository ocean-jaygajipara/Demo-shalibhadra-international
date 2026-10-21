<?php

namespace App\Http\Controllers\software;

use App\Exports\ProductStockExport;
use App\Exports\ProductStockSampleExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Imports\ProductStockImport;
use App\Models\Company;
use App\Models\MasterWarehouse;
use App\Models\ProductStockHistory;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\DataTables;
use App\Models\ProductVariantPrice;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;



class ProductStockController extends Controller
{
    public $modules = [];
    public function __construct()
    {

        parent::__construct();

        $this->modules = [

            'title' => 'Product Stock',
            'folder_path' => 'software.modules.inventory.product-stock',
            'route' => 'product-stock',
            'table_name' => (new ProductStockHistory())->getTable(),
            'permisstion_prefix' => 'product-stock',
            'module_name' => 'Product Stock',
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
                (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "warehouse_name", 'name' => 'warehouse_name', 'td_label' => 'Warehouse Name', 'className' =>  ''],
                (object)['data' => "product_category", 'name' => 'product_category', 'td_label' => 'Product Category', 'className' =>  ''],
                (object)['data' => "product_name", 'name' => 'product_name', 'td_label' => 'Product Name', 'className' =>  ''],
                (object)['data' => "product_code", 'name' => 'product_code', 'td_label' => 'Product Code', 'className' =>  ''],
                (object)['data' => "available_product_stock", 'name' => 'available_product_stock', 'td_label' => 'Available Stock Qty', 'className' =>  ''],
                (object)['data' => "available_product_stock_kg", 'name' => 'available_product_stock_kg', 'td_label' => 'Available Stock Kg', 'className' =>  ''],
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

                if (!$request->has('filter_company') || empty($request->filter_company)) {
                    return datatables()->of(collect([]))->make(true); // Empty response
                }


                $latest = ProductStockHistory::select(
                        DB::raw('MAX(id) as id')
                    )
                    ->groupBy(
                        'company_id',
                        'warehouse_id',
                        'product_category_id',
                        'product_variant_price_id'
                    );

                $data = ProductStockHistory::with([
                        'company',
                        'product',
                        'product_category',
                        'warehouse',
                    ])
                    ->whereIn('id', $latest)
                    ->where(function ($query) use ($modules, $loginUserId) {
                        if (Auth::guard('team_person')->check() && $modules['company_id']) {
                            // If person sees only personal data
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

                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }
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
                $data = $data->orderBy('id', 'desc');



               return Datatables::of($data)
                ->addColumn('company_name', function ($row) {
                    return $row->company?->company_name ?? '-';
                })
                ->addColumn('warehouse_name', function ($row) {
                    return $row->warehouse?->name ?? '-';
                })
                ->addColumn('product_category', function ($row) {
                    return $row->product_category?->name ?? '-';
                })
                ->addColumn('product_name', function ($row) {
                    return $row->product?->product_name ?? '-';
                })
                ->addColumn('product_code', function ($row) {
                    return $row->product?->part_no ?? '-';
                })
                ->addColumn('available_product_stock_kg', function ($row) {
                    return number_format($row->total_kg, 3);
                })
                ->addColumn('available_product_stock', function ($row) {

                    $stock = (int)$row->total_quantity;
                    $min   = $row->product?->min_stock_qty;
                    $max   = $row->product?->max_stock_qty;

                    $color = null;
                    $tooltip = null;

                    if ($min !== null && $max !== null && $min != 0 && $max != 0) {

                        if ($stock < $min) {
                            $color = 'danger';
                            $tooltip = 'Stock is below the Minimum Stock level';
                        } elseif ($stock > $max) {
                            $color = 'success';
                            $tooltip = 'Stock exceeds the Maximum Stock level';
                        } else {
                            $color = 'warning';
                            $tooltip = 'Stock level is within the acceptable range';
                        }
                    }

                    if ($color) {
                        return '<span class="badge badge-center bg-' . $color . ' bg-glow w-80"
                                    data-bs-toggle="tooltip" title="' . $tooltip . '">'
                                    . $stock .
                            '</span>';
                    }

                    return $stock;
                })

                ->rawColumns(['available_product_stock'])
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

    public function show(string $id)
    {
        if ($id = "print") {
            return self::print();
        }
        dd($id);
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

        return Excel::download(new ProductStockExport($request->all(), $this->authenticateLoginUserDetails, $modules, $loginUserId), 'ProductStock-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?: null;
        $company_id = $modules['company_id'] = $this->authenticateLoginUserDetails?->company_id ?? null;
        $modules['parent_type_id'] = $this->authenticateLoginUserDetails?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;
        if (count(config('constants.permissions'))) {
            foreach (config('constants.permissions') as $value) {
                $modules[$value . '_permission'] = (!$modules['company_id'])
                    ? true
                    : Gate::check('hasPermission', [$value, $modules['module_name']]);
            }
        }
        if (!$modules['print_permission']) {
            abort(403, 'Unauthorized');
        }
        try {


            $latest = ProductStockHistory::select(
                    DB::raw('MAX(id) as id')
                )
                ->groupBy(
                    'company_id',
                    'warehouse_id',
                    'product_category_id',
                    'product_variant_price_id'
                );

            $data = ProductStockHistory::with([
                    'company',
                    'product',
                    'product_category',
                    'warehouse',
                ])
                ->whereIn('id', $latest)
                ->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check() && $modules['company_id']) {
                        // If person sees only personal data
                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });

            $filter_search = $request->filter_search;
            $data = $data->when($filter_search, function ($q) use ($filter_search) {
                $q->whereHas('product', function ($p) use ($filter_search) {
                    $p->where('product_name', 'like', "%{$filter_search}%")
                    ->orWhere('part_no', 'like', "%{$filter_search}%")
                    ->orWhere('hsn_code', 'like', "%{$filter_search}%");
                });
            });

            if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                $data = $data->withTrashed();
            }
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
            $data = $data->orderBy('id', 'desc');
            $ProductStock = $data->get();

            $filter_warehouse = $request->filter_warehouse ?? '-';

            return view($modules['folder_path'] . '.print', compact('ProductStock', 'company_id', 'modules','filter_warehouse'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function import()
    {
        $modules = $this->modules;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;

        View::share('modules', $modules);

        return view($modules['folder_path'] . '.import');
    }

    public function import_store(Request $request)
    {
        $modules = $this->modules;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;

        $request->validate([
            'company_id' => 'required|exists:' . (new Company)->getTable() . ',id',
            'warehouse_id' => 'required|exists:' . (new MasterWarehouse)->getTable() . ',id',
            'import_file' => 'required|file|mimes:xlsx,csv',
        ]);

        $companyId = $request->company_id;
        $warehouseId = $request->warehouse_id;

        Excel::import(new ProductStockImport($companyId, $warehouseId), $request->file('import_file'));

        return back()->with('success', 'Product stock imported successfully!');
    }


    public function sampleDownload(Request $request)
    {
        $companyId = $request->query('company_id');
        $warehouseId = $request->query('warehouse_id');

        // Return the Excel download response
        return Excel::download(new ProductStockSampleExport($companyId, $warehouseId), 'product_stock_sample.xlsx');
    }
}
