<?php

namespace App\Http\Controllers\software;

use App\Exports\DispatchExport;
use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\SalesStatus;
use App\Models\SalesOrder;
use App\Models\ProductStockHistory;
use App\Models\SalesOrderItems;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\DispatchRequest;
use App\Models\DispatchItems;
use Illuminate\Support\Facades\Session;
use App\Helpers\Helper;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\SendDispatchJob;

class DispatchController extends Controller
{
    public $modules = [];

    public function __construct()
    {
        parent::__construct();
        $this->modules = [
            'title' => 'Dispatch',
            'folder_path' => 'software.modules.sales_management.dispatch_orders',
            'route' => 'dispatch_orders',
            'table_name' => (new Dispatch())->getTable(),
            'permisstion_prefix' => 'dispatch_orders',
            'module_name' => 'Dispatch',
            'company_id' => ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null
        ];
    }

    public function index(Request $request)
    {
        $modules = $this->modules;
        $modules['currentGuard'] = ($this->currentGuard) ? $this->currentGuard : null;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $modules['show_packing_slip'] = (isset($modules['company_id']) && !$modules['company_id']) ? true : Gate::check('hasPermission', [config('constants.permissions.add'), 'Packing Slip']);
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
                // (object)['data' => 'select', 'name' => 'select', 'orderable' => false, 'searchable' => false, 'className' => 'text-center w-5', 'td_label' => 'Select'],
                (object)['data' => "company", 'name' => 'company_id', 'td_label' => 'Company Name', 'className' =>  ''],
                (object)['data' => "dispatch_no", 'name' => 'dispatch_no', 'td_label' => 'Dispatch No', 'className' =>  ''],
                (object)['data' => "order_no", 'name' => 'order_no', 'td_label' => 'Order No', 'className' =>  ''],
                (object)['data' => "dispatch_date", 'name' => 'dispatch_date', 'td_label' => 'Dispatch Date', 'className' =>  ''],
                (object)['data' => "dispatch_order_status", 'name' => 'dispatch_order_status', 'td_label' => 'Dispatch Order Status', 'className' =>  ''],
                (object)['data' => "customer_name", 'name' => 'customer_name', 'td_label' => 'Customer Name', 'className' =>  ''],
                (object)['data' => "team_person_name", 'name' => 'team_person_name', 'td_label' => 'Team Person Name', 'className' =>  ''],
                (object)['data' => "dispatch_qty", 'name' => 'dispatch_qty', 'td_label' => 'Dispatch Qty', 'className' =>  ''],
                (object)['data' => "action", 'name' => 'action', 'td_label' => 'Action', 'orderable' => false, 'searchable' => false, 'className' =>  'w-10 text-start'],
            ];
            if ($modules['show_packing_slip']) {
                array_unshift($columns, (object)[
                    'data' => 'select',
                    'name' => 'select',
                    'orderable' => false,
                    'searchable' => false,
                    'className' => 'text-center w-5',
                    'td_label' => 'Select'
                ]);
            }
            if ($modules['company_id']) {
                // array_unshift($columns, (object)['data' => "company_name", 'name' => 'company_name', 'td_label' => 'Company Name', 'className' =>  '']);
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
                $data = Dispatch::select('*');
                $data = $data->where(function ($query) use ($modules, $loginUserId) {
                    if (Auth::guard('team_person')->check()) {
                        $query->where('company_id', $modules['company_id']);
                        if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                            $query->where('created_by', $loginUserId);
                        }
                    }
                });
                $data = $data->with(['company', 'customer', 'sales_order', 'dispatch_items', 'team_person', 'admin_person']);
                $data = $data->orderBy('id', 'desc');

                $returnData = Datatables::of($data)
                    ->filter(function ($query) use ($request) {
                        if ($request->has('filter_company') && $request->filter_company) {
                            $query->where('company_id', $request->filter_company);
                        }

                        if ($request->has('filter_dispatch_date') && $request->filter_dispatch_date) {
                            $filter_dispatch_date = $request->filter_dispatch_date;
                            $filter_dispatch_date = explode(" to ", $filter_dispatch_date);
                            $fromDate = null;
                            $toDate = null;

                            if (count($filter_dispatch_date) == 2) {
                                $fromDate = Helper::convert_date($filter_dispatch_date[0], "d/m/Y", "Y-m-d");
                                $toDate = Helper::convert_date($filter_dispatch_date[1], "d/m/Y", "Y-m-d");
                            }
                            if ($fromDate && $toDate) {
                                $query->whereDate((new Dispatch())->getTable() . '.dispatch_date', ">=", $fromDate);
                                $query->whereDate((new Dispatch())->getTable() . '.dispatch_date', "<=", $toDate);
                            } elseif ($fromDate) {
                                $query->whereDate((new Dispatch())->getTable() . '.dispatch_date', $fromDate);
                            }
                        }

                        if ($request->has('search')) {
                            $search = $request->search;

                            $query->where(function ($q) use ($search) {
                                $q->where('dispatch_no', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($q2) use ($search) {
                                        $q2->where('customer_name', 'like', "%{$search}%");
                                    })
                                    ->orWhereHas('sales_order', function ($q2) use ($search) {
                                        $q2->where('sales_order_no', 'like', "%{$search}%");
                                    });
                            });
                        }
                    })
                    ->addColumn('team_person_name', function ($row) {
                        if ($row->created_type == 'admin') {
                            return $row->admin_person->name;
                        } else {
                            return $row->team_person->name;
                        }
                    })
                    ->editColumn('dispatch_date', function ($row) {
                        return \Carbon\Carbon::parse($row->dispatch_date)->format('d-m-Y');
                    })
                    ->addColumn('company', function ($row) {
                        return $row->company->company_name;
                    })
                    ->addColumn('customer_name', function ($row) use ($modules) {
                        $clientCode   = optional($row->customer)->client_code;
                        $customerName = optional($row->customer)->customer_name;

                        if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))) {
                            return '<span style="white-space:nowrap;">'.$clientCode.'<br>'.$customerName.'</span>';
                        }

                        return $clientCode;
                    })
                    ->addColumn('dispatch_order_status', function ($row) {
                        if (empty($row->sales_status)) {
                            return '<span class="badge bg-glow" style="background-color: #ccc">N/A</span>';
                        }
                        return '<span class="badge bg-glow" style="background-color: ' . $row->sales_status->status_color . '">' . $row->sales_status->statusname . '</span>';
                    })
                    ->addColumn('order_no', function ($row) {
                        return $row?->sales_order?->sales_order_no;
                    })
                    ->addColumn('dispatch_qty', function ($row) {
                        return $row->dispatch_items->sum('dispatch_quantity');
                    })
                    ->addColumn('select', function ($row) {
                        $checkbox = '';

                        $currentStatus = SalesStatus::find($row?->sales_status_id);

                        if ($currentStatus?->statusslug != 'pg') {
                            $checkbox .= '<input type="checkbox" class="form-check-input checkbox"
                            data-dispatch_no="' . $row->dispatch_no . '"
                            data-customer_id="' . $row->customer_id . '"
                            data-company_id="' . $row->company_id . '"
                            name="checked_id[]" id="checked_id_' . $row->id . '"
                            value="' . $row->id . '">';
                        }

                        return $checkbox;
                    })

                    ->addColumn('action', function ($row) use ($modules) {

                        $action = '';
                        $action .= '<div class="">';

                        // $currentStatus = SalesStatus::find($row?->sales_status_id);

                        // if ($currentStatus?->statusslug != 'pg') {
                        //     $action .= '<input type="checkbox" style="margin-top: 5%; margin-left: 3%;" class="form-check-input fs-base checkbox" data-dispatch_no="' . $row->dispatch_no . '" data-customer_id="' . $row->customer_id . '" data-company_id="' . $row->company_id . '" name="checked_id[]" id="checked_id" value="' . $row->id . '">';
                        // }
                        $action .= '<button class="btn btn-icon waves-effect waves-light" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu">';

                        if (!$row?->deleted_at) {
                            if (!empty($modules['view_permission'])) {
                                $action .= '
                                <li>
                                <a href="' . route($modules["route"] . ".dispatchShow", [$row->id]) . '" class="dropdown-item">
                                <i class="fa-solid fa-eye"></i> View
                                </a>
                                </li>
                                ';
                            }

                            if ($modules['delete_permission'] == true) {
                                $action .= '<li>
                                            <a href="javascript:void(0)" data-id="' . $row->id . '" data-did="' . route($modules["route"] . ".destroy", [$row["id"]]) . '" class="dropdown-item deletebutton mx-1"><i class="fa-solid fa-trash"></i> Delete</a>
                                        </li>';
                            }


                            if ($row?->sales_status?->is_repeat == 1) {
                                $action .= '<li>
                                        <a href="javascript:void(0)" data-clone="' . route($modules["route"] . ".clone") . '" class="dropdown-item record-clone m-1" data-id="' . $row->id . '" data-quatation-txt="repeat" data-type="repeat" data-bs-original-title="Repeat Data">
                                            <i class="fa-solid fa fa-repeat"></i> Repeat
                                        </a>
                                    </li>';
                            }
                            if (!empty($row?->sales_status_id)) {
                                $currentStatus = SalesStatus::find($row?->sales_status_id);

                                if ($currentStatus?->statusslug == 'pd') {
                                    $nextStatus = SalesStatus::where('statusslug', 'cd')
                                        ->where('salestype', 'Dispatch Orders')
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                } else {
                                    $nextStatus = SalesStatus::where('company_id', $row->company_id)
                                        ->where('salestype', 'Dispatch Orders')
                                        // ->where('display_order', '>', $currentStatus->display_order)
                                        ->where('parent_status_id', $currentStatus->id)
                                        ->where('id', '>', $currentStatus->id)
                                        ->orderBy('display_order', 'asc')
                                        ->first();
                                }

                                if (!empty($nextStatus)) {
                                    $action .= '<li class="dropdown-divider"></li>';
                                    if ($nextStatus->is_redirect == 1) {
                                        $url = route('packing_slip.create');
                                        if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                            $action .= '<li>
                                                        <a class="change_status dropdown-item mx-1 add_packing_slip" id="status_add_packing_slip" href="javascript:void(0)">
                                                            ' . $nextStatus->statusname . '
                                                            <form id="status-packing-slip-form" action="' . url("software/packing_slip/create") . '" method="POST" class="d-none">
                                                                ' . csrf_field() . '
                                                                <input type="hidden" id="dispatch_id" name="dispatch_id" value="' . $row->id . '" autocomplete="off">
                                                                <input type="hidden" id="dispatch_company_id" name="dispatch_company_id" value="' . $row->company_id . '" autocomplete="off">
                                                                <input type="hidden" id="dispatch_customer_id" name="dispatch_customer_id" value="' . $row->customer_id . '" autocomplete="off">
                                                            </form>
                                                        </a>
                                                    </li>';
                                        }
                                    } else {
                                        $url = "";
                                        // route($modules["route"] . ".change-dispatch-order-status");

                                        if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                            $action .= '<li>
                                                        <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                    </li>';
                                        }
                                    }
                                }
                            } else {
                                $nextStatus = SalesStatus::where('company_id', $row->company->id)->where('parent_status_id', null)->where('salestype', 'Sales Orders')->orderBy('display_order', 'asc')->first();
                                if (!empty($nextStatus)) {
                                    if (isset($modules['approval_permission']) && $modules['approval_permission']) {
                                        $url = "";
                                        // route($modules["route"] . ".change-dispatch-order-status");
                                        $action .= '<li class="dropdown-divider"></li>
                                                <li>
                                                    <a data-url="' . $url . '" href="javascript:void(0)" data-id="' . $row->id . '" data-next-status-id="' . $nextStatus->id . '" class="change_status dropdown-item mx-1">Change to ' . $nextStatus->statusname . '</a>
                                                </li>';
                                    }
                                }
                            }
                        } else {
                            $action .= '<li>
                                            <a href="javascript:void(0)" data-restore="' . route($modules["route"] . ".restore", ['id' => $row["id"]]) . '" class="record-restore dropdown-item" data-id="' . $row->id . '" data-bs-original-title="Restore Data">
                                                Restore
                                            </a>
                                        </li>';
                        }
                        $action .= '</ul>
                                </div>
                            ';

                        return $action;
                    })

                    ->rawColumns(['action', 'dispatch_order_status', 'select', 'customer_name'])
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
            View::share('sales_order_id', $id);
            View::share('modules', $modules);
            $salesOrder = SalesOrder::with('customer', 'company', 'price_list')->findOrFail($id);
            View::share('edit', $salesOrder);
            return view($modules['folder_path'] . '.form');
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }

    public function store(DispatchRequest $request)
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

            $status_data = SalesStatus::where('company_id', $validated['company_id'])
                ->where('salestype', 'Dispatch Orders')
                ->where('display_order', 1)
                ->first();
            if (!empty($status_data)) {
                $validated['sales_status_id'] = $status_data->id;
            }
            // dd("Ln-253",$status_data);
            // dd()

            if ($modules['currentGuard'] == 'admin_software') {
                $validated['created_type'] = 'admin';
            } else {
                $validated['created_type'] = 'team';
            }
            $validated['created_by'] = $loginUserId;



            $dispatch = Dispatch::create($validated);
            $lastInsertedId = $dispatch->id;
            $stock_check = $request->stock_check;

            if ($request->has('product_name') && is_array($request->product_name)) {
                foreach ($request->product_name as $key => $productItem) {

                    if ($stock_check == 'yes') {
                        if ($request->dispatch_quantity[$key] > $request->available_quantity[$key]) {
                            DB::rollBack();
                            return Redirect::back()->withErrors(['Dispatch quantity cannot be greater than order remaining quantity.'])->withInput();
                        }
                    }

                    $dispatchItem = DispatchItems::create([
                        'dispatch_id'   => $lastInsertedId,
                        'sales_order_item_id' => $request->sales_order_item_id[$key],
                        'product_id' => $request->product_id[$key],
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'product_name' => $productItem,
                        'unit' => $request->unit[$key],
                        'product_description' => $request->product_description[$key],
                        'remaining_quantity' => $request->remaining_quantity[$key],
                        'available_quantity' => $request->available_quantity[$key],
                        'dispatch_quantity' => $request->dispatch_quantity[$key],
                        'created_by' => $loginUserId,
                    ]);
                    $lastItemId = $dispatchItem->id;
                    $salesOrderItem = SalesOrderItems::withTrashed()->findOrFail($request->sales_order_item_id[$key]);
                    $newRemainingQty = $salesOrderItem->remaining_quantity - $request->dispatch_quantity[$key];

                    if ($newRemainingQty < 0) {
                        DB::rollBack();
                        return Redirect::back()->withErrors(['Dispatch quantity exceeds remaining quantity.'])->withInput();
                    }
                    $salesOrderItem->update([
                        'remaining_quantity' => $newRemainingQty,
                    ]);

                    $data = SalesOrderItems::withTrashed()->with('product_variant_price.product')->findOrFail($request->sales_order_item_id[$key]);

                    $product_total_quantity = Helper::getProductStockTotalQuantity($request->company_id, $request->warehouse_id, $data->product_variant_price->product->product_category_id, $data->product_variant_price->product->product_sub_category_id, $request->product_variant_price_id[$key]);

                    $addProductStock = ProductStockHistory::create([
                        'company_id' => $request->company_id,
                        'warehouse_id' => $request->warehouse_id,
                        'product_category_id' => $data->product_variant_price->product->product_category_id,
                        'product_subcategory_id' => $data->product_variant_price->product->product_sub_category_id,
                        'product_variant_price_id' => $request->product_variant_price_id[$key],
                        'dispatch_id' => $lastInsertedId,
                        'total_quantity' => (int)$product_total_quantity - (int)$request->dispatch_quantity[$key],
                        'debit' => $request->dispatch_quantity[$key],
                        'date' => $request->dispatch_date,
                        'remark' => 'Dispatch Added of Dispatch No. - ' . $request->dispatch_no,
                        'created_by' => $loginUserId
                    ]);
                }
            }


            $salesOrderItems = SalesOrderItems::where('sales_order_id', $request->sales_order_id)->get();

            $isFullyDispatched = $salesOrderItems->every(function ($item) {
                return $item->remaining_quantity == 0;
            });
            //dd($isFullyDispatched);

            $salesOrder = SalesOrder::findOrFail($request->sales_order_id);
            if ($isFullyDispatched) {
                $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "fd", 'Sales Orders');
            } else {
                $sales_status_id = Helper::getSalesStatusNameById($request->company_id, "pd", 'Sales Orders');
            }
            if (!$sales_status_id) {
                return "";
            }
            $old_salesorder_status_id = $salesOrder->sales_status_id;
            //dd($sales_status_id);
            $salesOrder->update([
                'sales_status_id' => $sales_status_id,
                'updated_by' => $loginUserId
            ]);
            if ($isFullyDispatched) {
                SendDispatchJob::dispatch($lastInsertedId, 'create', $old_salesorder_status_id, $sales_status_id);
            } else {
                SendDispatchJob::dispatch($lastInsertedId, 'create', $old_salesorder_status_id, $sales_status_id);
            }

            DB::commit();
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


        $dataDelete = Dispatch::findOrFail($id);
        $isAjax = ($request->ajax()) ? true : false;


        try {
            if ($dataDelete) {
                $validated['deleted_by_id'] = $loginUserId;
                $dataDelete->update($validated);

                $dispatchItems = DispatchItems::where('dispatch_id', $id)->get();
                if ($dispatchItems->isNotEmpty()) {
                    // Update each item with deleted_by_id before deleting
                    foreach ($dispatchItems as $item) {
                        $item->update(['deleted_by_id' => $loginUserId]);
                        $item->delete(); // Soft delete assumed
                    }
                }

                $salesOrderItemsIds = SalesOrderItems::where('sales_order_id', $dataDelete->sales_order_id)
                    ->pluck('id');

                if ($salesOrderItemsIds->isNotEmpty()) {
                    $dataSalesOrderItems = SalesOrderItems::where('sales_order_id', $dataDelete->sales_order_id)->get();
                    $dispatchItemsCount = DispatchItems::whereIn('sales_order_item_id', $salesOrderItemsIds)
                        ->whereNull('deleted_at') // assuming soft deletes
                        ->count();

                    $salesOrder = SalesOrder::findOrFail($dataDelete->sales_order_id);

                    $sales_approve_id = Helper::getSalesStatusNameById($dataDelete->company_id, "app", 'Sales Orders');
                    $sales_partially_id = Helper::getSalesStatusNameById($dataDelete->company_id, "pd", 'Sales Orders');

                    $salesOrder->update([
                        'sales_status_id' => $dispatchItemsCount == 0 ? $sales_approve_id : $sales_partially_id,
                        'updated_by' => $loginUserId,
                    ]);
                    SendDispatchJob::dispatch($id, 'delete');
                }

                if ($dataDelete->delete()) {
                    if ($isAjax) {

                        $product_stock_history_data = ProductStockHistory::where('dispatch_id', $id)->get()->toArray();
                        if (is_array($product_stock_history_data) && !empty($product_stock_history_data)) {
                            foreach ($product_stock_history_data as $key => $value) {
                                $product_total_quantity = Helper::getProductStockTotalQuantity($value['company_id'], $value['warehouse_id'], $value['product_category_id'], $value['product_subcategory_id'], $value['product_variant_price_id']);

                                ProductStockHistory::where('dispatch_id', $id)->where("id", $value['id'])->delete();
                                ProductStockHistory::create([
                                    'company_id' => $value['company_id'],
                                    'warehouse_id' => $value['warehouse_id'],
                                    'product_category_id' => $value['product_category_id'],
                                    'product_subcategory_id' => $value['product_subcategory_id'],
                                    'product_variant_price_id' => $value['product_variant_price_id'],
                                    'dispatch_id' => $value['dispatch_id'],
                                    'total_quantity' => (int)$product_total_quantity + (int)$value['debit'],
                                    'credit' => (int)$value['debit'],
                                    'date' => $value['date'],
                                    'remark' => 'deleted of dispatch No. - ' . $value['id'],
                                    'created_by' => $loginUserId
                                ]);
                            }
                        }


                        if ($dataSalesOrderItems->isNotEmpty()) {
                            // Update each item with deleted_by_id before deleting
                            foreach ($dataSalesOrderItems as $salesitem) {
                                $dispatch_quantity = DispatchItems::where('sales_order_item_id', $salesitem->id)->whereNull('deleted_at')->sum('dispatch_quantity');
                                $salesitem->update(['remaining_quantity' => ($dispatch_quantity == 0) ? $salesitem->quantity : ($salesitem->quantity - $dispatch_quantity)]);
                            }
                        }

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

    public function getSalesOrderItems(Request $request)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        try {
            $sales_order_id = $request->sales_order_id;
            $warehouse_id = $request->warehouse_id;

            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            // $data = DB::table('sales_order_items')
            //     ->select(
            //         'sales_order_items.*',
            //         DB::raw('product_stock_history.id as product_stock_id'),
            //         //DB::raw('IFNULL(SUM(product_stock_history.total_quantity), 0) as available_quantity')
            //         'product_stock_history.total_quantity as available_quantity'
            //     )
            //     ->leftJoin('product_stock_history', function ($join) use ($warehouse_id) {
            //         $join->on('product_stock_history.product_variant_price_id', '=', 'sales_order_items.product_variant_price_id')
            //             ->where('product_stock_history.warehouse_id', '=', $warehouse_id)->whereNull('product_stock_history.deleted_at');
            //     })
            //     ->where('sales_order_items.sales_order_id', $sales_order_id)
            //     ->where('sales_order_items.remaining_quantity', '!=', 0)
            //     ->groupBy('sales_order_items.product_variant_price_id')->get();

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

            $data = DB::table('sales_order_items')
                ->select(
                    'sales_order_items.*',
                    'latest_stock.id as product_stock_id',
                    'latest_stock.total_quantity as available_quantity'
                )
                ->leftJoinSub($latestStockSub, 'latest_stock', function ($join) {
                    $join->on('latest_stock.product_variant_price_id', '=', 'sales_order_items.product_variant_price_id');
                })
                ->where('sales_order_items.sales_order_id', $sales_order_id)
                ->where('sales_order_items.remaining_quantity', '!=', 0)
                ->orderBy('sales_order_items.id', 'ASC')
                ->get();

            $data = $data->map(function ($item) use ($warehouse_id) {
                $item->add_stock_url = route('product-stock.add', [$item->product_variant_price_id, $warehouse_id]);
                return $item;
            });

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function show(string $id)
    {
        // if ($id = "print") {
        //     return self::print();
        // }
        // dd($id);
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
            $dispatch = Dispatch::withTrashed()->findOrFail($id);
            $dispatch->restore();

            ProductStockHistory::withTrashed()->where('dispatch_id', $id)->restore();
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

        return Excel::download(new DispatchExport($request->all(), $this->authenticateLoginUserDetails, $modules), 'Dispatch-' . Helper::convert_date("", "Y-m-d H:i:s", "Ymd-His") . '.xlsx');
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

            $data = Dispatch::select('*');
            $data = $data->where(function ($query) use ($modules, $loginUserId) {
                if (Auth::guard('team_person')->check()) {
                    $query->where('company_id', $modules['company_id']);
                    if ($modules['personal_data_permission'] && $modules['all_data_permission'] == false) {
                        $query->where('created_by', $loginUserId);
                    }
                }
            });
            $data = $data->with(['company', 'customer', 'sales_order', 'dispatch_items', 'team_person', 'admin_person']);

            if ($request->has('company') && $request->company) {
                $data->where('company_id', $request->company);
            }

            if ($request->has('dispatch_date') && $request->dispatch_date) {

                $dispatch_date = $request->dispatch_date;
                $dispatch_date = explode(" to ", $dispatch_date);
                $fromDate = null;
                $toDate = null;

                if (count($dispatch_date) == 2) {
                    $fromDate = Helper::convert_date($dispatch_date[0], "d/m/Y", "Y-m-d");
                    $toDate = Helper::convert_date($dispatch_date[1], "d/m/Y", "Y-m-d");
                }
                if ($fromDate && $toDate) {
                    $data->whereDate((new Dispatch())->getTable() . '.dispatch_date', ">=", $fromDate);
                    $data->whereDate((new Dispatch())->getTable() . '.dispatch_date', "<=", $toDate);
                } elseif ($fromDate) {
                    $data->whereDate((new Dispatch())->getTable() . '.dispatch_date', $fromDate);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;

                $data->where(function ($q) use ($search) {
                    $q->where('dispatch_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('customer_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('sales_order', function ($q2) use ($search) {
                            $q2->where('sales_order_no', 'like', "%{$search}%");
                        });
                });
            }
            $dispatch = $data->orderBy('id', 'desc')->get();

            return view($modules['folder_path'] . '.print', compact('dispatch', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function dispatchShow($id)
    {

        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;
        $modules['parent_type_id'] = ($this->authenticateLoginUserDetails?->parent_type_id) ? $this->authenticateLoginUserDetails?->parent_type_id : null;
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
            $modules['viewPermission'] = Gate::check('hasPermission', ['view', $modules['module_name']]);
            $modules['addPermission'] = Gate::check('hasPermission', ['add', $modules['module_name']]);
            $modules['editPermission'] = Gate::check('hasPermission', ['update', $modules['module_name']]);
            $modules['deletePermission'] = Gate::check('hasPermission', ['delete', $modules['module_name']]);
            $modules['personalDataPermission'] = Gate::check('hasPermission', ['personal_data', $modules['module_name']]);
            $modules['allDataPermission'] = Gate::check('hasPermission', ['all_data', $modules['module_name']]);

            $dispatch = Dispatch::with(['company', 'customer', 'team_person', 'transport_by', 'transport_detail'])->findOrFail($id);

            return view($modules['folder_path'] . '.' . 'general_view', compact('dispatch', 'company_id', 'modules'));

            //return view($modules['folder_path'] . '.revise', compact('quotation','company_id','modules'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
    public function dispatchPrint($id)
    {
        $modules = $this->modules;
        $modules['authLoginUserDetail'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails : null;
        $company_id = $modules['company_id'] = ($this->authenticateLoginUserDetails) ? $this->authenticateLoginUserDetails?->company_id : null;
        $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;

        try {

            View::share('modules', $modules);
            $dispatch = Dispatch::with(['company', 'customer', 'team_person', 'transport_by', 'transport_detail'])->findOrFail($id);


            return view($modules['folder_path'] . '.single_print', compact('dispatch', 'company_id'));
        } catch (\Exception $e) {
            return Redirect::route($modules['route'] . '.index')->withErrors($e->getMessage());
        }
    }
}
