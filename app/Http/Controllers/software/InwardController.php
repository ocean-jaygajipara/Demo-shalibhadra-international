<?php

namespace App\Http\Controllers\software;

use App\Exports\InwardExport;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Inward;
use App\Models\PurchaseOrder;
use App\Models\ProductStockHistory;
use App\Models\Product;
use App\Models\PurchaseOrderItems;
use App\Models\SalesStatus;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\InwardRequest;
use App\Models\InwardItems;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendInwardOrderJob;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InwardController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Inward',
            'folder_path' => 'software.modules.purchase_management.inwards',
            'route' => 'inwards',
            'table_name' => (new Inward())->getTable(),
            'permisstion_prefix' => 'inwards',
            'module_name' => 'Inward',
            'authLoginUserDetail' => null,
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null,
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

        try {
            View::share('modules', $modules);

            $columns = [
                (object)['data' => "company.company_name", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "inward_no", 'name' => 'inward_no', 'td_label' => 'Inward No'],
                (object)['data' => "po_no", 'name' => 'po_no', 'td_label' => 'PO No', 'className' =>  ''],
                (object)['data' => "inward_date", 'name' => 'inward_date', 'td_label' => 'Inward Date', 'className' =>  ''],
                (object)['data' => "vendor_name", 'name' => 'vendor_name', 'td_label' => 'Vendor Name', 'className' =>  ''],
                (object)['data' => "inward_qty", 'name' => 'inward_qty', 'td_label' => 'Inward Qty', 'className' =>  ''],
                (object)['data' => "warehouse_name", 'name' => 'warehouse_name', 'td_label' => 'Warehouse Name', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-center'],
            ];
            if ($modules['company_id']) {
                $columns = array_filter($columns, function ($col) {
                    return $col->name !== 'company_id';
                });

                $columns = array_values($columns);
            }
            View::share("columns", $columns);

            if ($request->ajax()) {
                if (!$modules['view_permission']) {
                    return $this->sendError('Unauthorized', [], [], 403);
                }
                $data = Inward::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $query->where('company_id', $modules['company_id']);
                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
                $data = $data->with(['vendor', 'purchase_order', 'inward_items', 'warehouse', 'company']);
                $data = $data->orderBy('id', 'desc');
                if (isset($modules['restore_permission']) && $modules['restore_permission']) {
                    $data = $data->withTrashed();
                }

                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_inward_date') && $request->filter_inward_date) {
                            $filter_inward_date = $request->filter_inward_date;
                            $filter_inward_date = explode(" to ", $filter_inward_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_inward_date) == 2) {
                                $fromDate = Helper::convert_date($filter_inward_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_inward_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Inward())->getTable() . '.inward_date', ">=", $fromDate);
                                $query->whereDate((new Inward())->getTable() . '.inward_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Inward())->getTable() . '.inward_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('inward_no', 'like', "%{$search}%")
                                    ->orWhereHas('vendor', function ($q2) use ($search) {
                                        $q2->where('vendor_name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('purchase_order', function ($q2) use ($search) {
                                        $q2->where('purchase_order_no', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('warehouse', function ($q2) use ($search) {
                                        $q2->where('name', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->editColumn('date', function ($row) {
                        // return $row?->date;
                        return Helper::convert_date($row?->date, "Y-m-d", "d-m-Y");
                    })
                    ->addColumn('warehouse_name', function ($row) {
                        if ($row->warehouse) {
                            return $row->warehouse->name;
                        } else {
                            return '-';
                        }
                    })
                    ->addColumn('vendor_name', function ($row) use ($modules) {
                        $vendorCode = optional($row->vendor)->vendor_code;
                        $vendorName = $row->vendor_name ?? optional($row->vendor)->vendor_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            $vendorInfo = '<span style="white-space:nowrap;">' . $vendorCode . '<br>' . $vendorName . '</span>';
                        } else {
                            $vendorInfo = $vendorCode ?? '-';
                        }
                        return $vendorInfo;
                    })
                    ->addColumn('po_no', function ($row) {
                        return $row->purchase_order->purchase_order_no ?? '-';
                    })
                    ->addColumn('inward_qty', function ($row) {
                        return $row->inward_items->sum('inward_quantity');
                    })
                    ->addColumn('action', function ($row) use ($modules) {
                        $action = '';

                        $action .= '
                    <div class="">
                        <button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1">
                                    <i class="fa-solid fa-trash mt-1 me-1"></i> Delete
                                </a>
                            </li>';
                            }


                            $action .= '<li>
                            <a href="' . route($modules["route"] . ".inwardShow", [$row["id"]]) . '" target="_blank" class="dropdown-item m-1">
                                <i class="fa-solid fa fa-eye mt-1 me-1"></i> View
                            </a>
                        </li>';
                        } else {

                            if (!empty($modules['restore_permission'])) {
                                $action .= '<li>
                                <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                    <i class="ti ti-history"></i> Restore
                                </a>
                            </li>';
                            }
                        }

                        $action .= '</ul></div>';

                        return $action;
                    })


                    ->rawColumns(['action', 'vendor_name'])
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

    public function insert($id)
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

            View::share('purchase_order_id', $id);

            $purchaseOrder = PurchaseOrder::with(['company', 'vendor'])->findOrFail($id);
            View::share('edit', $purchaseOrder);

            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(InwardRequest $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

        DB::beginTransaction();
        try {
            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;
            // return $validated;
            $inward = Inward::create($validated);
            $lastInsertedId = $inward->id;

            if ($request->has('product_name') && is_array($request->product_name)) {
                foreach ($request->product_name as $key => $productItem) {

                    // if($request->inward_quantity[$key] > $request->available_quantity[$key]){
                    //    DB::rollBack();
                    //    return Redirect::back()->withErrors(['Inward quantity cannot be greater than order remaining quantity.'])->withInput();
                    // }

                    $inwardItem = InwardItems::create([
                        'inward_id'   => $lastInsertedId,
                        'purchase_order_item_id' => $request->purchase_order_item_id[$key],
                        'product_id' => $request->product_id[$key],
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'product_name' => $productItem,
                        'unit' => $request->unit[$key],
                        'product_description' => $request->product_description[$key],
                        'remaining_quantity' => $request->remaining_quantity[$key],
                        'available_quantity' => $request->available_quantity[$key],
                        'inward_quantity' => $request->inward_quantity[$key],
                        'product_weight' => $request->product_weight[$key],
                        'is_qc_done' => $request->is_qc_required == '1' ? 0 : 1,
                        'created_by' => $loginUserId,
                    ]);
                    $lastItemId = $inwardItem->id;

                    $purchaseOrderItem = PurchaseOrderItems::withTrashed()->findOrFail($request->purchase_order_item_id[$key]);
                    $newRemainingQty = $purchaseOrderItem->remaining_quantity - $request->inward_quantity[$key];


                    // if ($newRemainingQty < 0) {
                    //     return response()->json(['error' => 'Inward quantity exceeds remaining quantity.'], 422);
                    // }

                    $purchaseOrderItem->update([
                        'remaining_quantity' => $newRemainingQty,
                    ]);

                    if ($request->is_qc_required != '1') {
                        $data = PurchaseOrderItems::withTrashed()->with('product')->findOrFail($request->purchase_order_item_id[$key]);

                        $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $request->warehouse_id, $data->product->product_category_id, $request->product_id[$key]);


                        $total_remaining_qty = (int)$product_total_quantity + (int)$request->inward_quantity[$key];
                        $total_kg = 0;
                        if ($total_remaining_qty > 0) {
                            $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $request->product_weight[$key]);
                        }

                        $addProductStock = ProductStockHistory::create([
                            'company_id' => $request->company_id,
                            'warehouse_id' => $request->warehouse_id,
                            'product_category_id' => $data->product->product_category_id,
                            'product_subcategory_id' => '',
                            'product_variant_price_id' => $request->product_id[$key],
                            'inward_id' => $lastInsertedId,
                            'total_quantity' => (int)$product_total_quantity + (int)$request->inward_quantity[$key],
                            'total_kg' => $total_kg,
                            'credit' => $request->inward_quantity[$key],
                            'date' => $request->inward_date,
                            'remark' => 'Inward Added of Inward No. - ' . $request->inward_no,
                            'created_by' => $loginUserId
                        ]);
                    }
                }
            }

            $purchaseOrderItems = PurchaseOrderItems::where('purchase_order_id', $request->purchase_order_id)->get();
            $isFullyInward = $purchaseOrderItems->every(function ($item) {
                return $item->remaining_quantity == 0;
            });

            $purchaseOrder = PurchaseOrder::withTrashed()->findOrFail($request->purchase_order_id);
            if ($isFullyInward) {
                $sales_status_id = Helper::getSalesStatusNameById($request->company_id, 'fi', "Purchase Order");
            } else {
                $sales_status_id = Helper::getSalesStatusNameById($request->company_id, 'pi', "Purchase Order");
            }
            $old_salesorder_status_id = $purchaseOrder->sales_status_id;
            $purchaseOrder->update([
                'sales_status_id' => $sales_status_id,
                'updated_by' => $loginUserId
            ]);
            if ($isFullyInward) {
                SendInwardOrderJob::dispatch($lastInsertedId, 'create', $old_salesorder_status_id, $sales_status_id);
            } else {
                SendInwardOrderJob::dispatch($lastInsertedId, 'create', $old_salesorder_status_id, $sales_status_id);
            }
            DB::commit();

            if ($request->is_qc_required == '1') {
                return Redirect::route('qc-inspection-report.create', ['inward_id' => $lastInsertedId])->withSuccess($modules['title'] . ' created successfully. Proceed to QC Inspection.');
            }

            return Redirect::route($modules['route'] . '.index')->withSuccess($modules['title'] . ' create successfully');
        } catch (\Exception $e) {
            DB::rollBack();
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

        $dataDelete = Inward::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;

        DB::beginTransaction();
        try {
            if ($dataDelete) {
                $validated['deleted_by'] = $loginUserId;
                $dataDelete->update($validated);

                if ($dataDelete->delete()) {
                    if ($isAjax) {

                        $product_stock_history_data = ProductStockHistory::where('inward_id', $id)->get()->toArray();
                        if (is_array($product_stock_history_data) && !empty($product_stock_history_data)) {
                            foreach ($product_stock_history_data as $key => $value) {
                                $product_total_quantity = Helper::getProductStockTotalQuantity($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_variant_price_id']);

                                $ProductData = Product::findOrFail($value['product_variant_price_id']);

                                $total_remaining_qty = (int)$product_total_quantity - (int)$value['credit'];
                                $total_kg = 0;
                                if ($total_remaining_qty > 0) {

                                    $wt = (float)$ProductData->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float)$ProductData->gross_wt;
                                    }
                                    $total_kg = Helper::qtyToKgConvertForStock($total_remaining_qty, $wt);
                                }

                                ProductStockHistory::where('inward_id', $id)->where("id", $value['id'])->delete();
                                ProductStockHistory::create([
                                    'company_id' => $value['company_id'],
                                    'warehouse_id' => $value['warehouse_id'],
                                    'product_category_id' => $value['product_category_id'],
                                    'product_subcategory_id' => '',
                                    'product_variant_price_id' => $value['product_variant_price_id'],
                                    'inward_id' => $value['inward_id'],
                                    'total_quantity' => (int)$product_total_quantity - (int)$value['credit'],
                                    'total_kg' => $total_kg,
                                    'debit' => (int)$value['credit'],
                                    'date' => $value['date'],
                                    'remark' => 'deleted of Inward No. - ' . $dataDelete->inward_no,
                                    'created_by' => $loginUserId
                                ]);
                            }
                        }


                        InwardItems::where('inward_id', $id)->delete();

                        $dataPurchaseOrder = PurchaseOrder::findOrFail($dataDelete->purchase_order_id);
                        if ($dataPurchaseOrder) {
                            $dataPurchaseOrderItemsIds = PurchaseOrderItems::where('purchase_order_id', $dataDelete->purchase_order_id)->pluck('id');
                            $inwardItemsCount = InwardItems::whereIn('purchase_order_item_id', $dataPurchaseOrderItemsIds)->whereNull('deleted_at')->count();

                            $dataPurchaseOrderItems = PurchaseOrderItems::where('purchase_order_id', $dataDelete->purchase_order_id)->get();
                            if ($dataPurchaseOrderItems->isNotEmpty()) {
                                foreach ($dataPurchaseOrderItems as $purchaseitem) {
                                    $inward_quantity = InwardItems::where('purchase_order_item_id', $purchaseitem->id)->whereNull('deleted_at')->sum('inward_quantity');

                                    $total_kg = $purchaseitem->quantity;
                                    $remaining_quantity = $purchaseitem->quantity;

                                    $productData = Product::find($purchaseitem->product_id);

                                    $wt = (float)$productData->net_wt;
                                    if ($wt == 0.0) {
                                        $wt = (float)$productData->gross_wt;
                                    }
                                    if ($purchaseitem->quantity > 0) {
                                        if ($purchaseitem->unit == 'pcs') {
                                            $remaining_quantity = $purchaseitem->quantity;
                                            $total_kg = Helper::qtyToKgConvertForStock($purchaseitem->quantity, $wt);
                                        } else if ($purchaseitem->unit == 'gross') {
                                            $total_qty = $request->grosse_per_qty[$key] * $purchaseitem->quantity;
                                            $remaining_quantity = $total_qty;
                                            $total_kg = Helper::qtyToKgConvertForStock($total_qty, $wt);
                                        } else if ($purchaseitem->unit == 'kg') {
                                            $remaining_quantity = Helper::kgToQtyConvertForStock($purchaseitem->quantity, $wt);
                                        }
                                    }

                                    $purchaseitem->update(['remaining_quantity' => ($inward_quantity == 0) ? $remaining_quantity : ($remaining_quantity - $inward_quantity),'total_kg' => $total_kg]);
                                }
                            }

                            $sales_approve_id = Helper::getSalesStatusNameById($dataDelete->company_id, "app", 'Purchase Order');
                            $sales_partially_id = Helper::getSalesStatusNameById($dataDelete->company_id, "pi", 'Purchase Order');
                            $dataPurchaseOrder->update([
                                'sales_status_id' => $inwardItemsCount == 0 ? $sales_approve_id : $sales_partially_id,
                                'updated_by' => $loginUserId,
                            ]);
                        }

                        SendInwardOrderJob::dispatch($id, 'delete');
                        DB::commit();
                        return $this->sendResponse([], $modules['title'] . ' delete successfully');
                    }
                }
            }
            if ($isAjax) {
                DB::rollBack();
                return $this->sendResponse([], "something went wrong please try again later");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function getPurchaseOrderItems(Request $request)
    {
        $modules = $this->modules;
        try {
            $purchase_order_id = $request->purchase_order_id;
            $warehouse_id = $request->warehouse_id;

            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            // $data = DB::table('purchase_order_items')
            //     ->select(
            //         'purchase_order_items.*',
            //         DB::raw('product_stock_history.id as product_stock_id'),
            //         DB::raw('IFNULL(SUM(product_stock_history.total_quantity), 0) as available_quantity')
            //     )
            //     ->leftJoin('product_stock_history', function ($join) use ($warehouse_id) {
            //         $join->on('product_stock_history.product_variant_price_id', '=', 'purchase_order_items.product_variant_price_id')
            //             ->where('product_stock_history.warehouse_id', '=', $warehouse_id)->whereNull('product_stock_history.deleted_at');
            //     })
            //     ->where('purchase_order_items.purchase_order_id', $purchase_order_id)
            //     ->where('purchase_order_items.remaining_quantity', '!=', 0)
            //     ->whereNull('purchase_order_items.deleted_at')
            //     ->groupBy('purchase_order_items.product_variant_price_id')
            //     ->get();

            $latestStockSub = DB::table('product_stock_history as psh1')
                ->select('psh1.*')
                ->where('psh1.warehouse_id', $warehouse_id)
                ->whereNull('psh1.deleted_at')
                ->whereRaw('psh1.id = (
                        SELECT MAX(psh2.id)
                        FROM product_stock_history as psh2
                        WHERE psh2.product_variant_price_id = psh1.product_variant_price_id
                        AND psh2.warehouse_id = ?
                        AND psh2.deleted_at IS NULL
                    )', [$warehouse_id]);

            $data = DB::table('purchase_order_items')
                ->select(
                    'purchase_order_items.*',
                    'latest_stock.id as product_stock_id',
                    'latest_stock.total_quantity as available_quantity',
                    'products.gross_wt',
                    'products.net_wt',
                    'products.grosse_per_qty'
                )
                ->leftJoin('products', function ($join) {
                    $join->on('products.id', '=', 'purchase_order_items.product_id')
                        ->whereNull('products.deleted_at');
                })
                ->leftJoinSub($latestStockSub, 'latest_stock', function ($join) {
                    $join->on('latest_stock.product_variant_price_id', '=', 'purchase_order_items.product_id');
                })
                ->where('purchase_order_items.purchase_order_id', $purchase_order_id)
                ->where('purchase_order_items.remaining_quantity', '!=', 0)
                ->whereNull('purchase_order_items.deleted_at')
                ->orderBy('purchase_order_items.id', 'ASC')
                ->get();

            // dd($data);
            $data = $data->map(function ($item) use ($warehouse_id) {
                $item->add_stock_url = route('product-stock.add', [$item->product_id, $warehouse_id]);
                if ($item->remaining_quantity > 0) {

                    $wt = (float)$item->net_wt;
                    if ($wt == 0.0) {
                        $wt = (float)$item->gross_wt;
                    }
                    $item->remaining_kg = Helper::qtyToKgConvertForStock($item->remaining_quantity, $wt);

                    $item->remaining_kg = ($item->remaining_kg >= 1)
                        ? number_format($item->remaining_kg, 3)         // 1199.99784 → 1200
                        : number_format($item->remaining_kg, 3);
                }

                if ($item->available_quantity > 0) {

                    $wt = (float)$item->net_wt;
                    if ($wt == 0.0) {
                        $wt = (float)$item->gross_wt;
                    }
                    $item->available_kg = Helper::qtyToKgConvertForStock($item->available_quantity, $wt);
                }
                $item->wt_pc = $wt;
                return $item;
            });
            return $this->sendResponse($data, "In Purchase Order Items List");
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function restore(string $id)
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
            $inward = Inward::withTrashed()->findOrFail($id);
            $inward->restore();

            ProductStockHistory::withTrashed()->where('inward_id', $id)->restore();
            InwardItems::withTrashed()->where('inward_id', $id)->restore();
            return Redirect::back()->withSuccess($modules['title'] . ' restored successfully!');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function exportExcel(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

        return Excel::download(new InwardExport($request->all(), $this->authenticateLoginUserDetails,$modules), 'Inward-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
    }

    public function print(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
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

            $data = Inward::select('*');
            $data = $data->where(function ($query) use ($modules, $loginUserId) {
                if (Auth::guard('team_person')->check()) {
                    $query->where('company_id', $modules['company_id']);
                    if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                        $query->where('created_by', $loginUserId);
                    }
                }
            });
            $data = $data->with(['vendor', 'purchase_order', 'inward_items', 'warehouse', 'company']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('parent_status_id') && $request->parent_status_id) {
                $data->where('sales_status_id', $request->parent_status_id);
            }

            if ($request->has('inward_date') && $request->inward_date) {

                $inward_date = $request->inward_date;
                $inward_date = explode(" to ", $inward_date);
                $fromDate = null;
                $toDate = null;

                if (count($inward_date) == 2) {
                    $fromDate = Helper::convert_date($inward_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($inward_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new Inward())->getTable() . '.inward_date', ">=", $fromDate);
                    $data->whereDate((new Inward())->getTable() . '.inward_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new Inward())->getTable() . '.inward_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('inward_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function ($q2) use ($search) {
                            $q2->where('vendor_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('purchase_order', function ($q2) use ($search) {
                            $q2->where('purchase_order_no', 'like', "%{$search}%");
                        })
                        ->orWhereHas('warehouse', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }
            $inwards = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('inwards', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function inwardShow($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $modules['currentGuard'] = $this->currentGuard ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $modules['parent_type_id'] = $modules['authLoginUserDetail']?->parent_type_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        try {
            // Permissions
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            // Load Inward with relationships
            $inward = Inward::with([
                'company.company_details',

                'vendor',
                'term_condition',
                'transport_by',
                'transport_detail',
                'warehouse',
                'purchase_order',
                'inward_items.purchase_order_item'
            ])->findOrFail($id);
            //   dd($inward);

            $inward->inward_items = $inward->inward_items ?? collect();

            return view($modules['folder_path'] . '.' . $inward->company->order_view_format, compact('inward', 'company_id', 'modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function inwardPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id ?? null;
        $loginUserId = $modules['authLoginUserDetail']?->id ?? null;

        try {
            // Share modules with all views
            View::share('modules', $modules);

            // Load Inward with required relationships
            $inward = Inward::with([
                'company',
                'vendor',
                'term_condition',
                'transport_by',
                'transport_detail',
                'warehouse',
                'purchase_order',
                'inward_items.purchase_order_item'
            ])->findOrFail($id);

            $inward->inward_items = $inward->inward_items ?? collect();

            return view($modules['folder_path'] . '.single_print', compact('inward', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function sendInwardPdf($id, Request $request)
    {
        ini_set('max_execution_time', 600);

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = $this->authenticateLoginUserDetails ?? null;
        $company_id = $modules['company_id'] = $modules['authLoginUserDetail']?->company_id;
        $loginUserId = $modules['authLoginUserDetail']?->id;

        // Load Inward with necessary relationships
        $inward = Inward::with([
            'company',
            'vendor',
            'team_person',
            'inward_items.purchase_order_item', // Adjust if the relationship is named differently
            'term_condition',
            'transport_by',
            'transport_detail'
        ])->findOrFail($id);

        // Load logos
        $sidebarMainLogo = public_path('software/img/logo.png');
        $sidebarSmallLogo = public_path('software/img/ring.png');
        $sidebarHeaderLogo = '';

        // Company-specific logos
        if ($inward->company_id) {
            $company = Helper::getCompanyDetailById($inward->company_id);

            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon ?? $sidebarSmallLogo;
                $sidebarHeaderLogo = $company?->order_header_logo ?? '';
            }
        }

        // Convert logos to base64
        $sidebarMainLogo = Helper::webpToBase64($sidebarMainLogo);
        $sidebarHeaderLogo = Helper::webpToBase64($sidebarHeaderLogo);
        // dd($sidebarHeaderLogo);
        // PDF view path
        $viewPath = $modules['folder_path'] . '.single_pdf' ?? 'default_view';

        // Render HTML
        $html = View::make($viewPath, compact(
            'inward',
            'modules',
            'sidebarMainLogo',
            'sidebarHeaderLogo'
        ))->render();

        // Define file path
        $year = now()->format('Y');
        $month = now()->format('m');
        $company_slug = Str::slug($inward->company->company_name);
        $folder = "uploads/{$company_id}-{$company_slug}/inward/{$year}-{$month}/";
        $uploadedPath = public_path($folder);

        if (!file_exists($uploadedPath)) {
            mkdir($uploadedPath, 0755, true);
        }

        $filename = 'Inward_' . $inward->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = $uploadedPath . $filename;

        // Generate PDF
        Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->save($filepath);

        // Email Data
        $data = [
            'customer_name' => $inward->vendor?->vendor_name ?? '-',
            'company_name' => $inward->company?->company_name ?? '-',
        ];

        $attachments = [[
            'file' => $filepath,
            'options' => [
                'as' => $filename,
                'mime' => 'application/pdf',
            ]
        ]];

        // Send Email
        Helper::mail_new(
            $inward->company_id,
            'Inward',
            'inward_send_pdf',
            $inward->vendor->email_id,

            optional($inward->vendor)->id,
            'Inward/sendInwardPdf',
            $data,
            $attachments
        );

        return Redirect::route($modules['route'] . '.inwardShow', [$inward->id])
            ->with('success', 'Inward PDF sent via email.');
    }
}
