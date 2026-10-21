@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $isEdit = isset($edit) && $edit?->invoice_order_no ? $edit?->invoice_order_no : null;

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

        .shipping-customer-readonly .select2-container {
            pointer-events: none !important;
        }

        .shipping-customer-readonly .select2-container .select2-selection {
            background-color: #f8f9fa !important;
            cursor: not-allowed !important;
            border-color: #d9dee3 !important;
        }
    </style>

    <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
            @php
                if (isset($quotation_id) && $quotation_id) {
                    $breadcrumbTitle = 'Quotation to ' . $page_title;
                } elseif ($isEdit) {
                    $breadcrumbTitle = 'Edit ' . $page_title;
                } else {
                    $breadcrumbTitle = 'Create ' . $page_title;
                }
            @endphp


            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [
                    ['title' => $page_title, 'url' => route($route . '.index')],
                    [
                        'title' => $breadcrumbTitle,
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

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form
                action="{{ $is_edit_invoice ? route($route . '.store') : route($route . '.update', [$edit->id]) }}"
                method="POST" enctype="multipart/form-data">

                @csrf

                @if (!$is_edit_invoice)
                    @method('PUT')
                @endif

                <input type="hidden" name="company_id" id="company_id"
                    value="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}" />
                <input type="hidden" name="customer_name" id="customer_name"
                    value="{{ isset($edit) && $edit?->id ? $edit?->customer_name : '' }}" />
                <input type="hidden" name="contact_person_name" id="contact_person_name"
                    value="{{ isset($edit) && $edit?->id ? $edit?->contact_person_name : '' }}" />
                <input type="hidden" name="price_list_id" id="price_list_id"
                    value="{{ isset($edit) && $edit?->id ? $edit?->price_list_id : '' }}" />
                <input type="hidden" name="packing_slip_id" id="packing_slip_id"
                    value="{{ isset($packing_slip_id) && $packing_slip_id ? $packing_slip_id : (isset($edit) && $edit?->id ? $edit?->packing_slip_id : '') }}" />
                <input type="hidden" name="sales_order_id" id="sales_order_id"
                    value="{{ isset($edit) && $edit?->id ? $edit?->sales_order_id : '' }}" />
                <input type="hidden" id="is_igst" name="is_igst"
                    value="{{ isset($edit) && $edit?->id ? $edit?->customer->is_igst : '' }}" />
                <input type="hidden" name="state" id="state"
                    value="{{ isset($edit) && $edit?->id ? $edit?->state : '' }}" />
                <div class="">
                    <!-- Section 1: Customer Details -->
                    <h4 class="mb-3">Customer Details</h4>
                    <div class="row g-3">

                        @if (!$company_id)
                            <div class="col-md-3">
                                <label>Select Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id" disabled
                                    class="form-control readonly-look @error('company_id') is-invalid @enderror search_by_company select2"
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
                            <label class="form-label">Search Customer</label>
                            <select id="customer_id_display"
                                class="form-control select2 search_by_customer @error('customer_id') is-invalid @enderror"
                                disabled data-append="search_by_customer"
                                data-selectedcustomerid="{{ old('customer_id', $edit->customer_id ?? '') }}">
                                <option value="" disabled
                                    {{ old('customer_id', $edit->customer_id ?? '') ? '' : 'selected' }}>
                                    Select Customer
                                </option>
                            </select>
                            <input type="hidden" name="customer_id" id="customer_id"
                                value="{{ old('customer_id', $edit->customer_id ?? '') }}">

                            @error('customer_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>


                        <div class="col-md-2 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Product Category <span class="text-danger">*</span></label>

                                <!-- Disabled Select2 (UI Only) -->
                                <select id="product_category_id_display"
                                    class="form-control select2 search_by_product_category @error('product_category_id') is-invalid @enderror"
                                    data-append="search_by_product_category"
                                    data-selectedCategoryId="{{ old('product_category_id', $edit->sales_order->product_category_id ?? '') }}">
                                    <option value="" disabled
                                        {{ old('product_category_id', $edit->sales_order->product_category_id ?? '') ? '' : 'selected' }}>
                                        Select Product Category
                                    </option>
                                </select>

                                <!-- Hidden input that actually submits to DB -->
                                <input type="hidden" name="product_category_id" id="product_category_id"
                                    value="{{ old('product_category_id', $edit->sales_order->product_category_id ?? '') }}">

                                @error('product_category_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>


                        <div class="col-md-2">
                            <label class="form-label">GSTIN</label>
                            <input type="text" class="form-control" name="gst_no" id="gst_no"
                                value="{{ old('gst_no', $edit?->gst_no ?? ($edit?->sales_order?->gst_no ?? '')) }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Invoice Type <span class="text-danger">*</span></label>
                            <select name="invoice_type" id="invoice_type"
                                class="form-control select2 @error('invoice_type') is-invalid @enderror">
                                <option value="">Select Type</option>
                                @foreach (config('constants.invoice_type') as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ (old('invoice_type') !== null)
                                            ? (old('invoice_type') == $key ? 'selected' : '')
                                            : ((isset($edit) && $edit->invoice_type)
                                                ? ($edit->invoice_type == $key ? 'selected' : '')
                                                : ($key == 'tax' ? 'selected' : '')) }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                            @error('invoice_type')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Terms & Conditions</label>
                            <select name="term_condition_id" id="terms_id"
                                class="form-control select2 search_by_terms @error('term_condition_id') is-invalid @enderror"
                                data-append="search_by_terms"
                                data-selectedTermsId="{{ old('term_condition_id', $edit->term_condition_id ?? ($edit->sales_order->term_condition_id ?? '')) }}">
                                <option value="" disabled
                                    {{ old('term_condition_id', $edit->term_condition_id ?? ($edit->sales_order->term_condition_id ?? '')) ? '' : 'selected' }}>
                                    Select Terms
                                </option>
                            </select>

                            @error('term_condition_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <!-- PO Number -->
                        <div class="col-md-2">
                            <label class="form-label">Packing Slip No</label>
                            <input type="text" name="packing_slip_no"
                                class="form-control readonly-look @error('packing_slip_no') is-invalid @enderror"
                                value="{{ old('packing_slip_no', $edit->packing_slip_no ?? ($edit->packing_slips->packing_slip_no ?? '')) }}"
                                placeholder="Enter Packing Slip No">
                            @error('packing_slip_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>


                        <!-- PO Date -->
                        <div class="col-md-2 mt-3">
                            <label class="form-label">Packing Slip Date</label>
                            <input type="text" id="packing_slip_date_display"
                                class="form-control readonly-look @error('packing_slip_date') is-invalid @enderror"
                                value="{{ old(
                                    'packing_slip_date',
                                    isset($edit->packing_slip_date)
                                        ? date('d-m-Y', strtotime($edit->packing_slip_date))
                                        : (isset($edit->packing_slips->packing_slip_date)
                                            ? date('d-m-Y', strtotime($edit->packing_slips->packing_slip_date))
                                            : date('d-m-Y')),
                                ) }}"
                                placeholder="DD-MM-YYYY" readonly>
                            <input type="hidden" name="packing_slip_date" id="packing_slip_date"
                                value="{{ old(
                                    'packing_slip_date',
                                    isset($edit->packing_slip_date)
                                        ? date('Y-m-d', strtotime($edit->packing_slip_date))
                                        : (isset($edit->packing_slips->packing_slip_date)
                                            ? date('Y-m-d', strtotime($edit->packing_slips->packing_slip_date))
                                            : date('Y-m-d')),
                                ) }}">
                        </div>

                        {{-- <div class="col-md-2">
                            <label class="form-label">Invoice No</label>
                            <input type="text" class="form-control readonly-look" name="invoice_order_no"
                                id="invoice_order_no" placeholder="Enter Invoice No" readonly
                                value="{{ isset($edit) && $edit?->invoice_order_no ? $edit?->invoice_order_no : '' }}">
                        </div> --}}

                        <div class="col-md-2 mt-3">
                            <label class="form-label">Invoice Date</label>
                            <input type="text" id="invoice_order_date_display"
                                class="form-control @error('invoice_order_date') is-invalid @enderror"
                                value="{{ old('invoice_order_date', isset($edit->invoice_order_date) ? date('d-m-Y', strtotime($edit->invoice_order_date)) : date('d-m-Y')) }}"
                                placeholder="DD-MM-YYYY" readonly />

                            <input type="hidden" name="invoice_order_date" id="invoice_order_date"
                                value="{{ old('invoice_order_date', isset($edit->invoice_order_date) ? date('Y-m-d', strtotime($edit->invoice_order_date)) : date('Y-m-d')) }}" />

                        </div>
                        <!-- <div class="col-md-3 mt-3">
                                                <label class="form-label">Expected Dispatch Date</label>
                                                <input type="text" id="expected_dispatch_date_display"
                                                    class="form-control @error('expected_dispatch_date') is-invalid @enderror"
                                                    value="{{ old('expected_dispatch_date', isset($edit->expected_dispatch_date) ? date('d-m-Y', strtotime($edit->expected_dispatch_date)) : date('d-m-Y')) }}"
                                                    placeholder="DD-MM-YYYY" readonly />

                                                <input type="hidden" name="expected_dispatch_date" id="expected_dispatch_date"
                                                    value="{{ old('expected_dispatch_date', isset($edit->expected_dispatch_date) ? date('Y-m-d', strtotime($edit->expected_dispatch_date)) : date('Y-m-d')) }}" />
                                            </div> -->

                        <div class="col-md-2 mt-3">
                            <label class="form-label">Transport By</label>
                            <select name="transport_by_id" id="transport_by_id"
                                class="form-control select2 search_by_transport_by_id @error('transport_by_id') is-invalid @enderror"
                                data-append="search_by_transport_by_id"
                                data-selectedTransportbyId="{{ old('transport_by_id', $edit->transport_by_id ?? ($edit->sales_order->transport_by_id ?? ($transport_by_id ?? ''))) }}">
                                <option value="" disabled
                                    {{ old('transport_by_id', $edit->transport_by_id ?? ($edit->sales_order->transport_by_id ?? '')) ? '' : 'selected' }}>
                                    Select Transport By
                                </option>
                            </select>
                            @error('transport_by_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2 mt-3">
                            <label class="form-label">Transporter Detail</label>
                            <select name="transport_details_id" id="transport_details_id"
                                class="form-control select2 search_by_transport_details_id @error('transport_details_id') is-invalid @enderror"
                                data-append="search_by_transport_details_id"
                                data-selectedTransportdetailsId="{{ old('transport_details_id', $edit->transport_details_id ?? ($edit->sales_order->transport_details_id ?? ($transport_details_id ?? ''))) }}">
                                <option value="" disabled
                                    {{ old('transport_details_id', $edit->transport_details_id ?? ($edit->sales_order->transport_details_id ?? '')) ? '' : 'selected' }}>
                                    Select Transporter Detail
                                </option>
                            </select>
                            @error('transport_details_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Side: Customer Details -->
                        @if (
                            $modules['currentGuard'] === 'admin_software' ||
                                ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
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
                                                        id="contact_person_name_display">{{ isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : $edit?->sales_order?->contact_person_name }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <div class="row align-items-center">
                                            <label class="col-4"><strong>Phone:</strong></label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="phone" id="phone"
                                                    value="{{ old('phone', $edit->phone ?? $edit->customer->mobile_number ?? '') }}"
                                                    placeholder="Enter phone">
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
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3">Address Details</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Billing Address</label>
                            <textarea class="form-control" name="billing_address" id="billing_address" rows="2"
                                onkeyup="copyBillingToShipping()">{{ isset($edit) && $edit?->billing_address ? $edit?->billing_address : '' }}</textarea>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="same_as_billing" value="1" id="same_as_billing" checked>
                                <label class="form-check-label" for="same_as_billing">
                                    Shipping Address is same as Billing Address
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6" id="shipping_address_div" style="display: none;">
                            <div class="mb-2 shipping-customer-readonly">
                                <label class="form-label">Ship To Customer</label>
                                @php
                                    $shippingCustomer = $edit->shipping_customer ?? ($edit->sales_order?->shipping_customer ?? null);
                                    $selectedShipCustId = old('shipping_customer_id', $edit->shipping_customer_id ?? ($edit->sales_order?->shipping_customer_id ?? ''));
                                    if (!$shippingCustomer && $selectedShipCustId) {
                                        $shippingCustomer = \App\Models\Customer::find($selectedShipCustId);
                                    }
                                @endphp
                                <select id="shipping_customer_id_select"
                                    class="form-control select2"
                                    disabled
                                    data-selectedShippingCustomerId="{{ $selectedShipCustId }}"
                                    style="background-color: #f8f9fa; cursor: not-allowed; pointer-events: none;">
                                    <option value="">Select Customer</option>
                                    @if ($shippingCustomer)
                                        <option value="{{ $shippingCustomer->id }}" selected>
                                            {{ ($shippingCustomer->client_code ? $shippingCustomer->client_code . ' - ' : '') . $shippingCustomer->customer_name . ($shippingCustomer->gst_number ? ' (GST: ' . $shippingCustomer->gst_number . ')' : '') }}
                                        </option>
                                    @endif
                                </select>
                                <input type="hidden" name="shipping_customer_id" id="shipping_customer_id"
                                    value="{{ $selectedShipCustId }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Shipping GSTIN</label>
                                <input type="text" class="form-control" name="shipping_gst_no" id="shipping_gst_no"
                                    value="{{ old('shipping_gst_no', $edit->shipping_gst_no ?? '') }}"
                                    placeholder="Ship To GST Number">
                            </div>
                            <label class="form-label">Shipping Address</label>
                            <textarea class="form-control" name="shipping_address" id="shipping_address" rows="2">{{ isset($edit) && $edit?->shipping_address ? $edit?->shipping_address : '' }}</textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h3 class="mt-3">E-Way Bill Details</h3>
                    <div class="row">
                        {{-- <div class="col-md-4">
                            <label>Distance (KM) <span class="text-danger">*</span></label>
                            <input type="number" name="distance"
                                class="form-control"
                                value="{{ old('distance', $edit?->ewayBill?->distance ?? '') }}">
                        </div> --}}

                        <div class="col-md-4">
                            <label>Vehicle Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_no"
                                class="form-control"
                                value="{{ old('vehicle_no', $edit?->ewayBill?->currentVehicle?->vehicle_no ?? $edit?->ewayBill?->vehicle_no) }}">
                        </div>

                        <div class="col-md-4">
                            <label>Vehicle Type <span class="text-danger">*</span></label>
                            <select name="vehicle_type" class="form-control">
                                <option value="">Select</option>
                                <option value="R" {{ old('vehicle_type', $edit?->ewayBill?->currentVehicle?->vehicle_type ?? $edit?->ewayBill?->vehicle_type) == 'R' ? 'selected' : '' }}>Regular</option>
                                <option value="RW" {{ old('vehicle_type', $edit?->ewayBill?->currentVehicle?->vehicle_type ?? $edit?->ewayBill?->vehicle_type) == 'RW' ? 'selected' : '' }}>Rail / Water</option>
                                <option value="O" {{ old('vehicle_type', $edit?->ewayBill?->currentVehicle?->vehicle_type ?? $edit?->ewayBill?->vehicle_type) == 'O' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label>L. R. No. </label>
                            <input type="text" name="lr_no"
                                class="form-control"
                                value="{{ old('lr_no', $edit?->ewayBill?->currentVehicle?->lr_no ?? $edit?->ewayBill?->lr_no) }}">
                        </div>

                        <div class="col-md-4 mt-3">
                            <label class="form-label">L. R. Date </label>
                            <input type="text" id="lr_date_display"
                                class="form-control @error('lr_date') is-invalid @enderror"
                                value="{{ old('lr_date', isset($edit?->ewayBill?->lr_date) ? date('d-m-Y', strtotime($edit?->ewayBill?->lr_date)) : date('d-m-Y')) }}"
                                placeholder="DD-MM-YYYY" />

                            <input type="hidden" name="lr_date" id="lr_date"
                                value="{{ old('lr_date', isset($edit?->ewayBill?->lr_date) ? date('Y-m-d', strtotime($edit?->ewayBill?->lr_date)) : date('Y-m-d')) }}" />
                        </div>
                    </div>

                    <hr class="my-4">
                    <h4 class="mb-3">Invoice Items</h4>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select name="product_variant_price_id" id="product_variant_price_id"
                                class="form-control @error('product_variant_price_id') is-invalid @enderror search_by_product_variant select2"
                                data-append="search_by_product_variant" autofocus>
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
                                    value="" placeholder="Enter" step="0.01" min="0">
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
                                    <th style="min-width: 200px;">Product Code</th>
                                    <th style="min-width: 200px;">Product Description</th>
                                    <th style="min-width: 200px;">HSN</th>
                                    <th style="min-width: 200px;">Unit</th>
                                    <th style="min-width: 200px;">Kg / Qty / Grosse</th>
                                    <th style="min-width: 200px;">Price</th>
                                    <th class="d-none" style="min-width: 200px;">Rate</th>
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
                                {{-- Show invoice_items when editing existing invoice (is_edit_invoice = false) --}}
                                @if (isset($edit) &&
                                        isset($is_edit_invoice) &&
                                        !$is_edit_invoice &&
                                        isset($edit->id) &&
                                        $edit->invoice_items &&
                                        count($edit->invoice_items) > 0)
                                    @foreach ($edit->invoice_items as $item)

                                        @php
                                            $product_tax = (float)$item->product_hsn?->product_tax ?? '';
                                            $igst = $product_tax ?? '';
                                            $cgst = $product_tax / 2 ?? '';
                                            $sgst = $product_tax / 2 ?? '';
                                        @endphp
                                        <tr>
                                            <input type="hidden" class="form-control" name="item_id[]"
                                                value="{{ $item->quotation_item_id }}">
                                            <input type="hidden" class="form-control" name="product_id[]"
                                                value="{{ $item->product_id }}">
                                            <input type="hidden" class="form-control" name="is_combo[]"
                                            value="{{ $item->is_combo }}">
                                            <input type="hidden" class="form-control grosse_per_qty"
                                                name="grosse_per_qty[]" value="{{ $item->product?->grosse_per_qty }}">

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
                                            <td>
                                                <input type="text" class="form-control bg-light" name="hsn_code[]"
                                                    value="{{ $item->hsn_code && strtolower($item->hsn_code) !== 'null' ? $item->hsn_code : '' }}"
                                                    data-product_tax="{{ $item->product_hsn?->product_tax ?? '' }}"
                                                    readonly>
                                            </td>

                                            <td style="display: flex; align-items: center; gap: 6px;">
                                                <input type="text" class="form-control unit bg-light" name="unit[]"
                                                    readonly value="{{ $item->unit ?? '' }}">

                                                @if (!empty($item->unit) && strtolower($item->unit) === 'gross')
                                                    <span class="badge bg-secondary" style="font-size:12px;">
                                                        qty : {{ $item->product->grosse_per_qty ?? 0 }}
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                <input type="text" class="form-control quantity"
                                                    onchange="updateInnerAndOuterFromQty(this)" name="quantity[]"
                                                    value="{{ strtolower($item->unit) === 'kg'
                                                        ? number_format($item->quantity ?? 0, 3, '.', '')
                                                        : (int) ($item->quantity ?? 0) }}"
                                                    onkeypress="{{ strtolower($item->unit) === 'kg' ? 'return isDecimal(event)' : 'return isNumber(event)' }}">
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

                                            <td class="d-none"><input type="text"
                                                    class="form-control rate_in_amount bg-light" name="rate_in_amount[]"
                                                    onkeypress="return isDecimal(event)"
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
                                                    value="{{ $item->gst_in_percentage ?? '' }}"
                                                    data-original-gst="{{ $item->gst_in_percentage ?? '' }}" readonly></td>
                                            <td class="igst_div tax_type_dv"><input type="text"
                                                    class="form-control gst_amount bg-light" name="gst_amount_array[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->gst_amount ?? '' }}" readonly></td>

                                            <td class="cgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="cgst_in_percentage[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->cgst_in_percentage ?? '' }}"
                                                    data-original-cgst="{{ $item->cgst_in_percentage ?? '' }}" readonly></td>
                                            <td class="cgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="cgst_amount_array[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->cgst_amount ?? '' }}" readonly></td>


                                            <td class="sgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="sgst_in_percentage[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->sgst_in_percentage ?? '' }}"
                                                    data-original-sgst="{{ $item->sgst_in_percentage ?? '' }}" readonly></td>
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

                                {{-- Only show packing_slip_item when creating NEW invoice from packing slip (is_edit_invoice = true) --}}
                                @if (isset($is_edit_invoice) && $is_edit_invoice && isset($edit) && $edit?->packing_slip_item)
                                    @php
                                        $total_gst_amount = 0;
                                        $total_gst_percentage = 0;
                                        $is_igst = 0;
                                    @endphp
                                    @foreach ($edit?->packing_slip_item as $item)

                                        @php
                                            $cgst_amount = floatval($item->sales_order_item->cgst_amount ?? 0);
                                            $sgst_amount = floatval($item->sales_order_item->sgst_amount ?? 0);
                                            $gst_amount = floatval($item->sales_order_item->gst_amount ?? 0);

                                            $cgst_per = floatval($item->sales_order_item->cgst_in_percentage ?? 0);
                                            $sgst_per = floatval($item->sales_order_item->sgst_in_percentage ?? 0);
                                            $gst_per = floatval($item->sales_order_item->gst_in_percentage ?? 0);

                                            // Default
                                            $row_gst_amount = 0;
                                            $row_gst_percentage = 0;

                                            // ✔ Condition: If both CGST and SGST exist, use them
                                            if ($cgst_amount > 0 && $sgst_amount > 0) {
                                                $row_gst_amount = $cgst_amount + $sgst_amount;
                                                $row_gst_percentage = $cgst_per + $sgst_per;
                                            } else {
                                                $is_igst = 1;
                                                $row_gst_amount = $gst_amount;
                                                $row_gst_percentage = $gst_per;
                                            }
                                        @endphp
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
                                            <td><label
                                                    class="product_code_txt">{{ $item->product_code ?? '' }}</label><input
                                                    type="hidden" class="form-control bg-light" name="product_code[]"
                                                    value="{{ $item->product_code ?? '' }}" readonly></td>

                                            <td><label class="product_name_txt">{!! $item?->product_name ?? '' !!}</label><input
                                                    type="hidden" class="form-control bg-light" name="product_name[]"
                                                    value="{{ $item?->product_name ?? '' }}" readonly></td>

                                            <input type="hidden" class="form-control" name="product_description[]"
                                                value="{{ $item->product_description == 'null' || $item->product_description == null ? '' : $item->product_description }}">

                                            <td><input type="text" class="form-control bg-light" name="hsn_code[]"
                                                    value="{{ $item->hsn_code && strtolower($item->hsn_code) !== 'null' ? $item->hsn_code : '' }}"
                                                    data-product_tax="{{ $item->product_hsn?->product_tax ?? '' }}"
                                                    readonly></td>

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
                                                        ? number_format($item->total_qty ?? 0, 3, '.', '')
                                                        : (int) ($item->total_qty ?? 0) }}"
                                                    onkeypress="{{ strtolower($item->unit) === 'kg' ? 'return isDecimal(event)' : 'return isNumber(event)' }}">
                                            </td>

                                            <td>
                                                <input type="text" class="form-control price"
                                                    onkeyup="recalculateRow(this)" onchange="recalculateRow(this)"
                                                    name="price[]"
                                                    value="{{ $item->sales_order_item->price_in_amount ?? '' }}"
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
                                            <td class="d-none"><input type="text"
                                                    class="form-control rate_in_amount bg-light" name="rate_in_amount[]"
                                                    onkeypress="return isDecimal(event)"
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
                                                    value="{{ $row_gst_percentage ?? 0 }}" data-original-gst="{{ $row_gst_percentage ?? 0 }}"
                                                    readonly></td>
                                            <td class="igst_div tax_type_dv"><input type="text"
                                                    class="form-control gst_amount bg-light" name="gst_amount_array[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $row_gst_amount ?? '' }}" readonly>
                                            </td>


                                            <td class="cgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="cgst_in_percentage[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ number_format($row_gst_percentage / 2, 2) ?? '' }}"
                                                    data-original-cgst="{{ number_format($row_gst_percentage / 2, 2) ?? '' }}" readonly></td>
                                            <td class="cgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="cgst_amount_array[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ number_format($row_gst_amount / 2, 2) ?? '' }}" readonly>
                                            </td>


                                            <td class="sgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="sgst_in_percentage[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ number_format($row_gst_percentage / 2, 2) ?? '' }}"
                                                    data-original-sgst="{{ number_format($row_gst_percentage / 2, 2) ?? '' }}" readonly></td>
                                            <td class="sgst_div tax_type_dv"><input type="text"
                                                    class="form-control bg-light" name="sgst_amount_array[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ number_format($row_gst_amount / 2, 2) ?? '' }}" readonly>
                                            </td>



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
                                    <td>
                                        <input type="text"
                                            value="{{ isset($edit) && $edit?->master_quantity !== null
                                                ? number_format($edit->master_quantity, 2)
                                                : '' }}"
                                            class="form-control bg-light text-end"
                                            readonly
                                            id="master_quantity"
                                            name="master_quantity">
                                    </td>
                                    <td></td>
                                    {{-- <td></td> --}}
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

                            <div class="col-md-3 d-none">
                                <label class="form-label">Transport</label>
                                <input type="number"
                                    value="{{ isset($edit) && $edit?->transport_charge ? $edit?->transport_charge : '' }}"
                                    class="form-control" onchange="recalculateRow(this)" value="" id="transport"
                                    name="transport_charge" min="0">
                            </div>

                            <div class="col-md-3 d-none">
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

                    {{-- <div class="col-md-12 col-sm-12 mb-3">
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
                    </div> --}}

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
                                {{ $isEdit ? 'Update' : 'Create' }} {{ $page_title ?? '' }}
                            </button>

                            <button type="submit" name="submit_type" value="save_and_eway_bill" class="btn btn-primary mt-1 mb-1">
                                Submit & EwayBill
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

        flatpickr("#invoice_order_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('invoice_order_date', isset($edit->invoice_order_date) ? date('d-m-Y', strtotime($edit->invoice_order_date)) : date('d-m-Y')) }}",
            maxDate: "{{ date('d-m-Y') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('invoice_order_date').value = ymd;
            }
        });

        flatpickr("#lr_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('lr_date', isset($edit->lr_date) ? date('d-m-Y', strtotime($edit->lr_date)) : date('d-m-Y')) }}",
            maxDate: "{{ date('d-m-Y') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('lr_date').value = ymd;
            }
        });

        flatpickr("#expected_dispatch_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('expected_dispatch_date', isset($edit->expected_dispatch_date) ? date('d-m-Y', strtotime($edit->expected_dispatch_date)) : date('d-m-Y')) }}",
            minDate: "{{ date('Y-m-d') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('expected_dispatch_date').value = ymd;
            }
        });

        $(document).ready(function() {
            var $orderTypeSelect = $("#order_type");
            $orderTypeSelect.select2({
                placeholder: "Select an order type",
                allowClear: true
            });
        });

        $('#productBody .price').each(function() {
            if (typeof recalculateRow === 'function') recalculateRow(this);
        });
    </script>

    <script>
        const isDhruvikUser = {{ $isDhruvik ? 'true' : 'false' }};
        let termsInstance;
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
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


        $(document).ready(function() {
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

            // Store original GST values for existing invoice items on page load
            $('#productBody tr').each(function() {
                let row = $(this);
                let gstInput = row.find('input[name="gst_in_percentage[]"]');
                let cgstInput = row.find('input[name="cgst_in_percentage[]"]');
                let sgstInput = row.find('input[name="sgst_in_percentage[]"]');

                // Store original values from data attributes or current values
                if (!gstInput.data('original-gst')) {
                    let originalGst = gstInput.attr('data-original-gst') || gstInput.val() || '';
                    let originalCgst = cgstInput.attr('data-original-cgst') || cgstInput.val() || '';
                    let originalSgst = sgstInput.attr('data-original-sgst') || sgstInput.val() || '';

                    gstInput.data('original-gst', originalGst);
                    cgstInput.data('original-cgst', originalCgst);
                    sgstInput.data('original-sgst', originalSgst);
                }
            });

            // Handle invoice type change for GST calculation
            handleInvoiceTypeGST();
        });

        // Function to handle GST calculation based on invoice type
        function handleInvoiceTypeGST() {
            $('#invoice_type').on('change', function() {
                applyGSTBasedOnType();
            });

            // Apply when customer changes (state might change)
            let invoiceTypeMerchantKey = 'merchant'; // From config constants
            $(document).on('change', '.search_by_customer', function() {
                setTimeout(function() {
                    if ($('#invoice_type').val() === invoiceTypeMerchantKey) {
                        applyGSTBasedOnType();
                    }
                }, 500); // Wait for customer info to load
            });

            // Apply on page load if invoice type is already selected
            if ($('#invoice_type').val()) {
                setTimeout(function() {
                    applyGSTBasedOnType();
                }, 500);
            }
        }

        function applyGSTBasedOnType() {
            let invoiceType = $('#invoice_type').val();
            // Use constants from config
            let invoiceTypeTextKey = 'tax';
            let invoiceTypeSezKey = 'SEZ';
            let invoiceTypeMerchantKey = 'merchant';

            if (!invoiceType) {
                return;
            }
            let customerState = $('#state').val() || '';
            let isGujarat = customerState.toLowerCase().includes('gujarat');

            // Get all rows in the product table
            $('#productBody tr').each(function() {
                let row = $(this);
                let productVariantPriceId = row.find('input[name="product_variant_price_id[]"]').val();

                if (invoiceType === invoiceTypeMerchantKey) {
                    // Store original GST values in data attributes before applying merchant GST
                    let gstInput = row.find('input[name="gst_in_percentage[]"]');
                    let cgstInput = row.find('input[name="cgst_in_percentage[]"]');
                    let sgstInput = row.find('input[name="sgst_in_percentage[]"]');

                    // Get current values
                    let currentGst = gstInput.val() || '';
                    let currentCgst = cgstInput.val() || '';
                    let currentSgst = sgstInput.val() || '';

                    // Check if original values are already stored (from data attribute or jQuery data)
                    let storedOriginalGst = gstInput.data('original-gst');
                    if (storedOriginalGst === undefined || storedOriginalGst === null || storedOriginalGst === '') {
                        storedOriginalGst = gstInput.attr('data-original-gst');
                    }

                    // Store original values if not already stored (only if current value is not merchant GST)
                    if (!storedOriginalGst || storedOriginalGst === '' || storedOriginalGst === '0.1') {
                        // Only store if current value is not merchant GST (0.1)
                        if (currentGst !== '0.1' && currentGst !== '0.05') {
                            gstInput.data('original-gst', currentGst);
                            cgstInput.data('original-cgst', currentCgst);
                            sgstInput.data('original-sgst', currentSgst);
                        } else {
                            // If already merchant GST, try to get from HTML data attribute
                            let htmlOriginalGst = gstInput.attr('data-original-gst');
                            if (htmlOriginalGst) {
                                gstInput.data('original-gst', htmlOriginalGst);
                                cgstInput.data('original-cgst', cgstInput.attr('data-original-cgst') || '');
                                sgstInput.data('original-sgst', sgstInput.attr('data-original-sgst') || '');
                            }
                        }
                    }

                    // Merchant Exporter => GST 0.1 (10%) for all items
                    if (isGujarat) {
                        // Gujarat under hoy (Within Gujarat) => CGST => 0.05 (5%), SGST => 0.05 (5%)
                        row.find('input[name="cgst_in_percentage[]"]').val(0.05);
                        row.find('input[name="sgst_in_percentage[]"]').val(0.05);
                        row.find('input[name="gst_in_percentage[]"]').val(0.1); // Store total GST (10% = 5% + 5%)
                    } else {
                        // Gujarat bar nu hoy (Outside Gujarat) => IGST => 0.1 (10%)
                        row.find('input[name="gst_in_percentage[]"]').val(0.1);
                        row.find('input[name="cgst_in_percentage[]"]').val(''); // Clear CGST
                        row.find('input[name="sgst_in_percentage[]"]').val(''); // Clear SGST
                    }

                    // Recalculate the row after updating GST percentages
                    let priceInput = row.find('input.price');
                    if (priceInput.length) {
                        recalculateRow(priceInput[0]);
                    }
                } else if (invoiceType === invoiceTypeTextKey) {

                    // Tax type - restore original GST values
                    // First try to get from data attribute (set via .data())
                    let originalGst = row.find('input[name="gst_in_percentage[]"]').data('original-gst');
                    let originalCgst = row.find('input[name="cgst_in_percentage[]"]').data('original-cgst');
                    let originalSgst = row.find('input[name="sgst_in_percentage[]"]').data('original-sgst');
                    console.log("L-1226 ", originalGst, originalCgst, originalSgst);

                    // If not in data attribute, try to get from HTML data attribute
                    if (originalGst === undefined || originalGst === null || originalGst === '') {
                        originalGst = row.find('input[name="gst_in_percentage[]"]').attr('data-original-gst');
                        originalCgst = row.find('input[name="cgst_in_percentage[]"]').attr('data-original-cgst');
                        originalSgst = row.find('input[name="sgst_in_percentage[]"]').attr('data-original-sgst');
                    }

                    // If original values are stored, use them
                    if (originalGst !== undefined && originalGst !== null && originalGst !== '') {
                        row.find('input[name="gst_in_percentage[]"]').val(originalGst);
                        row.find('input[name="cgst_in_percentage[]"]').val(originalCgst || '');
                        row.find('input[name="sgst_in_percentage[]"]').val(originalSgst || '');
                    } else {
                        // If no original values stored, fetch from product via AJAX
                        if (productVariantPriceId) {
                            fetchProductGST(productVariantPriceId, row);
                        } else {
                            // Fallback: calculate from current IGST or split CGST/SGST
                            let currentGst = parseFloat(row.find('input[name="gst_in_percentage[]"]').val()) || 0;
                            if (currentGst > 0) {
                                let halfGst = currentGst / 2;
                                if (isGujarat) {
                                    // Within state: split into CGST and SGST
                                    row.find('input[name="cgst_in_percentage[]"]').val(halfGst);
                                    row.find('input[name="sgst_in_percentage[]"]').val(halfGst);
                                    row.find('input[name="gst_in_percentage[]"]').val(currentGst);
                                } else {
                                    // Outside state: use IGST
                                    row.find('input[name="gst_in_percentage[]"]').val(currentGst);
                                    row.find('input[name="cgst_in_percentage[]"]').val('');
                                    row.find('input[name="sgst_in_percentage[]"]').val('');
                                }
                            }
                        }
                    }

                    // Recalculate the row after restoring GST percentages
                    let priceInput = row.find('input.price');
                    if (priceInput.length) {
                        recalculateRow(priceInput[0]);
                    }
                }else if (invoiceType === invoiceTypeSezKey) {
                    // Store original GST values before setting to 0 (similar to merchant logic)
                    let gstInput = row.find('input[name="gst_in_percentage[]"]');
                    let cgstInput = row.find('input[name="cgst_in_percentage[]"]');
                    let sgstInput = row.find('input[name="sgst_in_percentage[]"]');

                    // Get current values
                    let currentGst = gstInput.val() || '';
                    let currentCgst = cgstInput.val() || '';
                    let currentSgst = sgstInput.val() || '';

                    // Store original values if not already stored
                    let storedOriginalGst = gstInput.data('original-gst');
                    if (storedOriginalGst === undefined || storedOriginalGst === null || storedOriginalGst === '') {
                        storedOriginalGst = gstInput.attr('data-original-gst');
                    }

                    // Only store if current value is not already 0
                    if (!storedOriginalGst || storedOriginalGst === '') {
                        if (currentGst !== '0' && currentGst !== '') {
                            gstInput.data('original-gst', currentGst);
                            cgstInput.data('original-cgst', currentCgst);
                            sgstInput.data('original-sgst', currentSgst);
                        } else {
                            // If already 0, try to get from HTML data attribute
                            let htmlOriginalGst = gstInput.attr('data-original-gst');
                            if (htmlOriginalGst) {
                                gstInput.data('original-gst', htmlOriginalGst);
                                cgstInput.data('original-cgst', cgstInput.attr('data-original-cgst') || '');
                                sgstInput.data('original-sgst', sgstInput.attr('data-original-sgst') || '');
                            }
                        }
                    }

                    // Set all GST values to 0 for SEZ/Export
                    row.find('input[name="gst_in_percentage[]"]').val(0);
                    row.find('input[name="cgst_in_percentage[]"]').val(0);
                    row.find('input[name="sgst_in_percentage[]"]').val(0);

                    // Recalculate the row after updating GST percentages
                    let priceInput = row.find('input.price');
                    if (priceInput.length) {
                        recalculateRow(priceInput[0]);
                    }
                }
            });
        }

        // Function to fetch product GST from API
        function fetchProductGST(productVariantPriceId, row) {
            let company_id = $(".search_by_company option:selected").val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }
            if (company_id == undefined || company_id == null) {
                company_id = $('input[name="company_id"]').val();
            }

            $.ajax({
                url: '{{ env('API_URL') }}get-product-info',
                type: "POST",
                dataType: "Json",
                data: {
                    product_id: productVariantPriceId,
                    customer_id: $('#customer_id').val(),
                    company_id: company_id,
                    module_name: "{{ $route ?? '' }}",
                },
                success: function(response) {
                    let data = response.data;
                    let gst = parseFloat(data.gst) || 0;
                    let halfGst = gst / 2;
                    let customerState = $('#state').val() || '';
                    let isGujarat = customerState.toLowerCase().includes('gujarat');

                    // Store original values
                    row.find('input[name="gst_in_percentage[]"]').data('original-gst', gst);
                    row.find('input[name="cgst_in_percentage[]"]').data('original-cgst', halfGst);
                    row.find('input[name="sgst_in_percentage[]"]').data('original-sgst', halfGst);

                    // Apply original GST
                    if (isGujarat) {
                        // Within state: CGST and SGST
                        row.find('input[name="cgst_in_percentage[]"]').val(halfGst);
                        row.find('input[name="sgst_in_percentage[]"]').val(halfGst);
                        row.find('input[name="gst_in_percentage[]"]').val(gst);
                    } else {
                        // Outside state: IGST
                        row.find('input[name="gst_in_percentage[]"]').val(gst);
                        row.find('input[name="cgst_in_percentage[]"]').val('');
                        row.find('input[name="sgst_in_percentage[]"]').val('');
                    }

                    // Recalculate
                    let priceInput = row.find('input.price');
                    if (priceInput.length) {
                        recalculateRow(priceInput[0]);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching product GST: ", error);
                }
            });
        }

        function validateFlatDiscount(input) {
            var price = $(input).closest('tr').find('.price').val();
            var discount = parseFloat(input.value);

            if (discount > parseFloat(price)) {
                toastr.error("Discount (Flat) cannot be more than Price (in INR)!");
                input.value = '';
            }
        }

        $(document).ready(function() {
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
            $(document).on("change", "#order_type", function() {
                applyInputRules();
            });

            $(document).on("keydown", "#order_price", function(e) {
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

                <td class="d-none"><input class="form-control rate_in_amount bg-light" name="rate_in_amount[]" value="0" readonly onkeypress="return isDecimal(event)"></td>
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
                    _token: "{{ csrf_token() }}",
                },
                success: function(response) {

                    let data = response.data;
                    console.log(data);
                    $("#productBody tr:last").find('input[name="product_name[]"]').val(data
                        .product_name);
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

                    $("#productBody tr:last").find('input[name="hsn_code[]"]').val(data.hsn_code ?? '');
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

                    $("#productBody tr:last").find('input[name="price[]"]').val(parseFloat(data.price ||
                        0).toFixed(2));
                    $("#productBody tr:last").find('.gst-label').text('');

                    let gst = parseFloat(data.gst) || 0;
                    let halfGst = gst / 2;

                    // Apply GST based on invoice type
                    let invoiceType = $('#invoice_type').val();
                    let invoiceTypeMerchantKey = 'merchant'; // From config constants
                    let customerState = $('#state').val() || '';
                    let isGujarat = customerState.toLowerCase().includes('gujarat');

                    if (invoiceType === invoiceTypeMerchantKey) {
                        // Merchant Exporter => GST 0.1 (10%) for all items
                        if (isGujarat) {
                            // Gujarat under hoy (Within Gujarat) => CGST => 0.05 (5%), SGST => 0.05 (5%)
                            $("#productBody tr:last").find('input[name="cgst_in_percentage[]"]').val(
                                0.05);
                            $("#productBody tr:last").find('input[name="sgst_in_percentage[]"]').val(
                                0.05);
                            $("#productBody tr:last").find('input[name="gst_in_percentage[]"]').val(
                                0.1); // Store total GST (10% = 5% + 5%)
                        } else {
                            // Gujarat bar nu hoy (Outside Gujarat) => IGST => 0.1 (10%)
                            $("#productBody tr:last").find('input[name="gst_in_percentage[]"]').val(
                                0.1);
                            $("#productBody tr:last").find('input[name="cgst_in_percentage[]"]').val(
                                ''); // Clear CGST
                            $("#productBody tr:last").find('input[name="sgst_in_percentage[]"]').val(
                                ''); // Clear SGST
                        }
                    } else {
                        // Text/Tax - use default GST from product
                        let productGst = parseFloat(data.gst) || 0;
                        let productHalfGst = productGst / 2;

                        // Store original GST values in data attributes
                        $("#productBody tr:last").find('input[name="gst_in_percentage[]"]').data('original-gst', productGst);
                        $("#productBody tr:last").find('input[name="cgst_in_percentage[]"]').data('original-cgst', productHalfGst);
                        $("#productBody tr:last").find('input[name="sgst_in_percentage[]"]').data('original-sgst', productHalfGst);

                        // Apply GST based on customer state
                        let customerState = $('#state').val() || '';
                        let isGujarat = customerState.toLowerCase().includes('gujarat');

                        if (isGujarat) {
                            // Within state: CGST and SGST
                            $("#productBody tr:last").find('input[name="gst_in_percentage[]"]').val(
                                productGst);
                            $("#productBody tr:last").find('input[name="cgst_in_percentage[]"]').val(
                                productHalfGst);
                            $("#productBody tr:last").find('input[name="sgst_in_percentage[]"]').val(
                                productHalfGst);
                        } else {
                            // Outside state: IGST
                            $("#productBody tr:last").find('input[name="gst_in_percentage[]"]').val(
                                productGst);
                            $("#productBody tr:last").find('input[name="cgst_in_percentage[]"]').val(
                                '');
                            $("#productBody tr:last").find('input[name="sgst_in_percentage[]"]').val(
                                '');
                        }
                    }

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

        // Handle "Same as Billing" checkbox
        $(document).ready(function() {
            // Check if editing and addresses are different
            @php
                $isInvoiceSameAsBilling = true;
                if (isset($edit)) {
                    $so = $edit?->sales_order;
                    $currentGst = $edit?->gst_no ?? ($so?->gst_no ?? ($edit?->customer?->gst_number ?? ''));
                    $currentShipGst = $edit?->shipping_gst_no ?? ($so?->shipping_gst_no ?? '');
                    $currentBilling = trim($edit?->billing_address ?? ($so?->billing_address ?? ''));
                    $currentShipping = trim($edit?->shipping_address ?? ($so?->shipping_address ?? ''));
                    $currentCustomerId = $edit?->shipping_customer_id ?? ($so?->shipping_customer_id ?? null);

                    if (!empty($currentCustomerId)) {
                        $isInvoiceSameAsBilling = false;
                    } elseif ($currentBilling !== $currentShipping) {
                        $isInvoiceSameAsBilling = false;
                    } elseif (!empty($currentShipGst) && !empty($currentGst) && trim($currentShipGst) !== trim($currentGst)) {
                        $isInvoiceSameAsBilling = false;
                    }
                }
            @endphp

            @if (!$isInvoiceSameAsBilling)
                $('#same_as_billing').prop('checked', false);
                $('#shipping_address_div').show();
            @else
                $('#same_as_billing').prop('checked', true);
                $('#shipping_address_div').hide();
                copyBillingToShipping();
            @endif

            if ($('#shipping_customer_id_select').length > 0) {
                $('#shipping_customer_id_select').select2({
                    width: '100%',
                    disabled: true
                });
            }

            $('#same_as_billing').on('change', function() {
                if ($(this).is(':checked')) {
                    // Copy billing to shipping and hide the shipping div
                    copyBillingToShipping();
                    $('#shipping_customer_id').val('');
                    if ($('#shipping_customer_id_select').length > 0) {
                        $('#shipping_customer_id_select').val('').trigger('change.select2');
                    }
                    $('#shipping_address_div').hide();
                } else {
                    // Show shipping address div for editing and empty all shipping fields
                    $('#shipping_address_div').show();
                    $('#shipping_customer_id').val('');
                    if ($('#shipping_customer_id_select').length > 0) {
                        $('#shipping_customer_id_select').val('').trigger('change.select2');
                    }
                    $('#shipping_gst_no').val('');
                    $('#shipping_address').val('');
                }
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

            $(document).on('submit', 'form', function() {
                if ($('#same_as_billing').is(':checked')) {
                    $('#shipping_address').val($('#billing_address').val());
                    $('#shipping_gst_no').val($('#gst_no').val());
                    $('#shipping_customer_id').val('');
                }
            });
        });

        // Function to copy billing to shipping when checkbox is checked
        function copyBillingToShipping() {
            if ($('#same_as_billing').is(':checked')) {
                $('#shipping_address').val($('#billing_address').val());
                $('#shipping_gst_no').val($('#gst_no').val());
                $('#shipping_customer_id').val('');
            }
        }
    </script>

    @include('utils.getTermsInfo')
@endpush
