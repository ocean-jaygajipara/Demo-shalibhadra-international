@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

    $authDetail = $modules['authLoginUserDetail'] ?? ($authLoginUserDetail ?? (Auth::guard('admin_software')->user() ?? Auth::guard('team_person')->user()));
    $isDhruvik = false;
    if ($authDetail) {
        $userCompanyId = (int)($authDetail->company_id ?? ($company_id ?? ($modules['company_id'] ?? 0)));
        $userTeamRoleId = (int)($authDetail->team_role_id ?? 0);
        $authName = strtoupper($authDetail->name ?? '');
        $authUsername = strtoupper($authDetail->username ?? '');
        $authEmpCode = strtoupper($authDetail->employee_code ?? '');

        if (
            (Auth::guard('admin_software')->check() && !Auth::guard('team_person')->check()) ||
            ($userCompanyId == 2 && ($userTeamRoleId == 2 || (int)$authDetail->id == 2 || $authEmpCode === 'EMP-001')) ||
            str_contains($authName, 'DHRUVIK') ||
            str_contains($authUsername, 'DHRUVIK')
        ) {
            $isDhruvik = true;
        }
    }
@endphp
@section('title', $page_title)

@section('content')
<style>
    .ck-editor__editable_inline {
        min-height: 150px;
    }
    /* Packing Slip Items – Compact ERP Style */
    #packing_slip_items th,
    #packing_slip_items td {
        padding: 5px 6px !important;
        font-size: 12px;
        line-height: 1.4;
        white-space: nowrap;
    }

    #packing_slip_items thead th {
        padding-top: 4px !important;
        padding-bottom: 4px !important;
        font-size: 11.5px;
        vertical-align: middle;
    }

    #packing_slip_items tr {
        height: auto !important;
    }

    /* Sr No + Checkbox column */
    #packing_slip_items th:first-child,
    #packing_slip_items td:first-child {
        width: 40px;
        padding-left: 4px !important;
        padding-right: 4px !important;
    }

    /* Inputs inside table */
    #packing_slip_items input.form-control {
        height: 24px;
        padding: 2px 4px;
        font-size: 12px;
    }

    /* Remove extra margin from buttons */
    #packing_slip_items .btn-sm {
        padding: 2px 6px;
        font-size: 12px;
    }

    #customer_order_items_table th,
    #customer_order_items_table td {
        padding: 5px 6px !important;
        font-size: 12px;
        line-height: 1.2;
        white-space: nowrap;
    }

    #customer_order_items_table thead th {
        font-size: 11.5px;
        padding-top: 4px !important;
        padding-bottom: 4px !important;
    }

    #customer_order_items_table tr {
        height: auto !important;
    }

    #customer_order_items_table th:first-child,
    #customer_order_items_table td:first-child {
        width: 30px;
        padding-left: 4px !important;
        padding-right: 4px !important;
    }
    .table-scroll-x {
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
    }

    #packing_slip_items {
        min-width: 2000px; /* as per your columns width */
    }
    .product_name_txt {
        white-space: normal;
        word-break: break-word;
        max-width: 150px;
    }
    .product_code_txt {
        white-space: normal;
        word-break: break-word;
        max-width: 120px;
    }
    .adjustment_quantity_text{
       white-space: normal;
       word-break: break-word;
       max-width: 130px;
    }
    table.dataTable td.action-col,
    table.dataTable th.action-col {
        width: 50px !important;
        max-width: 50px !important;
        min-width: 50px !important;
    }
</style>

<div class="d-flex justify-content-lg-between px-1">
    @include('software.inlcudes.breadcrumb', [
    'breadcrumbArray' => [
    ['title' => $page_title, 'url' => route($route . '.index')],
    ['title' => (isset($edit) && $edit?->id) ? "Edit ".$page_title : "Create ".$page_title , 'url' => ''],
    ],
    ])
    <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}"> <i
            class="menu-icon ti ti-chevrons-left"></i> Back</a>
</div>

<div class="card my-3 mb-4">
    <div class="card-body p-0">

        @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
            @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form action="{{ isset($packing_slip_id) && $packing_slip_id ? route($route . '.update', [$packing_slip_id]) : route($route . '.store') }}"
            method="POST" enctype="multipart/form-data">

            <input type="hidden" name="sales_order_id" id="sales_order_id" value="{{ isset($sales_order_id) && $sales_order_id ? $sales_order_id : $edit->sales_order_id }}" />
            <input type="hidden" name="packing_slip_id" id="packing_slip_id" value="{{ isset($packing_slip_id) && $packing_slip_id ? $packing_slip_id : '' }}" />
            <input type="hidden" name="company_id" value="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}" />
            <input type="hidden" name="customer_id" value="{{ isset($edit) && $edit?->customer_id ? $edit?->customer_id : '' }}" />
            <input type="hidden" name="customer_name" id="customer_name" value="{{ $edit->customer_name ?? $edit->customer->customer_name ?? '-' }}" />
            <input type="hidden" name="contact_person_name" id="contact_person_name" value="{{ isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : '' }}" />
            <input type="hidden" name="phone" id="phone" value="{{ isset($edit) && $edit?->phone ? $edit?->phone : '' }}" />
            <input type="hidden" name="state" id="state" value="{{ isset($edit) && $edit?->state ? $edit?->state : '' }}" />
            <input type="hidden" name="address" id="address" value="{{ isset($edit) && $edit?->address ? $edit?->address : '' }}" />
            <input type="hidden" name="price_list_id" id="price_list_id" value="{{ isset($edit) && $edit?->price_list_id ? $edit?->price_list_id : '' }}" />

            @csrf

            @if (isset($packing_slip_id) && $packing_slip_id)
                @method('PUT')
            @endif

            {{-- DISPATCH DETAIL --}}
            <div class=" mb-3">
                <div class="card-body row">
                    {{-- Company --}}

                    <div class="row g-3">

                        @if (!$company_id)
                        <div class="col-md-3">
                            <label>Select Company <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id" disabled
                                class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                data-append="search_by_company"
                                data-selectedCompanyId="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('company_id', $edit->company_id ?? '') ? '' : 'selected' }}>
                                    Select Company
                                </option>
                            </select>
                            @error('company_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        @else
                            <input type="hidden" class="form-control search_by_company" name="company_id"
                                value="{{ $company_id }}" />
                        @endif

                        @if (!isset($sales_order_id) && !isset($packing_slip_id))
                        <div class="col-md-3 mt-3">
                            <label>Select Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id"
                                class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                                data-append="search_by_customer"
                                data-selectedCustomerId="{{ old('customer_id', $edit->customer_id ?? '') }}"
                                autofocus>
                                <option value="" {{ old('customer_id', $edit->customer_id ?? '') ? '' : 'selected' }}>
                                    Select Customer
                                </option>
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        @else
                        <div class="col-md-3 mt-3">
                            <label>Customer Code<span class="text-danger">*</span></label>
                            <input type="text" readonly name="client_code" id="client_code" class="bg-light form-control @error('client_code') is-invalid @enderror"
                                value="{{ old('client_code', $edit->customer?->client_code ?? '') }}">
                            @error('client_code')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id', $edit->customer_id ?? '') }}">
                        </div>
                        @endif

                        {{-- Packing No --}}
                        <div class="col-md-3 mt-3">
                            <label>Packing Slip No <span class="text-danger">*</span></label>
                            <input type="text" readonly name="packing_slip_no" id="packing_slip_no" class="bg-light form-control @error('packing_slip_no') is-invalid @enderror"
                                value="{{ old('packing_slip_no', $edit->packing_slip_no ?? '') }}">
                            @error('packing_slip_no')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        {{-- Packing Slip Date --}}
                        <div class="col-md-3 mt-3">
                            <label class="form-label">Packing Slip Date <span
                                    class="text-danger">*</span></label>
                            <div style="position: relative;">
                                    <input type="text"
                                        id="packing_slip_date_display"
                                        class="bg-light form-control @error('packing_slip_date') is-invalid @enderror"
                                        value="{{ old('packing_slip_date')
                                            ? date('d-m-Y', strtotime(old('packing_slip_date')))
                                            : (isset($edit->packing_slip_date)
                                                    ? date('d-m-Y', strtotime($edit->packing_slip_date))
                                                    : date('d-m-Y')) }}"
                                        placeholder="DD-MM-YYYY"
                                        style="padding-right: 30px;"
                                        readonly />

                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                        <i class="fa fa-calendar" aria-hidden="true"></i>
                                    </span>
                            </div>

                            <input type="hidden" name="packing_slip_date" id="packing_slip_date"
                                value="{{ old('packing_slip_date')
                                    ? date('Y-m-d', strtotime(old('packing_slip_date')))
                                    : (isset($edit->packing_slip_date)
                                        ? date('Y-m-d', strtotime($edit->packing_slip_date))
                                        : date('Y-m-d')) }}" />
                        </div>

                        {{-- Sales Order No --}}
                        <div class="col-md-3 mt-3">
                            <label>Sales Order No</label>
                            <input type="text" readonly name="sales_order_no" id="sales_order_no" class="bg-light form-control @error('sales_order_no') is-invalid @enderror" value="{{ old('sales_order_no', $edit->sales_order_no ?? '') }}">
                            @error('sales_order_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        {{-- Sales Order Date --}}
                        <div class="col-md-3 mt-3">
                            <label class="form-label">Sales Order Date</label>
                            <div style="position: relative;">
                                    <input type="text"
                                        id="sales_order_date_display"
                                        class="bg-light form-control"
                                        placeholder="DD-MM-YYYY"
                                        style="padding-right: 30px;"
                                        value="{{ old('sales_order_date_display')
                                                ? date('d-m-Y', strtotime(old('sales_order_date_display')))
                                                : (isset($edit?->sales_order_date)
                                                        ? date('d-m-Y', strtotime($edit?->sales_order_date))
                                                        : '') }}"
                                        readonly />

                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                        <i class="fa fa-calendar" aria-hidden="true"></i>
                                    </span>
                            </div>

                            <input type="hidden" name="sales_order_date" id="sales_order_date"
                                value="{{ old('sales_order_date')
                                 ? date('d-m-Y', strtotime(old('sales_order_date')))
                                : (isset($edit?->sales_order_date)
                                        ? date('d-m-Y', strtotime($edit?->sales_order_date))
                                        : '') }}" />
                        </div>


                        <div class="col-md-3 mt-3">
                            <label>Pending Qty <span class="text-danger">*</span></label>
                            <input type="text" readonly  name="pending_qty" id="pending_qty" class="@error('pending_qty') is-invalid @enderror form-control bg-light" value="{{ old('pending_qty', $total_pending_qty ?? $total_pending_qty ?? '') }}" required readonly>
                            @error('pending_qty')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>



                        <div class="col-md-3 mt-3">
                            @if(isset($packing_slip_id) && $packing_slip_id)
                                <label>Warehouse</label>
                                <div class="form-control bg-light" style="border:1px solid #ddd;">
                                    {{ $edit->warehouse?->name ?? 'N/A' }}
                                </div>
                                <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $edit->warehouse_id }}">
                            @else
                                <label>Select Warehouse <span class="text-danger">*</span></label>
                                {{-- <select name="warehouse_id" id="warehouse_id"
                                    class="form-control @error('warehouse_id') is-invalid @enderror search_by_warehouse select2"
                                    data-append="search_by_warehouse" data-defaultfirst="finished goods"
                                    data-selectedWarehouseId="{{ isset($edit) && $edit?->warehouse_id ? $edit?->warehouse_id : '' }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('warehouse_id', $edit->warehouse_id ?? '') ? '' : 'selected' }}>
                                        Select Warehouse
                                    </option>
                                </select> --}}


                                 <select name="warehouse_id" id="warehouse_id"
                                    class="form-control @error('warehouse_id') is-invalid @enderror search_by_warehouse select2"
                                    data-append="search_by_warehouse"
                                    data-defaultfirst="finished goods"
                                    data-selectedWarehouseId="{{ old('warehouse_id', $edit->warehouse_id ?? '') }}"
                                    data-old-warehouse="{{ old('warehouse_id', '') }}"
                                    autofocus>

                                    <option value="" disabled
                                        {{ old('warehouse_id', $edit->warehouse_id ?? '') ? '' : 'selected' }}>
                                        Select Warehouse
                                    </option>
                                </select>
                                @error('warehouse_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            @endif
                        </div>

                    </div>
                    @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                    <!-- Left: Customer Details -->
                    <div class="col-md-6 mt-4">
                        <div class="mt-3">
                            <!-- Customer -->
                            <div class="col-8 mb-2">
                                <div class="row">
                                    <div class="col-4"><label><strong>Customer Code:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->customer ? $edit?->customer->client_code : 'N/A' }}</strong></p></div>
                                </div>
                            </div>
                            <!-- Name -->
                            <div class="col-8 mb-2 d-none">
                                <div class="row">
                                    <div class="col-4"><label><strong>Name:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : 'N/A' }}</strong></p></div>
                                </div>
                            </div>
                            <!-- Phone -->
                            <div class="col-8 mt-2 mb-2">
                                <div class="row">
                                    <div class="col-4"><label><strong>Phone:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->phone ? $edit?->phone : 'N/A' }}</strong></p></div>
                                </div>
                            </div>
                            <!-- Address -->
                            <div class="col-8 mt-2 mb-2">
                                <div class="row">
                                    <div class="col-4"><label><strong>Address:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->address ? $edit?->address : 'N/A' }}</strong></p></div>
                                </div>
                            </div>
                            <!-- State -->
                            <div class="col-8 mt-2 mb-2">
                                <div class="row">
                                    <div class="col-4"><label><strong>State:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->state ? $edit?->state : 'N/A' }}</strong></p></div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- right: shipping billing Details -->
                    <div class="col-md-3 mt-4">
                        <div class="mt-3">
                            <div class="col-8 mb-2">
                                <div class="row">
                                    <input type="hidden" name="shipping_address" id="shipping_address" value="{{ isset($edit) && $edit ? $edit?->shipping_address : '' }}">
                                    <div class="col-4"><label><strong>Shipping Address:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit ? $edit?->shipping_address : 'N/A' }}</strong></p></div>
                                </div>
                            </div>

                            <div class="col-8 mb-2">
                                <div class="row">
                                    <input type="hidden" name="billing_address" id="billing_address" value="{{ isset($edit) && $edit ? $edit?->billing_address : '' }}">
                                    <div class="col-4"><label><strong>Billing Address:</strong></label></div>
                                    <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit ? $edit?->billing_address : 'N/A' }}</strong></p></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-3 mt-4 d-none">
                        <div class="row">
                            <label class="col-5"><strong>Gross Wt. (gms) : </strong></label>
                            <div class="col-7">
                                <p class="mb-0"><span id="gross_wt_display">{{ old('gross_wt_display', $total_gross_wt ?? '') }}</span></p>
                            </div>

                            <label class="col-5 mt-1"><strong>Net Wt. (gms) : </strong></label>
                            <div class="col-7 mt-1">
                                <p class="mb-0"><span id="net_wt_display">{{ old('net_wt_display', $total_net_wt ?? '') }}</span></p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Customer Order Items Selection --}}
                {{-- @if (!isset($sales_order_id) && !isset($packing_slip_id)) --}}
                <div class="card mb-3" id="customer_order_items_selection" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Select Items to Add</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive table-scroll-x border">
                            <table class="table table-bordered mb-0" id="customer_order_items_table">
                                <thead class="table-light text-center align-middle">
                                    <tr>
                                        <th style="width: 50px;"><input type="checkbox" id="select_all_items"></th>
                                        <th style="width: 50px;">Sr No</th>
                                        <th style="width: 150px;">OA No</th>
                                        <th style="width: 150px;">OA Date</th>
                                        <th style="width: 250px;">Product Code</th>
                                        <th style="width: 320px;">Product Description</th>
                                        <th style="width: 100px;">Ordered</th>
                                        <th style="width: 100px;">Unit</th>
                                        <th style="width: 100px;">WT/PC</th>
                                        <th style="width: 120px;">Available Stock (KG)</th>
                                        <th style="width: 120px;">Available Stock (QTY)</th>
                                    </tr>
                                </thead>
                                <tbody id="customer_order_items_tbody">
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" id="add_selected_items_btn">
                                <i class="fa fa-plus"></i> Add Selected Items
                            </button>
                        </div>
                    </div>
                </div>
                {{-- @endif --}}

                {{-- Packing slip items --}}
                <div class="card mb-3" id="packing_slip_items_selection">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Packing Slip Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive table-scroll-x border"
                            style="overflow-x:auto; white-space:nowrap;">
                            <table class="table table-bordered mb-0" id="packing_slip_items" style="min-width:2000px;">
                                <thead class="table-light text-center align-middle">
                                    <tr>
                                        <th style="min-width: 50px;">Sr No</th>
                                        <th style="min-width: 100px;">OA No</th>
                                        <th style="min-width: 100px;">OA Date</th>
                                        <th style="min-width: 120px;">Product Code</th>
                                        <th style="min-width: 200px;">Product Description</th>
                                        <th style="min-width: 100px;">Ordered</th>
                                        <th style="min-width: 100px;">Unit</th>
                                        <th style="min-width: 80px;">WT/PC</th>
                                        <th style="min-width: 110px;">No of CTN</th>
                                        <th style="min-width: 130px;">Qty/PC</th>
                                        <th style="min-width: 130px;">PKTS PER CTN</th>
                                        <th style="min-width: 130px;">Qty/Ctn</th>
                                        <th style="min-width: 130px;">Actual WT/Ctn</th>
                                        <th style="min-width: 100px;">Bag/Carton</th>
                                        <th style="min-width: 130px;">Total Qty</th>
                                        <th style="min-width: 130px;">Total Weight</th>
                                        {{-- <input type="checkbox" id="select_all_packing_items"> --}}
                                        <th style="min-width: 130px;" class="adjustment_quantity_text">  Adjustment Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($edit) && $edit?->packing_slip_item)
                                    @foreach ($edit?->packing_slip_item as $item)
                                            <tr data-item-id="{{ $item->sales_order_item_id }}">
                                                <input type="hidden" name="is_combo[]" value="{{ $item->is_combo }}"/>
                                                <input type="hidden" name="sales_order_item_id[]" value="{{ $item->sales_order_item_id }}">
                                                <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                <input type="hidden" name="product_variant_price_id[]" value="{{ $item->product_variant_price_id }}">
                                                <input type="hidden" name="remaining_quantity[]" value="{{ $item->sales_order_item->remaining_quantity }}">
                                                <input type="hidden" name="hsn_code[]" value="{{ $item->hsn_code }}">
                                                <input type="hidden" name="product_description[]" value="{{ $item->product_description }}">


                                                <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                                <td><label class="sales_order_no_txt">{{ $item->sales_order_no }}</label></td>
                                                <td><label class="sales_order_date_txt">{{ $item->sales_order_date }}</label></td>
                                                <td>
                                                    @php
                                                        $editProdPartNo = $item->product?->part_no ?? $item->product_code ?? '';
                                                        $editProdName = $item->product ? ($item->product->product_name ?: $item->product->product_description) : ($item->product_name ?? '');
                                                        $custItemInfo = '';
                                                        $psCustomer = $edit->customer ?? ($edit->sales_order->customer ?? null);
                                                        if ($psCustomer && $psCustomer->customer_name) {
                                                            $cItem = \App\Models\ProductCustomerItem::where('company_id', $edit->company_id)
                                                                ->where('product_id', $item->product_id)
                                                                ->where('customer_name', $psCustomer->customer_name)
                                                                ->whereNull('deleted_at')
                                                                ->first();
                                                            if ($cItem) {
                                                                $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                                                $custDesc = trim($cItem->customer_item_description ?? '');
                                                                $custParts = array_filter([$custCode, $custDesc]);
                                                                $custItemInfo = implode(' ', $custParts);
                                                            }
                                                        }
                                                        $displayCode = $editProdPartNo;
                                                        if ($isDhruvik && !empty($custItemInfo)) {
                                                            $displayCode .= ' (' . $custItemInfo . ')';
                                                        } elseif (empty($editProdPartNo) || $editProdPartNo === '-') {
                                                            $displayCode = $item->product_code ?? '';
                                                            if (!$isDhruvik && !empty($displayCode)) {
                                                                $displayCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayCode);
                                                            }
                                                        }
                                                    @endphp
                                                    <label class="product_code_txt">{{ $displayCode }}</label>
                                                    <input type="hidden" name="product_code[]" value="{{ $item->product_code }}">
                                                </td>
                                                <td>
                                                    <label class="product_name_txt">{{ $editProdName }}</label>
                                                    <input type="hidden" name="product_name[]" value="{{ $editProdName }}">
                                                </td>
                                                <td>
                                                    <label class="ordered_qty_txt">{{ $item->sales_order_item->quantity }}</label>
                                                    <input type="hidden" name="quantity[]" value="{{ $item->available_stock }}">
                                                </td>

                                                <td class="unit-cell" style="display: flex; align-items: center; gap: 6px;">
                                                    <label>{{ $item->sales_order_item->unit ?? '' }}</label>
                                                    <input type="hidden" name="unit[]" class="form-control calc-field" value="{{ $item->sales_order_item->unit ?? '' }}">
                                                    @if (!empty($item->sales_order_item->unit) && strtolower($item->sales_order_item->unit) === 'gross')
                                                        <span class="badge bg-secondary" style="font-size:12px;">
                                                            qty : {{ $item->product->grosse_per_qty ?? 0 }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <label class="wt_pc_txt">{{ $item->product->net_wt }}</label>
                                                    <input type="hidden" name="wt_pc[]" value="{{ $item->product->net_wt }}">
                                                </td>
                                                 <td><input type="text" name="no_of_carton[]" class="form-control calc-field" value="{{ $item->no_of_carton }}" onkeypress="return isNumber(event)"></td>
                                                <td><input type="text" name="qty_pc[]" class="form-control calc-field" value="{{ $item->qty_pc }}" placeholder="QTY PER PC" onkeypress="return isDecimal(event)"></td>
                                                <td><input type="text" name="total_packet[]" class="form-control calc-field" value="{{ $item->total_packet }}" placeholder="PKTS PET CTN" onkeypress="return isDecimal(event)"></td>
                                                <td><input type="text" name="qty_ctn[]" class="form-control bg-light" value="{{ $item->qty_ctn }}" readonly></td>
                                                <td><input type="text" name="actual_wt_ctn[]" class="form-control bg-light" value="{{ $item->actual_wt_ctn }}" readonly></td>
                                                <td><input type="text" name="box_no[]" class="form-control calc-field bag-carton-input" value="{{ $item->box_no }}" placeholder="Cartoon Number" onkeypress="return isNumber(event)"></td>
                                                <td><input type="hidden" name="db_total_qty[]" value="{{ $item->total_qty }}"/><input type="text" name="total_qty[]" class="form-control calc-field bg-light" value="{{ $item->total_qty }}" readonly></td>
                                                <td><input type="hidden" name="db_total_weight[]" value="{{ $item->total_weight }}"/><input type="text" name="total_weight[]" class="form-control calc-field bg-light" value="{{ $item->total_weight }}" readonly></td>
                                                <td class="text-center adjustment_quantity_text">
                                                    <input type="hidden" name="packing_selected[{{ $loop->index }}]" value="0">
                                                    <input type="checkbox"
                                                        class="item-packing-checkbox"
                                                        name="packing_selected[{{ $loop->index }}]"
                                                        value="{{ $item->sales_order_item_id }}"
                                                        {{ $item->qty_pc > 0 ? 'checked' : '' }}>
                                                </td>
                                                <td><button type="button" class="remove_packing_slip_item btn btn-danger btn-sm">✖</button></td>
                                            </tr>
                                    @endforeach
                                    @endif
                                </tbody>

                                <tfoot>
                                    <tr>
                                        <td colspan="8" class="text-end"><strong></strong></td>
                                        <td><input type="text" name="total_no_of_carton" id="total_no_of_carton" class="form-control bg-light" readonly></td>
                                        <td colspan="2" class="text-end"><strong></strong></td>
                                        <td><input type="text" id="sum_qty_ctn" class="form-control bg-light" readonly></td>
                                        <td><input type="text" id="sum_actual_wt_ctn" class="form-control bg-light" readonly></td>
                                        <td></td>
                                        <td><input type="text" id="sum_total_qty" class="form-control bg-light" readonly></td>
                                        <td><input type="text" id="sum_total_weight" class="form-control bg-light" readonly></td>
                                        <td></td>
                                    </tr>

                                </tfoot>

                            </table>
                        </div>
                    </div>
                </div>


                {{-- SUBMIT BUTTON --}}



                @php
                    $showButtons = !isset($edit) || !empty($edit->created_by);
                @endphp

                @if ($showButtons)
                <div class="text-center mb-3">
                    <button type="submit" class="btn btn-primary" id="submit_btn">Submit</button>
                    <a href="{{ route($route . '.index') }}" class="btn btn-secondary">Back</a>
                </div>
                @endif

            </div>
        </form>
    </div>
</div>
@endsection


@push('page_scripts')
 @if (!$company_id)
@include('utils.getCompany')
@endif
@include('utils.getWarehouse')
@include('utils.getCustomer')
@include('utils.getCustomerSales')
@if ($generatePackingSlipNo)
@include('utils.generatePackingSlipNo')
@endif
<script>
    initializeCKEditor(".ckeditor_common_cls");
    var customerOrderItemsData = [];

    var customerId = $("#customer_id").val();
    var companyId = $('input[name="company_id"]').val() || $('#company_id').val();
    if (customerId && companyId) {
        get_customer_order_items(customerId, companyId);
    }

    $(document).on('change', '#warehouse_id', function() {
        var customerId = $("#customer_id").val();
        var companyId = $('input[name="company_id"]').val() || $('#company_id').val();

        if (customerId && companyId) {
            get_customer_order_items(customerId, companyId);
        }
    });

    // Handle customer selection for new packing slip
    @if (!isset($sales_order_id) && !isset($packing_slip_id))
    $(document).on('change', '#customer_id', function() {
        var customerId = $(this).val();
        var companyId = $('input[name="company_id"]').val() || $('#company_id').val();

        if (customerId && companyId) {
            get_customer_order_items(customerId, companyId);

            // Auto-fill customer details
            $.ajax({
                url: "{{ env('API_URL') }}get-customer",
                type: 'POST',
                dataType: 'json',
                data: {
                    company_id: companyId,
                    customer_id: customerId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status && response.data && response.data.length > 0) {
                        var customer = response.data[0];
                        $('#client_code').val(customer.client_code || '');
                        $('input[name="customer_name"]').val(customer.customer_name || '');
                        $('input[name="contact_person_name"]').val(customer.contact_person_name || '');
                        $('input[name="phone"]').val(customer.mobile_number || '');
                        $('input[name="state"]').val(customer.state_name || '');
                        $('input[name="address"]').val(customer.address || '');
                    }
                }
            });
        } else {
            $('#packing_slip_items tbody').html('');
        }
    });
    @endif

    // Store customer order items data globally
    var customerOrderItemsData = [];

    function get_customer_order_items(customerId, companyId) {
        $('#customer_order_items_selection').hide();
        var packing_slip_id = $('#packing_slip_id').val();
        if(packing_slip_id){
            $('#packing_slip_items').show();
             $('.item-packing-checkbox').prop('checked', false);
        }else{
            $('#packing_slip_items').hide();
        }

        var requestData = {
            customer_id: customerId,
            company_id: companyId,
            _token: "{{ csrf_token() }}"
        };

        var warehouse_id = $('#warehouse_id').val();
        // if (!warehouse_id) {
        //     warehouse_id = $('#warehouse_id').attr("data-selectedwarehouseid");
        //     if (!warehouse_id) {
        //         toastr.error('Please select Warehouse.');
        //         return;
        //     }
        // }

        if (warehouse_id && warehouse_id !== "") {
            requestData.warehouse_id = warehouse_id;
        }

        $.ajax({
            url: "{{ route($route.'.get-customer-order-items') }}",
            type: 'POST',
            dataType: 'json',
            data: requestData,
            success: function(response) {
                customerOrderItemsData = response.items || [];
                var total_gross_wt = response.total_gross_wt || 0;
                var total_net_wt = response.total_net_wt || 0;
                var total_pending_qty = response.total_pending_qty || 0;

                $("#gross_wt_display").text(total_gross_wt);
                $("#net_wt_display").text(total_net_wt);
                $("#pending_qty").val(total_pending_qty);

                let pendingQty = parseFloat(total_pending_qty);
                if (pendingQty <= 0) {
                    $("#select_all_packing_items").parent('th').hide();
                    $(".item-packing-checkbox").parent('td').hide();
                }else{
                    $("#select_all_packing_items").parent('th').show();
                    $(".item-packing-checkbox").parent('td').show();
                }

                // Display items in selection table
                var selectionRows = '';
                $.each(customerOrderItemsData, function(index, item) {
                    let srNo = index + 1;
                    let productName = item.product_name || '';

                    var orderedQty = item.ordered_quantity || item.quantity || 0;
                    var is_combo = item.is_combo || 0;
                    var remainingQty = item.remaining_quantity || 0;
                    var availableStock = item.available_stock || 0;
                    var salesOrderNo = (item.sales_order && item.sales_order.sales_order_no) ? item.sales_order.sales_order_no : '';
                    var productDescription = (item.product_description) ? item.product_description : '';
                    var salesOrderDate = (item.sales_order && item.sales_order.formatted_sales_order_date) ? item.sales_order.formatted_sales_order_date : '';
                    var productCode = item.display_product_code || (item.product && item.product.part_no ? item.product.part_no : (item.product_code || ''));
                    if (item.customer_item_code && item.customer_item_code !== productCode && !productCode.includes('(')) {
                        productCode += ' (' + item.customer_item_code + ')';
                    }

                    if(is_combo == 0){
                        var wtPcVal = (item.product.net_wt == null || item.product.net_wt === "") ? "" : item.product.net_wt;
                    }else{
                        var wtPcVal = (item.net_wt == null || item.net_wt === "") ? "" : item.net_wt;
                    }

                    selectionRows += '<tr data-item-id="' + item.id + '">';
                    selectionRows += '<td class="text-center"><input type="checkbox" class="item-checkbox" value="' + item.id + '"></td>';
                    selectionRows += '<td class="text-center sr_no_txt">' + srNo + '</td>';
                    selectionRows += '<td><label class="sales_order_no_txt">'+salesOrderNo+'</label></td>';
                    selectionRows += '<td><label class="sales_order_date_txt">'+salesOrderDate+'</label></td>';
                    selectionRows += '<td><label class="product_code_txt">'+productCode+'</label></td>';
                    selectionRows += '<td><label class="product_name_txt">'+productName+'</label></td>';
                    selectionRows += '<td><label class="ordered_qty_txt">'+orderedQty+'</label></td>';
                    selectionRows += '<td class="unit-cell"><label>'+ (item.unit || '') +'</label></td>';
                    selectionRows += '<td><label class="wt_pc_txt">'+wtPcVal+'</label></td>';
                    selectionRows += '<td><label class="available_stock_kg_txt">'+item.available_stock_kg+'</label></td>';
                    selectionRows += '<td><label class="available_stock_qty_txt">'+item.available_stock_qty+'</label></td>';
                    selectionRows += '</tr>';
                });

                $('#customer_order_items_tbody').html(selectionRows);
                if (customerOrderItemsData.length > 0) {
                    $('#customer_order_items_selection').show();
                } else {
                    toastr.info('No items found for this customer');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching customer order items:', error);
                toastr.error('Failed to load customer order items');
            }
        });
    }

    // Select all items
    $(document).on('change', '#select_all_items', function() {
        $('.item-checkbox').prop('checked', $(this).prop('checked'));
    });


    function calculateTotals() {
        setTimeout(function () {
            let rows = $('#packing_slip_items tbody tr');
            if (rows.length == 0) {
                $("#sum_qty_ctn").val('0.000');
                $("#sum_actual_wt_ctn").val('0.000');
                $("#sum_total_qty").val('0.000');
                $("#sum_total_weight").val('0.000');
                $("#total_no_of_carton").val('0');
                return;
            }
        }, 300);

        let sumQtyCtn = 0;
        let sumActualWtCtn = 0;
        let sumTotalQty = 0;
        let sumTotalWeight = 0;
        let finalCartonNo = 0;

        $('#packing_slip_items tbody tr').each(function () {
            let qtyCtn = parseFloat($(this).find('input[name="qty_ctn[]"]').val()) || 0;
            let actualWtCtn = parseFloat($(this).find('input[name="actual_wt_ctn[]"]').val()) || 0;
            let totalQty = parseFloat($(this).find('input[name="total_qty[]"]').val()) || 0;
            let totalWeight = parseFloat($(this).find('input[name="total_weight[]"]').val()) || 0;
            let bagCarton = parseFloat($(this).find('input[name="box_no[]"]').val()) || 0;
            let noOfCarton = parseFloat($(this).find('input[name="no_of_carton[]"]').val()) || 0;

            let finalCartonNo = (noOfCarton + bagCarton) - 1;
            if (!isNaN(finalCartonNo) && finalCartonNo > 0) {
                $("#total_no_of_carton").val(finalCartonNo);
            }
            sumQtyCtn += qtyCtn;
            sumActualWtCtn += actualWtCtn;
            sumTotalQty += totalQty;
            sumTotalWeight += totalWeight;
        });

        $("#sum_qty_ctn").val(sumQtyCtn.toFixed(3));
        $("#sum_actual_wt_ctn").val(sumActualWtCtn.toFixed(3));
        $("#sum_total_qty").val(sumTotalQty.toFixed(3));
        $("#sum_total_weight").val(sumTotalWeight.toFixed(3));

    }





    $(document).on("input", ".calc-field", function () {
        calculateTotals();
    });

    $(document).ready(function () {
        calculateTotals();
    });


    $(document).on('change', '#select_all_packing_items', function() {
        $('.item-packing-checkbox').prop('checked', $(this).prop('checked'));
    });


    $(document).ready(function () {
        let pendingQty = parseFloat($("#pending_qty").val()) || 0;
        if (pendingQty <= 0) {
            $(".adjustment_quantity_text").hide();
            $(".item-packing-checkbox").parent('td').hide();
        } else {
            $(".adjustment_quantity_text").show();
            $(".item-packing-checkbox").parent('td').show();
        }
    });


    // Add selected items to packing slip table
    $(document).on('click', '#add_selected_items_btn', function() {
        var selectedItems = [];
        $('.item-checkbox:checked').each(function() {
            var itemId = $(this).val();
            selectedItems.push(itemId);
        });
        console.log("selectedItems", selectedItems);
        var warehouse_id = $('#warehouse_id').val();
        if (!warehouse_id) {
            warehouse_id = $('#warehouse_id').attr("data-selectedwarehouseid");
            if (!warehouse_id) {
                toastr.error('Please select Warehouse.');
                return;
            }
        }

        if (selectedItems.length === 0) {
            toastr.error('Please select at least one item to add');
            return;
        }

        // Check for duplicates
        var existingItemIds = [];
        $('#packing_slip_items tbody tr').each(function() {
            var existingId = $(this).find('input[name="sales_order_item_id[]"]').val();
            if (existingId) {
                existingItemIds.push(existingId.toString());
            }
        });


        let pendingQty = parseFloat($("#pending_qty").val());
        if (pendingQty <= 0) {
            $(".adjustment_quantity_text").hide();
            $(".item-packing-checkbox").parent('td').hide();
        }else{
            $(".adjustment_quantity_text").show();
            $(".item-packing-checkbox").parent('td').show();
        }


        var grosse_per_qty = 0;
        $.each(selectedItems, function(index, selectedItemId) {
            var newRows = '';
            let rowIndex = $('#packing_slip_items tbody tr').length;

            // Find item in customerOrderItemsData
            var item = customerOrderItemsData.find(function(i) {
                return i.id.toString() === selectedItemId.toString();
            });

            if (!item) return true;

            let productName = item.product_name || '';
            let is_combo = item.is_combo || 0;
            // Escape quotes for HTML attributes to preserve original product name
            let productNameEscaped = productName.replace(/"/g, '&quot;');

            var salesOrderNo = (item.sales_order && item.sales_order.sales_order_no) ? item.sales_order.sales_order_no : '';
            var salesOrderDate = (item.sales_order && item.sales_order.formatted_sales_order_date) ? item.sales_order.formatted_sales_order_date : '';
            var productCode = item.display_product_code || ((item.product && item.product.part_no) ? item.product.part_no : (item.product_code || ''));
            if (item.customer_item_code && item.customer_item_code !== productCode && !productCode.includes('(')) {
                productCode += ' (' + item.customer_item_code + ')';
            }
            if (item.product && item.product.grosse_per_qty) {
                grosse_per_qty = item.product.grosse_per_qty;
            }

            if(is_combo == 0){
                var wtPcVal = (item.product && item.product.net_wt) ? item.product.net_wt : "";
            }else{
                var wtPcVal = (item.net_wt == null || item.net_wt === "") ? "" : item.net_wt;
            }

            var topRow = $('#customer_order_items_table tr[data-item-id="' + selectedItemId + '"]');
            var orderedQty = parseFloat(topRow.find('.ordered_qty_txt').text()) || 0;

            let cartonValue = '';
            if (cartonStarted && index === 0) {
                cartonValue = getLastCartonNumber();
            }

            if(orderedQty >= 0){
                let srNo = $('#packing_slip_items tbody tr').length + 1;
                newRows += '<tr data-item-id="' + item.id + '">';
                newRows += '<input type="hidden" name="is_combo[]" value="'+item.is_combo+'"/><input type="hidden" name="sales_order_item_id[]" value="' + item.id + '">';
                newRows += '<td class="text-center sr_no_txt">' + srNo + '</td>';
                newRows += '<input type="hidden" name="product_id[]" value="' + item.product_id + '">';
                newRows += '<input type="hidden" name="product_variant_price_id[]" value="' + (item.product_variant_price_id || '') + '">';
                newRows += '<input type="hidden" name="remaining_quantity[]" value="' + item.remaining_quantity + '">';
                newRows += '<input type="hidden" name="hsn_code[]" value="' + (item.hsn_code || '') + '">';
                newRows += '<input type="hidden" name="product_description[]" value="' + (item.product_description || '') + '">';


                newRows += '<td><label class="sales_order_no_txt">' + salesOrderNo + '</label></td>';
                newRows += '<td><label class="sales_order_date_txt">' + salesOrderDate + '</label></td>';
                newRows += '<td><label class="product_code_txt">' + productCode + '</label><input type="hidden" name="product_code[]" value="' + productCode + '"></td>';
                newRows += '<td><label class="product_name_txt">' + productName + '</label><input type="hidden" name="product_name[]" value="' + productNameEscaped + '"></td>';
                newRows += '<td><label class="ordered_qty_txt">' + orderedQty + '</label><input type="hidden" name="quantity[]" value="' + orderedQty + '"></td>';
                newRows += '<td class="unit-cell"><label>' + (item.unit || '') + '</label><input type="hidden" name="unit[]" class="form-control calc-field" value="' + (item.unit || '') + '"></td>';
                newRows += '<td><label class="wt_pc_txt">' + wtPcVal + '</label><input type="hidden" name="wt_pc[]" value="' + wtPcVal + '"></td>';
                newRows += '<td><input type="text" name="no_of_carton[]" class="form-control calc-field" value="'+cartonValue+'" onkeypress="return isNumber(event)"></td>';
                newRows += '<td><input type="text" name="qty_pc[]" class="form-control calc-field" value="" placeholder="QTY PER PC" onkeypress="return isDecimal(event)"></td>';
                newRows += '<td><input type="text" name="total_packet[]" class="form-control calc-field" value="" placeholder="PKTS PET CTN" onkeypress="return isDecimal(event)"></td>';
                newRows += '<td><input type="text" name="qty_ctn[]" class="form-control calc-field bg-light" value="" readonly></td>';
                newRows += '<td><input type="text" name="actual_wt_ctn[]" class="form-control calc-field bg-light" value="" readonly></td>';
                newRows += '<td><input type="text" name="box_no[]" class="form-control calc-field bag-carton-input" value="" placeholder="Cartoon Number" onkeypress="return isNumber(event)"></td>';
                newRows += '<td><input type="text" name="total_qty[]" class="form-control calc-field bg-light" value="" readonly></td>';
                newRows += '<td><input type="text" name="total_weight[]" class="form-control calc-field bg-light" value="" readonly></td>';
                newRows += `
                        <td class="text-center">
                            <input type="hidden" name="packing_selected[${rowIndex}]" value="0">
                            <input type="checkbox" class="item-packing-checkbox"
                                name="packing_selected[${rowIndex}]" value="1">
                        </td>`;
                newRows += '<td><button type="button" class="remove_packing_slip_item btn btn-danger btn-sm">✖</button></td>';
                newRows += '</tr>';

                $('#packing_slip_items tbody').append(newRows);
            }
        });


        $('#packing_slip_items tbody tr').each(function() {
            let unitType = $(this).find('input[name="unit[]"]').val();
            if (unitType && unitType.toLowerCase() === "gross" && grosse_per_qty > 0) {
                let $unitCell = $(this).find('.unit-cell');
                if ($unitCell.find('.badge').length === 0) {
                    $unitCell.css({
                        display: "flex",
                        "align-items": "center",
                        gap: "6px"
                    });
                    $unitCell.append(`
                        <span class="badge bg-secondary" style="font-size:12px;">
                            qty : ${grosse_per_qty}
                        </span>
                    `);
                }
            }
        });

        recalculateRow();
        $('#packing_slip_items').show();
        $('.item-checkbox').prop('checked', false);
        $('#select_all_items').prop('checked', false);
        toastr.success('Items added successfully');
        savePackingSlipToLocalStorage();
    });

    let cartonStarted = false;
    $(document).on('keyup', 'input[name="no_of_carton[]"]', function () {
        let val = parseInt($(this).val());
        if (!isNaN(val)) {
            cartonStarted = true;
        }
    });

    function getLastCartonNumber() {
        let last = 0;

        $('#packing_slip_items tbody tr').each(function () {
            let cartonVal = parseInt($(this).find('input[name="no_of_carton[]"]').val());
            let boxVal = parseInt($(this).find('input[name="box_no[]"]').val());

            cartonVal = isNaN(cartonVal) ? 0 : cartonVal;
            boxVal = isNaN(boxVal) ? 0 : boxVal;

            let total = cartonVal + boxVal;

            if (total > last) {
                last = total;
            }
        });

        return last;
    }

    function recalculateRow() {
        $('#packing_slip_items').on('input', '.calc-field', function() {
            var $row = $(this).closest('tr');
            var itemId = $row.data('item-id');

            var no_of_carton = parseFloat($row.find('input[name="no_of_carton[]"]').val()) || 0;
            var box_no = parseFloat($row.find('input[name="box_no[]"]').val()) || 0;
            var wt_pc = parseFloat($row.find('input[name="wt_pc[]"]').val()) || 0;
            var qty_pc = parseFloat($row.find('input[name="qty_pc[]"]').val()) || 0;
            var total_packet = parseFloat($row.find('input[name="total_packet[]"]').val()) || 0;
            var remaining_quantity = parseFloat($row.find('input[name="remaining_quantity[]"]').val()) || 0;

            var ordered_qty = parseFloat($row.find('input[name="quantity[]"]').val()) || 0;
            var unitType = ($row.find('input[name="unit[]"]').val() || "").toLowerCase();

            if(qty_pc != '' && total_packet != ''){
                var qty_ctn = qty_pc * total_packet;
                
                // For KG unit, don't multiply by box_no; for other units (pcs, gross), multiply by box_no
                // var total_qty = (unitType === 'kg') ? qty_ctn : (qty_ctn * box_no);
                var total_qty = qty_ctn * box_no;
                
                var actual_wt_ctn = (qty_ctn * wt_pc) / 1000;
                $row.find('input[name="qty_ctn[]"]').val(qty_ctn.toFixed(2));
                $row.find('input[name="actual_wt_ctn[]"]').val(actual_wt_ctn.toFixed(3));
                $row.find('input[name="total_qty[]"]').val(total_qty.toFixed(2));

                var actual_wt_ctn_cal = parseFloat($row.find('input[name="actual_wt_ctn[]"]').val()) || 0;
                
                // For KG unit, use total_qty; for other units (pcs, gross), calculate weight
                var total_weight = (unitType === 'kg') ? total_qty : (actual_wt_ctn_cal * box_no);
                
                $row.find('input[name="total_weight[]"]').val(total_weight.toFixed(3));

                let newRemainingQty = 0;
                newRemainingQty = ordered_qty - total_qty;
                newRemainingQty = Math.max(0, newRemainingQty);

                let topRow = $('#customer_order_items_table tr[data-item-id="' + itemId + '"]');
                topRow.find('.ordered_qty_txt').text(newRemainingQty.toFixed(3));
            }
        });
    }

    $(document).on('click', '.remove_packing_slip_item', function() {
        var rowCount = $('#packing_slip_items tbody tr').length;
        if (rowCount > 1) {
            $(this).closest('tr').remove();
            recalculateRow();
            calculateTotals();
        } else {
            calculateTotals();
            toastr.error('At least one row is required.');
        }
    });

    var packing_slip_id = $('#packing_slip_id').val();
    if (packing_slip_id) {
        recalculateRow();
    }

    function getWarehouseId() {
        return $('#warehouse_id').val() || $('#warehouse_id').data('old-warehouse');
    }


    function getPackingSlipStorageKey() {
        let warehouseId = getWarehouseId();
        return warehouseId;
    }


    function savePackingSlipToLocalStorage() {
        let storageKey = getPackingSlipStorageKey();
        if (!storageKey) return;

        let rows = [];

        $('#packing_slip_items tbody tr').each(function () {
            let row = {};

            $(this).find('input, select, textarea').each(function () {
                let name = $(this).attr('name');
                if (!name) return;

                if ($(this).attr('type') === 'checkbox') {
                    row[name] = $(this).is(':checked') ? 1 : 0;
                } else {
                    row[name] = $(this).val();
                }
            });

            row.sales_order_no_txt   = $(this).find('.sales_order_no_txt').text().trim();
            row.sales_order_date_txt = $(this).find('.sales_order_date_txt').text().trim();
            row.product_code_txt     = $(this).find('.product_code_txt').text().trim();
            row.product_name_txt     = $(this).find('.product_name_txt').text().trim();
            row.ordered_qty_txt      = $(this).find('.ordered_qty_txt').text().trim();
            row.wt_pc_txt            = $(this).find('.wt_pc_txt').text().trim();

            rows.push(row);
        });

        localStorage.setItem(storageKey, JSON.stringify(rows));
    }


    function restorePackingSlipFromLocalStorage() {
        let storageKey = getPackingSlipStorageKey();
        if (!storageKey) return;

        let saved = localStorage.getItem(storageKey);
        if (!saved) return;

        let rows = JSON.parse(saved);
        $('#packing_slip_items tbody').empty();

        rows.forEach((row, index) => {

            let newRow = `
            <tr>
                <td class="text-center sr_no_txt">${index + 1}</td>

                <input type="hidden" name="is_combo[]" value="${row['is_combo[]'] || ''}">
                <input type="hidden" name="sales_order_item_id[]" value="${row['sales_order_item_id[]'] || ''}">
                <input type="hidden" name="product_id[]" value="${row['product_id[]'] || ''}">
                <input type="hidden" name="product_variant_price_id[]" value="${row['product_variant_price_id[]'] || ''}">
                <input type="hidden" name="remaining_quantity[]" value="${row['remaining_quantity[]'] || ''}">
                <input type="hidden" name="hsn_code[]" value="${row['hsn_code[]'] || ''}">
                <input type="hidden" name="product_description[]" value="${row['product_description[]'] || ''}">

                <td><label>${row.sales_order_no_txt || ''}</label></td>
                <td><label>${row.sales_order_date_txt || ''}</label></td>

                <td>
                    <label>${row['product_code[]'] || ''}</label>
                    <input type="hidden" name="product_code[]" value="${row['product_code[]'] || ''}">
                </td>

                <td>
                    <label>${row['product_name[]'] || ''}</label>
                    <input type="hidden" name="product_name[]" value="${row['product_name[]'] || ''}">
                </td>

                <td>
                    <label>${row['quantity[]'] || ''}</label>
                    <input type="hidden" name="quantity[]" value="${row['quantity[]'] || ''}">
                </td>

                <td class="unit-cell">
                    <label>${row['unit[]'] || ''}</label>
                    <input type="hidden" name="unit[]" value="${row['unit[]'] || ''}">
                </td>

                <td>
                    <label>${row['wt_pc[]'] || ''}</label>
                    <input type="hidden" name="wt_pc[]" value="${row['wt_pc[]'] || ''}">
                </td>

                <td><input type="text" class="form-control calc-field" name="no_of_carton[]" value="${row['no_of_carton[]'] || ''}"></td>
                <td><input type="text" class="form-control calc-field" name="qty_pc[]" value="${row['qty_pc[]'] || ''}"></td>
                <td><input type="text" class="form-control calc-field" name="total_packet[]" value="${row['total_packet[]'] || ''}"></td>
                <td><input type="text" class="form-control bg-light" readonly name="qty_ctn[]" value="${row['qty_ctn[]'] || ''}"></td>
                <td><input type="text" class="form-control bg-light" readonly name="actual_wt_ctn[]" value="${row['actual_wt_ctn[]'] || ''}"></td>
                <td><input type="text" class="form-control calc-field" name="box_no[]" value="${row['box_no[]'] || ''}"></td>
                <td><input type="text" class="form-control bg-light" readonly name="total_qty[]" value="${row['total_qty[]'] || ''}"></td>
                <td><input type="text" class="form-control bg-light" readonly name="total_weight[]" value="${row['total_weight[]'] || ''}"></td>

                <td class="text-center">
                    <input type="hidden" name="packing_selected[${index}]" value="0">
                    <input type="checkbox" name="packing_selected[${index}]"
                        ${row[`packing_selected[${index}]`] == 1 ? 'checked' : ''}>
                </td>

                <td>
                    <button type="button" class="remove_packing_slip_item btn btn-danger btn-sm">✖</button>
                </td>
            </tr>
            `;

            $('#packing_slip_items tbody').append(newRow);

            if ((row['unit[]'] || '').toLowerCase() === 'gross' && row['grosse_per_qty[]']) {
                let $cell = $('#packing_slip_items tbody tr:last .unit-cell');
                $cell.css({ display: 'flex', gap: '6px', 'align-items': 'center' });
                $cell.append(`<span class="badge bg-secondary">qty : ${row['grosse_per_qty[]']}</span>`);
            }
        });

        setTimeout(() => {
            recalculateRow();
            calculateTotals();
        }, 300);

        $('#packing_slip_items').show();

        let pendingQty = parseFloat($("#pending_qty").val()) || 0;
        if (pendingQty <= 0) {
            $(".adjustment_quantity_text").hide();
            $(".item-packing-checkbox").parent('td').hide();
        } else {
            $(".adjustment_quantity_text").show();
            $(".item-packing-checkbox").parent('td').show();
        }
    }


    $(document).on('input change', '#packing_slip_items input, #packing_slip_items select', function () {
        savePackingSlipToLocalStorage();
    });


    $(document).on('click', '.remove_packing_slip_item', function () {
        $(this).closest('tr').remove();

        $('#packing_slip_items tbody tr').each(function (i) {
            $(this).find('.sr_no_txt').text(i + 1);
        });

        savePackingSlipToLocalStorage();
    });


    $(document).on('change', '#warehouse_id', function () {
        $('#packing_slip_items tbody').empty();
        $('#packing_slip_items').hide();

        setTimeout(() => {
            restorePackingSlipFromLocalStorage();
        }, 100);
    });


    $(document).ready(function () {
        let warehouseId = getWarehouseId();
        if (warehouseId) {
            restorePackingSlipFromLocalStorage();
        }
    });
</script>


@if (!$errors->any())
<script>
    localStorage.clear();
</script>
@endif

@endpush
