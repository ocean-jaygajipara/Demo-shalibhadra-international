@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
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
                if (isset($edit) && $edit?->id) {
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
            <form id="job_work_issue_form"
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <input type="hidden" name="vendor_name" id="vendor_name"
                    value="{{ old('vendor_name', isset($edit) && $edit?->vendor ? $edit?->vendor->vendor_name : '') }}" />
                <input type="hidden" name="contact_person_name" id="contact_person_name"
                    value="{{ old('contact_person_name', isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : '') }}" />
                <input type="hidden" name="state" id="state"
                    value="{{ old('state', isset($edit) && $edit?->state ? $edit?->state : '') }}" />

                <div class="">
                    <!-- Section 1: Vendor Details -->
                    <h4 class="mb-3">Vendor Details</h4>
                    <div class="row g-3">
                        @if (!$company_id)
                            <div class="col-md-3">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id"
                                    class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                    data-append="search_by_company"
                                    data-selectedCompanyId="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : old('company_id') }}"
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
                            <label class="form-label">Search Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendor_id"
                                class="form-control @error('vendor_id') is-invalid @enderror search_by_vendor select2"
                                data-append="search_by_vendor"
                                data-selectedVendorId="{{ isset($edit) && $edit?->vendor_id ? $edit?->vendor_id : old('vendor_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('vendor_id', $edit->vendor_id ?? '') ? '' : 'selected' }}>
                                    Select Vendor
                                </option>
                            </select>
                            @error('vendor_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>


                        <div class="col-md-3 col-sm-12 mb-2">
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

                        <div class="col-md-3">
                            <label class="form-label">Job Work Type <span class="text-danger">*</span></label>
                            <select name="job_work_type_id" id="job_work_type_id"
                                class="form-control @error('job_work_type_id') is-invalid @enderror search_by_job_work_type_id select2"
                                data-append="search_by_job_work_type_id"
                                data-selectedjobworktypeid="{{ isset($edit) && $edit?->job_work_type_id ? $edit?->job_work_type_id : old('job_work_type_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('job_work_type_id', $edit->job_work_type_id ?? '') ? '' : 'selected' }}>
                                    Select Job work Type
                                </option>
                            </select>
                            @error('job_work_type_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    
    
                    <div class="row">
                        @if (
                            $modules['currentGuard'] === 'admin_software' ||
                                ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6 mt-3">
                                        <div class="row align-items-center">
                                            <label class="col-4"><strong>Vendor:</strong></label>
                                            <div class="col-8">
                                                <p class="mb-0">
                                                    <span id="vendor_name_display">
                                                        {{ old('vendor_name', isset($edit) && $edit?->vendor ? $edit?->vendor->vendor_name : 'N/A') }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-3">
                                        <div class="row align-items-center">
                                            <label class="col-4"><strong>Phone:</strong></label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="phone" id="phone"
                                                    value="{{ old('phone', $edit->customer->mobile_number ?? '') }}"
                                                    placeholder="Enter phone">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-md-6 mt-3">
                                        <div class="row align-items-start">
                                            <label class="col-4"><strong>Address:</strong></label>
                                            <div class="col-8">
                                                <textarea class="form-control" name="address" id="address" rows="2" placeholder="Enter address">{{ old('address', $edit->customer->address ?? '') }}</textarea>
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

                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-md-4 mt-3">
                                    <label class="form-label">Job Work Issue No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control readonly-look" name="job_work_issue_no"
                                        id="job_work_issue_no" placeholder="Enter Job Work Issue No" readonly
                                        value="{{ old('job_work_issue_no', isset($edit) && $edit?->job_work_issue_no ? $edit?->job_work_issue_no : '') }}">
                                </div>

                                <div class="col-md-4 mt-3">
                                    <label>Select Warehouse <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" id="warehouse_id"
                                        class="form-control @error('warehouse_id') is-invalid @enderror search_by_warehouse select2"
                                        data-append="search_by_warehouse"
                                        data-selectedWarehouseId="{{ isset($edit) && $edit?->warehouse_id ? $edit?->warehouse_id : '' }}"
                                        autofocus>
                                        <option value="" disabled
                                            {{ old('warehouse_id', $edit->warehouse_id ?? '') ? '' : 'selected' }}>
                                            Select Warehouse
                                        </option>
                                    </select>
                                    @error('warehouse_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-4 mt-3">
                                    <label class="form-label">Job Work Issue Date <span
                                            class="text-danger">*</span></label>
                                    <div style="position: relative;">
                                        <input type="text" id="job_work_issue_date_display"
                                            class="form-control @error('job_work_issue_date') is-invalid @enderror"
                                            value="{{ old('job_work_issue_date')
                                                ? date('d-m-Y', strtotime(old('job_work_issue_date')))
                                                : (isset($edit->job_work_issue_date)
                                                    ? date('d-m-Y', strtotime($edit->job_work_issue_date))
                                                    : date('d-m-Y')) }}"
                                            placeholder="DD-MM-YYYY" style="padding-right: 30px;" />

                                        <span
                                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                            <i class="fa fa-calendar" aria-hidden="true"></i>
                                        </span>
                                    </div>

                                    <input type="hidden" name="job_work_issue_date" id="job_work_issue_date"
                                        value="{{ old('job_work_issue_date', isset($edit->job_work_issue_date) ? date('Y-m-d', strtotime($edit->job_work_issue_date)) : date('Y-m-d')) }}" />
                                </div>

                                <div class="col-md-12 mt-3">
                                    <h5 class="mb-0">E-Way Bill Details</h5>
                                </div>
                                <div class="col-md-3 mt-3">
                                    <label class="form-label">Vehicle No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="vehicle_no" id="vehicle_no"
                                        placeholder="Enter Vehicle No"
                                        value="{{ old('vehicle_no', isset($edit) && $edit?->vehicle_no ? $edit?->vehicle_no : ($edit?->jobWorkIssueEwayBill?->vehicle_no ?? 'GJ10TW4865')) }}">
                                </div>

                                <div class="col-md-3 mt-3">
                                    <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                    <select name="vehicle_type" id="vehicle_type" class="form-control select2">
                                        <option value="R" {{ (old('vehicle_type', $edit?->jobWorkIssueEwayBill?->vehicle_type ?? '') == 'R') ? 'selected' : '' }}>Regular</option>
                                        <option value="RW" {{ (old('vehicle_type', $edit?->jobWorkIssueEwayBill?->vehicle_type ?? '') == 'RW') ? 'selected' : '' }}>Rail / Water</option>
                                        <option value="O" {{ (old('vehicle_type', $edit?->jobWorkIssueEwayBill?->vehicle_type ?? '') == 'O') ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>

                                <div class="col-md-3 mt-3">
                                    <label class="form-label">LR No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="lr_no" id="lr_no"
                                        placeholder="Enter LR No"
                                        value="{{ old('lr_no', $edit?->jobWorkIssueEwayBill?->lr_no ?? '') }}">
                                        @error('lr_no')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                </div>

                                <div class="col-md-3 mt-3">
                                    <label class="form-label">LR Date <span class="text-danger">*</span></label>
                                     <div style="position: relative;">
                                        <input type="text" id="lr_date_display"
                                            class="form-control"
                                            value="{{ old('lr_date')
                                                ? date('d-m-Y', strtotime(old('lr_date')))
                                                : (isset($edit->jobWorkIssueEwayBill->lr_date)
                                                    ? date('d-m-Y', strtotime($edit->jobWorkIssueEwayBill->lr_date))
                                                    : '') }}"
                                            placeholder="DD-MM-YYYY" style="padding-right: 30px;" />
                                            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                                <i class="fa fa-calendar" aria-hidden="true"></i>
                                            </span>
                                     </div>
                                    <input type="hidden" name="lr_date" id="lr_date"
                                        value="{{ old('lr_date', $edit->jobWorkIssueEwayBill->lr_date ?? '') }}" />
                                        @error('lr_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                </div>


                                <div class="row g-3 mt-1 mb-4">
                                    <div class="col-md-4">
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

                                    <div class="col-md-4">
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

                                    <div class="col-md-4">
                                        <label class="form-label">GSTIN</label>
                                        <input type="text" class="form-control" name="gst_no" id="gst_no"
                                            value="{{ old('gst_no', $edit?->gst_no ?? ($edit?->vendor?->gst_number ?? '')) }}">
                                    </div>
                                </div>
                            </div>
                    </div>



                    <hr class="my-4">
                    <h4 class="mb-3">Job Work Order Items</h4>

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
                                    <th style="min-width: 200px;">Issue Item</th>
                                    <th style="min-width: 200px;">Stock (Kgs)</th>
                                    <th style="min-width: 200px;">Issue (Kgs)</th>
                                    <th style="min-width: 200px;">Gross (Kgs)</th>
                                    <th style="min-width: 200px;">BL(%)</th>
                                    <th style="min-width: 200px;">Rate</th>
                                    <th style="min-width: 200px;">Taxable</th>
                                    <th style="min-width: 200px;">CGST (%)</th>
                                    <th style="min-width: 200px;">CGST Amount</th>
                                    <th style="min-width: 200px;">SGST (%)</th>
                                    <th style="min-width: 200px;">SGST Amount</th>
                                    <th style="min-width: 200px;">Rate+</th>
                                    <th style="min-width: 200px;">Net. Amount</th>
                                    <th style="min-width: 50px;">Action</th>
                                </tr>
                            </thead>


                            <tbody id="productBody">
                                @if (isset($edit) && $edit?->job_work_issue_items)
                                    @foreach ($edit?->job_work_issue_items as $item)
                                        <tr>
                                            <input type="hidden" class="form-control" name="item_id[]"
                                                value="{{ $item->job_work_order_item_id }}">
                                            <input type="hidden" class="form-control" name="product_id[]"
                                                value="{{ $item->product_id }}">
                                            <input type="hidden" class="form-control" name="product_gst[]"
                                                value="{{ $item?->product?->product_tax_id }}">

                                            <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_code[]"
                                                    value="{{ $item->product_code ?? '' }}" readonly><label
                                                    class="product_code_txt">{{ $item->product_code ?? '' }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_name[]"
                                                    value="{{ $item->product_name ?? '' }}" readonly><label
                                                    class="product_name_txt">{{ $item->product_name ?? '' }}</label></td>
                                            <td><input type="text" class="form-control bg-light"
                                                    name="product_stock[]" value="{{ $item->product_stock ?? '' }}"
                                                    tabindex="-1"></td>
                                            <td><input type="text" class="form-control" name="issue_weight[]"
                                                    value="{{ $item->issue_weight ?? '' }}"
                                                    onkeypress="return isDecimal(event)" onchange="recalculateRow(this)">
                                            </td>
                                            <td><input type="text" class="form-control" name="gross_weight[]"
                                                    value="{{ $item->gross_weight ?? '' }}"
                                                    onkeypress="return isDecimal(event)" onchange="recalculateRow(this)">
                                            </td>
                                            <td><input type="text" class="form-control bg-light"
                                                    name="bl_percentage[]" value="{{ $item->bl_percentage ?? '' }}"
                                                    onkeypress="return isNumber(event)" readonly></td>
                                            <td><input type="text" class="form-control rate_in_amount"
                                                    name="rate_in_amount[]" onkeypress="return isDecimal(event)"
                                                    onchange="recalculateRow(this)"
                                                    value="{{ $item->rate_in_amount ?? '' }}"></td>
                                            <td><input type="text" class="form-control taxable_amount bg-light"
                                                    name="taxable_amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->taxable_amount ?? '' }}" readonly tabindex="-1">
                                            </td>
                                            <td><input type="text" class="form-control cgst_percentage bg-light"
                                                    name="cgst_percentage[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->cgst_percentage ?? '' }}" readonly tabindex="-1">
                                            </td>
                                            <td><input type="text" class="form-control cgst_amount bg-light"
                                                    name="cgst_amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->cgst_amount ?? '' }}" readonly tabindex="-1"></td>
                                            <td><input type="text" class="form-control sgst_percentage bg-light"
                                                    name="sgst_percentage[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->sgst_percentage ?? '' }}" readonly tabindex="-1">
                                            </td>
                                            <td><input type="text" class="form-control sgst_amount bg-light"
                                                    name="sgst_amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->sgst_amount ?? '' }}" readonly tabindex="-1"></td>
                                            <td><input type="text" class="form-control total_rate_in_amount bg-light"
                                                    name="total_rate_in_amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->total_rate_in_amount ?? '' }}" readonly
                                                    tabindex="-1"></td>

                                            <td><input type="text" class="form-control amount bg-light"
                                                    name="amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->amount ?? '' }}" readonly></td>
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
                                    <td></td>
                                    <td><input type="number"
                                            value="{{ old('master_rate', isset($edit) && $edit?->master_rate ? $edit?->master_rate : '') }}"
                                            class="form-control bg-light master_rate" readonly="" name="master_rate"
                                            id="master_rate"></td>
                                    <td><input type="number"
                                            value="{{ old('master_taxable', isset($edit) && $edit?->master_taxable ? $edit?->master_taxable : '') }}"
                                            class="form-control bg-light master_taxable" readonly=""
                                            name="master_taxable" id="master_taxable"></td>
                                    <td><input type="number"
                                            value="{{ old('total_cgst_percentage', isset($edit) && $edit?->total_cgst_percentage ? $edit?->total_cgst_percentage : '') }}"
                                            class="form-control bg-light total_cgst_percentage" readonly=""
                                            name="total_cgst_percentage" id="total_cgst_percentage"></td>
                                    <td><input type="number"
                                            value="{{ old('total_cgst_amount', isset($edit) && $edit?->total_cgst_amount ? $edit?->total_cgst_amount : '') }}"
                                            class="form-control bg-light total_cgst_amount" readonly=""
                                            name="total_cgst_amount" id="total_cgst_amount"></td>
                                    <td><input type="number"
                                            value="{{ old('total_sgst_percentage', isset($edit) && $edit?->total_sgst_percentage ? $edit?->total_sgst_percentage : '') }}"
                                            class="form-control bg-light total_sgst_percentage" readonly=""
                                            name="total_sgst_percentage" id="total_sgst_percentage"></td>
                                    <td><input type="number"
                                            value="{{ old('total_sgst_amount', isset($edit) && $edit?->total_sgst_amount ? $edit?->total_sgst_amount : '') }}"
                                            class="form-control bg-light total_sgst_amount" readonly=""
                                            name="total_sgst_amount" id="total_sgst_amount"></td>
                                    <td><input type="number"
                                            value="{{ old('master_total_rate', isset($edit) && $edit?->master_total_rate ? $edit?->master_total_rate : '') }}"
                                            class="form-control bg-light master_total_rate" readonly=""
                                            name="master_total_rate" id="master_total_rate"></td>
                                    <td><input type="number"
                                            value="{{ old('master_total_amount', isset($edit) && $edit?->master_total_amount ? $edit?->master_total_amount : '') }}"
                                            class="form-control bg-light master_total_amount" readonly=""
                                            name="master_total_amount" id="master_total_amount"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class=" mt-5">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label">Packing Weight</label>
                                <input type="number"
                                    value="{{ old('total_packing_weight', isset($edit) && $edit?->total_packing_weight ? $edit?->total_packing_weight : '') }}"
                                    class="form-control" placeholder="Enter Packing Weight" id="total_packing_weight"
                                    value="" name="total_packing_weight">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Item Weight</label>
                                <input type="text"
                                    value="{{ old('total_item_weight', isset($edit) && $edit?->total_item_weight ? $edit?->total_item_weight : '') }}"
                                    class="form-control bg-light" value="" id="total_item_weight"
                                    name="total_item_weight" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Gross Weight</label>
                                <input type="text"
                                    value="{{ old('total_gross_weight', isset($edit) && $edit?->total_gross_weight ? $edit?->total_gross_weight : '') }}"
                                    class="form-control bg-light" value="" id="total_gross_weight"
                                    name="total_gross_weight" readonly>
                            </div>


                        </div>
                        <div class="row justify-content-end mt-4">
                            <div class="col-md-6">
                                <div class="mb-3 row">
                                    <label class="col-sm-6 col-form-label">Sub Total</label>
                                    <div class="col-sm-6">
                                        <input type="text"
                                            value="{{ old('sub_total', isset($edit) && $edit?->sub_total ? $edit?->sub_total : '') }}"
                                            class="form-control bg-light" value="0.0000" id="sub_total" name="sub_total"
                                            readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-6 col-form-label">Round Off</label>
                                    <div class="col-sm-6">
                                        <input type="number"
                                            value="{{ old('round_off', isset($edit) && $edit?->round_off ? $edit?->round_off : '') }}"
                                            class="form-control bg-light" value="0.0000" id="round_off" name="round_off"
                                            readonly>
                                    </div>
                                </div>

                                <div class="mb-3 row">
                                    <label class="col-sm-6 col-form-label">Grand Total</label>
                                    <div class="col-sm-6">
                                        <input type="text"
                                            value="{{ old('grand_total', isset($edit) && $edit?->grand_total ? $edit?->grand_total : '') }}"
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
                                {{ old('term_and_condition', isset($edit) && $edit?->term_and_condition ? $edit->term_and_condition : '') }}
                            </textarea>
                            @error('term_and_condition')
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

                            <button type="submit" name="submit_type" value="save_and_eway_bill"
                                class="btn btn-info mt-1 mb-1 text-white">
                                {{ isset($edit) ? 'Update & E-Way Bill' : 'Submit & E-Way Bill' }}
                            </button>

                            <button type="submit" name="submit_type" value="save_print"
                                class="btn btn-primary mt-1 mb-1">
                                Submit & Print
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#job_work_issue_date_display", {
            dateFormat: "d-m-Y",
            allowInput: true,
            defaultDate: "{{ old('job_work_issue_date', isset($edit->job_work_issue_date) ? date('d-m-Y', strtotime($edit->job_work_issue_date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('job_work_issue_date').value = ymd; // hidden input for submission
            }
        });

        
        flatpickr("#lr_date_display", {
            dateFormat: "d-m-Y",
            allowInput: true,
            defaultDate: "{{ old('lr_date', isset($edit->lr_date) ? date('d-m-Y', strtotime($edit->lr_date)) : date('d-m-Y')) }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('lr_date').value = ymd;
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>
    <script>
        let termsInstance;
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getWarehouse')
    @if (!isset($edit))
        @include('utils.generateJobWorkIssueNo')
    @endif
    @include('utils.getVendorSales')
    @include('utils.getProductCategory')
    @include('utils.getVendorInfo')
    @include('utils.getJobWorkType')
    @include('utils.getProductAndVariant')
    @include('utils.getTransportby')
    @include('utils.getTransportDetails')
    <script>
        initializeCKEditor(".ckeditor_common_cls");

        // Initialize flatpickr for Job Work Issue Date (editable)
        $(document).ready(function() {
            flatpickr("#job_work_issue_date_display", {
                dateFormat: "d-m-Y",
                defaultDate: "{{ old('job_work_issue_date', isset($edit->job_work_issue_date) ? date('d-m-Y', strtotime($edit->job_work_issue_date)) : date('d-m-Y')) }}",
                onChange: function(selectedDates, dateStr, instance) {
                    const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                    document.getElementById('job_work_issue_date').value = ymd;
                }
            });
        });

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

        // $(document).on('keydown', function (e) {
        //     if (e.key === 'Tab') {
        //         e.preventDefault();
        //         $('#vendor_id').focus();
        //     }
        // });

        function validateFlatDiscount(input) {
            var price = $(input).closest('tr').find('.price').val();
            var discount = parseFloat(input.value);

            if (discount > parseFloat(price)) {
                toastr.error("Discount (Flat) cannot be more than Price (in INR)!");
                input.value = '';
            }
        }

        function updateBLPercentage() {
            let selectedOption = $(".search_by_job_work_type_id ").find('option:selected');
            let blCheckbox = selectedOption.data('bl_checkbox');
            let blPercentage = selectedOption.data('bl_percentage');

            let totalGrossWeight = 0;
            let totalBlPercentage = 0;
            $("#productBody tr").each(function() {

                $(this).find('input[name="bl_percentage[]"]').val(blPercentage);
                let bl_percentage = parseFloat($(this).find('input[name="bl_percentage[]"]').val()) || 0;
                let issueWeight = parseFloat($(this).find('input[name="issue_weight[]"]').val()) || 0;
                let grossWeight = parseFloat($(this).find('input[name="gross_weight[]"]').val()) || 0;

                totalGrossWeight += grossWeight;
                totalBlPercentage += ((issueWeight * bl_percentage) / 100);
            });
            let finalGrossWeight = totalGrossWeight + totalBlPercentage;
            $("#total_gross_weight").val(formatWeight(finalGrossWeight.toFixed(3)));
        }

        $(document).on('change', '.search_by_job_work_type_id', function() {
            updateBLPercentage(this);
        });



        document.getElementById("addRowBtn").addEventListener("click", function() {
            let tableBody = document.getElementById("productBody");
            let newRow = document.createElement("tr");
            let product_id = $('#product_variant_price_id').val();
            let vendor_id = $('#vendor_id').val();
            let company_id = $('#company_id').val();

            if (product_id == '') {
                toastr.error("Please select product!");
                return;
            }

            if (company_id == undefined || company_id == null) {
                company_id = $('input[name="company_id"]').val();
            }

            let exists = false;
            $("#productBody tr").each(function() {
                let existing_product_id = $(this).children("input[name='product_id[]']").val();
                console.log("existing_product_id:", existing_product_id, "new product_id:", product_id);
                if (existing_product_id == product_id) {
                    exists = true;
                    return false;
                }
            });

            if (exists) {
                toastr.error("This product already exists!");
                return;
            }

            let orderTypeValue = $('#order_type').val();
            let srNo = $('#productBody tr').length + 1;

            newRow.innerHTML = `
                <input type="hidden" class="form-control" name="item_id[]" value="0">
                <input type="hidden" class="form-control" name="product_id[]">
                <input type="hidden" class="form-control" name="product_gst[]">

                <td class="sr_no_txt"></td>
                <td><input type="hidden" class="form-control bg-light" name="product_code[]" readonly><label class="product_code_txt"></label></td>
                <td><input type="hidden" class="form-control bg-light" name="product_name[]" readonly><label class="product_name_txt"></label></td>
                <td><input type="text" class="form-control bg-light" name="product_stock[]" readonly></td>
                <td><input type="text" class="form-control" name="issue_weight[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)" ></td>
                <td><input type="text" class="form-control" name="gross_weight[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                <td><input type="text" class="form-control bg-light" name="bl_percentage[]" onkeypress="return isNumber(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control rate_in_amount" name="rate_in_amount[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                <td><input type="text" class="form-control taxable_amount bg-light" name="taxable_amount[]" onkeypress="return isDecimal(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control cgst_percentage bg-light" name="cgst_percentage[]" onkeypress="return isDecimal(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control cgst_amount bg-light" name="cgst_amount[]" onkeypress="return isDecimal(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control sgst_percentage bg-light" name="sgst_percentage[]" onkeypress="return isDecimal(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control sgst_amount bg-light" name="sgst_amount[]" onkeypress="return isDecimal(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control total_rate_in_amount bg-light" name="total_rate_in_amount[]" onkeypress="return isDecimal(event)" readonly tabindex="-1"></td>
                <td><input type="text" class="form-control amount bg-light" name="amount[]" readonly onkeypress="return isDecimal(event)" tabindex="-1"></td>
                <td><button type="button" class="btn btn-danger deleteRow" onclick="removeRow(this)">X</button></td>
            `;
            tableBody.appendChild(newRow);

            $.ajax({
                url: '{{ env('API_URL') }}get-product-info',
                type: "POST",
                dataType: "Json",
                data: {
                    product_id: product_id,
                    vendor_id: $('#vendor_id').val(),
                    company_id: company_id,
                    module_name: "{{ $route ?? '' }}",
                    orderTypeValue: orderTypeValue || $('#order_type').val() || "",
                },
                success: function(response) {
                    let data = response.data;
                    let $lastRow = $("#productBody tr:last");
                    $("#productBody tr:last").find('input[name="product_name[]"]').val($(
                        '#product_variant_price_id option:selected').text());
                    $("#productBody tr:last").find('.product_name_txt').text($(
                        '#product_variant_price_id option:selected').text());
                    $('#product_variant_price_id').val('').trigger('change');
                    $("#productBody tr:last").find('.sr_no_txt').text(srNo);

                    $("#productBody tr:last").find('input[name="product_code[]"]').val(data.part_no);
                    $("#productBody tr:last").find('.product_code_txt').text(data.part_no);
                    $("#productBody tr:last").find('input[name="product_id[]"]').val(data.product_id);
                    $("#productBody tr:last").find('input[name="product_gst[]"]').val(data.gst);
                    $("#productBody tr:last").find('input[name="product_stock[]"]').val(data
                        .product_stock);
                    $("#productBody tr:last").find('input[name="rate_in_amount[]"]').val(data.price);

                    updateBLPercentage();

                },
                error: function(xhr, status, error) {
                    console.error("Error: ", error);
                }
            });
        });

        function saveTableToLocalStorage() {
            let rows = [];
            $("#productBody tr").each(function() {
                let row = {};
                $(this).find("input, select").each(function() {
                    let name = $(this).attr("name");
                    if (name) {
                        if ($(this).hasClass("select2")) {
                            // Use Select2’s value
                            row[name] = $(this).val();
                            console.log($(this))
                        } else {
                            row[name] = $(this).val();
                        }
                    }
                });
                rows.push(row);
            });
            localStorage.setItem("productTable", JSON.stringify(rows));
        }

        $(document).ready(function() {
            let savedRows = localStorage.getItem("productTable");
            if (savedRows) {
                savedRows = JSON.parse(savedRows);
                savedRows.forEach(function(row) {
                    let newRow = document.createElement("tr");

                    // build row dynamically
                    newRow.innerHTML = `
                        <input type="hidden" name="item_id[]" value="${row['item_id[]'] || 0}">
                        <input type="hidden" name="product_id[]" value="${row['product_id[]'] || ''}">
                        <input type="hidden" name="product_gst[]" value="${row['product_gst[]'] || ''}">

                        <td><input type="hidden" name="product_code[]" value="${row['product_code[]'] || ''}"><label class="product_code_txt">${row['product_code[]'] || ''}</label></td>
                        <td><input type="hidden" name="product_name[]" value="${row['product_name[]'] || ''}"><label class="product_name_txt">${row['product_name[]'] || ''}</label></td>
                        <td><input type="text" class="form-control bg-light" name="product_stock[]" readonly value="${row['product_stock[]'] || ''}" tabindex="-1"></td>
                        <td><input type="text" class="form-control" name="issue_weight[]" value="${row['issue_weight[]'] || ''}" onkeypress="return isDecimal(event)"  onchange="recalculateRow(this)"></td>
                        <td><input type="text" class="form-control" name="gross_weight[]" value="${row['gross_weight[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><input type="text" class="form-control bg-light" name="bl_percentage[]" value="${row['bl_percentage[]'] || ''}" onkeypress="return isNumber(event)" readonly></td>
                        <td><input type="text" class="form-control" name="rate_in_amount[]" value="${row['rate_in_amount[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><input type="text" class="form-control taxable_amount" name="taxable_amount[]" readonly value="${row['taxable_amount[]'] || ''}" onkeypress="return isDecimal(event)" tabindex="-1"></td>
                        <td><input type="text" class="form-control cgst_percentage" name="cgst_percentage[]" readonly value="${row['cgst_percentage[]'] || ''}" onkeypress="return isDecimal(event)" tabindex="-1"></td>
                        <td><input type="text" class="form-control cgst_amount" name="cgst_amount[]" readonly value="${row['cgst_amount[]'] || ''}" onkeypress="return isDecimal(event)" tabindex="-1"></td>
                        <td><input type="text" class="form-control sgst_percentage" name="sgst_percentage[]" readonly value="${row['sgst_percentage[]'] || ''}" onkeypress="return isDecimal(event)" tabindex="-1"></td>
                        <td><input type="text" class="form-control sgst_amount" name="sgst_amount[]" readonly value="${row['sgst_amount[]'] || ''}" onkeypress="return isDecimal(event)" tabindex="-1"></td>
                        <td><input type="text" class="form-control total_rate_in_amount" name="total_rate_in_amount[]" readonly value="${row['total_rate_in_amount[]'] || ''}" onkeypress="return isDecimal(event)"></td>
                        <td><input type="text" class="form-control amount" name="amount[]" readonly value="${row['amount[]'] || ''}" onkeypress="return isDecimal(event)" tabindex="-1"></td>
                        <td><button type="button" class="btn btn-danger deleteRow" onclick="removeRow(this)">X</button></td>
                    `;

                    $("#productBody").append(newRow);
                });
            }
        });

        function removeRow(btn) {
            $(btn).closest("tr").remove();
            saveTableToLocalStorage();
        }

        $(document).on("change keyup", "#productBody input, #productBody select", function() {
            saveTableToLocalStorage();
        });


        @if ($errors->any())
        @else

            localStorage.removeItem("productTable");
        @endif


        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("deleteRow")) {
                e.target.closest("tr").remove();
                recalcTotals();
            }
        });

        $(document).on('input', 'input[name="issue_weight[]"]', function() {
            let row = $(this).closest('tr');
            let val = $(this).val();
            row.find('input[name="gross_weight[]"]').val(formatWeight(val));
        });

        function recalculateRow(element) {
            let row = element.closest("tr");

            let grossWeight = parseFloat($(row).find('input[name="gross_weight[]"]').val()) || 0;
            let bl_percentage = parseFloat($(row).find('input[name="bl_percentage[]"]').val()) || 0;

            let rate = parseFloat($(row).find('input[name="rate_in_amount[]"]').val()) || 0;
            let gst = parseFloat($(row).find('input[name="product_gst[]"]').val()) || 0;

            if (grossWeight > 0 && rate > 0 && gst > 0) {
                let bl_grossWeight = grossWeight + ((grossWeight * bl_percentage) / 100);
                bl_grossWeight = formatWeight(bl_grossWeight.toFixed(3));
                let taxable = bl_grossWeight * rate;
                $(row).find('input[name="taxable_amount[]"]').val(taxable.toFixed(2));

                let cgstPer = gst / 2;
                let sgstPer = gst / 2;
                $(row).find('input[name="cgst_percentage[]"]').val(cgstPer.toFixed(2));
                $(row).find('input[name="sgst_percentage[]"]').val(sgstPer.toFixed(2));

                let cgstAmt = (taxable * cgstPer) / 100;
                let sgstAmt = (taxable * sgstPer) / 100;
                $(row).find('input[name="cgst_amount[]"]').val(cgstAmt.toFixed(2));
                $(row).find('input[name="sgst_amount[]"]').val(sgstAmt.toFixed(2));

                let totalRate = rate + ((rate * gst) / 100);
                $(row).find('input[name="total_rate_in_amount[]"]').val(totalRate.toFixed(2));

                let amount = taxable + cgstAmt + sgstAmt;
                $(row).find('input[name="amount[]"]').val(amount.toFixed(2));
            } else {
                $(row).find('input[name="taxable_amount[]"]').val('');
                $(row).find('input[name="cgst_percentage[]"]').val('');
                $(row).find('input[name="sgst_percentage[]"]').val('');
                $(row).find('input[name="cgst_amount[]"]').val('');
                $(row).find('input[name="sgst_amount[]"]').val('');
                $(row).find('input[name="total_rate_in_amount[]"]').val('');
                $(row).find('input[name="amount[]"]').val('');
            }
            recalcTotals();
        }


        function recalcTotals() {
            let totalRate = 0;
            let totalTaxable = 0;
            let totalCgstAmt = 0;
            let totalSgstAmt = 0;
            let totalAmount = 0;
            let totalRows = 0;
            let totalItemWeight = 0;
            let totalGrossWeight = 0;
            let totalBlPercentage = 0;

            $("#productBody tr").each(function() {
                let rate = parseFloat($(this).find('input[name="rate_in_amount[]"]').val()) || 0;
                let taxable = parseFloat($(this).find('input[name="taxable_amount[]"]').val()) || 0;
                let cgstAmt = parseFloat($(this).find('input[name="cgst_amount[]"]').val()) || 0;
                let sgstAmt = parseFloat($(this).find('input[name="sgst_amount[]"]').val()) || 0;
                let totalRateAmt = parseFloat($(this).find('input[name="total_rate_in_amount[]"]').val()) || 0;
                let amount = parseFloat($(this).find('input[name="amount[]"]').val()) || 0;
                let bl_percentage = parseFloat($(this).find('input[name="bl_percentage[]"]').val()) || 0;

                let issueWeight = parseFloat($(this).find('input[name="issue_weight[]"]').val()) || 0;
                let grossWeight = parseFloat($(this).find('input[name="gross_weight[]"]').val()) || 0;

                totalRate += rate;
                totalTaxable += taxable;
                totalCgstAmt += cgstAmt;
                totalSgstAmt += sgstAmt;
                totalAmount += amount;
                totalRows++;

                totalItemWeight += issueWeight;
                totalGrossWeight += grossWeight;
                totalBlPercentage += ((issueWeight * bl_percentage) / 100);
            });

            let finalGrossWeight = totalGrossWeight + totalBlPercentage;

            $("#total_item_weight").val(formatWeight(totalItemWeight.toFixed(3)));
            $("#total_gross_weight").val(formatWeight(finalGrossWeight.toFixed(3)));

            $("#master_rate").val(totalRate.toFixed(2));
            $("#master_taxable").val(totalTaxable.toFixed(2));
            $("#total_cgst_amount").val(totalCgstAmt.toFixed(2));
            $("#total_sgst_amount").val(totalSgstAmt.toFixed(2));
            $("#master_total_rate").val((totalRate + ((totalRate * ((totalCgstAmt + totalSgstAmt) / totalTaxable || 0))))
                .toFixed(2));
            $("#master_total_amount").val(totalAmount.toFixed(2));


            let cgstPer = (totalCgstAmt && totalTaxable) ? (totalCgstAmt * 100 / totalTaxable) : 0;
            let sgstPer = (totalSgstAmt && totalTaxable) ? (totalSgstAmt * 100 / totalTaxable) : 0;

            $("#total_cgst_percentage").val(cgstPer.toFixed(2));
            $("#total_sgst_percentage").val(sgstPer.toFixed(2));


            $("#sub_total").val(totalAmount.toFixed(2));

            let grand_total = totalAmount;
            let decimalPart = grand_total % 1;
            $("#round_off").val(decimalPart.toFixed(2));
            $("#grand_total").val((grand_total - decimalPart + Math.round(decimalPart)).toFixed(2));
        }

        function formatWeight(val) {
            val = val.toString().trim();
            if (val === "" || val === "." || val.endsWith(".")) return val;

            let num = parseFloat(val);
            if (isNaN(num)) return "";

            let intPart = Math.floor(num);
            let decimal = Math.round((num % 1) * 1000);
            let decStr = decimal.toString().padStart(3, "0");

            let firstDigit = parseInt(decStr[0]);
            let lastTwo = parseInt(decStr.slice(1));
            let newFirstDigit = firstDigit;

            let finalDecimal = 0;

            if (lastTwo <= 29) {
                finalDecimal = newFirstDigit * 100;
            } else if (lastTwo <= 79) {
                finalDecimal = newFirstDigit * 100 + 50;
            } else {
                newFirstDigit = firstDigit + 1;
                finalDecimal = newFirstDigit * 100;
            }
            return (intPart + finalDecimal / 1000).toFixed(3);
        }
    </script>
@endpush
