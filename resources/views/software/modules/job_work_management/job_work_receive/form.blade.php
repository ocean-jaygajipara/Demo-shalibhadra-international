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

        #quotation-used-items-table.table> :not(caption)>*>* {
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
            <form
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
                <input type="hidden" name="total_issue_gross_weight" id="total_issue_gross_weight"
                    value="{{ old('total_issue_gross_weight', isset($edit) && $edit?->master_gross_weight !== null ? $edit?->master_gross_weight : '') }}" />
                <input type="hidden" name="master_total_current_pending_org" id="master_total_current_pending_org" value="" />



                <div class="">
                    <!-- Section 1: Vendor Details -->
                    <h4 class="mb-3">Vendor Details</h4>
                    <div class="row g-3 mb-3">
                        <input type="hidden" id="job_work_issue_id" name="job_work_issue_id"
                            value="{{ isset($edit) && $edit?->id ? $edit?->job_work_issue_id : '' }}">
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
                            <label class="form-label">Job Work Issue No<span class="text-danger">*</span></label>
                            @if (isset($edit) && $edit?->id)
                                <input type="text" class="form-control" readonly tabindex="-1"
                                    value="{{ $edit?->job_work_issue?->job_work_issue_no ?? old('job_work_issue_no') }}">
                            @else
                                <select name="job_work_issue_no" id="job_work_issue_no"
                                    class="form-control @error('job_work_issue_no') is-invalid @enderror search_by_job_work_issue_no select2"
                                    data-append="search_by_job_work_issue_no"
                                    data-selectedJobWorkIssueNo="{{ $edit?->job_work_issue?->job_work_issue_no ?? old('job_work_issue_no') }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('job_work_issue_no', $edit?->job_work_issue?->job_work_issue_no ?? '') ? '' : 'selected' }}>
                                        Select Job Work Issue No
                                    </option>
                                </select>
                                @error('job_work_issue_no')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            @endif
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

                        <div class="col-md-3 mt-3">
                            <label class="form-label">Job Work Issue Date <span class="text-danger">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="job_work_issue_date_display" class="form-control"
                                    placeholder="DD-MM-YYYY" style="padding-right: 30px;"
                                    value="{{ old('job_work_issue_date_display')
                                        ? date('d-m-Y', strtotime(old('job_work_issue_date_display')))
                                        : (isset($edit?->job_work_issue?->job_work_issue_date)
                                            ? date('d-m-Y', strtotime($edit?->job_work_issue?->job_work_issue_date))
                                            : date('d-m-Y')) }}"
                                    readonly tabindex="-1" />

                                <span
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </span>
                            </div>

                            <input type="hidden" name="job_work_issue_date" id="job_work_issue_date"
                                value="{{ old('job_work_issue_date')
                                    ? date('d-m-Y', strtotime(old('job_work_issue_date')))
                                    : (isset($edit?->job_work_issue?->job_work_issue_date)
                                        ? date('d-m-Y', strtotime($edit?->job_work_issue?->job_work_issue_date))
                                        : date('d-m-Y')) }}" />
                        </div>

                    </div>


                    <div class="row g-3 mb-3">
                        @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                        <div class="col-md-6 mt-3">
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <div class="row align-items-center">
                                        <label class="col-4"><strong>Vendor Name:</strong></label>
                                        <div class="col-8">
                                            <input type="hidden" name="vendor_id" id="vendor_id"
                                                value="{{ isset($edit) && $edit?->vendor_id ? $edit?->vendor_id : old('vendor_id') }}" />
                                            <p class="mb-0"><span
                                                    id="vendor_name_display">{{ old('vendor_name', isset($edit) && $edit?->vendor ? $edit?->vendor->vendor_name : 'N/A') }}</span>
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
                                            value="{{ old('phone', $edit->customer->mobile_number ?? '') }}"
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
                                                placeholder="Enter address">{{ old('address', $edit->customer->address ?? '') }}</textarea>
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

                            <input type="hidden" name="vendor_id" id="vendor_id"
                                                value="{{ isset($edit) && $edit?->vendor_id ? $edit?->vendor_id : old('vendor_id') }}" />
                        @endif

                        <div class="col-md-3 mt-6">
                            <label>Warehouse <span class="text-danger">*</span></label>
                            <select id="warehouse_id" name="warehouse_id"
                                class="form-control @error('warehouse_id') is-invalid @enderror search_by_warehouse select2"
                                data-append="search_by_warehouse"
                                data-selectedWarehouseId="{{ $edit->warehouse_id ?? '' }}" autofocus>
                                <option value="" disabled
                                    {{ old('warehouse_id', $edit->warehouse_id ?? '') ? '' : 'selected' }}>
                                    Select Warehouse
                                </option>
                            </select>
                            @error('warehouse_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Job Work Receive No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control readonly-look" name="job_work_receive_no"
                                id="job_work_receive_no" placeholder="Enter Job Work Receive No" readonly tabindex="-1"
                                value="{{ old('job_work_receive_no', isset($edit) && $edit?->job_work_receive_no ? $edit?->job_work_receive_no : '') }}">
                        </div>

                        <div class="col-md-3 mt-3">
                            <label class="form-label">Job Work Receive Date <span class="text-danger">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="job_work_receive_date_display"
                                    class="form-control @error('job_work_receive_date') is-invalid @enderror"
                                    value="{{ old('job_work_receive_date')
                                        ? date('d-m-Y', strtotime(old('job_work_receive_date')))
                                        : (isset($edit->job_work_receive_date)
                                            ? date('d-m-Y', strtotime($edit->job_work_receive_date))
                                            : date('d-m-Y')) }}"
                                    placeholder="DD-MM-YYYY" style="padding-right: 30px;" />

                                <span
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </span>
                            </div>

                            <input type="hidden" name="job_work_receive_date" id="job_work_receive_date"
                                value="{{ old('job_work_receive_date')
                                    ? date('Y-m-d', strtotime(old('job_work_receive_date')))
                                    : (isset($edit->job_work_receive_date)
                                        ? date('Y-m-d', strtotime($edit->job_work_receive_date))
                                        : date('Y-m-d')) }}" />
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Process Received<span class="text-danger">*</span></label>
                            <select name="process_receive_id" id="process_receive_id"
                                class="form-control @error('process_receive_id') is-invalid @enderror search_by_process_receive_id select2"
                                data-append="search_by_process_receive_id" data-default-grid="yes"
                                data-selectedProcessReceivedid="{{ isset($edit) && $edit?->process_receive_id ? $edit?->process_receive_id : old('process_receive_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('process_receive_id', $edit->process_receive_id ?? '') ? '' : 'selected' }}>
                                    Select Process Received
                                </option>
                            </select>
                            @error('process_receive_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>


                        <div class="col-md-3">
                            <label class="form-label">Remarks </label>
                            <textarea class="form-control autosize" name="remarks" id="remarks">{{ isset($edit) && $edit?->remarks ? $edit?->remarks : old('remarks') }} </textarea>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h4 class="mb-3">Job Work Received Items</h4>

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
                                    <th style="width: 50px;">Sr No</th>
                                    <th style="min-width: 200px;">Item Code</th>
                                    <th style="min-width: 200px;">Item Name</th>
                                    <th style="min-width: 200px;">Stock</th>
                                    <th style="min-width: 200px;">Pending Weight</th>
                                    <th style="min-width: 200px;">Received Weight</th>
                                    <th style="min-width: 200px;">Gross Weight</th>
                                    <th style="min-width: 200px;">Wt/Pc</th>
                                    <th style="min-width: 200px;">Qty</th>
                                    <th style="min-width: 200px;">Rate</th>
                                    <th style="min-width: 200px;">BL</th>
                                    <th style="min-width: 200px;">Incoming Scrap</th>
                                    <th style="min-width: 50px;">Action</th>
                                </tr>
                            </thead>

                            @php
                                $uniqueTime = time();
                            @endphp

                            <tbody id="productBody">
                                @if (isset($edit) && $edit?->job_work_receive_items)
                                    @foreach ($edit?->job_work_receive_items as $key => $item)
                                        @php
                                            $issueItem = $edit->job_work_issue_items[$key] ?? null;
                                            $remaining_weight = $issueItem ? $issueItem->remaining_weight : 0;
                                            $uniqueId = $uniqueTime . '-' . $key;

                                            $issue_gross_weight = 0;
                                            if ($item->job_work_issue_item_id > 0) {
                                                $issue_gross_weight = $remaining_weight + $item?->received_weight;
                                            }

                                        @endphp

                                        <tr data-row-id="{{ $uniqueId }}">
                                            <input type="hidden" class="form-control" name="item_id[]"
                                                value="{{ $item->job_work_order_item_id }}">
                                            <input type="hidden" class="form-control" name="job_work_issue_item_id[]"
                                                value="{{ $item->job_work_issue_item_id }}">
                                            <input type="hidden" class="form-control" name="product_id[]"
                                                value="{{ $item->product_id }}">
                                            <input type="hidden" class="form-control" name="product_gross_weight[]"
                                                value="{{ $item?->product?->gross_wt }}">
                                            <input type="hidden" class="form-control" name="product_net_weight[]"
                                                value="{{ $item?->product?->net_wt }}">
                                            <input type="hidden" class="form-control" name="issue_gross_weight[]"
                                                value="{{ $issue_gross_weight }}">
                                            <input type="hidden" class="form-control" name="master_gross_weight[]"
                                                value="{{ $edit->master_gross_weight }}">

                                            <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_code[]"
                                                    value="{{ $item->product_code ?? '' }}" readonly><label
                                                    class="product_code_txt">{{ $item->product_code ?? '' }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_name[]"
                                                    value="{{ $item->product_name ?? '' }}" readonly><label
                                                    class="product_name_txt">{{ $item->product_name ?? '' }}</label></td>
                                            <td style="display: flex; align-items: center;">
                                                <input type="text" class="form-control bg-light"
                                                    name="product_stock[]" value="{{ $item->product_stock ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    tabindex="-1">
                                                &nbsp;&nbsp;
                                                <small class="text-muted product_stock_txt mt-2">
                                                    @if (!empty($item->product_stock))
                                                        ({{ number_format($item->product_stock / 1000, 2) }} Kg)
                                                    @endif
                                                </small>
                                            </td>
                                            <td><input type="text" class="form-control bg-light"
                                                    name="pending_weight[]" value="{{ $item->pending_weight ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    readonly tabindex="-1"></td>

                                            <td><input type="text" class="form-control" name="received_weight[]"
                                                    value="{{ $item->received_weight ?? '' }}"
                                                    onkeypress="return isDecimal(event)" onchange="recalculateRow(this)">
                                            </td>
                                            <td><input type="text" class="form-control" name="gross_weight[]"
                                                    value="{{ $item->gross_weight ?? '' }}"
                                                    onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>

                                            <td><input type="text" class="form-control" name="wt_pc[]"
                                                    value="{{ $item->wt_pc ?? '' }}" onkeypress="return isDecimal(event)"
                                                    onchange="recalculateRow(this)"></td>
                                            <td><input type="text" class="form-control bg-light" name="quantity[]"
                                                    value="{{ $item->quantity ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    readonly tabindex="-1"></td>
                                            <td><input type="text" class="form-control rate_in_amount"
                                                    name="rate_in_amount[]" onkeypress="return isDecimal(event)"
                                                    value="{{ $item->rate_in_amount ?? '' }}" onchange="recalcTotals()">
                                            </td>
                                            <td><input type="hidden" name="db_bl_percentage[]"
                                                    value="{{ $item->bl_percentage ?? '' }}" /><input type="text"
                                                    class="form-control" name="bl_percentage[]"
                                                    value="{{ $item->bl_percentage ?? '' }}"
                                                    onkeypress="return isDecimal(event)" onchange="recalculateRow(this)">
                                            </td>
                                            <td><input type="hidden" name="db_incoming_scrap[]"
                                                    value="{{ $item->incoming_scrap ?? '' }}" /><input type="text"
                                                    class="form-control" name="incoming_scrap[]"
                                                    value="{{ $item->incoming_scrap ?? '' }}"
                                                    onkeypress="return isDecimal(event)" onchange="recalculateRow(this)">
                                            </td>
                                            <td><button type="button" class="btn btn-danger deleteRow"
                                                    data-row-id="{{ $uniqueId }}"
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
                                    <td></td>
                                    <td></td>

                                    <td> <input type="number"
                                            value="{{ isset($edit) && $edit?->master_rate ? $edit?->master_rate : old('master_rate') }}"
                                            class="form-control bg-light master_rate" readonly="" name="master_rate"
                                            id="master_rate" tabindex="-1"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h4 class="mb-3 mt-3">Job Work Used Items</h4>
                    <div class="table-responsive border" style="">
                        <table class="table table-bordered mb-0" id="quotation-used-items-table">
                            <thead class="table-light text-center align-middle">
                                <tr>
                                    <th style="min-width: 200px;">Item Code</th>
                                    <th style="min-width: 200px;">Item Used</th>
                                    <th style="min-width: 200px;">Used For</th>
                                    <th style="min-width: 200px;">Our Stock</th>
                                    <th style="min-width: 200px;">Challan Stock at JobWorker</th>
                                    <th style="min-width: 200px;">Used Qty</th>
                                    <th style="min-width: 200px;">Used Weight</th>
                                </tr>
                            </thead>


                            <tbody id="productUsedBody">
                                @if (isset($edit) && $edit?->job_work_receive_items)
                                    @foreach ($edit?->job_work_receive_items as $key => $item)
                                        @php
                                            $uniqueId = $uniqueTime . '-' . $key;
                                        @endphp

                                        <tr data-row-id="{{ $uniqueId }}">
                                            <td><input type="hidden" class="form-control bg-light"
                                                    name="product_code_1[]" value="{{ $item->product_code ?? '' }}"
                                                    readonly><label
                                                    class="product_code_txt">{{ $item->product_code ?? '' }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_used[]"
                                                    value="{{ $item->product_used ?? '' }}" readonly><label
                                                    class="product_used_txt">{{ $item->product_used ?? '' }}</label></td>
                                            <td><label class="product_name_txt">{{ $item->product_name ?? '' }}</label>
                                            <td><input type="hidden" name="db_our_stock[]" value="{{ $item->our_stock ?? '' }}" />
                                                <input type="text" class="form-control bg-light" name="our_stock[]"
                                                    value="{{ $item->our_stock ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    readonly tabindex="-1"></td>
                                            <td><input type="text" class="form-control bg-light"
                                                    name="challan_stock[]" value="{{ $item->challan_stock ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    readonly tabindex="-1"></td>
                                            <td><input type="text" class="form-control bg-light" name="used_qty[]"
                                                    value="{{ $item->used_qty ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    readonly tabindex="-1"></td>
                                            <td><input type="text" class="form-control bg-light" name="used_weight[]"
                                                    value="{{ $item->used_weight ?? '' }}"
                                                    onkeypress="return isNumber(event)" onchange="recalculateRow(this)"
                                                    readonly tabindex="-1"></td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class=" mt-5">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label">Total Pending</label>
                                <input type="number"
                                    value="{{ old('master_total_pending', isset($edit) && $edit?->master_total_pending !== null ? $edit?->master_total_pending : '') }}"
                                    class="form-control bg-light" placeholder="Enter Total Pending"
                                    id="master_total_pending" value="" name="master_total_pending" readonly
                                    tabindex="-1">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Total Net. Weight</label>
                                <input type="text"
                                    value="{{ old('master_total_net_weight', isset($edit) && $edit?->master_total_net_weight ? $edit?->master_total_net_weight : '') }}"
                                    class="form-control bg-light" value="" placeholder="Enter Total Net. Weight"
                                    id="master_total_net_weight" name="master_total_net_weight" readonly tabindex="-1">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Total Gross Weight</label>
                                <input type="text"
                                    value="{{ old('master_total_gross_weight', isset($edit) && $edit?->master_total_gross_weight ? $edit?->master_total_gross_weight : '') }}"
                                    class="form-control bg-light" value="" placeholder="Enter Total Gross Weight"
                                    id="master_total_gross_weight" name="master_total_gross_weight" readonly
                                    tabindex="-1">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Current Pending</label>
                                <input type="text"
                                    value="{{ old('master_total_current_pending', isset($edit) && $edit?->master_total_current_pending !== null ? $edit?->master_total_current_pending : '') }}"
                                    class="form-control bg-light" value="" placeholder="Enter Current Pending"
                                    id="master_total_current_pending" name="master_total_current_pending" readonly
                                    tabindex="-1">
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
                        <button type="submit" class="btn btn-success mt-1 mb-1 submit_btn">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>

                        <button type="submit" name="submit_type" value="save_print" class="btn btn-primary mt-1 mb-1 submit_print_btn">
                            Submit & Print
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1 cancel_btn">Cancel</a>
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
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>
    <script>
        let termsInstance;
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getWarehouse')
    @if (!isset($edit))
        @include('utils.generateJobWorkReceiveNo')
    @endif
    @include('utils.getProductCategory')

    @include('utils.getJobWorkIssueNo')
    @include('utils.getProcessReceived')

    <script>
        // Configuration for getProductAndVariant
        window.getProductAndVariantConfig = {
            available_stock: true
        };
    </script>
    @include('utils.getProductAndVariant')
    {{-- @include('utils.getProductAndVariantWithOutCategory') --}}
    <script>
        flatpickr("#job_work_receive_date_display", {
            dateFormat: "d-m-Y",
            allowInput: true,
            defaultDate: "{{ old('job_work_receive_date', isset($edit?->job_work_receive_date) ? date('d-m-Y', strtotime($edit?->job_work_receive_date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('job_work_receive_date').value = ymd; // hidden input for submission
            }
        });
    </script>
    <script>
        // Global variables to manage API requests and prevent duplicates
        let jobWorkIssueProductInfoRequest = null;
        let jobWorkIssueProductInfoLoading = false;
        let jobWorkIssueProductInfoDebounceTimer = null;

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

        $(document).on('click', '#addRowBtn', function() {
            let company_id = $(".search_by_company option:selected").val() || $('meta[name="company_id"]').attr(
                'value');
            let vendor_id = $('#vendor_id').val();
            let product_id = $('#product_variant_price_id').val();

            if (vendor_id == '') {
                toastr.error("Please select Job Work Issue No");
                return;
            }

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

                    let newRow = document.createElement("tr");
                    let newUsedRow = document.createElement("tr");

                    let index = $("#productBody tr").length;
                    let uniqueId = Date.now() + "-" + index;


                    let firstMasterGrossWeight = $("#total_issue_gross_weight").val();


                    // ---- Product Row ----
                    newRow.setAttribute("data-row-id", uniqueId);
                    newRow.innerHTML = `
                        <input type="hidden" class="form-control" name="item_id[]" value="">
                        <input type="hidden" name="job_work_issue_item_id[]" value="">
                        <input type="hidden" class="form-control" name="product_id[]" value="${data.product_id}">
                        <input type="hidden" class="form-control" name="product_gross_weight[]" value="${data.gross_wt}">
                        <input type="hidden" class="form-control" name="product_net_weight[]" value="${data.net_wt}">
                        <input type="hidden" class="form-control" name="issue_gross_weight[]" value="">
                        <input type="hidden" class="form-control" name="master_gross_weight[]" value="${firstMasterGrossWeight}">

                        <td class="sr_no_txt">${srNo}</td>
                        <td><input type="hidden" class="form-control bg-light" name="product_code[]" readonly value="${data.part_no}"><label class="product_code_txt">${data.part_no}</label></td>
                        <td><input type="hidden" class="form-control bg-light" name="product_name[]" readonly value="${data.product_name}"><label class="product_name_txt">${data.product_name}</label></td>

                        <td style="display: flex;"><input type="text" class="form-control bg-light" name="product_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly value="" tabindex="-1">&nbsp;&nbsp;<small class="text-muted product_stock_txt mt-2"></small></td>
                        <td><input type="text" class="form-control bg-light" name="pending_weight[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control" name="received_weight[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><input type="text" class="form-control" name="gross_weight[]" onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>
                        <td><input type="text" class="form-control" name="wt_pc[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)" value=""></td>
                        <td><input type="text" class="form-control bg-light" name="quantity[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control rate_in_amount" name="rate_in_amount[]" onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>
                        <td><input type="text" class="form-control" name="bl_percentage[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><input type="text" class="form-control" name="incoming_scrap[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><button type="button" class="btn btn-danger deleteRow" onclick="removeRow(this)" data-row-id="${uniqueId}">X</button></td>
                    `;
                    $("#productBody").append(newRow);

                    let productSubId = data.product_id;
                    let exists = false;
                    $('input[name="product_sub_id[]"]').each(function() {
                        if ($(this).val() == productSubId) {
                            exists = true;
                            return false;
                        }
                    });
                    if (exists) {
                        return;
                    }


                    // ---- Used Product Row ----
                    newUsedRow.setAttribute("data-row-id", uniqueId);
                    newUsedRow.innerHTML = `
                        <td><input type="hidden" class="form-control" name="product_sub_id[]" value="${data.product_id}"><input type="hidden" class="form-control bg-light" name="product_code_1[]" readonly value="${data.part_no}"><label class="product_code_txt">${data.part_no}</label></td>
                        <td><input type="hidden" name="product_used[]" value="${data.product_bom}" readonly><label class="product_used_txt">${data.product_bom}</label></td>
                        <td><label class="product_name_txt">${data.product_name}</label></td>
                        <td><input type="text" class="form-control bg-light" name="our_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control bg-light" name="challan_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control bg-light" name="used_qty[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control bg-light" name="used_weight[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                    `;


                    $("#productUsedBody").append(newUsedRow);
                }
            });
        });

        $(document).on('change', '#job_work_issue_no', function() {
            // Clear any existing debounce timer
            if (jobWorkIssueProductInfoDebounceTimer) {
                clearTimeout(jobWorkIssueProductInfoDebounceTimer);
            }

            // Debounce the API call to prevent rapid successive calls
            jobWorkIssueProductInfoDebounceTimer = setTimeout(function() {
                fetchJobWorkIssueProductInfo();
            }, 300); // 300ms debounce delay
        });

        function fetchJobWorkIssueProductInfo() {
            // Prevent duplicate calls if request is already in progress
            if (jobWorkIssueProductInfoLoading) {
                console.log("Job work issue product info request already in progress, skipping duplicate call");
                return;
            }

            let company_id = $(".search_by_company option:selected").val() || $('meta[name="company_id"]').attr('value');
            let selectedOption = $('#job_work_issue_no').find('option:selected');
            let job_work_issue_date = selectedOption.data('job_work_issue_date');
            let job_work_issue_no = selectedOption.data('job_work_issue_no');
            let warehouse_id = selectedOption.data('warehouse_id');

            if (!company_id || !job_work_issue_no) {
                return;
            }

            // Abort previous request if any
            if (jobWorkIssueProductInfoRequest !== null) {
                jobWorkIssueProductInfoRequest.abort();
            }

            jobWorkIssueProductInfoLoading = true; // Set loading flag
            jobWorkIssueProductInfoRequest = $.ajax({
                url: '{{ env('API_URL') }}get-job-issue-product-info',
                type: "POST",
                dataType: "json",
                data: {
                    job_work_issue_no: job_work_issue_no,
                    company_id: company_id,
                },
                success: function(response) {
                    jobWorkIssueProductInfoLoading = false; // Reset loading flag on success
                    let items = response.data.job_work_issue_items || [];
                    let job_work_issue = response.data;

                    $("#job_work_issue_id").val(job_work_issue.id);
                    $("#product_category_id").attr("data-selectedCategoryId",response.data.product_category_id);
                    $("#warehouse_id").attr("data-selectedWarehouseId",warehouse_id);

                    let vendorInfo = response.data.vendor;
                    $("#gst_no").val(vendorInfo.gst_number);

                    $("#job_work_issue_date").val(job_work_issue_date);
                    $("#job_work_issue_date_display").val(job_work_issue_date);
                    $("#vendor_id").val(vendorInfo.id);
                    $("#vendor_name").val(vendorInfo.vendor_name);
                    $("#vendor_name_display").text(vendorInfo.vendor_name);

                    $("#address").val(vendorInfo.address);
                    $("#address_display").text(vendorInfo.address);

                    $("#phone").val(vendorInfo.mobile_number);
                    $("#phone_display").text(vendorInfo.mobile_number);

                    $("#state").val(vendorInfo.state.name);
                    $("#state_display").text(vendorInfo.state.name);

                    $("#shipping_address").val(vendorInfo.shipping_address);
                    $("#billing_address").val(vendorInfo.billing_address);

                    // // Clear existing rows
                    $("#productBody").empty();
                    $("#productUsedBody").empty();

                    $("#total_issue_gross_weight").val(job_work_issue.master_gross_weight);
                    $("#master_total_pending").val(job_work_issue.master_gross_weight);

                    if (job_work_issue.master_total_current_pending !== null &&
                        job_work_issue.master_total_current_pending !== undefined &&
                        job_work_issue.master_total_current_pending !== '') {

                        $("#master_total_current_pending").val(formatWeight(job_work_issue.master_total_current_pending));
                        $("#master_total_current_pending_org").val(formatWeight(job_work_issue.master_total_current_pending));
                    } else {
                        $("#master_total_current_pending").val(formatWeight(job_work_issue.master_gross_weight));
                        $("#master_total_current_pending_org").val(formatWeight(job_work_issue.master_gross_weight));
                    }

                    items.forEach(function(data, index) {
                        let newRow = document.createElement("tr");
                        let newUsedRow = document.createElement("tr");
                        let uniqueId = Date.now() + "-" + index;

                        let productSubId = data.product_id;
                        let exists = false;
                        $('input[name="product_sub_id[]"]').each(function() {
                            if ($(this).val() == productSubId) {
                                exists = true;
                                return false;
                            }
                        });
                        if (exists) {
                            return;
                        }


                        /*
                        // ---- Product Row ----
                        newRow.setAttribute("data-row-id", uniqueId);
                        newRow.innerHTML = `
                        <input type="hidden" class="form-control" name="item_id[]" value="">
                        <input type="hidden" name="job_work_issue_item_id[]" value="${data.id}">
                        <input type="hidden" class="form-control" name="product_id[]" value="${data.product_id}">
                        <input type="hidden" class="form-control" name="product_gross_weight[]" value="${data.product.gross_wt}">
                        <input type="hidden" class="form-control" name="product_net_weight[]" value="${data.product.net_wt}">
                        <input type="hidden" class="form-control" name="issue_gross_weight[]" value="${data.gross_weight}">
                        <input type="hidden" class="form-control" name="master_gross_weight[]" value="${job_work_issue.master_gross_weight}">

                        <td><input type="hidden" class="form-control bg-light" name="product_code[]" readonly value="${data.product_code}"><label class="product_code_txt">${data.product_code}</label></td>
                        <td><input type="hidden" class="form-control bg-light" name="product_name[]" readonly value="${data.product_name}"><label class="product_name_txt">${data.product_name}</label></td>

                        <td style="display: flex;"><input type="text" class="form-control bg-light" name="product_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly value="" tabindex="-1">&nbsp;&nbsp;<small class="text-muted product_stock_txt mt-2"></small></td>
                        <td><input type="text" class="form-control bg-light" name="pending_weight[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control" name="received_weight[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><input type="text" class="form-control" name="gross_weight[]" onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>
                        <td><input type="text" class="form-control" name="wt_pc[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)" value=""></td>
                        <td><input type="text" class="form-control bg-light" name="quantity[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control rate_in_amount" name="rate_in_amount[]" onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>
                        <td><input type="text" class="form-control" name="bl_percentage[]" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                        <td><button type="button" class="btn btn-danger deleteRow" onclick="removeRow(this)" data-row-id="${uniqueId}">X</button></td>
                    `;

                        // ---- Used Product Row ----
                        newUsedRow.setAttribute("data-row-id", uniqueId);
                        newUsedRow.innerHTML = `
                        <td><input type="hidden" class="form-control" name="product_sub_id[]" value="${data.product_id}"><input type="hidden" class="form-control bg-light" name="product_code_1[]" readonly value="${data.product_code}"><label class="product_code_txt">${data.product_code}</label></td>
                        <td><input type="hidden" name="product_used[]" value="${data.product_bom}" readonly><label class="product_used_txt">${data.product_bom}</label></td>
                        <td><label class="product_name_txt">${data.product_name}</label></td>
                        <td><input type="text" class="form-control bg-light" name="our_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control bg-light" name="challan_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control bg-light" name="used_qty[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                        <td><input type="text" class="form-control bg-light" name="used_weight[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                    `;

                        $("#productBody").append(newRow);
                        $("#productUsedBody").append(newUsedRow);
                        $('#product_variant_price_id').val('').trigger('change');
                        // initialize dropdowns
                        */
                    });
                    fetchProductCategory();
                    fetch_warehouse();
                },
                error: function(xhr, status, error) {
                    jobWorkIssueProductInfoLoading = false; // Reset loading flag on error
                    if (status !== 'abort') {
                        console.error("Error: ", error);
                    }
                },
                complete: function() {
                    jobWorkIssueProductInfoRequest = null; // Reset request object
                }
            });
        }


        function saveJobWorkToLocalStorage() {
            let rows = [];

            $("#productBody tr").each(function() {
                let row = {
                    type: "product"
                };
                row["row_id"] = $(this).attr("data-row-id") || "";

                $(this).find("input, select").each(function() {
                    let name = $(this).attr("name");
                    if (name) {
                        row[name] = $(this).val();
                    }
                });
                rows.push(row);
            });


            $("#productUsedBody tr").each(function() {
                let row = {
                    type: "used"
                };
                row["row_id"] = $(this).attr("data-row-id") || "";

                $(this).find("input, select").each(function() {
                    let name = $(this).attr("name");
                    if (name) {
                        row[name] = $(this).val();
                    }
                });
                rows.push(row);
            });

            localStorage.setItem("jobWorkTable", JSON.stringify(rows));
        }


        $(document).ready(function() {
            let savedRows = localStorage.getItem("jobWorkTable");
            if (savedRows) {
                savedRows = JSON.parse(savedRows);
                savedRows.forEach(function(row) {
                    let newRow = document.createElement("tr");
                    newRow.setAttribute("data-row-id", row["row_id"]);

                    if (row.type === "product") {
                        newRow.innerHTML = `
                            <input type="hidden" name="item_id[]" value="${row['item_id[]'] || ''}">
                            <input type="hidden" name="job_work_issue_item_id[]" value="${row['job_work_issue_item_id[]'] || ''}">
                            <input type="hidden" name="product_id[]" value="${row['product_id[]'] || ''}">
                            <input type="hidden" name="product_gross_weight[]" value="${row['product_gross_weight[]'] || ''}">
                            <input type="hidden" name="product_net_weight[]" value="${row['product_net_weight[]'] || ''}">
                            <input type="hidden" class="form-control" name="issue_gross_weight[]" value="${row['issue_gross_weight[]'] || ''}">
                            <input type="hidden" class="form-control" name="master_gross_weight[]" value="${row['master_gross_weight[]'] || ''}">



                            <td><input type="hidden" name="product_code[]" value="${row['product_code[]'] || ''}"><label class="product_code_txt">${row['product_code[]'] || ''}</label></td>
                            <td><input type="hidden" name="product_name[]" value="${row['product_name[]'] || ''}"><label class="product_name_txt">${row['product_name[]'] || ''}</label></td>
                            <td style="display: flex;"><input type="text" class="form-control bg-light" name="product_stock[]" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly value="${row['product_stock[]'] || ''}" tabindex="-1">&nbsp;&nbsp;<small class="text-muted product_stock_txt mt-2"></small></td>
                            <td><input type="text" class="form-control bg-light" name="pending_weight[]" value="${row['pending_weight[]'] || ''}" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                            <td><input type="text" class="form-control" name="received_weight[]" value="${row['received_weight[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                            <td><input type="text" class="form-control" name="gross_weight[]" value="${row['gross_weight[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>
                            <td><input type="text" class="form-control" name="wt_pc[]" value="${row['wt_pc[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                            <td><input type="text" class="form-control bg-light" name="quantity[]" value="${row['quantity[]'] || ''}" onkeypress="return isNumber(event)" onchange="recalculateRow(this)" readonly tabindex="-1"></td>
                            <td><input type="text" class="form-control" name="rate_in_amount[]" value="${row['rate_in_amount[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalcTotals()"></td>
                            <td><input type="text" class="form-control" name="bl_percentage[]" value="${row['bl_percentage[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                            <td><input type="text" class="form-control" name="incoming_scrap[]" value="${row['incoming_scrap[]'] || ''}" onkeypress="return isDecimal(event)" onchange="recalculateRow(this)"></td>
                            <td><button type="button" class="btn btn-danger deleteRow" onclick="removeRow(this)" data-row-id="${row['row_id']}">X</button></td>
                        `;
                        $("#productBody").append(newRow);
                    }

                    if (row.type === "used") {
                        let newRow = document.createElement("tr");
                        newRow.setAttribute("data-row-id", row["row_id"]);
                        newRow.innerHTML = `
                            <td>><input type="hidden" class="form-control" name="product_sub_id[]" value="${row['product_sub_id[]'] || ''}"><input type="hidden" class="form-control bg-light" name="product_code_1[]" readonly value="${row['product_code_1[]'] || ''}"><label class="product_code_txt">${row['product_code_1[]'] || ''}</label></td>
                            <td><input type="hidden" name="product_used[]" value="${row['product_used[]'] || ''}" readonly><label class="product_used_txt">${row['product_used[]'] || ''}</label></td>
                            <td><label class="product_name_txt">${row['product_name[]'] || ''}</label></td>
                            <td><input type="text" class="form-control bg-light" name="our_stock[]" value="${row['our_stock[]'] || ''}" readonly tabindex="-1"></td>
                            <td><input type="text" class="form-control bg-light" name="challan_stock[]" value="${row['challan_stock[]'] || ''}" readonly tabindex="-1"></td>
                            <td><input type="text" class="form-control bg-light" name="used_qty[]" value="${row['used_qty[]'] || ''}" readonly tabindex="-1"></td>
                            <td><input type="text" class="form-control bg-light" name="used_weight[]" value="${row['used_weight[]'] || ''}" readonly tabindex="-1"></td>
                        `;
                        $("#productUsedBody").append(newRow);
                    }
                });
            }
        });


        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("deleteRow")) {
                let rowId = e.target.getAttribute("data-row-id");
                document.querySelector(`#productBody tr[data-row-id="${rowId}"]`)?.remove();
                document.querySelector(`#productUsedBody tr[data-row-id="${rowId}"]`)?.remove();
                recalcTotals();
                saveJobWorkToLocalStorage();
            }
        });

        $(document).on("change keyup", "#productBody input, #productBody select", function() {
            saveJobWorkToLocalStorage();
        });

        @if ($errors->any())
        @else

            localStorage.removeItem("jobWorkTable");
        @endif


        function recalculateRow(element) {
            let row = $(element).closest("tr");
            let rowIndex = row.index();
            let $used = $("#productUsedBody tr").eq(rowIndex);


            let issueGrossWeight = parseFloat($(row).find('input[name="issue_gross_weight[]"]').val()) || 0;
            let masterGrossWeight = parseFloat($(row).find('input[name="master_gross_weight[]"]').val()) || 0;
            let grossWeight = parseFloat($(row).find('input[name="product_gross_weight[]"]').val()) || 0;
            let netWeight = parseFloat($(row).find('input[name="product_net_weight[]"]').val()) || 0;
            let receivedWt = parseFloat($(row).find('input[name="received_weight[]"]').val()) || 0;
            let wtPerPc = parseFloat($(row).find('input[name="wt_pc[]"]').val()) || 0;
            let job_work_issue_item_id = $(row).find('input[name="job_work_issue_item_id[]"]').val();

            let received_weight = parseFloat($(row).find('input[name="received_weight[]"]').val()) || 0;
            if (received_weight > 0) {
                $(row).find('input[name="gross_weight[]"]').val(formatWeight(received_weight));
            } else {
                $(row).find('input[name="gross_weight[]"]').val('');
            }

            if (job_work_issue_item_id > 0) {
                // if (receivedWt > issueGrossWeight) {
                //     toastr.error("Received weight cannot be more than Issue Gross Weight!");
                //     $(row).find('input[name="received_weight[]"]').val(issueGrossWeight);
                //     $(row).find('input[name="gross_weight[]"]').val(issueGrossWeight);
                //     receivedWt = issueGrossWeight;
                // }

                // if (receivedWt <= issueGrossWeight) {
                //     let pendingWeight = issueGrossWeight - receivedWt;
                //     $(row).find('input[name="pending_weight[]"]').val(pendingWeight.toFixed(2));

                //     if (wtPerPc > 0 && receivedWt > 0) {
                //         let qty = ((receivedWt / wtPerPc)  * 1000);
                //         $(row).find('input[name="quantity[]"]').val(qty.toFixed(2));
                //     }

                //     if (wtPerPc > 0 && receivedWt > 0) {
                //         let stock = (wtPerPc * 1000) / receivedWt;
                //         $(row).find('input[name="product_stock[]"]').val(stock.toFixed(2));

                //         let stockKg = (stock / 1000).toFixed(2);
                //         $(row).find('.product_stock_txt').text(`(${stockKg} Kg)`);
                //     }

                //     if ($used.length) {
                //         if (grossWeight > 0 && $(row).find('input[name="quantity[]"]').val() >= 0) {
                //             let ourStock = (grossWeight * $(row).find('input[name="quantity[]"]').val())/1000;
                //             $used.find('input[name="our_stock[]"]').val(ourStock.toFixed(2));
                //         } else {
                //             $used.find('input[name="our_stock[]"]').val('');
                //         }

                //         if (grossWeight > 0 && $(row).find('input[name="quantity[]"]').val() >= 0) {
                //             let used_weight = ($(row).find('input[name="quantity[]"]').val() * grossWeight)/1000;
                //             $used.find('input[name="used_weight[]"]').val(used_weight.toFixed(2));
                //         }else{
                //             $used.find('input[name="used_weight[]"]').val('');
                //         }
                //     }
                // }
            } else {
                let totalPendingWeight = 0;
                $("#quotation-items-table tr").each(function() {
                    let val = parseFloat($(this).find('input[name="pending_weight[]"]').val()) || 0;
                    totalPendingWeight += val;
                });
                if (totalPendingWeight == 0) {
                    totalPendingWeight = $("#total_issue_gross_weight").val();
                }
                receivedWt = parseFloat(receivedWt) || 0;
                totalPendingWeight = parseFloat(totalPendingWeight) || 0;

                if (receivedWt > totalPendingWeight) {
                    receivedWt = totalPendingWeight;
                    toastr.error("Received weight cannot be more than Issue Gross Weight!");
                    $(row).find('input[name="received_weight[]"]').val(0);
                    $(row).find('input[name="gross_weight[]"]').val(0);
                    $(row).find('input[name="quantity[]"]').val(0);
                }

                if (receivedWt < totalPendingWeight) {
                    if (wtPerPc > 0 && receivedWt > 0) {
                        let qty = ((receivedWt / wtPerPc) * 1000);
                        $(row).find('input[name="quantity[]"]').val(parseInt(qty));
                    }

                    if (wtPerPc > 0 && receivedWt > 0) {
                        let stock = (wtPerPc * 1000) / receivedWt;
                        $(row).find('input[name="product_stock[]"]').val(stock.toFixed(3));

                        let stockKg = (stock / 1000).toFixed(2);
                        $(row).find('.product_stock_txt').text(`(${stockKg} Kg)`);
                    }

                    if ($used.length) {
                        if (grossWeight > 0 && $(row).find('input[name="quantity[]"]').val() >= 0) {
                            let ourStock = (grossWeight * $(row).find('input[name="quantity[]"]').val()) / 1000;
                            $used.find('input[name="our_stock[]"]').val(formatWeight(ourStock.toFixed(3)));
                        } else {
                            $used.find('input[name="our_stock[]"]').val('');
                        }

                        if (grossWeight > 0 && $(row).find('input[name="quantity[]"]').val() >= 0) {
                            let used_weight = (grossWeight * $(row).find('input[name="quantity[]"]').val()) / 1000;
                            $used.find('input[name="used_weight[]"]').val(formatWeight(used_weight.toFixed(3)));
                        } else {
                            $used.find('input[name="used_weight[]"]').val('');
                        }
                    }
                }
            }
            recalcTotals(element);
        }

        function recalcTotals(element = null) {
            let totalGrossWeight = 0;
            let currentPendingWeight = 0;
            let totalNetWeight = 0;
            let MasterRateInAmount = 0;
            let total_bl_percentage = 0;
            let db_total_bl_percentage = 0;
            let db_total_incoming_scrap = 0;
            let TotalissueGrossWeight = 0;
            let total_incoming_scrap = 0;

            let MasterissueGrossWeight = parseFloat($("#productBody tr:first").find('input[name="master_gross_weight[]"]')
                .val()) || 0;

            $("#productBody tr").each(function() {
                let $row = $(this);
                let pendingWt = parseFloat($row.find('input[name="pending_weight[]"]').val()) || 0;
                let receivedWt = parseFloat($row.find('input[name="received_weight[]"]').val()) || 0;
                let grossWt = parseFloat($row.find('input[name="gross_weight[]"]').val()) || 0;
                let bl_percentage = parseFloat($row.find('input[name="bl_percentage[]"]').val()) || 0;
                let incoming_scrap = parseFloat($row.find('input[name="incoming_scrap[]"]').val()) || 0;
                let db_bl_percentage = parseFloat($row.find('input[name="db_bl_percentage[]"]').val()) || 0;
                let db_incoming_scrap = parseFloat($row.find('input[name="db_incoming_scrap[]"]').val()) || 0;
                let rate_in_amount = parseFloat($row.find('input[name="rate_in_amount[]"]').val()) || 0;
                let job_work_issue_item_id = $row.find('input[name="job_work_issue_item_id[]"]').val();
                let issue_gross_weight = parseFloat($row.find('input[name="issue_gross_weight[]"]').val()) || 0;

                totalNetWeight += receivedWt;
                totalGrossWeight += grossWt;
                currentPendingWeight += pendingWt;
                MasterRateInAmount += rate_in_amount;
                total_bl_percentage += bl_percentage;
                total_incoming_scrap += incoming_scrap;
                db_total_bl_percentage += db_bl_percentage;
                db_total_incoming_scrap += db_incoming_scrap;
                TotalissueGrossWeight += issue_gross_weight;
            });

            setTimeout(() => {
                $("#master_total_net_weight").val(totalNetWeight.toFixed(2));
                $("#master_total_gross_weight").val(totalGrossWeight.toFixed(2));

                let issueGross = parseFloat(MasterissueGrossWeight) || 0;
                let totalNet = parseFloat(totalNetWeight) || 0;
                let dbBL = parseFloat(db_total_bl_percentage) || 0;
                let dbIncoming = parseFloat(db_total_incoming_scrap) || 0;
                let bl = parseFloat(total_bl_percentage) || 0;
                let incoming_scrap = parseFloat(total_incoming_scrap) || 0;

                let pending_w = issueGross - totalNet + dbBL - bl + dbIncoming - incoming_scrap;

                // final rounded safe value
                pending_w = Math.round((pending_w + Number.EPSILON) * 100) / 100;

                let master_total_pending = pending_w;
                let new1_master_total_pending = 0
                // prevent negative
                if (master_total_pending < 0) {
                    new1_master_total_pending = Math.round(((issueGross - totalNet) + Number.EPSILON) * 100) / 100;
                }


                if (master_total_pending >= 0) {
                    $("#master_total_pending").val(master_total_pending.toFixed(2));
                    $(".submit_btn, .submit_print_btn").prop('disabled', false);
                    $(".cancel_btn").removeClass('disabled').css('pointer-events', 'auto');
                } else {
                    toastr.error(`BL% cannot be greater than Total Pending Weight`);
                    $(".submit_btn, .submit_print_btn").prop('disabled', true);
                    $(".cancel_btn").addClass('disabled').css('pointer-events', 'none');
                    if (typeof element !== 'undefined' && element) {
                        let row = $(element).closest("tr");
                        if (row.find('input[name="bl_percentage[]"]').val() > 0) {
                            row.find('input[name="bl_percentage[]"]').val(0);
                        } else {
                            row.find('input[name="received_weight[]"]').val(0);
                            row.find('input[name="gross_weight[]"]').val(0);
                            row.find('input[name="quantity[]"]').val(0);
                        }
                    }
                    $("#master_total_pending").val(new1_master_total_pending.toFixed(2));
                }
                $("#master_rate").val(MasterRateInAmount.toFixed(2));

                let totalOurStock = 0;
                let totalDbOurStock = 0;
                $("#productUsedBody tr").each(function() {
                    let $row = $(this);
                    totalOurStock += parseFloat($row.find('input[name="our_stock[]"]').val()) || 0;
                    totalDbOurStock += parseFloat($row.find('input[name="db_our_stock[]"]').val()) || 0;
                });

                let master_total_current_pending_cr = $("#master_total_current_pending_org").val();
                let master_issue_weight_cr = $("#total_issue_gross_weight").val();

                let final_value = 0;
                if (master_total_current_pending_cr !== "" && master_total_current_pending_cr !== null && !isNaN(master_total_current_pending_cr) && parseFloat(master_total_current_pending_cr) > 0) {
                    final_value = parseFloat(master_total_current_pending_cr);
                } else {
                     console.log("final_value"+master_issue_weight_cr)
                    final_value = parseFloat(master_issue_weight_cr) || 0;
                }
                
                // Current Pending (Stock) = (Master_Org_Pending OR Issue_Total) - (New_Stock_Used - Old_Stock_In_This_Receive) - (New_Scrap - Old_Scrap_In_This_Receive)
                let currentPending = final_value - (totalOurStock - totalDbOurStock) - (total_incoming_scrap - db_total_incoming_scrap);
                //  console.log("final_value"+final_value)
                //  console.log("totalOurStock"+totalOurStock)
                //  console.log("totalDbOurStock"+totalDbOurStock)
                //  console.log("total_incoming_scrap"+total_incoming_scrap)
                //  console.log("db_total_incoming_scrap"+db_total_incoming_scrap)
                //  console.log("our_stock_cr"+currentPending)
                if (isNaN(currentPending) || currentPending < 0) {
                    currentPending = 0;
                }

                $("#master_total_current_pending").val(formatWeight(currentPending.toFixed(3)));
            }, 1000);
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
            }
            else if (lastTwo <= 79) {
                finalDecimal = newFirstDigit * 100 + 50;
            }
            else {
                newFirstDigit = firstDigit + 1;
                finalDecimal = newFirstDigit * 100;
            }
            return (intPart + finalDecimal / 1000).toFixed(3);
        }
    </script>
@endpush
