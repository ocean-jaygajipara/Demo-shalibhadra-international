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
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <input type="hidden" name="vendor_name" id="vendor_name"
                    value="{{ old('vendor_name', isset($edit) && $edit?->vendor ? $edit?->vendor->vendor_name : '') }}" />
                <input type="hidden" name="contact_person_name" id="contact_person_name"
                    value="{{ old('contact_person_name', isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : '') }}"  />
                <input type="hidden" name="state" id="state" value="{{ isset($edit) && $edit?->id ? $edit?->state : '' }}" />

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

                        <div class="col-md-2 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Product Category <span class="text-danger">*</span></label>
                                <select name="product_category_id" id="product_category_id"
                                    class="form-control @error('product_category_id') is-invalid @enderror search_by_product_category select2"
                                    data-append="search_by_product_category"  data-defaultFirst="rod and bar"
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
                            <label class="form-label">Mobile No. </label>
                            <input type="text" class="form-control" name="mobile_no" id="mobile_no" onkeypress="return isNumber(event)"
                                value="{{ isset($edit) && $edit?->mobile_no ? $edit?->mobile_no : old('mobile_no') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="work_type" id="settle_po" value="settle_po"
                                        {{ old('work_type', $edit->work_type ?? '') === 'settle_po' ? 'checked' : '' }} >
                                    <label class="form-check-label" for="settle_po">Settle PO</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="work_type" id="job_work" value="job_work"
                                        {{ old('work_type', $edit->work_type ?? 'job_work') === 'job_work' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="job_work">Job Work</label>
                                </div>
                            </div>
                        </div>


                    </div>

                    <div class="row">
                        <!-- Left Side: Vendor Details -->
                        @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <div class="row align-items-center">
                                        <label class="col-4"><strong>Vendor:</strong></label>
                                        <div class="col-8">
                                            <p class="mb-0"><span
                                                    id="vendor_name_display"> {{ old('vendor_name', isset($edit) && $edit?->vendor ? $edit?->vendor->vendor_name : 'N/A') }}</span>
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
                        @endif
                        <!-- Right Side: 4 Input Fields -->
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Job Work Order No <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control readonly-look" name="job_work_order_no"
                                        id="job_work_order_no" placeholder="Enter Job Work Order No" readonly
                                        value="{{ old('job_work_order_no', isset($edit) && $edit?->job_work_order_no ? $edit?->job_work_order_no : '') }} ">
                                </div>

                                <div class="col-md-6 mt-3">
                                    <label class="form-label">Job Work Order Date <span
                                            class="text-danger">*</span></label>
                                    <div style="position: relative;">
                                            <input type="text"
                                                id="job_work_order_date_display"
                                                class="form-control @error('job_work_order_date') is-invalid @enderror"
                                                value="{{ old('job_work_order_date')
                                                ? date('d-m-Y', strtotime(old('job_work_order_date')))
                                                : (isset($edit->job_work_order_date)
                                                        ? date('d-m-Y', strtotime($edit->job_work_order_date))
                                                        : date('d-m-Y')) }}"
                                                placeholder="DD-MM-YYYY"
                                                style="padding-right: 30px;"
                                                />

                                            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                                <i class="fa fa-calendar" aria-hidden="true"></i>
                                            </span>
                                    </div>

                                    <input type="hidden" name="job_work_order_date" id="job_work_order_date"
                                        value="{{ old('job_work_order_date', isset($edit->job_work_order_date) ? date('Y-m-d', strtotime($edit->job_work_order_date)) : date('Y-m-d')) }}" />
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
                        <div class="col-md-4 row" style="margin-top: -22px;">
                            <div class="col-md-6">
                                <label for="order_type">Order Type</label>
                                <select name="order_type" id="order_type" class="form-control form-control select2">
                                    @foreach($modules['order_type'] as $key => $label)
                                        <option value="{{ $key }}" {{ old('order_type', $edit->order_type ?? '') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="order_price">
                                    Add Order
                                </label>
                                <input type="text" name="order_price" id="order_price"
                                    class="form-control"
                                    value="{{ old('order_price', $edit->order_price ?? '') }}"
                                    placeholder="Enter">
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
                                    <th style="min-width: 200px;">Code</th>
                                    <th style="min-width: 200px;">Item Name</th>
                                    <th style="min-width: 200px;">Stock (Kgs)</th>
                                    <th style="min-width: 200px;">Kg / Pcs / Gross</th>
                                    <th style="min-width: 200px;">Unit</th>

                                    <th style="min-width: 200px;">Rate</th>
                                    <th style="min-width: 100px;" class="d-none">Rate</th>
                                    <th style="min-width: 200px;">Taxable Amount</th>
                                    <th style="min-width: 200px;" class="tax_type_dv d-none">Taxable</th>

                                    <th style="min-width: 200px;" class="cgst_div tax_type_dv">CGST</th>
                                    <th style="min-width: 200px;" class="cgst_div tax_type_dv">CGST Amount</th>
                                    <th style="min-width: 200px;" class="sgst_div tax_type_dv">SGST</th>
                                    <th style="min-width: 200px;" class="sgst_div tax_type_dv">SGST Amount</th>

                                    <th style="min-width: 200px;" class="tax_type_dv">Subtotal</th>
                                    <th style="min-width: 50px;">Action</th>
                                </tr>
                            </thead>

                            <tbody id="productBody">
                                @if (isset($edit) && $edit?->job_work_order_items)
                                    @foreach ($edit?->job_work_order_items as $item)
                                        <tr>
                                            <input type="hidden" class="form-control" name="item_id[]"
                                                value="{{ $item->job_work_order_item_id }}">
                                            <input type="hidden" class="form-control" name="product_id[]"
                                                value="{{ $item->product_id }}">
                                            <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]"  value="{{ $item?->product?->grosse_per_qty }}">
                                            <input type="hidden" class="form-control" name="total_stock_quantity[]" value="{{ $item->total_stock_quantity }}">
                                            <input type="hidden" class="form-control" name="product_weight[]" value="{{ $item->net_wt }}">

                                            <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_code[]"
                                                    value="{{ $item->product_code ?? '' }}" readonly><label class="product_code_txt">{{ $item->product_code ?? '' }}</label></td>
                                            <td><input type="hidden" class="form-control bg-light" name="product_name[]"
                                                    value="{{ $item->product_name ?? '' }}" readonly><label class="product_name_txt">{{ $item->product_name ?? '' }}</label></td>
                                            <td><input type="text" class="form-control bg-light" name="product_stock[]"
                                                    value="{{ $item->product_stock ?? '' }}"></td>
                                            <td><input type="text" class="form-control quantity"
                                                onchange="updateInnerAndOuterFromQty(this)" name="quantity[]"
                                                value="{{ strtolower($item->unit) === 'kg'
                                                    ? number_format($item->quantity ?? 0, 3, '.', '')
                                                    : (int) ($item->quantity ?? 0)
                                                }}" onkeypress="{{ strtolower($item->unit) === 'kg' ? 'return isDecimal(event)' : 'return isNumber(event)' }}">
                                            </td>
                                            <td style="display: flex; align-items: center; gap: 6px;">
                                                <input type="text"
                                                    class="form-control unit bg-light"
                                                    name="unit[]"
                                                    readonly
                                                    value="{{ $item->unit ?? '' }}">

                                                @if(!empty($item->unit) && strtolower($item->unit) === 'gross')
                                                    <span class="badge bg-secondary" style="font-size:12px;">
                                                        qty : {{ $item->product->grosse_per_qty ?? 0 }}
                                                    </span>
                                                @endif
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
                                            <td><input type="text" class="form-control amount bg-light" name="amount[]"
                                                    onkeypress="return isDecimal(event)" value="{{ $item->amount ?? '' }}"
                                                    readonly><input type="hidden" class="form-control taxable_amount bg-light" name="taxable_amount[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->taxable_amount ?? '' }}" readonly></td>
                                            <td class="tax_type_dv d-none"><input type="text"
                                                    class="form-control taxable_amount bg-light" name="taxable_amount[]"
                                                    onkeypress="return isDecimal(event)"
                                                    value="{{ $item->taxable_amount ?? '' }}" readonly></td>

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
                                            <td style="text-align:center; vertical-align:middle;"><button type="button" class="btn btn-danger deleteRow"
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
                                    <td>
                                        <input type="number" step="0.01"
                                            value="{{ isset($edit) && $edit?->master_quantity ? number_format($edit?->master_quantity, 2, '.', '') : '' }}"
                                            class="form-control bg-light" readonly id="master_quantity"
                                            name="master_quantity">
                                    </td>
                                    <td></td>
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

                                <div class="mb-3 row align-items-center d-none">
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
        flatpickr("#job_work_order_date_display", {
            dateFormat: "d-m-Y",
            allowInput: true,
            defaultDate: "{{ old('job_work_order_date', isset($edit->job_work_order_date) ? date('d-m-Y', strtotime($edit->job_work_order_date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('job_work_order_date').value = ymd; // hidden input for submission
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

    @if (!isset($edit))
        @include('utils.generateJobWorkOrderNo')
    @endif
    @include('utils.getVendorSales')
    @include('utils.getVendorInfo')
    @include('utils.getProductCategory')
    @include('utils.getProductAndVariant')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    <script>
        fetch_vendorInfo();
        $(document).ready(function() {
            var $orderTypeSelect = $("#order_type");
            $orderTypeSelect.select2({
                placeholder: "Select an order type",
                allowClear: true
            });

            // Initialize flatpickr for Job Work Order Date (editable)
            flatpickr("#job_work_order_date_display", {
                dateFormat: "d-m-Y",
                defaultDate: "{{ old('job_work_order_date', isset($edit->job_work_order_date) ? date('d-m-Y', strtotime($edit->job_work_order_date)) : date('d-m-Y')) }}",
                onChange: function(selectedDates, dateStr, instance) {
                    const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                    document.getElementById('job_work_order_date').value = ymd;
                }
            });
        });

    </script>

    <script>
        initializeCKEditor(".ckeditor_common_cls");


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
            let vendor_id = $('#vendor_id').val();
            let company_id = $('#company_id').val();

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

            if (company_id == undefined || company_id == null) {
                company_id = $('input[name="company_id"]').val();
            }

            // let exists = false;
            // $("#productBody tr").each(function() {
            //     let existing_product_id = $(this).children("input[name='product_id[]']").val();
            //     console.log("existing_product_id:", existing_product_id, "new product_id:", product_id);
            //     if (existing_product_id == product_id) {
            //         exists = true;
            //         return false;
            //     }
            // });

            // if (exists) {
            //     toastr.error("This product already exists!");
            //     return;
            // }

            let orderTypeValue = $('#order_type').val();
            let srNo = $('#productBody tr').length + 1;

            newRow.innerHTML = `
                <input type="hidden" class="form-control" name="item_id[]" value="0">
                <input type="hidden" class="form-control" name="product_id[]">
                <input type="hidden" class="form-control" name="total_stock_quantity[]">
                <input type="hidden" class="form-control" name="product_weight[]">
                <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]">

                <td class="sr_no_txt"></td>
                <td><input type="hidden" name="product_code[]" readonly><label class="product_code_txt"></label></td>
                <td><input type="hidden" name="product_name[]" readonly><label class="product_name_txt"></label></td>
                <td><input type="text" class="form-control bg-light" name="product_stock[]" readonly></td>
                <td><input type="text" class="form-control quantity" name="quantity[]" value="1"></td>
                <td><input type="text" class="form-control unit bg-light" name="unit[]" readonly></td>

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


                <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="cgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="cgst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_in_percentage[]" readonly onkeypress="return isDecimal(event)"></td>
                <td class="sgst_div tax_type_dv"><input type="text" class="form-control bg-light" name="sgst_amount_array[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td class="tax_type_dv"><input type="text" class="form-control subtotal bg-light" name="subtotal[]" value="0" readonly onkeypress="return isDecimal(event)"></td>

                <td style="text-align:center; vertical-align:middle;"><button type="button" class="btn btn-danger deleteRow remove-row">X</button></td>
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
                    $("#productBody tr:last").find('input[name="product_name[]"]').val($(
                        '#product_variant_price_id option:selected').text());
                    $("#productBody tr:last").find('.product_name_txt').text($(
                        '#product_variant_price_id option:selected').text());
                    $('#product_variant_price_id').val('').trigger('change');
                    $("#productBody tr:last").find('.sr_no_txt').text(srNo);

                    $("#productBody tr:last").find('input[name="product_code[]"]').val(data.part_no);
                    $("#productBody tr:last").find('.product_code_txt').text(data.part_no);
                    $("#productBody tr:last").find('input[name="product_id[]"]').val(data.product_id);

                    $("#productBody tr:last").find('input[name="total_stock_quantity[]"]').val(data
                        .total_stock_quantity);
                    $("#productBody tr:last").find('input[name="product_weight[]"]').val(data
                        .net_wt);
                    $("#productBody tr:last").find('input[name="product_stock[]"]').val(data.product_stock);
                    $("#productBody tr:last").find('input[name="unit[]"]').val(orderTypeValue);
                    $("#productBody tr:last").find('input[name="quantity[]"]').val(orderPrice);
                    $("#productBody tr:last").find('input[name="grosse_per_qty[]"]').val(data.grosse_per_qty);

                    $('#product_id').val('').trigger('change');


                    let unitVal = orderTypeValue.toLowerCase();
                    let qtyInput = $("#productBody tr:last").find('input[name="quantity[]"]');

                    if (unitVal === "kg") {
                        qtyInput.attr("onkeypress", "return isDecimal(event)");
                    } else {
                        qtyInput.attr("onkeypress", "return isNumber(event)");
                    }

                    let unitLower = (orderTypeValue || '').toLowerCase();
                    let finalPrice = parseFloat(data.price || 0);
                    if (unitLower === 'kg' && data.gross_labour_charges > 0) {
                        finalPrice = parseFloat(data.gross_labour_charges);
                    } else if ((unitLower === 'gross' || unitLower === 'grosse') && data.job_work_gross_rate > 0) {
                        finalPrice = parseFloat(data.job_work_gross_rate);
                    } else if ((unitLower === 'pcs' || unitLower === 'pc')) {
                        if (data.job_work_pcs_rate > 0) {
                            finalPrice = parseFloat(data.job_work_pcs_rate);
                        } else if (data.job_work_gross_rate > 0) {
                            finalPrice = parseFloat(data.job_work_gross_rate) / 144;
                        }
                    }

                    if (unitLower === "gross" || unitLower === "grosse") {
                        let $unitCell = $("#productBody tr:last").find('input[name="unit[]"]').closest('td');
                        let $unitInput = $unitCell.find('input[name="unit[]"]');
                        $unitCell.css({
                            display: "flex",
                            "align-items": "center",
                            gap: "6px"
                        });

                        $unitCell.append(
                            `<span class="badge bg-secondary" style="font-size:12px;">qty : ${data.grosse_per_qty || 144}</span>`
                        );
                    }

                    $("#productBody tr:last").find('input[name="price[]"]').val(finalPrice.toFixed(2));
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

                    $('#order_type').val('').trigger('change');
                    $('#order_price').val('');

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
                $(this).find("input, select, textarea").each(function() {
                    let name = $(this).attr("name");
                    if (name) {
                        if ($(this).hasClass("select2")) {
                            row[name] = $(this).val();
                        } else if ($(this).attr("type") === "checkbox") {
                            row[name] = $(this).prev("input[type='hidden']").val(); // store 0/1 from hidden
                        } else {
                            row[name] = $(this).val();
                        }
                    }
                });
                rows.push(row);
            });

            localStorage.setItem("productTable", JSON.stringify(rows));
        }

        // Restore table from localStorage on page load
        $(document).ready(function() {
            let savedRows = localStorage.getItem("productTable");
            if (savedRows) {
                savedRows = JSON.parse(savedRows);
                savedRows.forEach(function(row) {
                    let newRow = document.createElement("tr");

                    newRow.innerHTML = `
                        <input type="hidden" name="item_id[]" value="${row['item_id[]'] || 0}">
                        <input type="hidden" name="product_id[]" value="${row['product_id[]'] || ''}">
                        <input type="hidden" name="total_stock_quantity[]" value="${row['total_stock_quantity[]'] || ''}">
                        <input type="hidden" name="product_weight[]" value="${row['product_weight[]'] || ''}">
                        <input type="hidden" name="grosse_per_qty[]" value="${row['grosse_per_qty[]'] || ''}">

                        <td><input type="hidden" name="product_code[]" value="${row['product_code[]'] || ''}"><label class="product_code_txt">${row['product_code[]'] || ''}</label></td>
                        <td><input type="hidden" name="product_name[]" value="${row['product_name[]'] || ''}"><label class="product_name_txt">${row['product_name[]'] || ''}</label></td>
                        <td><input type="text" class="form-control bg-light" name="product_stock[]" readonly value="${row['product_stock[]'] || ''}"></td>
                        <td><input type="text" class="form-control quantity" name="quantity[]" value="${row['quantity[]'] || 1}" onkeypress="return isNumber(event)"></td>
                        <td style="display: flex; align-items: center; gap: 6px;">
                            <input type="text" class="form-control unit bg-light" name="unit[]" readonly value="${row['unit[]'] || ''}">
                            ${row['unit[]'] && row['unit[]'].toLowerCase() === 'gross'
                                ? `<span class="badge bg-secondary" style="font-size:12px;">qty : ${row['grosse_per_qty[]'] || ''}</span>`
                                : ''}
                        </td>
                        <td style="text-align:center; vertical-align:middle;"><button type="button" class="btn btn-danger deleteRow remove-row">X</button></td>
                    `;

                    $("#productBody").append(newRow);
                });
            }

            // Save changes on input/select/textarea change
            $(document).on("change keyup", "#productBody input, #productBody select, #productBody textarea", function() {
                saveTableToLocalStorage();
            });

            // Delete row handler
            $(document).on("click", ".deleteRow", function() {
                $(this).closest("tr").remove();
                saveTableToLocalStorage();
            });
        });

        @if ($errors->any())

        @else
            localStorage.removeItem("productTable");
        @endif

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("deleteRow")) {
                e.target.closest("tr").remove();
            }
        });
    </script>
@endpush
