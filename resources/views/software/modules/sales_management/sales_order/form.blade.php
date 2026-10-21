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

        #quotation-items-table.table> :not(caption)>*>* {
            padding: 0.15rem 0.40rem;
        }
    </style>

    <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
            @php
                if (isset($quotation_id) && $quotation_id) {
                    $breadcrumbTitle = 'Quotation to ' . $page_title;
                } elseif (isset($edit) && $edit?->id) {
                    $breadcrumbTitle = 'Edit ' . $page_title;
                } else {
                    $breadcrumbTitle = 'Create ' . $page_title;
                }
            @endphp


            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [
                    ['title' => $page_title, 'url' => route($route . '.index')],
                    [
                        'title' => isset($edit) && $edit?->id ? 'Edit ' . $page_title : 'Create ' . $page_title,
                        'url' => '',
                    ],
                ],
            ])
            <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}">
                <i class="menu-icon ti ti-chevrons-left"></i> Back
            </a>
        </div>
    </div>


    <div class="card my-3 mb-4">
        <div class="card-body">
            @if (isset($edit) && !empty($edit->quotation_id) && $edit->quotation_id > 0)
                <form id="salesOrderForm" action="{{ route($route . '.update', [$edit?->id]) }}" method="POST">
                @else
                    <form id="salesOrderForm"
                        action="{{ isset($quotation_id) && $quotation_id
                            ? route($route . '.store')
                            : (isset($edit) && $edit?->id
                                ? route($route . '.update', [$edit?->id])
                                : route($route . '.store')) }}"
                        method="POST">
            @endif

            @csrf
            @isset($edit)
                @if (empty($quotation_id) || !empty($edit?->quotation_id))
                    @method('PUT')
                @endif
            @endisset

            <input type="hidden" name="customer_name" id="customer_name"
                value="{{ isset($edit) && $edit?->id ? $edit?->customer_name : '' }}" />
            <input type="hidden" name="contact_person_name" id="contact_person_name"
                value="{{ isset($edit) && $edit?->id ? $edit?->contact_person_name : '' }}" />
            <input type="hidden" name="price_list_id" id="price_list_id"
                value="{{ isset($edit) && $edit?->id ? $edit?->price_list_id : '' }}" />
            <input type="hidden" name="quotation_id" id="quotation_id"
                value="{{ isset($quotation_id) && $quotation_id ? $quotation_id : (isset($edit) && $edit?->id ? $edit?->quotation_id : '') }}" />
            <input type="hidden" id="qty_is_value" value="" />
            <input type="hidden" name="state" id="state" value="{{ isset($edit) && $edit?->id ? $edit?->state : '' }}" />
            <input type="hidden" id="is_igst" name="is_igst"
                value="{{ isset($edit) && $edit?->id ? $edit?->customer->is_igst : '' }}" />

            <div class="">
                <!-- Section 1: Customer Details -->
                <h4 class="mb-3">Customer Details</h4>
                <div class="mt-minus-20 text-end">
                    @if (!empty($salesOrderRepeatCount))
                        &nbsp;&nbsp;<span class="badge bg-dark text-white"><strong>Repeat:
                                {{ $salesOrderRepeatCount }}</strong></span>
                    @endif
                </div>
                <div class="row g-3">
                    @if (!$company_id)
                        <div class="col-md-3">
                            <label class="form-label">Select Company</label>
                            <select name="company_id" id="company_id"
                                class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                data-append="search_by_company"
                                data-selectedCompanyId="{{ old('company_id', $edit->company_id ?? '') }}"
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

                    <div class="col-md-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Search Customer <span class="text-danger">*</span></label>
                        </div>
                        <select name="customer_id" id="customer_id"
                            class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                            data-append="search_by_customer"
                            data-selectedCustomerId="{{ old('customer_id', $edit->customer_id ?? '') }}"
                            autofocus required tabindex="1">
                            <option value="" disabled
                                {{ old('customer_id', $edit->customer_id ?? '') ? '' : 'selected' }}>
                                Type to search customer...
                            </option>
                        </select>
                        @error('customer_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                        <small class="text-muted" style="font-size: 11px;">
                            <i class="ti ti-info-circle"></i> Start typing to search by name or code
                        </small>
                    </div>

                    <div class="col-md-2 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Product Category <span class="text-danger">*</span></label>
                            <select name="product_category_id" id="product_category_id"
                                class="form-control @error('product_category_id') is-invalid @enderror search_by_product_category select2"
                                data-append="search_by_product_category"
                                data-defaultFirst="finish goods"
                                data-selectedCategoryId="{{ old('product_category_id', $edit->product_category_id ?? '') }}"
                                autofocus tabindex="2">
                                <option value="" disabled
                                    {{ old('product_category_id', $edit->product_category_id ?? '') ? '' : 'selected' }}>
                                    Select Product Category
                                </option>
                            </select>
                            @error('product_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">GSTIN</label>
                        <input type="text" class="form-control" name="gst_no" id="gst_no"
                            value="{{ old('gst_no', isset($edit) && $edit?->gst_no ? $edit?->gst_no : '') }}" tabindex="3">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Terms & Conditions</label>
                        <select name="term_condition_id" id="terms_id"
                            class="form-control @error('term_condition_id') is-invalid @enderror search_by_terms select2"
                            data-append="search_by_terms"
                            data-selectedTermsId="{{ old('term_condition_id', $edit->term_condition_id ?? '') }}"
                            autofocus tabindex="4">
                            <option value="" disabled
                                {{ old('term_condition_id', $edit->term_condition_id ?? '') ? '' : 'selected' }}>
                                Select Terms
                            </option>
                        </select>
                        @error('term_condition_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- PO Number -->
                    <div class="col-md-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" name="po_number"
                            class="form-control @error('po_number') is-invalid @enderror"
                            value="{{ old('po_number', $edit->po_number ?? '') }}" placeholder="Enter PO Number">
                        @error('po_number')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- PO Date -->
                    <div class="col-md-3 mt-3">
                        <label class="form-label">PO Date</label>
                        <input type="text" id="po_date_display"
                            class="form-control @error('po_date') is-invalid @enderror"
                            value="{{ old('po_date', isset($edit->po_date) ? date('d-m-Y', strtotime($edit->po_date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="po_date" id="po_date"
                            value="{{ old('po_date', isset($edit->po_date) ? date('Y-m-d', strtotime($edit->po_date)) : date('Y-m-d')) }}" />
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Sales Order No</label>
                        <input type="text" class="form-control readonly-look" name="sales_order_no"
                            id="sales_order_no" placeholder="Enter Sales Order No" readonly
                            value="{{ old('sales_order_no', $edit->sales_order_no ?? '') }}">
                    </div>

                    <div class="col-md-2 mt-3">
                        <label class="form-label">Sales Order Date</label>
                        <input type="text" id="sales_order_date_display"
                            class="form-control @error('sales_order_date') is-invalid @enderror"
                            value="{{ old('sales_order_date', isset($edit->sales_order_date) ? date('d-m-Y', strtotime($edit->sales_order_date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="sales_order_date" id="sales_order_date"
                            value="{{ old('sales_order_date', isset($edit->sales_order_date) ? date('Y-m-d', strtotime($edit->sales_order_date)) : date('Y-m-d')) }}" />

                    </div>
                    <div class="col-md-2 mt-3">
                        <label class="form-label">Expected Dispatch Date</label>
                        <input type="text" id="expected_dispatch_date_display"
                            class="form-control @error('expected_dispatch_date') is-invalid @enderror"
                            value="{{ old('expected_dispatch_date', isset($edit->expected_dispatch_date) ? date('d-m-Y', strtotime($edit->expected_dispatch_date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="expected_dispatch_date" id="expected_dispatch_date"
                            value="{{ old('expected_dispatch_date', isset($edit->expected_dispatch_date) ? date('Y-m-d', strtotime($edit->expected_dispatch_date)) : date('Y-m-d')) }}" />

                    </div>
                </div>

                <div class="row">
                    <!-- Left Side: Customer Details -->

                    @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6 mt-3">
                                <div class="row align-items-center">
                                    <label class="col-4"><strong>Customer Name:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span
                                                id="customer_name_display">{{ old('customer_name_display', $edit->customer_name ?? $edit->customer->customer_name ?? '-') }}</span>
                                        </p>
                                        <input type="hidden" name="customer_name_display" value="{{ old('customer_name_display', $edit->customer_name ?? $edit->customer->customer_name ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3 d-none">
                                <div class="row align-items-center">
                                    <label class="col-4"><strong>Contact Person Name:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span
                                                id="contact_person_name_display">{{ old('contact_person_name_display', isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : 'N/A') }}</span>
                                        </p>
                                        <input type="hidden" name="contact_person_name_display" value="{{ old('contact_person_name_display', $edit->contact_person_name ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3">
                                <div class="row align-items-center">
                                    <label class="col-4"><strong>Phone:</strong></label>
                                    <div class="col-8">
                                        <input type="text"
                                            class="form-control"
                                            name="phone"
                                            id="phone"
                                            value="{{ old('phone', $edit->phone ?? $edit->customer->mobile_number ?? '') }}"
                                            placeholder="Enter phone">
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6 mt-3">
                                <div class="row align-items-start">
                                    <label class="col-4"><strong>Address:</strong></label>
                                    <div class="col-8">
                                        <textarea class="form-control"
                                                name="address"
                                                id="address"
                                                rows="2"
                                                placeholder="Enter address">{{ old('address', isset($edit) ? ($edit->address ?? optional($edit->customer)->address) : '') }}</textarea>
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6 mt-3">
                                <div class="row align-items-center">
                                    <label class="col-4"><strong>City:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span id="city_display">{{ old('city_display', isset($edit) ? ($edit->customer->city->name ?? '') : '') }}</span></p>
                                        <input type="hidden" id="city" name="city" value="{{ old('city', isset($edit) ? ($edit->customer->city->name ?? '') : '') }}">
                                        <input type="hidden" name="city_display" value="{{ old('city_display', isset($edit) ? ($edit->customer->city->name ?? '') : '') }}">
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                    <label class="col-4"><strong>State:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span id="state_display">{{ old('state_display', isset($edit) ? ($edit->customer->state->name ?? '') : '') }}</span></p>
                                        <input type="hidden" id="state" name="state" value="{{ old('state', isset($edit) ? ($edit->customer->state->name ?? '') : '') }}">
                                        <input type="hidden" name="state_display" value="{{ old('state_display', isset($edit) ? ($edit->customer->state->name ?? '') : '') }}">
                                    </div>
                                </div>
                                 <div class="row align-items-center">
                                    <label class="col-4"><strong>Pincode:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span id="pincode_display">{{ old('pincode_display', isset($edit) ? ($edit->customer->pincode ?? '') : '') }}</span></p>
                                        <input type="hidden" id="pincode" name="pincode" value="{{ old('pincode', isset($edit) ? ($edit->customer->pincode ?? '') : '') }}">
                                        <input type="hidden" name="pincode_display" value="{{ old('pincode_display', isset($edit) ? ($edit->customer->pincode ?? '') : '') }}">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                     @else
                        <input type="hidden" name="phone" id="phone"
                            value="{{ isset($edit) && $edit?->id ? $edit?->phone : '' }}" />

                        <input type="hidden" name="address" id="address"
                            value="{{ isset($edit) && $edit?->id ? $edit?->address : '' }}" />
                    @endif
                    <!-- Right Side: 4 Input Fields -->
                    <div class="col-md-6">
                        <div class="row g-3 mt-0">

                            {{-- <div class="col-md-6">
                                <label class="form-label">Transport By</label>
                                <select name="transport_by_id" id="transport_by_id"
                                    class="form-control @error('transport_by_id') is-invalid @enderror search_by_transport_by_id select2"
                                    data-append="search_by_transport_by_id"
                                    data-selectedTransportbyId="{{ old('transport_by_id', isset($edit) && $edit?->transport_by_id ? $edit?->transport_by_id : '') }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('transport_by_id', $edit->transport_by_id ?? '') ? '' : 'selected' }}>
                                        Select Transport By
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Transporter Detail</label>
                                <select name="transport_details_id" id="transport_details_id"
                                    class="form-control @error('transport_details_id') is-invalid @enderror search_by_transport_details_id select2"
                                    data-append="search_by_transport_details_id"
                                    data-selectedTransportdetailsId="{{ old('transport_details_id', isset($edit) && $edit?->transport_details_id ? $edit?->transport_details_id : '') }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('transport_details_id', $edit->transport_details_id ?? '') ? '' : 'selected' }}>
                                        Select Transporter Detail
                                    </option>
                                </select>
                            </div> --}}
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="mb-3">Address Details</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Billing Address</label>
                        <textarea class="form-control" name="billing_address" id="billing_address" rows="2" onkeyup="copyBillingToShipping()">{{ old('billing_address', isset($edit) && $edit?->billing_address ? $edit?->billing_address : '') }}</textarea>

                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="same_as_billing" value="1" id="same_as_billing" checked>
                            <label class="form-check-label" for="same_as_billing">
                                Shipping Address is same as Billing Address
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6" id="shipping_address_div" style="display: none;">
                        <div class="mb-2">
                            <label class="form-label">Ship To Customer</label>
                            <select name="shipping_customer_id" id="shipping_customer_id"
                                class="form-control search_by_shipping_customer select2"
                                data-selectedShippingCustomerId="{{ old('shipping_customer_id', $edit->shipping_customer_id ?? '') }}">
                                <option value="">Select Customer</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Shipping GSTIN</label>
                            <input type="text" class="form-control" name="shipping_gst_no" id="shipping_gst_no"
                                value="{{ old('shipping_gst_no', $edit->shipping_gst_no ?? '') }}"
                                placeholder="Ship To GST Number">
                        </div>
                        <label class="form-label">Shipping Address</label>
                        <textarea class="form-control" name="shipping_address" id="shipping_address" rows="2">{{ old('shipping_address', isset($edit) && $edit?->shipping_address ? $edit?->shipping_address : '') }}</textarea>
                    </div>
                </div>

                <hr class="my-4">
                <h4 class="mb-3">Sales Order Items</h4>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <select name="product_variant_price_id" id="product_variant_price_id"
                            class="form-control @error('product_variant_price_id') is-invalid @enderror search_by_product_variant select2"
                            data-append="search_by_product_variant"
                            data-selectedProductVariantId="{{ old('product_variant_price_id', $edit->product_variant_price_id ?? '') }}"
                            data-selectedTaxId="{{ old('product_variant_price_id', $edit->product_variant_price_id ?? '') }}"
                            autofocus>
                            <option value="" disabled
                                {{ old('product_variant_price_id', $edit->product_variant_price_id ?? '') ? '' : 'selected' }}>
                                Select Product
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 row" style="margin-top: -22px;">

                        <div class="col-md-6">
                            <label for="order_type">Order Type</label>
                            <select name="order_type" id="order_type" class="form-control form-control select2">
                                @foreach ($modules['order_type'] as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('order_type', $edit->order_type ?? '') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="order_price">Add Order</label>
                            <input type="number" name="order_price" id="order_price" class="form-control"
                                value="{{ old('order_price', $edit->order_price ?? '') }}" placeholder="Enter"
                                step="0.01" min="0">
                        </div>


                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary" id="addRowBtn"> Add</button>
                    </div>
                </div>

                <div class="table-responsive border" style="">
                    <table class="table table-bordered mb-0" id="quotation-items-table">
                        <thead class="table-light text-center align-middle">
                            <tr>
                                <th style="min-width: 50px;">Sr No</th>
                                <th style="min-width: 200px;"><strong>Product Code</strong></th>
                                <th style="min-width: 200px;">Product Description</th>
                                <th style="min-width: 150px;">Current Stock</th>
                                <th style="min-width: 200px;">HSN</th>
                                <th style="min-width: 100px;">Unit</th>
                                <th style="min-width: 200px;">Kg / Pcs / Gross</th>
                                <th style="min-width: 200px;">Price</th>
                                <th style="min-width: 100px;" class="d-none">Rate</th>
                                <th style="min-width: 200px;">Taxable Amount</th>
                                <th style="min-width: 200px;" class="tax_type_dv d-none">Taxable</th>


                                <th style="min-width: 200px;" class="cgst_div tax_type_dv">CGST</th>
                                <th style="min-width: 200px;" class="cgst_div tax_type_dv">CGST Amount</th>
                                <th style="min-width: 200px;" class="sgst_div tax_type_dv">SGST</th>
                                <th style="min-width: 200px;" class="sgst_div tax_type_dv">SGST Amount</th>

                                <th style="min-width: 200px;" class="igst_div tax_type_dv">IGST</th>
                                <th style="min-width: 200px;" class="igst_div tax_type_dv">IGST Amount</th>


                                <th style="min-width: 200px;" class="tax_type_dv">Subtotal</th>
                                <th style="min-width: 50px;">Action</th>
                            </tr>
                        </thead>

                        <tbody id="productBody">
                            @php
                                $compVal = isset($edit) ? $edit->company_id : (isset($company_id) ? $company_id : null);
                                $fgWh = null;
                                if ($compVal) {
                                    $fgWh = \App\Models\MasterWarehouse::where('company_id', $compVal)
                                        ->where('status', 'active')
                                        ->where(function ($query) {
                                            $query->where('name', 'like', '%Finished Goods%')
                                                ->orWhere('name', 'like', '%FG%');
                                        })
                                        ->first();
                                    if (!$fgWh) {
                                        $fgWh = \App\Models\MasterWarehouse::where('company_id', $compVal)
                                            ->where('status', 'active')
                                            ->first();
                                    }
                                }
                                $fgWhId = $fgWh ? $fgWh->id : null;
                            @endphp
                            @if (old('product_id'))
                                @foreach (old('product_id') as $index => $prodId)
                                    <tr>
                                        <input type="hidden" class="form-control" name="item_id[]" value="{{ old('item_id')[$index] ?? 0 }}">
                                        <input type="hidden" class="form-control" name="product_id[]" value="{{ $prodId }}">
                                        <input type="hidden" class="form-control" name="is_combo[]" value="{{ old('is_combo')[$index] ?? 0 }}">
                                        <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]" value="{{ old('grosse_per_qty')[$index] ?? 1 }}">
                                        <input type="hidden" class="form-control" name="product_variant_price_id[]" value="{{ old('product_variant_price_id')[$index] ?? '' }}">

                                        <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                        @php
                                            $oldIsCombo = (int)(old('is_combo')[$index] ?? 0) === 1;
                                            $oldProdPartNo = '';
                                            $oldProdName = '';
                                            if ($oldIsCombo) {
                                                $oldCombo = \App\Models\ProductCombo::find($prodId);
                                                $oldProdPartNo = $oldCombo ? $oldCombo->part_no : (old('product_code')[$index] ?? '');
                                                $oldProdName = $oldCombo ? $oldCombo->combo_name : (old('product_name')[$index] ?? '');
                                            } else {
                                                $oldProductModel = \App\Models\Product::find($prodId);
                                                $oldProdPartNo = $oldProductModel ? $oldProductModel->part_no : (old('product_code')[$index] ?? '');
                                                $oldProdName = $oldProductModel ? ($oldProductModel->product_name ?: $oldProductModel->product_description) : (old('product_name')[$index] ?? '');
                                            }

                                            $oldCustInfo = '';
                                            $oldCustId = old('customer_id') ?? (isset($edit) ? $edit->customer_id : null);
                                            $oldCust = $oldCustId ? \App\Models\Customer::find($oldCustId) : null;
                                            if ($oldCust && $oldCust->customer_name) {
                                                $cItem = \App\Models\ProductCustomerItem::where('company_id', $compVal)
                                                    ->where('product_id', $prodId)
                                                    ->where('customer_name', $oldCust->customer_name)
                                                    ->whereNull('deleted_at')
                                                    ->first();
                                                if ($cItem) {
                                                    $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                                    $custDesc = trim($cItem->customer_item_description ?? '');
                                                    $custParts = array_filter([$custCode, $custDesc]);
                                                    $oldCustInfo = implode(' ', $custParts);
                                                }
                                            }
                                            $oldDisplayCode = $oldProdPartNo;
                                            if (!empty($oldCustInfo)) {
                                                $oldDisplayCode .= ' (' . $oldCustInfo . ')';
                                            } elseif (empty($oldProdPartNo)) {
                                                $oldDisplayCode = old('product_code')[$index] ?? '';
                                            }
                                        @endphp
                                        <td><label class="product_code_txt">{{ $oldDisplayCode }}</label><input type="hidden" class="form-control bg-light" name="product_code[]" value="{{ old('product_code')[$index] ?? '' }}" readonly></td>
                                        <td><label class="product_name_txt">{{ $oldProdName }}</label><input type="hidden" class="form-control bg-light" name="product_name[]" value="{{ old('product_name')[$index] ?? '' }}" readonly></td>
                                        <input type="hidden" class="form-control" name="product_description[]" value="{{ old('product_description')[$index] ?? '' }}">
                                        @php
                                            $stockVal = '-';
                                            if ($fgWhId) {
                                                $oldIsCombo = (int)(old('is_combo')[$index] ?? 0) === 1;
                                                $oldUnit = old('unit')[$index] ?? '';
                                                $oldProdId = $prodId;
                                                $oldProdVarPriceId = old('product_variant_price_id')[$index] ?? $prodId ?? null;

                                                $oldProduct = \App\Models\Product::find($oldProdId);
                                                $oldProdCatId = $oldProduct ? $oldProduct->product_category_id : null;

                                                $stockQty = \App\Helpers\Helper::getProductStockTotalQuantity($compVal, $fgWhId, $oldProdCatId, $oldProdVarPriceId, $oldIsCombo);
                                                $stockKg = \App\Helpers\Helper::getProductStockTotalKG($compVal, $fgWhId, $oldProdCatId, $oldProdVarPriceId, $oldIsCombo);

                                                $unitLower = strtolower((string)$oldUnit);
                                                if ($unitLower === 'kg') {
                                                    $stockVal = number_format($stockKg, 3, '.', '');
                                                } elseif ($unitLower === 'gross') {
                                                    $stockVal = number_format($stockQty, 3, '.', '');
                                                } else {
                                                    $stockVal = round($stockQty);
                                                }
                                            }
                                        @endphp
                                        <td><input type="text" class="form-control bg-light" name="current_stock[]" readonly value="{{ $stockVal }}"></td>
                                        <td><input type="text" class="form-control bg-light" name="hsn_code[]" value="{{ old('hsn_code')[$index] ?? '' }}" readonly></td>
                                        <td style="display: flex; align-items: center; gap: 6px;">
                                            <input type="text" class="form-control unit bg-light" name="unit[]" readonly value="{{ old('unit')[$index] ?? '' }}">
                                            @if (!empty(old('unit')[$index]) && strtolower(old('unit')[$index]) === 'gross')
                                                <span class="badge bg-secondary" style="font-size:12px;">qty : {{ old('grosse_per_qty')[$index] ?? 0 }}</span>
                                            @endif
                                        </td>
                                        <td><input type="text" class="form-control quantity" onchange="updateInnerAndOuterFromQty(this)" name="quantity[]" value="{{ old('quantity')[$index] ?? 1 }}"></td>
                                        <td>
                                            <input type="text" class="form-control price" onchange="recalculateRow(this)" name="price[]" value="{{ old('price')[$index] ?? 0 }}" onkeypress="return isDecimal(event)">
                                            <div class="gst-label" style="font-size: 12px; color: gray;"></div>
                                        </td>
                                        <td class="d-none"><input type="text" class="form-control rate_in_amount bg-light" name="rate_in_amount[]" value="{{ old('rate_in_amount')[$index] ?? 0 }}" readonly></td>
                                        <td>
                                            <input type="hidden" class="form-control amount bg-light" name="amount[]" value="{{ old('amount')[$index] ?? 0 }}" readonly>
                                            <input type="text" class="form-control taxable_amount bg-light" name="taxable_amount[]" value="{{ old('taxable_amount')[$index] ?? 0 }}" readonly>
                                            <small class="text-muted d-block taxable-display" style="font-size: 11px;">Taxable: {{ old('taxable_amount')[$index] ?? '0.00' }}</small>
                                        </td>
                                        <td class="tax_type_dv d-none"><input type="text" class="form-control taxable_amount bg-light" name="taxable_amount[]" value="{{ old('taxable_amount')[$index] ?? 0 }}" readonly></td>
                                        <td class="igst_div tax_type_dv"><input type="text" class="form-control gst_in_percentage bg-light" name="gst_in_percentage[]" value="{{ old('gst_in_percentage')[$index] ?? '' }}" readonly></td>
                                        <td class="igst_div tax_type_dv"><input type="text" class="form-control gst_amount bg-light" name="gst_amount_array[]" value="{{ old('gst_amount_array')[$index] ?? 0 }}" readonly></td>
                                        <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_in_percentage[]" value="{{ old('cgst_in_percentage')[$index] ?? '' }}" readonly></td>
                                        <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_amount_array[]" value="{{ old('cgst_amount_array')[$index] ?? 0 }}" readonly></td>
                                        <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_in_percentage[]" value="{{ old('sgst_in_percentage')[$index] ?? '' }}" readonly></td>
                                        <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_amount_array[]" value="{{ old('sgst_amount_array')[$index] ?? 0 }}" readonly></td>
                                        <td class="tax_type_dv"><input type="text" class="form-control subtotal bg-light" name="subtotal[]" value="{{ old('subtotal')[$index] ?? 0 }}" readonly></td>
                                        <td><button type="button" class="btn btn-danger deleteRow remove-row">X</button></td>
                                    </tr>
                                @endforeach
                            @elseif (isset($edit) && (isset($quotation_id) ? $edit->quotation_items : $edit->sales_order_items))
                                @foreach ((isset($quotation_id) ? $edit->quotation_items : $edit->sales_order_items) as $item)
                                    <tr>
                                        <input type="hidden" class="form-control" name="item_id[]"
                                            value="{{ isset($quotation_id) ? $item->id : $item->quotation_item_id }}">
                                        <input type="hidden" class="form-control" name="product_id[]"
                                            value="{{ $item->product_id }}">
                                        <input type="hidden" class="form-control" name="is_combo[]"
                                            value="{{ $item->is_combo }}">
                                        <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]"
                                            value="{{ $item->product->grosse_per_qty }}">

                                        <input type="hidden" class="form-control" name="product_variant_price_id[]"
                                            value="{{ $item->product_variant_price_id }}">

                                        <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                        @php
                                            $editIsCombo = (int)($item->is_combo ?? 0) === 1;
                                            $editProdPartNo = '';
                                            $editProdName = '';
                                            if ($editIsCombo) {
                                                $editCombo = \App\Models\ProductCombo::find($item->product_id);
                                                $editProdPartNo = $editCombo ? $editCombo->part_no : ($item->product_code ?? '');
                                                $editProdName = $editCombo ? $editCombo->combo_name : ($item->product_name ?? '');
                                            } else {
                                                $editProductModel = \App\Models\Product::find($item->product_id);
                                                $editProdPartNo = $editProductModel ? $editProductModel->part_no : ($item->product_code ?? '');
                                                $editProdName = $editProductModel ? ($editProductModel->product_name ?: $editProductModel->product_description) : ($item->product_name ?? '');
                                            }
                                            $custItemInfo = '';
                                            $soCustomer = $edit->customer ?? null;
                                            if ($soCustomer && $soCustomer->customer_name) {
                                                $cItem = \App\Models\ProductCustomerItem::where('company_id', $edit->company_id)
                                                    ->where('product_id', $item->product_id)
                                                    ->where('customer_name', $soCustomer->customer_name)
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
                                        <td><label class="product_code_txt">{{ $displayCode }}</label><input
                                                type="hidden" class="form-control bg-light" name="product_code[]"
                                                value="{{ $item->product_code ?? '' }}" readonly></td>
                                        <td><label class="product_name_txt">{{ $editProdName }}</label><input
                                                type="hidden" class="form-control bg-light" name="product_name[]"
                                                value="{{ $editProdName }}" readonly></td>

                                        <input type="hidden" class="form-control" name="product_description[]"
                                                value="{{ $item->product_description ?? '' }}">
                                        @php
                                            $stockVal = '-';
                                            if ($fgWhId) {
                                                $prodCatId = $item->product ? $item->product->product_category_id : null;
                                                $prodVarPriceId = $item->product_variant_price_id ?? $item->product_id ?? null;
                                                $isCombo = (int)($item->is_combo ?? 0) === 1;

                                                $stockQty = \App\Helpers\Helper::getProductStockTotalQuantity($compVal, $fgWhId, $prodCatId, $prodVarPriceId, $isCombo);
                                                $stockKg = \App\Helpers\Helper::getProductStockTotalKG($compVal, $fgWhId, $prodCatId, $prodVarPriceId, $isCombo);

                                                $unitLower = strtolower((string)$item->unit);
                                                if ($unitLower === 'kg') {
                                                    $stockVal = number_format($stockKg, 3, '.', '');
                                                } elseif ($unitLower === 'gross') {
                                                    $stockVal = number_format($stockQty, 3, '.', '');
                                                } else {
                                                    $stockVal = round($stockQty);
                                                }
                                            }
                                        @endphp
                                        <td><input type="text" class="form-control bg-light" name="current_stock[]" readonly value="{{ $stockVal }}"></td>
                                        <td><input type="text" class="form-control bg-light" name="hsn_code[]"
                                                value="{{ $item->hsn_code ?? '' }}" readonly></td>

                                        <td style="display: flex; align-items: center; gap: 6px;">
                                            <input type="text" class="form-control unit bg-light" name="unit[]"
                                                readonly value="{{ $item->unit ?? '' }}">

                                            @if (!empty($item->unit) && strtolower($item->unit) === 'gross')
                                                <span class="badge bg-secondary" style="font-size:12px;">
                                                    qty : {{ $item->product->grosse_per_qty ?? 0 }}
                                                </span>
                                            @endif
                                        </td>

                                        <td><input type="text" class="form-control quantity"
                                                onchange="updateInnerAndOuterFromQty(this)" name="quantity[]"
                                                value="{{ strtolower($item->unit) === 'kg'
                                                    ? number_format($item->quantity ?? 0, 3, '.', '')
                                                    : (int) ($item->quantity ?? 0)
                                                }}"  onkeypress="{{ strtolower($item->unit) === 'kg' ? 'return isDecimal(event)' : 'return isNumber(event)' }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control price"
                                                onchange="recalculateRow(this)" name="price[]"
                                                value="{{ $item->price_in_amount ?? '' }}"
                                                onkeypress="return isDecimal(event)">
                                            @if ($item->product_variant_price?->including_gst == '1')
                                                <div class="gst-label" style="font-size: 12px; color: gray;">
                                                    Including GST Price: {{ $item->product_variant_price?->price ?? 0 }}
                                                </div>
                                            @else
                                                <div class="gst-label" style="font-size: 12px; color: gray;"></div>
                                            @endif
                                        </td>
                                        <td class="d-none"><input type="text" class="form-control rate_in_amount bg-light"
                                                name="rate_in_amount[]" onkeypress="return isDecimal(event)"
                                                value="{{ $item->rate_in_amount ?? '' }}" readonly></td>
                                        <td><input type="hidden" class="form-control amount bg-light" name="amount[]"
                                                onkeypress="return isDecimal(event)" value="{{ $item->amount ?? '' }}"
                                                readonly><input type="text" class="form-control taxable_amount bg-light" name="taxable_amount[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->taxable_amount ?? '' }}" readonly></td>
                                        <td class="tax_type_dv d-none"><input type="text"
                                                class="form-control taxable_amount bg-light" name="taxable_amount[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->taxable_amount ?? '' }}" readonly></td>
                                        <td class="igst_div tax_type_dv"><input type="text"
                                                class="form-control gst_in_percentage bg-light" name="gst_in_percentage[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->gst_in_percentage ?? '' }}" readonly></td>
                                        <td class="igst_div tax_type_dv"><input type="text"
                                                class="form-control gst_amount bg-light" name="gst_amount_array[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->gst_amount ?? '' }}" readonly></td>



                                        <td class="cgst_div tax_type_dv"><input type="text"
                                                class="form-control bg-light" name="cgst_in_percentage[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->cgst_in_percentage ?? '' }}" readonly></td>
                                        <td class="cgst_div tax_type_dv"><input type="text"
                                                class="form-control bg-light" name="cgst_amount_array[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->cgst_amount ?? '' }}" readonly></td>


                                        <td class="sgst_div tax_type_dv"><input type="text"
                                                class="form-control bg-light" name="sgst_in_percentage[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->sgst_in_percentage ?? '' }}" readonly></td>
                                        <td class="sgst_div tax_type_dv"><input type="text"
                                                class="form-control bg-light" name="sgst_amount_array[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->sgst_amount ?? '' }}" readonly></td>


                                        <td class="tax_type_dv"><input type="text"
                                                class="form-control subtotal bg-light" name="subtotal[]"
                                                onkeypress="return isDecimal(event)" value="{{ $item->sub_total ?? '' }}"
                                                readonly></td>
                                        <td><button type="button" class="btn btn-danger deleteRow"
                                                onclick="removeRow(this)">X</button></td>
                                    </tr>
                                @endforeach
                            @endif

                        </tbody>

                        <tbody>
                            <tr>
                                <td class="text-center"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    <input type="number" step="0.01"
                                        value="{{ isset($edit) && $edit?->master_quantity ? number_format($edit?->master_quantity, 2, '.', '') : '' }}"
                                        class="form-control bg-light" readonly id="master_quantity"
                                        name="master_quantity">
                                </td>

                                <td></td>
                                <td class="d-none"><input type="number"
                                        value="{{ isset($edit) && $edit?->master_amount ? $edit?->master_amount : '' }}"
                                        class="form-control bg-light" readonly="" name="master_amount"
                                        id="master_amount"></td>
                                <td class="tax_type_dv"><input type="number"
                                        value="{{ isset($edit) && $edit?->master_taxable_amount ? $edit?->master_taxable_amount : '' }}"
                                        class="form-control bg-light" readonly="" name="master_taxable_amount"
                                        id="master_taxable_amount"></td>

                                <td class="cgst_div"></td>
                                <td class="cgst_div"></td>
                                <td class="sgst_div"></td>
                                <td class="sgst_div"></td>
                                <td class="igst_div"></td>
                                <td class="igst_div"></td>
                                <td class="tax_type_dv d-none"><input type="number"
                                        value="{{ isset($edit) && $edit?->master_gst_amount ? $edit?->master_gst_amount : '' }}"
                                        class="form-control bg-light" readonly="" name="master_gst_amount"
                                        id="master_gst_amount"></td>
                                <td class="tax_type_dv"><input type="number"
                                        value="{{ isset($edit) && $edit?->master_sub_total ? $edit?->master_sub_total : '' }}"
                                        class="form-control bg-light" readonly="" name="master_sub_total"
                                        id="master_sub_total"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class=" mt-5">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <label class="form-label">Packing & Forwarding Charge</label>
                            <input type="number"
                                value="{{ isset($edit) && $edit?->packing_forwarding_charge ? $edit?->packing_forwarding_charge : '' }}"
                                class="form-control" onchange="recalculateRow(this)" value=""
                                id="packing_forwarding_charge" name="packing_forwarding_charge" min="0">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Transport</label>
                            <input type="number"
                                value="{{ isset($edit) && $edit?->transport_charge ? $edit?->transport_charge : '' }}"
                                class="form-control" onchange="recalculateRow(this)" value="" id="transport"
                                name="transport_charge" min="0">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Freight</label>
                            <input type="number"
                                value="{{ isset($edit) && $edit?->freight_charge ? $edit?->freight_charge : '' }}"
                                class="form-control" onchange="recalculateRow(this)" value="" id="freight"
                                name="freight_charge" min="0">
                        </div>
                    </div>
                    <div class="row justify-content-end mt-4">
                        <div class="col-md-6">
                            <div class="mb-3 row">
                                <label class="col-sm-6 col-form-label">Total Taxable</label>
                                <div class="col-sm-6">
                                    <input type="text" value="{{ isset($edit) && $edit?->total_taxable ? $edit?->total_taxable : '' }}" class="form-control bg-light" value="0.0000" id="total_taxable" name="total_taxable" readonly>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label class="col-sm-6 col-form-label">
                                    GST Amount
                                    <input type="checkbox" id="is_gst_applicable" name="is_gst_applicable"
                                        value="1" class="form-check-input ms-2"
                                        {{ isset($edit) && $edit?->is_gst_applicable === '1' ? 'checked' : '' }}>
                                </label>
                                <div class="col-sm-6">
                                    <input type="text"
                                        value="{{ isset($edit) && $edit?->gst_amount ? $edit?->gst_amount : '' }}"
                                        class="form-control bg-light" value="0.0000" id="gst_amount" name="gst_amount"
                                        readonly>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label class="col-sm-6 col-form-label">
                                    TCS Amount
                                    <input type="checkbox" id="is_tcs_applicable" name="is_tcs_applicable"
                                        value="1" class="form-check-input ms-2"
                                        {{ isset($edit) && $edit?->is_tcs_applicable === '1' ? 'checked' : '' }}>
                                </label>
                                <div class="col-sm-6">
                                    <input type="text"
                                        value="{{ isset($edit) && $edit?->tcs_amount ? $edit?->tcs_amount : '' }}"
                                        class="form-control bg-light" value="0.0000" id="tcs_amount" name="tcs_amount"
                                        readonly>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-6 col-form-label">Round Off</label>
                                <div class="col-sm-6">
                                    <input type="number"
                                        value="{{ isset($edit) && $edit?->round_off ? $edit?->round_off : '' }}"
                                        class="form-control bg-light" value="0.0000" id="round_off" name="round_off"
                                        readonly>
                                </div>
                            </div>

                            <div class="mb-3 row">
                                <label class="col-sm-6 col-form-label">Grand Total</label>
                                <div class="col-sm-6">
                                    <input type="text"
                                        value="{{ isset($edit) && $edit?->grand_total ? $edit?->grand_total : '' }}"
                                        class="form-control bg-light fw-semibold fs-5" value="0.0000" id="grand_total"
                                        name="grand_total" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h4 class="mb-3">Extra Details</h4>

                <div class="col-md-12 col-sm-12 mb-3">
                    <div class="form-group">
                        <label class="form-label"> Terms and Condition </label>
                        <textarea rows="3" cols="3"
                            class="ckeditor_common_cls form-control @error('term_and_condition') is-invalid @enderror"
                            name="term_and_condition" placeholder="Enter Terms and Condition" id="terms_and_condition_detail">
                                {{ isset($edit) && $edit?->term_and_condition ? $edit?->term_and_condition : old('term_and_condition') }}
                            </textarea>
                        @error('term_and_condition')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-12 col-sm-12 mb-3">
                    <div class="form-group">
                        <label class="form-label">Regards </label>
                        <textarea rows="3" cols="3"
                            class="ckeditor_common_cls form-control @error('regards') is-invalid @enderror" name="regards"
                            placeholder="Enter Regards" id="regards">
                                    @php
                                        if (Auth::guard('admin_software')->check()) {
                                            $loginUserName = Auth::guard('admin_software')?->user()?->name;
                                            $loginUserPhone = Auth::guard('admin_software')?->user()?->phone;
                                        }
                                        if (Auth::guard('team_person')->check()) {
                                            $loginUserName = Auth::guard('team_person')?->user()?->name;
                                            $loginUserPhone = Auth::guard('team_person')?->user()?->mobile_no;
                                        }

                                        $regards = $loginUserName . '<br>';
                                        $regards .= $loginUserPhone . '<br>';
                                    @endphp
                                    {{ isset($edit) && $edit?->regards ? $edit?->regards : $regards }}
                                </textarea>
                        @error('regards')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <div class="col-md-12 col-sm-12 mb-3">
                    <div class="form-group">
                        <label class="form-label">Note </label>
                        <textarea rows="3" cols="3"
                            class="ckeditor_common_cls form-control @error('notes') is-invalid @enderror" name="notes"
                            placeholder="Enter Note" id="note_detail">
                                {{ isset($edit) && $edit?->notes ? $edit?->notes : old('notes') }}
                            </textarea>
                        @error('notes')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>


                @php
                    $showButtons = !isset($edit) || !empty($edit->created_by);
                @endphp

                @if ($showButtons)
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-success mt-1 mb-1">
                        {{ isset($edit) ? 'Update' : 'Submit' }}
                    </button>
                    <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                </div>
                @endif


            </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="{{ asset('software/custom/price_calculation.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#po_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('po_date', isset($edit->po_date) ? date('d-m-Y', strtotime($edit->po_date)) : date('d-m-Y')) }}",
            maxDate: "{{ date('d-m-Y') }}",
            minDate: "1900-01-01", // optional, allows all past dates from 1900
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('po_date').value = ymd;
            }
        });

        flatpickr("#sales_order_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('sales_order_date', isset($edit->sales_order_date) ? date('d-m-Y', strtotime($edit->sales_order_date)) : date('d-m-Y')) }}",
            maxDate: "{{ date('d-m-Y') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('sales_order_date').value = ymd;
            }
        });

        flatpickr("#expected_dispatch_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('expected_dispatch_date', isset($edit->expected_dispatch_date) ? date('d-m-Y', strtotime($edit->expected_dispatch_date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('expected_dispatch_date').value = ymd; // hidden input for submission
            }
        });


        $(document).ready(function() {
            var $orderTypeSelect = $("#order_type");
            $orderTypeSelect.select2({
                placeholder: "Select an order type",
                allowClear: true
            });
        });
    </script>

    <script>
        const isDhruvikUser = {{ $isDhruvik ? 'true' : 'false' }};
        let termsInstance;
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    @if ($generateSalesOrderNo)
        @include('utils.generateSalesOrderNo')
    @endif

    @include('utils.getProductCategory')
    @include('utils.getTerms')
    @include('utils.getCustomerSales')
    @include('utils.getCustomerInfo')
    @include('utils.getProductAndVariant')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')

    <script>
        fetch_customerInfo();
        initializeCKEditor(".ckeditor_common_cls");

        function checkMax(input) {
            if (input.value > 100) {
                input.value = '';
                toastr.error("Discount percentage cannot be more than 100!");
            }
        }

        function validateAndRecalculate(input, row) {
            validateFlatDiscount(input);
            recalculateRow(input, row);
        }

        function validateFlatDiscount(input) {
            var price = $(input).closest('tr').find('.price').val();
            var discount = parseFloat(input.value);

            if (discount > parseFloat(price)) {
                toastr.error("Discount (Flat) cannot be more than Price (in INR)!");
                input.value = '';
            }
        }


        $(document).ready(function () {
            let is_igst = parseInt($("#is_igst").val()) || 0;

            if (is_igst == 1) {
                $(".cgst_div").hide();
                $(".sgst_div").hide();
                $(".igst_div").show();
            } else {
                $(".cgst_div").show();
                $(".sgst_div").show();
                $(".igst_div").hide();
            }
        });


        $(document).ready(function () {
            function applyInputRules() {
                let type = $("#order_type").val();

                if (type === "kg") {
                    $("#order_price").attr("step", "0.01");
                    $("#order_price").removeAttr("pattern");
                } else {
                    $("#order_price").attr("step", "1");
                    $("#order_price").attr("pattern", "\\d+");

                    let val = $("#order_price").val();
                    if (val && val.includes(".")) {
                        $("#order_price").val(val.split(".")[0]);
                    }
                }
            }

            applyInputRules();
            $(document).on("change", "#order_type", function () {
                applyInputRules();
            });

            $(document).on("keydown", "#order_price", function (e) {
                let type = $("#order_type").val();
                if (type !== "kg") {
                    if (e.key === "." || e.key === ",") {
                        e.preventDefault();
                    }
                }
            });
        });


        document.getElementById("addRowBtn").addEventListener("click", function() {
            let tableBody = document.getElementById("productBody");
            let newRow = document.createElement("tr");
            let product_id = $('#product_variant_price_id').val();
            let customer_id = $('#customer_id').val();

            let productSelect = $('#product_variant_price_id');
            let isCombo = productSelect.find(':selected').data('product_combo') === true;

            let orderType = $('#order_type').val();
            let orderPrice = $('#order_price').val().trim();
            if (!orderType) {
                toastr.error("Please select order type!");
                return;
            }

            if (orderPrice === '') {
                toastr.error("Please enter order price!");
                return;
            }

            if (product_id == '') {
                toastr.error("Please select product!");
                return;
            }

            if (customer_id == '') {
                toastr.error("Please select customer!");
                return;
            }
            let company_id = $(".search_by_company option:selected").val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }
            if (company_id == undefined || company_id == null) {
                company_id = $('input[name="company_id"]').val();
            }

            let orderTypeValue = $('#order_type').val();
            let srNo = $('#productBody tr').length + 1;

            newRow.innerHTML = `
                <input type="hidden" class="form-control" name="item_id[]" value="0">
                <input type="hidden" class="form-control" name="product_id[]">
                <input type="hidden" class="form-control" name="is_combo[]">
                <input type="hidden" class="form-control" name="product_variant_price_id[]">
                <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]">

                <td class="sr_no_txt">${srNo}</td>
                <td><input type="hidden" name="product_code[]" readonly><label class="product_code_txt"></label></td>
                <td><input type="hidden" name="product_name[]" readonly><label class="product_name_txt"></label></td>
                <input type="hidden" class="form-control" name="product_description[]">
                <td><input type="text" class="form-control bg-light" name="current_stock[]" readonly value="-"></td>
                <td><input type="text" class="form-control bg-light" name="hsn_code[]" readonly></td>
                <td><input type="text" class="form-control unit bg-light" name="unit[]" readonly></td>
                <td><input type="text" class="form-control quantity" onchange="updateInnerAndOuterFromQty(this)" name="quantity[]" value="1"></td>
                <td>
                    <input type="text" class="form-control price" onchange="recalculateRow(this)" name="price[]" value="0" onkeypress="return isDecimal(event)">
                    <div class="gst-label" style="font-size: 12px; color: gray;"></div>
                </td>

                <td class="d-none"><input type="text" class="form-control rate_in_amount bg-light" name="rate_in_amount[]" value="0" readonly onkeypress="return isDecimal(event)"></td>
                <td>
                    <input type="text" class="form-control amount bg-light" name="amount[]" value="0" readonly onkeypress="return isDecimal(event)">
                    <input type="hidden" class="form-control taxable_amount bg-light" name="taxable_amount[]" value="0" readonly onkeypress="return isDecimal(event)">
                    <small class="text-muted d-block taxable-display" style="font-size: 11px;">Taxable: 0.00</small>
                </td>
                <td class="tax_type_dv d-none"><input type="text" class="form-control taxable_amount bg-light" name="taxable_amount[]" value="0" readonly onkeypress="return isDecimal(event)"></td>
                <td class="igst_div tax_type_dv"><input type="text" class="form-control gst_in_percentage bg-light" name="gst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="igst_div tax_type_dv"><input type="text" class="form-control gst_amount bg-light" name="gst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="tax_type_dv"><input type="text" class="form-control subtotal bg-light" name="subtotal[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td><button type="button" class="btn btn-danger deleteRow remove-row">X</button></td>
            `;
            tableBody.appendChild(newRow);

            $.ajax({
                url: '{{ env('API_URL') }}get-product-info',
                type: "POST",
                dataType: "Json",
                data: {
                    product_id: product_id,
                    customer_id: $('#customer_id').val(),
                    company_id: company_id,
                    is_combo: isCombo,
                    module_name: "{{ $route ?? '' }}",
                    orderTypeValue: orderTypeValue || $('#order_type').val() || "",
                },
                success: function(response) {

                    let data = response.data;
                    console.log(data);
                    let customerItemCode = data.customer_item_code || data.customer_code || '';
                    let customerItemDescription = data.customer_item_description || '';
                    let productNameText = data.product_name || '';
                    let productDescriptionValue = data.description || '';

                    let customerParts = [];
                    if (customerItemCode && customerItemCode !== data.part_no) {
                        customerParts.push(customerItemCode);
                    }
                    if (customerItemDescription && customerItemDescription !== data.product_name && !customerParts.includes(customerItemDescription)) {
                        customerParts.push(customerItemDescription);
                    }
                    let customerInfoText = customerParts.join(' ');

                    let displayCode = data.part_no || '';
                    if (isDhruvikUser && customerInfoText) {
                        displayCode = `${data.part_no} (${customerInfoText})`;
                    }

                    $(newRow).find('input[name="product_name[]"]').val(productNameText);
                    $(newRow).find('input[name="is_combo[]"]').val(isCombo ? 1 : 0);
                    $(newRow).find('.product_name_txt').text(productNameText);
                    $(newRow).find('.sr_no_txt').text(srNo);
                    $(newRow).find('input[name="product_code[]"]').val(data.part_no);
                    $(newRow).find('.product_code_txt').text(displayCode);

                    $('#product_variant_price_id').val('').trigger('change');

                    $(newRow).find('input[name="product_id[]"]').val(data.product_id);
                    $(newRow).find('input[name="product_description[]"]').val(productDescriptionValue);
                    $(newRow).find('input[name="product_variant_price_id[]"]').val(data.product_variant_price_id);

                    $(newRow).find('input[name="hsn_code[]"]').val(data.hsn_code);
                    $(newRow).find('input[name="unit[]"]').val(orderTypeValue);
                    $(newRow).find('input[name="quantity[]"]').val(orderPrice);
                    $(newRow).find('input[name="grosse_per_qty[]"]').val(data.grosse_per_qty);

                    // Fetch and set current stock
                    let productCategoryId = data.product_category_id;
                    let productVariantPriceId = data.product_variant_price_id || data.product_id;

                    if (productCategoryId && productVariantPriceId) {
                        // First get the Finished Goods warehouse
                        $.ajax({
                            url: '{{ route('get-finished-goods-warehouse') }}',
                            type: "GET",
                            data: {
                                company_id: company_id
                            },
                            success: function(warehouseResponse) {
                                if (warehouseResponse.status && warehouseResponse.data) {
                                    let warehouseId = warehouseResponse.data.id;

                                    // Now get the current stock
                                    $.ajax({
                                        url: '{{ route('get-product-stock') }}',
                                        type: "GET",
                                        data: {
                                            company_id: company_id,
                                            warehouse_id: warehouseId,
                                            product_category_id: productCategoryId,
                                            product_variant_price_id: productVariantPriceId,
                                            is_combo: isCombo,
                                            unit: orderTypeValue
                                        },
                                        success: function(stockResponse) {
                                            if (stockResponse.status) {
                                                $(newRow).find('input[name="current_stock[]"]').val(stockResponse.data);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }

                    let unitVal = orderTypeValue.toLowerCase();
                    let qtyInput = $(newRow).find('input[name="quantity[]"]');

                    if (unitVal === "kg") {
                        qtyInput.attr("onkeypress", "return isDecimal(event)");
                    } else {
                        qtyInput.attr("onkeypress", "return isNumber(event)");
                    }


                    $(newRow).find('input[name="price[]"]').val(parseFloat(data.price || 0).toFixed(2));
                    $(newRow).find('.gst-label').text('');

                    let gst = parseFloat(data.gst) || 0;
                    let halfGst = gst / 2;

                    $(newRow).find('input[name="gst_in_percentage[]"]').val(parseFloat(data.gst));
                    $(newRow).find('input[name="cgst_in_percentage[]"]').val(halfGst);
                    $(newRow).find('input[name="sgst_in_percentage[]"]').val(halfGst);

                    if (data.gst != '' && data.gst != null && data.gst != undefined && data.gst != 0) {
                        $("#is_gst_applicable").prop("checked", true);
                    }


                    if (data.tax_type?.trim()) {
                        $(".tax_type_dv").toggle(data.tax_type.trim() === 'product_wise');
                    }

                    $(newRow).find('input[name="price[]"]').change();
                    $('#product_id').val('').trigger('change');
                    $('#product_variant_price_id').val('').trigger('change');

                    if (orderTypeValue.toLowerCase() === "grosse") {
                        let $unitCell = $(newRow).find('input[name="unit[]"]').closest('td');

                        // Wrap input in a flex container so input + badge align properly
                        let $unitInput = $unitCell.find('input[name="unit[]"]');
                        $unitCell.css({
                            display: "flex",
                            "align-items": "center",
                            gap: "6px"
                        });

                        // Append gray badge
                        $unitCell.append(
                            `<span class="badge bg-secondary" style="font-size:12px;">qty : ${data.grosse_per_qty}</span>`
                        );
                    }

                    $('#order_type').val('').trigger('change');
                    $('#order_price').val('');

                    if (is_igst == 1) {
                        $(".cgst_div").hide();
                        $(".sgst_div").hide();
                        $(".igst_div").show();
                    } else {
                        $(".cgst_div").show();
                        $(".sgst_div").show();
                        $(".igst_div").hide();
                    }

                },
                error: function(xhr, status, error) {
                    console.error("Error: ", error);
                }
            });
        });

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("deleteRow")) {
                e.target.closest("tr").remove();
                recalculateRow(e.target);
            }
        });
        $('#order_type').val('').trigger('change');

        // Quick Add Customer Button
        $('#add_customer_btn').on('click', function() {
            // Open customer create modal or redirect to customer create page
            var customerCreateUrl = "{{ route('customer.create') }}";
            window.open(customerCreateUrl, '_blank', 'width=1200,height=800,scrollbars=yes');
        });

        // Handle "Same as Billing" checkbox
        $(document).ready(function() {
            // Check if editing and addresses are different
            @php
                $isSoSameAsBilling = true;
                if (isset($edit)) {
                    $soGst = $edit?->gst_no ?? ($edit?->customer?->gst_number ?? '');
                    $soShipGst = $edit?->shipping_gst_no ?? '';
                    $soBilling = trim($edit?->billing_address ?? '');
                    $soShipping = trim($edit?->shipping_address ?? '');
                    $soCustomerId = $edit?->shipping_customer_id ?? null;

                    if (!empty($soCustomerId)) {
                        $isSoSameAsBilling = false;
                    } elseif ($soBilling !== $soShipping) {
                        $isSoSameAsBilling = false;
                    } elseif (!empty($soShipGst) && !empty($soGst) && trim($soShipGst) !== trim($soGst)) {
                        $isSoSameAsBilling = false;
                    }
                }
            @endphp

            @if (!$isSoSameAsBilling)
                $('#same_as_billing').prop('checked', false);
                $('#shipping_address_div').show();
                loadShippingCustomers();
            @else
                $('#same_as_billing').prop('checked', true);
                $('#shipping_address_div').hide();
                copyBillingToShipping();
            @endif

            $('#same_as_billing').on('change', function() {
                if ($(this).is(':checked')) {
                    // Copy billing to shipping and hide the shipping div
                    copyBillingToShipping();
                    $('#shipping_customer_id').val('').trigger('change.select2');
                    $('#shipping_address_div').hide();
                } else {
                    // Show shipping address div for editing and empty all shipping fields
                    $('#shipping_address_div').show();
                    $('#shipping_customer_id').removeAttr('data-selectedShippingCustomerId');
                    $('#shipping_customer_id').val('').trigger('change.select2');
                    $('#shipping_gst_no').val('');
                    $('#shipping_address').val('');
                    loadShippingCustomers();
                }
            });

            $(document).on('change', '#shipping_customer_id', function() {
                fetchShippingCustomerInfo();
            });

            $(document).on('input change keyup', '#billing_address, #gst_no', function() {
                copyBillingToShipping();
            });

            $(document).ajaxSuccess(function(event, xhr, settings) {
                if (settings.url && settings.url.indexOf('get-customer-info') !== -1) {
                    if ($('#same_as_billing').is(':checked')) {
                        copyBillingToShipping();
                    }
                }
            });
        });

        // Function to copy billing to shipping when checkbox is checked
        function copyBillingToShipping() {
            if ($('#same_as_billing').is(':checked')) {
                $('#shipping_address').val($('#billing_address').val());
                $('#shipping_gst_no').val($('#gst_no').val());
                if ($('#shipping_customer_id').val()) {
                    $('#shipping_customer_id').val('').trigger('change.select2');
                }
            }
        }

        function getSalesOrderCompanyId() {
            let company_id = $(".search_by_company option:selected").val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }
            if (!company_id) {
                company_id = $('#company_id').val();
            }
            return company_id;
        }

        function loadShippingCustomers() {
            let company_id = getSalesOrderCompanyId();
            let selectedId = $('#shipping_customer_id').attr('data-selectedShippingCustomerId') || $('#shipping_customer_id').val();

            if (!company_id) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-customer',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id
                },
                success: function(response) {
                    if (!response.status) {
                        return;
                    }

                    let options = "<option value=''>Select Customer</option>";
                    if (response.data && response.data.length > 0) {
                        $.each(response.data, function(i, item) {
                            let customer_name = item.customer_name || '';
                            let client_code = item.client_code || '';
                            let gst_number = item.gst_number || '';
                            let ship_address = item.shipping_address || item.billing_address || item.address || '';
                            let customerText = client_code ? (client_code + ' - ' + customer_name) : customer_name;
                            if (gst_number) {
                                customerText += ' (GST: ' + gst_number + ')';
                            }
                            let selected = (selectedId && String(selectedId) === String(item.id)) ? 'selected' : '';
                            options += `<option value="${item.id}" ${selected} data-gst="${gst_number}" data-shipping-address="${ship_address}">${customerText}</option>`;
                        });
                    }

                    let $shippingSelect = $('#shipping_customer_id');
                    if ($shippingSelect.hasClass('select2-hidden-accessible')) {
                        $shippingSelect.select2('destroy');
                    }
                    $shippingSelect.empty().append(options);
                    $shippingSelect.select2({
                        placeholder: "Type to search ship to customer...",
                        allowClear: true,
                        width: '100%'
                    });

                    if (selectedId) {
                        $shippingSelect.val(String(selectedId)).trigger('change');
                    }
                }
            });
        }

        function fetchShippingCustomerInfo() {
            let customer_id = $('#shipping_customer_id').val();
            if (!customer_id) {
                $('#shipping_address').val('');
                $('#shipping_gst_no').val('');
                return;
            }

            let $selected = $('#shipping_customer_id option:selected');
            let quickGst = $selected.attr('data-gst') || '';
            let quickAddress = $selected.attr('data-shipping-address') || '';

            if (quickAddress) {
                $('#shipping_address').val(quickAddress);
            }
            if (quickGst) {
                $('#shipping_gst_no').val(quickGst);
            }

            let company_id = getSalesOrderCompanyId();

            if (!company_id) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-customer-info',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                    customer_id: customer_id
                },
                success: function(response) {
                    if (!response.status || !response.data) {
                        return;
                    }

                    let customerInfo = response.data;
                    let shipAddress = customerInfo.shipping_address || customerInfo.billing_address || customerInfo.address || '';
                    if (shipAddress) {
                        $('#shipping_address').val(shipAddress);
                    }
                    if (customerInfo.gst_number) {
                        $('#shipping_gst_no').val(customerInfo.gst_number);
                    }
                }
            });
        }




        // 🚀 AJAX Form Submission
        $(document).on('submit', '#salesOrderForm', function(e) {
            e.preventDefault();
            console.log("AJAX Form Submission Triggered");

            if ($('#same_as_billing').is(':checked')) {
                $('#shipping_address').val($('#billing_address').val());
                $('#shipping_gst_no').val($('#gst_no').val());
                $('#shipping_customer_id').val('');
            }

            let form = $(this);
            let url = form.attr('action');
            let formData = new FormData(this);

            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('.select2-selection').removeClass('border-danger');

            var submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    console.log("Success:", response);
                    if (response.status) {
                        toastr.success(response.message || "Sales Order saved successfully!");
                        setTimeout(function() {
                            window.location.href = "{{ route($route . '.index') }}";
                        }, 1000);
                    } else {
                        toastr.error(response.message || "Something went wrong!");
                        submitBtn.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error("Error:", xhr);
                    submitBtn.prop('disabled', false);

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        let firstErrorField = null;

                        Object.keys(errors).forEach(key => {
                            let $field;
                            if (key.includes('.')) {
                                let parts = key.split('.');
                                let fieldName = parts[0] + '[]';
                                let index = parts[1];
                                $field = $(`[name="${fieldName}"]`).eq(index);
                                if (!$field.length) {
                                    $field = $(`[name="${parts[0]}"]`).eq(index);
                                }
                            } else {
                                $field = $(`[name="${key}"]`);
                                if (!$field.length) {
                                    $field = $(`[name="${key}[]"]`).first();
                                }
                            }

                            if ($field.length) {
                                let $displayTarget = $field;
                                if (!$field.is(':visible')) {
                                    let $visibleAlt = $field.closest('td, .col-md-3, .col-md-4, .col-md-6, .col-sm-6, .col-md-12').find('input:visible, select:visible, textarea:visible, .form-control:visible').first();
                                    if ($visibleAlt.length) {
                                        $displayTarget = $visibleAlt;
                                    }
                                }

                                if ($displayTarget.length && $displayTarget.is(':visible')) {
                                    $displayTarget.addClass('is-invalid');

                                    if ($displayTarget.hasClass('select2-hidden-accessible') || $displayTarget.hasClass('select2')) {
                                        let $container = $displayTarget.next('.select2-container');
                                        if ($container.length) {
                                            $container.find('.select2-selection').addClass('border-danger');
                                            if ($container.next('.invalid-feedback').length === 0) {
                                                $container.after(`<span class="invalid-feedback d-block"><strong>${errors[key][0]}</strong></span>`);
                                            }
                                        } else {
                                            if ($displayTarget.next('.invalid-feedback').length === 0) {
                                                $displayTarget.after(`<span class="invalid-feedback d-block"><strong>${errors[key][0]}</strong></span>`);
                                            }
                                        }
                                    } else {
                                        if ($displayTarget.next('.invalid-feedback').length === 0) {
                                            $displayTarget.after(`<span class="invalid-feedback d-block"><strong>${errors[key][0]}</strong></span>`);
                                        }
                                    }

                                    if (!firstErrorField) firstErrorField = $displayTarget;
                                } else {
                                    toastr.error(errors[key][0]);
                                }
                            } else {
                                toastr.error(errors[key][0]);
                            }
                        });

                        if (firstErrorField) {
                            $('html, body').animate({
                                scrollTop: firstErrorField.offset().top - 150
                            }, 500);
                        }
                    } else {
                        toastr.error(xhr.responseJSON?.message || "An error occurred. Please try again.");
                    }
                }
            });
        });

        // 🧹 Clear error styling when input changes
        $(document).on('change keyup focus', 'input, select, textarea', function() {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).next('.select2-container').find('.select2-selection').removeClass('border-danger');
            }
        });
    </script>

    @include('utils.getTermsInfo')
@endpush
