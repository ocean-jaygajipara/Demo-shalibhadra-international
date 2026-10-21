@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    // if (!isset($edit)) {
    //     $edit = isset($convertToQuatation) ? $convertToQuatation : null;
    // }
    if (!isset($edit)) {
        $edit = isset($inquiry_to_quatation) ? $inquiry_to_quatation : null;
    }

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

@section('page_leavel_style')
    <style>
        .mt-minus-40 {
            margin-top: -30px !important;
        }
    </style>
@endsection

@section('content')
    <style>
        .ck-editor__editable_inline {
            min-height: 150px;
        }
    </style>

    <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
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
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST">
                @csrf
                @if (isset($edit) && !empty($edit?->id))
                    @method('PUT')
                @endif
                <input type="hidden" name="customer_name" id="customer_name"
                    value="{{ isset($edit) && $edit?->id ? $edit?->customer_name : '' }}" />

                <input type="hidden" name="contact_person_name" id="contact_person_name"
                    value="{{ $edit->contact_person_name ?? ($inquiry_to_quatation['contact_person_name'] ?? '') }}" />
                <input type="hidden" id="selected_customer_id"
                    value="{{ $edit->customer_id ?? ($inquiry_to_quatation['customer_id'] ?? '') }}">
                <input type="hidden" name="price_list_id" id="price_list_id"
                    value="{{ $edit->price_list_id ?? ($inquiry_to_quatation['price_list_id'] ?? '') }}" />
                <input type="hidden" name="state" id="state" value="{{ isset($edit) && $edit?->id ? $edit?->state : '' }}" />

                <input type="hidden" id="qty_is_value" value="" />
                <input type="hidden" id="is_igst"
                value="{{ isset($edit) && $edit?->id ? $edit?->customer->is_igst : '' }}" />
                <div class="">
                    <!-- Section 1: Customer Details -->
                    <h4 class="mb-3">Customer Details</h4>

                    <div class="mt-minus-40 text-end">
                        @if (!empty($quotationReviseCount))
                            <span class="badge bg-dark text-white"><strong>Revise:
                                    {{ $quotationReviseCount }}</strong></span>
                        @endif
                        @if (!empty($quotationRepeatCount))
                            &nbsp;&nbsp;<span class="badge bg-dark text-white"><strong>Repeat:
                                    {{ $quotationRepeatCount }}</strong></span>
                        @endif
                    </div>

                    <div class="row g-3">
                        @if (!$company_id)
                            <div class="col-md-3">
                                <label class="form-label">Select Company</label>
                                <select name="company_id" id="company_id"
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

                        <div class="col-md-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label mb-0">Search Customer</label>

                                <!-- Small + Button -->
                                <button type="button" id="add_prospect_btn" class="btn btn-sm btn-primary"
                                    style="padding: 6px 7px;line-height: 15px;font-size: 39px;">
                                    +
                                </button>
                            </div>

                            <select name="customer_id" id="customer_id"
                                class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2 mt-1"
                                data-append="search_by_customer"
                                data-selectedcustomerid="{{ $modules['selected_customer_id'] ?? ($edit->customer_id ?? '') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('customer_id', $edit->customer_id ?? '') ? '' : 'selected' }}>
                                    Select Customer
                                </option>
                            </select>

                            @error('customer_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Product Category <span class="text-danger">*</span></label>
                                <select name="product_category_id" id="product_category_id"
                                    class="form-control @error('product_category_id') is-invalid @enderror search_by_product_category select2"
                                    data-append="search_by_product_category"
                                    data-selectedCategoryId="{{ isset($edit) && $edit?->product_category_id ? $edit?->product_category_id : old('product_category_id') }}"
                                    autofocus>
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
                                value="{{ isset($edit) && $edit?->gst_no ? $edit?->gst_no : '' }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Terms & Conditions</label>
                            <select name="term_condition_id" id="terms_id"
                                class="form-control @error('term_condition_id') is-invalid @enderror search_by_terms select2"
                                data-append="search_by_terms"
                                data-selectedTermsId="{{ isset($edit) && $edit?->term_condition_id ? $edit?->term_condition_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('term_condition_id', $edit->term_condition_id ?? '') ? '' : 'selected' }}>
                                    Select Terms
                                </option>
                            </select>
                            @error('term_condition_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">

                    <div class="row">
                        <!-- Left Side: Customer Details -->
                        @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))

                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <div class="row align-items-center">
                                        <label class="col-4"><strong>Customer:</strong></label>
                                        <div class="col-8">
                                            <p class="mb-0"><span
                                                    id="customer_name_display">{{ $edit->customer_name ?? $edit->customer->customer_name ?? '-' }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-3 d-none">
                                    <div class="row align-items-center">
                                        <label class="col-4"><strong>Name:</strong></label>
                                        <div class="col-8">
                                            <p class="mb-0"><span
                                                    id="contact_person_name_display">{{ isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : 'N/A' }}</span>
                                            </p>
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
                                        <p class="mb-0"><span id="city_display">{{ old('city', isset($edit) ? ($edit->customer->city->name ?? '') : '') }}</span></p>
                                        <input type="hidden" id="city" value="{{ old('city', isset($edit) ? ($edit->customer->city->name ?? '') : '') }}">
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                    <label class="col-4"><strong>State:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span id="state_display">{{ old('state', isset($edit) ? ($edit->customer->state->name ?? '') : '') }}</span></p>
                                        <input type="hidden" id="state" value="{{ old('state', isset($edit) ? ($edit->customer->state->name ?? '') : '') }}">
                                    </div>
                                </div>
                                 <div class="row align-items-center">
                                    <label class="col-4"><strong>Pincode:</strong></label>
                                    <div class="col-8">
                                        <p class="mb-0"><span id="pincode_display">{{ old('pincode', isset($edit) ? ($edit->customer->pincode ?? '') : '') }}</span></p>
                                        <input type="hidden" id="pincode" value="{{ old('pincode', isset($edit) ? ($edit->customer->pincode ?? '') : '') }}">
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
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Quotation No</label>
                                    <input type="text" class="form-control readonly-look" name="quotation_no"
                                        id="quotation_no" placeholder="Enter Quotation No" readonly
                                        value="{{ isset($edit) && $edit?->quotation_no ? $edit?->quotation_no : '' }}">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label class="form-label">Quotation Date</label>
                                    <input type="text" id="quotation_date_display"
                                        class="form-control @error('quotation_date') is-invalid @enderror"
                                        value="{{ old('quotation_date', isset($edit->quotation_date) ? date('d-m-Y', strtotime($edit->quotation_date)) : date('d-m-Y')) }}"
                                        placeholder="DD-MM-YYYY" readonly />

                                    <input type="hidden" name="quotation_date" id="quotation_date"
                                        value="{{ old('quotation_date', isset($edit->quotation_date) ? date('Y-m-d', strtotime($edit->quotation_date)) : date('Y-m-d')) }}" />

                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Transport By</label>
                                    <select name="transport_by_id" id="transport_by_id"
                                        class="form-control @error('transport_by_id') is-invalid @enderror search_by_transport_by_id select2"
                                        data-append="search_by_transport_by_id"
                                        data-selectedTransportbyId="{{ isset($edit) && $edit?->transport_by_id ? $edit?->transport_by_id : '' }}"
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
                                        data-selectedTransportdetailsId="{{ isset($edit) && $edit?->transport_details_id ? $edit?->transport_details_id : '' }}"
                                        autofocus>
                                        <option value="" disabled
                                            {{ old('transport_details_id', $edit->transport_details_id ?? '') ? '' : 'selected' }}>
                                            Select Transporter Detail
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3">Address Details</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Shipping Address</label>
                            <textarea class="form-control" name="shipping_address" id="shipping_address" rows="2">{{ isset($edit) && $edit?->shipping_address ? $edit?->shipping_address : '' }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" name="billing_address" id="billing_address" rows="2">{{ isset($edit) && $edit?->billing_address ? $edit?->billing_address : '' }}</textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h4 class="mb-3">Quotation Items</h4>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select name="product_variant_price_id" id="product_variant_price_id"
                                class="form-control @error('product_variant_price_id') is-invalid @enderror search_by_product_variant select2"
                                data-append="search_by_product_variant"
                                data-selectedProductVariantId="{{ isset($edit) && $edit?->product_variant_price_id ? $edit?->product_variant_price_id : '' }}"
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
                                    <th style="min-width: 200px;">Item Code</th>
                                    <th style="min-width: 200px;">Product Description</th>
                                    <th style="min-width: 200px;">HSN</th>
                                    <th style="min-width: 200px;">Unit</th>
                                    <th style="min-width: 200px;">Kg / Pcs / Gross</th>
                                    <th style="min-width: 200px;">Price</th>
                                    <th style="min-width: 200px;">Rate</th>
                                    <th style="min-width: 200px;">Amount</th>
                                    <th style="min-width: 200px;" class="tax_type_dv">Taxable</th>

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
                                @if (isset($edit) && $edit?->quotation_items)
                                    @foreach ($edit?->quotation_items as $item)
                                        <tr>
                                            <input type="hidden" class="form-control" name="item_id[]"
                                                value="{{ $item->quotation_item_id }}">
                                            <input type="hidden" class="form-control" name="product_id[]"
                                                value="{{ $item->product_id }}">
                                            <input type="hidden" class="form-control" name="is_combo[]"
                                                value="{{ $item->is_combo }}">
                                            <input type="hidden" class="form-control grosse_per_qty"
                                                name="grosse_per_qty[]" value="{{ $item->product->grosse_per_qty }}">

                                            <input type="hidden" class="form-control" name="product_variant_price_id[]"
                                                value="{{ $item->product_variant_price_id }}">
                                            <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                            @php
                                                $prodPartNo = $item->product->part_no ?? $item->product_code ?? '';
                                                $custItemCode = '';
                                                if (isset($edit->customer) && $edit->customer->customer_name) {
                                                    $cItem = \App\Models\ProductCustomerItem::where('company_id', $edit->company_id)
                                                        ->where('product_id', $item->product_id)
                                                        ->where('customer_name', $edit->customer->customer_name)
                                                        ->whereNull('deleted_at')
                                                        ->first();
                                                    if ($cItem) {
                                                        $custItemCode = $cItem->customer_item_code ?: $cItem->customer_code;
                                                    }
                                                }
                                                $displayCode = $prodPartNo;
                                                if ($isDhruvik && !empty($custItemCode) && $custItemCode !== $prodPartNo) {
                                                    $displayCode .= ' (' . $custItemCode . ')';
                                                } elseif (empty($prodPartNo) || $prodPartNo === '-') {
                                                    $displayCode = $item->product_code ?? '';
                                                    if (!$isDhruvik && !empty($displayCode)) {
                                                        $displayCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayCode);
                                                    }
                                                }
                                            @endphp
                                            <td><label
                                                    class="product_code_txt">{{ $displayCode }}</label><input
                                                    type="hidden" class="form-control bg-light" name="product_code[]"
                                                    value="{{ $item->product_code ?? '' }}" readonly></td>
                                            <td><label
                                                    class="product_name_txt">{{ $item->product_name ?? '' }}</label><input
                                                    type="hidden" class="form-control bg-light" name="product_name[]"
                                                    value="{{ $item->product_name ?? '' }}" readonly></td>

                                            <input type="hidden" class="form-control" name="product_description[]"
                                                    value="{{ $item->product_description ?? '' }}">
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
                                                }}" onkeypress="{{ strtolower($item->unit) === 'kg' ? 'return isDecimal(event)' : 'return isNumber(event)' }}">
                                            </td>

                                            <td>
                                                <input type="text" class="form-control price"
                                                    onchange="recalculateRow(this)" name="price[]"
                                                    value="{{ $item->price_in_amount ?? '' }}"
                                                    onkeypress="return isDecimal(event)">
                                                @if ($item->product_variant_price?->including_gst == '1')
                                                    <div class="gst-label" style="font-size: 12px; color: gray;">
                                                        Including GST Price:
                                                        {{ $item->product_variant_price?->price ?? 0 }}
                                                    </div>
                                                @else
                                                    <div class="gst-label" style="font-size: 12px; color: gray;"></div>
                                                @endif

                                            </td>


                                            <td><input type="text" class="form-control rate_in_amount bg-light"
                                                    name="rate_in_amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->rate_in_amount ?? '' }}" readonly></td>
                                            <td><input type="text" class="form-control amount bg-light"
                                                    name="amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->amount ?? '' }}" readonly></td>
                                            <td class="tax_type_dv"><input type="text"
                                                    class="form-control taxable_amount bg-light" name="taxable_amount[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->taxable_amount ?? '' }}" readonly></td>
                                            <td class="igst_div tax_type_dv"><input type="text"
                                                    class="form-control gst_in_percentage bg-light"
                                                    name="gst_in_percentage[]" onkeypress="return isDecimal(event)"
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
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->sub_total ?? '' }}" readonly></td>
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
                                    <td><input type="number"
                                            value="{{ isset($edit) && $edit?->master_quantity ? $edit?->master_quantity : '' }}"
                                            class="form-control bg-light" readonly="" id="master_quantity"
                                            name="master_quantity"></td>
                                    <td></td>
                                    <td></td>
                                    <td><input type="number"
                                            value="{{ isset($edit) && $edit?->master_amount ? $edit?->master_amount : '' }}"
                                            class="form-control bg-light" readonly="" name="master_amount"
                                            id="master_amount"></td>
                                    <td class="tax_type_dv"><input type="number"
                                            value="{{ isset($edit) && $edit?->master_taxable_amount ? $edit?->master_taxable_amount : '' }}"
                                            class="form-control bg-light" readonly="" name="master_taxable_amount"
                                            id="master_taxable_amount"></td>
                                    {{-- <td class="tax_type_dv"></td> --}}
                                    <td class="tax_type_dv d-none"><input type="number"
                                            value="{{ isset($edit) && $edit?->master_gst_amount ? $edit?->master_gst_amount : '' }}"
                                            class="form-control bg-light" readonly="" name="master_gst_amount"
                                            id="master_gst_amount"></td>
                                    <td class="cgst_div"></td>
                                    <td class="cgst_div"></td>
                                    <td class="sgst_div"></td>
                                    <td class="sgst_div"></td>
                                    <td class="igst_div"></td>
                                    <td class="igst_div"></td>
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
                                        <input type="text"
                                            value="{{ isset($edit) && $edit?->total_taxable ? $edit?->total_taxable : '' }}"
                                            class="form-control bg-light" value="0.0000" id="total_taxable"
                                            name="total_taxable" readonly>
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
                                            class="form-control bg-light" value="0.0000" id="gst_amount"
                                            name="gst_amount" readonly>
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
                                            class="form-control bg-light" value="0.0000" id="tcs_amount"
                                            name="tcs_amount" readonly>
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
                                            class="form-control bg-light fw-semibold fs-5" value="0.0000"
                                            id="grand_total" name="grand_total" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h4 class="mb-3">Extra Details</h4>

                    <div class="col-md-12 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label"> Terms and Condition</label>
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
                            <label class="form-label">Regards</label>
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
                            <label class="form-label">Note</label>
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
                            $showButtons = !isset($edit) || isset($inquiry_to_quatation) || !empty($edit->created_by);
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
        flatpickr("#quotation_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('quotation_date', isset($edit->quotation_date) ? date('d-m-Y', strtotime($edit->quotation_date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('quotation_date').value = ymd; // hidden input for submission
            }
        });
    </script>

    <script>
        const isDhruvikUser = {{ $isDhruvik ? 'true' : 'false' }};
        let termsInstance;
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif


    @if (!isset($edit))
        @include('utils.generateQuotationNo')
    @endif
    @include('utils.getProductCategory')
    @include('utils.getTransportby')
    @include('utils.getTransportDetails')
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

        function validateFlatDiscount(input) {
            var price = $(input).closest('tr').find('.price').val();
            var discount = parseFloat(input.value);

            if (discount > parseFloat(price)) {
                toastr.error("Discount (Flat) cannot be more than Price (in INR)!");
                input.value = '';
            }
        }

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

                <td class="sr_no_txt"></td>
                <td><input type="hidden" name="product_code[]" readonly><label class="product_code_txt"></label></td>
                <td><input type="hidden" name="product_name[]" readonly><label class="product_name_txt"></label></td>
                <input type="hidden" class="form-control" name="product_description[]">
                <td><input type="text" class="form-control bg-light" name="hsn_code[]" readonly></td>
                <td><input type="text" class="form-control unit bg-light" name="unit[]" readonly></td>
                <td><input type="text" class="form-control quantity" onchange="updateInnerAndOuterFromQty(this)" name="quantity[]" value="1"></td>
                <td>
                    <input type="text" class="form-control price" onchange="recalculateRow(this)" name="price[]" value="0" onkeypress="return isDecimal(event)">
                    <div class="gst-label" style="font-size: 12px; color: gray;"></div>
                </td>

                <td><input type="text" class="form-control rate_in_amount bg-light" name="rate_in_amount[]" value="0" readonly onkeypress="return isDecimal(event)"></td>
                <td><input type="text" class="form-control amount bg-light" name="amount[]" value="0" readonly onkeypress="return isDecimal(event)"></td>
                <td class="tax_type_dv"><input type="text" class="form-control taxable_amount bg-light" name="taxable_amount[]" value="0" readonly onkeypress="return isDecimal(event)"></td>
                <td class="igst_div tax_type_dv"><input type="text" class="form-control gst_in_percentage bg-light" name="gst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="igst_div tax_type_dv"><input type="text" class="form-control gst_amount bg-light" name="gst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="tax_type_dv"><input type="text" class="form-control subtotal bg-light" name="subtotal[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td><button type="button" class="btn btn-sm btn-danger deleteRow remove-row">X</button></td>
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
                    $("#productBody tr:last").find('input[name="product_name[]"]').val(data.product_name);
                    $("#productBody tr:last").find('input[name="is_combo[]"]').val(isCombo ? 1 : 0);
                    $("#productBody tr:last").find('.product_name_txt').text(data.product_name);
                    $("#productBody tr:last").find('.sr_no_txt').text(srNo);

                    let custItemCode = data.customer_item_code || data.customer_code || '';
                    let custItemDesc = data.customer_item_description || '';
                    let partNo = data.part_no || '';

                    let customerParts = [];
                    if (custItemCode && custItemCode !== partNo) {
                        customerParts.push(custItemCode);
                    }
                    if (custItemDesc && custItemDesc !== data.product_name && !customerParts.includes(custItemDesc)) {
                        customerParts.push(custItemDesc);
                    }
                    let customerInfoText = customerParts.join(' ');

                    let displayCode = partNo;
                    if (isDhruvikUser && customerInfoText) {
                        displayCode = `${partNo} (${customerInfoText})`;
                    }
                    $("#productBody tr:last").find('input[name="product_code[]"]').val(data.part_no);
                    $("#productBody tr:last").find('.product_code_txt').text(displayCode);

                    $('#product_variant_price_id').val('').trigger('change');

                    $("#productBody tr:last").find('input[name="product_id[]"]').val(data.product_id);
                    $("#productBody tr:last").find('input[name="product_description[]"]').val(data
                        .description);
                    $("#productBody tr:last").find('input[name="product_variant_price_id[]"]').val(data
                        .product_variant_price_id);

                    $("#productBody tr:last").find('input[name="hsn_code[]"]').val(data.hsn_code);
                    $("#productBody tr:last").find('input[name="unit[]"]').val(orderTypeValue);
                    $("#productBody tr:last").find('input[name="quantity[]"]').val(orderPrice);
                    $("#productBody tr:last").find('input[name="grosse_per_qty[]"]').val(data
                        .grosse_per_qty);

                    let unitVal = orderTypeValue.toLowerCase();
                    let qtyInput = $("#productBody tr:last").find('input[name="quantity[]"]');

                    if (unitVal === "kg") {
                        qtyInput.attr("onkeypress", "return isDecimal(event)");
                    } else {
                        qtyInput.attr("onkeypress", "return isNumber(event)");
                    }

                    // if (data.including_gst == 0) {
                    //     $("#productBody tr:last").find('input[name="price[]"]').val();
                    //     $("#productBody tr:last").find('.gst-label').text('');
                    // } else {
                    //     let new_price = data.price / (1 + parseFloat(data.gst) / 100);
                    //     $("#productBody tr:last").find('input[name="price[]"]').val(new_price.toFixed(
                    //         2));
                    // }

                    $("#productBody tr:last").find('input[name="price[]"]').val(parseFloat(data.price || 0).toFixed(2));
                    $("#productBody tr:last").find('.gst-label').text('');

                     let gst = parseFloat(data.gst) || 0;
                    let halfGst = gst / 2;

                    $("#productBody tr:last").find('input[name="gst_in_percentage[]"]').val(parseFloat(
                        data.gst));
                    $("#productBody tr:last").find('input[name="cgst_in_percentage[]"]').val(halfGst);
                    $("#productBody tr:last").find('input[name="sgst_in_percentage[]"]').val(halfGst);


                    if (data.gst != '' && data.gst != null && data.gst != undefined && data.gst != 0) {
                        $("#is_gst_applicable").prop("checked", true);
                    }


                    if (data.tax_type?.trim()) {
                        $(".tax_type_dv").toggle(data.tax_type.trim() === 'product_wise');
                    }

                    $("#productBody tr:last").find('input[name="price[]"]').change();
                    $('#product_id').val('').trigger('change');
                    $('#product_variant_price_id').val('').trigger('change');

                    if (orderTypeValue.toLowerCase() === "grosse") {
                        let $unitCell = $("#productBody tr:last").find('input[name="unit[]"]').closest(
                            'td');

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


                    let is_igst = $("#is_igst").val();
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
    </script>
    <script>
        $(document).on('click', '#add_prospect_btn', function() {
            // Store a flag in sessionStorage so the form knows to redirect back
            sessionStorage.setItem('redirect_to_quotation', '1');
            window.location.href = "{{ route('prospected-customer.create') }}";
        });
    </script>



    @include('utils.getTermsInfo')
@endpush
