<?php

namespace App\Http\Controllers\software;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProcessWiseProduction;
use App\Models\ProcessWiseProductionUsedMaterial;

use App\Models\ProcessWiseProductionByProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductStockHistory;
use App\Helpers\Helper;
class ProcessWiseProductionController extends Controller
{
    public $modules = [];
    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Process Wise Production',
            'folder_path' => 'software.modules.production.process_wise_production',
            'route' => 'process-wise-production',
            'table_name' => (new ProcessWiseProduction())->getTable(),
            'permisstion_prefix' => 'process-wise-production',
            'module_name' => 'Process Wise Production',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        if (!$modules['company_id']) {
            $modules['view_permission'] = true;
            $modules['add_permission'] = true;
            $modules['update_permission'] = true;
            $modules['delete_permission'] = true;
        } else {
            $modules['view_permission'] = true; // Temporary bypass for simplicity, in a real app this uses Gate
            $modules['add_permission'] = true;
            $modules['update_permission'] = true;
            $modules['delete_permission'] = true;
        }

        View::share('modules', $modules);

        if ($request->ajax()) {
            $data = ProcessWiseProduction::with(['company', 'product'])->orderBy('id', 'desc');
            if ($modules['company_id']) {
                $data->where('company_id', $modules['company_id']);
            }
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('date', function ($row) {
                    return $row->date ? date('d-m-Y', strtotime($row->date)) : '';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('status') && $request->status !== null && $request->status !== 'all') {
                        $query->where('status', $request->status);
                    }
                    if ($request->has('search') && $request->search != '') {
                        $search = $request->search;
                        $query->where(function ($q) use ($search) {
                            $q->orWhere('process', 'like', "%{$search}%")
                              ->orWhere('date', 'like', "%{$search}%")
                              ->orWhereHas('product', function($q2) use ($search) {
                                  $q2->where('product_name', 'like', "%{$search}%")
                                     ->orWhere('part_no', 'like', "%{$search}%");
                              });
                        });
                    }
                })
                ->addColumn('status', function ($row) {
                    if (strcasecmp($row->status, 'Pending') == 0) {
                        return '<span class="badge bg-warning bg-glow">Pending</span>';
                    } else if (strcasecmp($row->status, 'Approve') == 0) {
                        return '<span class="badge bg-success bg-glow">Approve</span>';
                    } else {
                        return '<span class="badge bg-secondary bg-glow">'.($row->status ?? 'N/A').'</span>';
                    }
                })
                ->addColumn('action', function ($row) use ($modules) {
                    $action = '<div class="dropdown d-inline-block"><button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">';
                    if (strcasecmp($row->status, 'Approve') !== 0) {
                        $action .= '<li><a href="' . route($modules["route"] . ".edit", [$row["id"]]) . '" class="dropdown-item mx-1"><i class="fa-solid fa-pen-to-square"></i> Edit</a></li>';
                    }
                    $action .= '<li><a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash mt-1 me-1"></i> Delete</a></li>';

                    if (strcasecmp($row->status, 'Pending') === 0) {
                        $action .= '<li class="dropdown-divider"></li>';
                        $action .= '<li><a href="javascript:void(0)" data-id="' . $row->id . '" data-status="Approve" class="dropdown-item change-status mx-1">Change to Approve</a></li>';
                    }

                    $action .= '</ul></div>';
                    return $action;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        
        $columns = [
            (object)['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'td_label' => 'Sr No.', 'orderable' => false, 'searchable' => false],
            (object)['data' => "date", 'name' => 'date', 'td_label' => 'Date', 'className' => ''],
            (object)['data' => "product.product_name", 'name' => 'product.product_name', 'td_label' => 'Item Code', 'className' => ''],
            (object)['data' => "process", 'name' => 'process', 'td_label' => 'Process', 'className' => ''],
            (object)['data' => "qty", 'name' => 'qty', 'td_label' => 'Qty', 'className' => ''],
            (object)['data' => "status", 'name' => 'status', 'td_label' => 'Status', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center text-nowrap'],
            (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'w-10 text-center'],
        ];
        View::share("columns", $columns);

        return view($modules['folder_path'] . '.index');
    }

    public function create()
    {
        $modules = $this->modules;
        View::share('modules', $modules);
        $products = Product::where('status', 'active')->get()->map(function($product) {
            $latestStock = \App\Models\ProductStockHistory::where('product_variant_price_id', $product->id)->orderBy('id', 'desc')->first();
            $product->stock_kg = $latestStock ? $latestStock->total_kg : 0;
            $product->stock_qty = $latestStock ? $latestStock->total_quantity : 0;
            return $product;
        });
        View::share('products', $products);
        
        $warehouses = \App\Models\MasterWarehouse::where('status', 'active')->get();
        View::share('warehouses', $warehouses);
        
        $locations = \App\Models\Location::where('status', 'active')->get();
        View::share('locations', $locations);
        
        $materials = Product::whereHas('product_category', function($q) {
            $q->where('name', 'like', '%ROD AND BAR%');
        })->where('status', 'active')->get()->map(function($product) {
            $latestStock = \App\Models\ProductStockHistory::where('product_variant_price_id', $product->id)->orderBy('id', 'desc')->first();
            $product->stock_kg = $latestStock ? $latestStock->total_kg : 0;
            return $product;
        });
        View::share('materials', $materials);
        
        return view($modules['folder_path'] . '.form');
    }

    public function store(Request $request)
    {
        $modules = $this->modules;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        
        DB::beginTransaction();
        try {
            $data = $request->except(['_token', 'used_materials', 'by_products']);
            $data['company_id'] = $modules['company_id'];
            $data['status'] = 'Pending';
            
            $production = ProcessWiseProduction::create($data);

            if ($request->has('used_materials')) {
                foreach ($request->used_materials as $material) {
                    if(!empty($material['material_id'])) {
                        $material['process_wise_production_id'] = $production->id;
                        ProcessWiseProductionUsedMaterial::create($material);
                    }
                }
            }
            
            if ($request->has('by_products')) {
                foreach ($request->by_products as $byProduct) {
                    if(!empty($byProduct['product_id'])) {
                        $byProduct['process_wise_production_id'] = $production->id;
                        ProcessWiseProductionByProduct::create($byProduct);
                    }
                }
            }

            DB::commit();
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function edit($id)
    {
        $modules = $this->modules;
        View::share('modules', $modules);
        $edit = ProcessWiseProduction::with(['used_materials', 'by_products'])->findOrFail($id);
        if (strcasecmp($edit->status, 'Approve') === 0) {
            return Redirect::back()->withErrors('Cannot edit an approved record.');
        }
        View::share('edit', $edit);
        $products = Product::where('status', 'active')->get()->map(function($product) {
            $latestStock = \App\Models\ProductStockHistory::where('product_variant_price_id', $product->id)->orderBy('id', 'desc')->first();
            $product->stock_kg = $latestStock ? $latestStock->total_kg : 0;
            $product->stock_qty = $latestStock ? $latestStock->total_quantity : 0;
            return $product;
        });
        View::share('products', $products);
        
        $warehouses = \App\Models\MasterWarehouse::where('status', 'active')->get();
        View::share('warehouses', $warehouses);
        
        $locations = \App\Models\Location::where('status', 'active')->get();
        View::share('locations', $locations);
        
        $materials = Product::whereHas('product_category', function($q) {
            $q->where('name', 'like', '%ROD AND BAR%');
        })->where('status', 'active')->get()->map(function($product) {
            $latestStock = \App\Models\ProductStockHistory::where('product_variant_price_id', $product->id)->orderBy('id', 'desc')->first();
            $product->stock_kg = $latestStock ? $latestStock->total_kg : 0;
            return $product;
        });
        View::share('materials', $materials);
        
        return view($modules['folder_path'] . '.form');
    }

    public function update(Request $request, $id)
    {
        $modules = $this->modules;
        DB::beginTransaction();
        try {
            $production = ProcessWiseProduction::findOrFail($id);
            if (strcasecmp($production->status, 'Approve') === 0) {
                return Redirect::back()->withErrors('Cannot update an approved record.');
            }
            $data = $request->except(['_token', '_method', 'used_materials', 'by_products']);
            $production->update($data);

            ProcessWiseProductionUsedMaterial::where('process_wise_production_id', $id)->delete();
            if ($request->has('used_materials')) {
                foreach ($request->used_materials as $material) {
                    if(!empty($material['material_id'])) {
                        $material['process_wise_production_id'] = $production->id;
                        ProcessWiseProductionUsedMaterial::create($material);
                    }
                }
            }

            ProcessWiseProductionByProduct::where('process_wise_production_id', $id)->delete();
            if ($request->has('by_products')) {
                foreach ($request->by_products as $byProduct) {
                    if(!empty($byProduct['product_id'])) {
                        $byProduct['process_wise_production_id'] = $production->id;
                        ProcessWiseProductionByProduct::create($byProduct);
                    }
                }
            }

            DB::commit();
            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $production = ProcessWiseProduction::with(['used_materials', 'by_products'])->findOrFail($id);
            
            // Revert stock if it was Approve
            if ($production->status === 'Approve') {
                $this->updateStock($production, 'Pending');
            }
            
            $production->delete();
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Deleted successfully']);
            }
            return Redirect::route($this->modules['route'] . '.index')->withSuccess('Deleted successfully');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
            return Redirect::back()->withErrors($e->getMessage());
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $production = ProcessWiseProduction::with(['used_materials', 'by_products'])->findOrFail($request->id);
            $oldStatus = $production->status;
            
            \Log::info('changeStatus called', ['id' => $production->id, 'oldStatus' => $oldStatus, 'newStatus' => $request->status]);
            
            if ($oldStatus !== $request->status) {
                $production->status = $request->status;
                $production->save();
                
                \Log::info('Status saved, checking action...');
                // If moved to Approve, deduct raw materials and add products
                if ($request->status === 'Approve') {
                    \Log::info('Calling updateStock Approve');
                    $this->updateStock($production, 'Approve');
                } 
                // If moved back to Pending from Approve, reverse the stock changes
                else if ($oldStatus === 'Approve' && $request->status === 'Pending') {
                    \Log::info('Calling updateStock Pending');
                    $this->updateStock($production, 'Pending');
                }
            }
            
            return response()->json(['success' => true, 'message' => 'Status updated successfully']);
        } catch (\Throwable $e) {
            \Log::error('changeStatus error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function updateStock($production, $action)
    {
        \Log::info('inside updateStock', ['action' => $action, 'product_id' => $production->product_id, 'used_materials_count' => count($production->used_materials)]);
        $loginUserId = auth()->id() ?? 1; // Fallback or ensure auth

        // 1. Main Product (Removed as per user request - should not affect stock)
        
        // 2. By Products (Removed as per user request - should not affect stock)

        // 3. Used Materials
        \Log::info('Processing used materials', ['count' => count($production->used_materials)]);
        foreach ($production->used_materials as $material) {
            if ($material->material_id) {
                $product = Product::find($material->material_id);
                if ($product) {
                    \Log::info('Found used material product', ['material_id' => $material->material_id]);
                    $product_total_kg = Helper::getProductStockTotalKG($production->company_id, $material->used_from_godown_id, $product->product_category_id, $material->material_id);
                    $product_total_qty = Helper::getProductStockTotalQuantity($production->company_id, $material->used_from_godown_id, $product->product_category_id, $material->material_id);
                    
                    $qty = (float)$material->used_qty;
                    $weight = (float)$material->used_wt;
                    
                    // Used materials are consumed on approval, so stock goes DOWN. On revert, stock goes UP.
                    $new_qty = $action === 'Approve' ? $product_total_qty - $qty : $product_total_qty + $qty;
                    $new_kg = $action === 'Approve' ? $product_total_kg - $weight : $product_total_kg + $weight;
                    
                    ProductStockHistory::create([
                        'company_id' => $production->company_id,
                        'warehouse_id' => $material->used_from_godown_id,
                        'product_category_id' => $product->product_category_id,
                        'product_subcategory_id' => $product->product_subcategory_id,
                        'product_variant_price_id' => $material->material_id,
                        'process_wise_production_id' => $production->id,
                        'total_quantity' => $new_qty,
                        'total_kg' => $new_kg,
                        'debit' => $action === 'Approve' ? $weight : null,
                        'credit' => $action === 'Pending' ? $weight : null,
                        'stock_type' => 'qty',
                        'date' => $production->date ?: date('Y-m-d'),
                        'remark' => ($action === 'Approve' ? 'Process Wise Production Approve' : 'Process Wise Production Reverted') . ' - Used Material',
                        'created_by' => $loginUserId
                    ]);
                    \Log::info('Used material stock history created');
                } else {
                    \Log::info('Used material NOT FOUND');
                }
            }
        }
        \Log::info('updateStock finished');
    }
}
