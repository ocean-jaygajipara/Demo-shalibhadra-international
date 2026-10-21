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

        /* #qc_inspection_pending_vw tbody tr:hover {
                background-color: #f5f5f5;
                cursor: pointer;
            } */

        #qc_inspection_pending_vw tbody tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }

        #qc_inspection_pending_vw tbody tr.table-active {
            background-color: #d1ecf1;
            /* light blue for selected row */
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
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <input type="hidden" name="product_id" id="product_id"
                    value="{{ old('product_id', isset($edit) && $edit?->product_id ? $edit?->product_id : '') }}" />
                <input type="hidden" name="job_work_receive_id" id="job_work_receive_id"
                    value="{{ old('job_work_receive_id', isset($edit) && $edit?->job_work_receive_id ? $edit?->job_work_receive_id : '') }}" />
                <input type="hidden" name="job_work_receive_item_id" id="job_work_receive_item_id"
                    value="{{ old('job_work_receive_item_id', isset($edit) && $edit?->job_work_receive_item_id ? $edit?->job_work_receive_item_id : '') }}" />
                <input type="hidden" name="inward_id" id="inward_id"
                    value="{{ old('inward_id', isset($edit) && $edit?->inward_id ? $edit?->inward_id : '') }}" />
                <input type="hidden" name="inward_item_id" id="inward_item_id"
                    value="{{ old('inward_item_id', isset($edit) && $edit?->inward_item_id ? $edit?->inward_item_id : '') }}" />
                <input type="hidden" name="state" id="state"
                    value="{{ old('state', isset($edit) && $edit?->state ? $edit?->state : '') }}" />
                <input type="hidden" name="is_size_required" id="is_size_required"/>

                <div class="">
                    <!-- Section 1: Vendor Details -->

                    <div class="row g-3">
                        @if (!$company_id)
                            <div class="col-md-3">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id"
                                    class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                    data-append="search_by_company"
                                    data-selectedCompanyId="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : (isset($pre_fill_inward) ? $pre_fill_inward->company_id : old('company_id')) }}"
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
                            <label class="form-label">QC Inspection No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control readonly-look" name="qc_inspection_no"
                                id="qc_inspection_no" placeholder="Enter QC Inspection No" readonly
                                value="{{ old('qc_inspection_no', isset($edit) && $edit?->qc_inspection_no ? $edit?->qc_inspection_no : '') }} ">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">QC Inspection Date <span class="text-danger">*</span></label>
                            <div style="position: relative;">
                                <input type="text" id="qc_inspection_date_display"
                                    class="form-control @error('qc_inspection_date') is-invalid @enderror"
                                    value="{{ old('qc_inspection_date')
                                        ? date('d-m-Y', strtotime(old('qc_inspection_date')))
                                        : (isset($edit->qc_inspection_date)
                                            ? date('d-m-Y', strtotime($edit->qc_inspection_date))
                                            : date('d-m-Y')) }}"
                                    placeholder="DD-MM-YYYY" style="padding-right: 30px;" />

                                <span
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                    <i class="fa fa-calendar" aria-hidden="true"></i>
                                </span>
                            </div>

                            <input type="hidden" name="qc_inspection_date" id="qc_inspection_date"
                                value="{{ old('qc_inspection_date', isset($edit->qc_inspection_date) ? date('Y-m-d', strtotime($edit->qc_inspection_date)) : date('Y-m-d')) }}" />
                        </div>
                    </div>


                    <div class="row mt-3">
                        <div class="col-md-3 mt-4">
                            <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            @if(isset($edit))
                                <input type="text" class="form-control"
                                    value="{{ $edit->vendor->vendor_name ?? '' }}" readonly>
                                <input type="hidden" name="vendor_id" value="{{ $edit->vendor_id }}">
                            @else
                                <select name="vendor_id" id="vendor_id"
                                    class="form-control @error('vendor_id') is-invalid @enderror search_by_vendor select2"
                                    data-append="search_by_vendor"
                                    data-selectedVendorId="{{ isset($edit) && $edit?->vendor_id ? $edit?->vendor_id : (isset($pre_fill_inward) ? $pre_fill_inward->vendor_id : old('vendor_id')) }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('vendor_id', $edit->vendor_id ?? '') ? '' : 'selected' }}>
                                        Select Vendor
                                    </option>
                                </select>
                                @error('vendor_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            @endif
                        </div>

                        @if (!isset($edit))
                            <div class="col-md-3 mt-4">
                                <button type="button" class="btn btn-primary mt-4" id="pendingListRowBtn"> Pending List
                                </button>
                            </div>
                        @endif

                        <div class="col-md-3 mt-4">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control readonly-look @error('product_code') is-invalid @enderror" name="product_code"
                                id="product_code" placeholder="Enter Item Code" readonly
                                value="{{ old('product_code', isset($edit) && $edit?->product_code ? $edit?->product_code : '') }} ">
                            @error('product_code')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-3 mt-4">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control readonly-look @error('product_name') is-invalid @enderror" name="product_name"
                                id="product_name" placeholder="Enter Product Name" readonly
                                value="{{ old('product_name', isset($edit) && $edit?->product_name ? $edit?->product_name : '') }} ">
                            @error('product_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                    </div>

                    <!-- Product Attachments Display Area -->
                    @php
                        $hasAttachments = isset($edit) && $edit->product && !empty($edit->product->attachments_list);
                    @endphp
                    <div class="row mt-3 {{ $hasAttachments ? '' : 'd-none' }}" id="product_attachments_row">
                        <div class="col-md-12">
                            <div class="form-group border rounded p-3 bg-light">
                                <label class="form-label fw-bold"><i class="ti ti-paperclip"></i> Product Attachments / Drawings:</label>
                                <div id="product_attachments_list" class="d-flex flex-wrap gap-2 mt-2">
                                    @if ($hasAttachments)
                                        @foreach ($edit->product->attachments_list as $attach)
                                            @php
                                                $company = $edit->product->company ?? null;
                                                $company_slug = \Illuminate\Support\Str::slug($company->company_name ?? 'default-company');
                                                $relativePath = "uploads/{$edit->product->company_id}-{$company_slug}/product-attachments/{$attach['file_path']}";
                                                $fileUrl = asset($relativePath);
                                            @endphp
                                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="ti ti-file-text"></i> {{ $attach['original_name'] ?? basename($attach['file_path']) }}
                                            </a>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="row g-3">
                        @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                        <div class="col-md-6 mt-3">
                            <div class="row">
                                <div class="col-md-6 mt-3">
                                    <div class="row align-items-center">
                                        <label class="col-4"><strong>Vendor:</strong></label>
                                        <div class="col-8">
                                            <p class="mb-0"><span id="vendor_name_display">
                                                    {{ old('vendor_name_display', isset($edit) && $edit?->vendor ? $edit?->vendor->vendor_name : 'N/A') }}</span>
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
                        <div class="col-md-6 mt-3">
                            <div class="row">
                                <div class="col-md-6 mt-4">
                                    <label class="form-label"> Issue For Process <span class="text-danger">*</span></label>
                                    <input type="hidden" name="issue_for_process_id" id="issue_for_process_id"
                                        value="{{ old('issue_for_process_id', isset($edit) && $edit?->issue_for_process_id ? $edit?->issue_for_process_id : '') }}" />
                                    <input type="text" class="form-control readonly-look @error('issue_for_process_name') is-invalid @enderror" name="issue_for_process_name"
                                        id="issue_for_process_name" placeholder="Enter Issue For Process Name" readonly
                                        value="{{ old('issue_for_process_name', isset($edit) && $edit?->issue_for_process?->name ? $edit?->issue_for_process?->name : '') }} ">
                                    @error('issue_for_process_name')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="form-label"> Warehouse <span class="text-danger">*</span></label>
                                    <input type="hidden" name="warehouse_id" id="warehouse_id"
                                        value="{{ old('warehouse_id', isset($edit) && $edit?->warehouse_id ? $edit?->warehouse_id : '') }}" />
                                    <input type="text" class="form-control readonly-look @error('warehouse_name') is-invalid @enderror" name="warehouse_name"
                                        id="warehouse_name" placeholder="Enter Warehouse Name" readonly
                                        value="{{ old('warehouse_name', isset($edit) && $edit?->warehouse?->name ? $edit?->warehouse?->name : '') }} ">
                                    @error('warehouse_name')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>



                    <div class="row mt-3">


                        <div class="col-md-2">
                            <label class="form-label">Weight<span class="text-danger">*</span></label>
                            <input type="text" class="form-control readonly-look @error('received_weight') is-invalid @enderror" name="received_weight"
                                id="received_weight" placeholder="Enter Weight" readonly
                                value="{{ old('received_weight', isset($edit) && $edit?->received_weight ? $edit?->received_weight : '') }} ">
                            @error('received_weight')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Ok Weight<span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ok_weight') is-invalid @enderror" name="ok_weight" id="ok_weight"
                                placeholder="Enter Ok Weight"
                                value="{{ old('ok_weight', isset($edit) && $edit?->ok_weight ? $edit?->ok_weight : '') }} ">
                            @error('ok_weight')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">WT/PC<span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('wt_pc') is-invalid @enderror" name="wt_pc" id="wt_pc"
                                placeholder="Enter WT/PC"
                                value="{{ old('wt_pc', isset($edit->job_work_receive_items->wt_pc) ? $edit->job_work_receive_items->wt_pc : ($edit->wt_pc ?? '')) }}" onkeypress="return isDecimal(event)">
                            @error('wt_pc')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Qty</label>
                            <input type="text" class="form-control" name="ok_qty" id="ok_qty"
                                placeholder="Enter Qty"
                                value="{{ old('ok_qty', $edit->ok_qty ?? '') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Rejection KG</label>
                            <input type="text" class="form-control @error('rejection_qty') is-invalid @enderror" name="rejection_qty" id="rejection_qty"
                                placeholder="Enter Rejection Qty"
                                value="{{ old('rejection_qty', isset($edit) && $edit?->rejection_qty ? $edit?->rejection_qty : '') }} ">
                            @error('rejection_qty')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Available Qty</label>
                            <input type="text" class="form-control @error('available_qty') is-invalid @enderror" name="available_qty" id="available_qty"
                                placeholder="Enter Available Qty"
                                value="{{ old('available_qty', isset($edit) && $edit?->available_qty ? $edit?->available_qty : '') }} " readonly>
                            @error('available_qty')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                    </div>


                    <hr>
                    <h5 class="mb-3 border-bottom pb-2">
                        Other Details
                    </h5>
                    <!-- Other Details Tabs -->
                    <div class="card my-3">
                        <div>
                            <ul class="nav nav-tabs" id="otherDetailsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="material-detail-tab" data-bs-toggle="tab"
                                        href="#material_detail" role="tab" aria-controls="material_detail"
                                        aria-selected="true">Material Details</a>
                                </li>
                            </ul>
                            <div class="tab-content p-3" id="otherDetailsTabsContent">

                                <!-- Material Details -->
                                <div class="tab-pane fade show active" id="material_detail" role="tabpanel"
                                    aria-labelledby="material-detail-tab">



                                    <div style="overflow-x: auto; width: 100%;">
                                    <table class="table table-bordered" style="min-width: 1200px;">
                                        <thead>
                                            <tr>
                                                <th style="min-width: 300px;">Dimension</th>
                                                <th style="min-width: 190px;">Size</th>
                                                <th style="min-width: 150px;">LTL</th>
                                                <th style="min-width: 150px;">UTL</th>
                                                <th style="min-width: 250px;">Instrument</th>
                                                @for ($i = 1; $i <= 10; $i++)
                                                    <th style="min-width: 150px;">Size {{ $i }}</th>
                                                @endfor
                                                <th style="min-width: 200px;">Remarks</th>
                                                <th style="min-width: 80px;">
                                                    <button type="button" class="btn btn-sm btn-success" id="addMaterialDetailRow">+ Add</button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="materialDetailTableBody">
                                            @php
                                                $oldMaterialDetail = old('material', $material_detail ?? []);

                                                $materialIndex = count($oldMaterialDetail);
                                                if ($materialIndex == 0) {
                                                    $oldMaterialDetail[] = [];
                                                }
                                            @endphp

                                            @foreach ($oldMaterialDetail as $i => $material)
                                                <tr>

                                                    <td style="min-width: 300px;">
                                                        <input type="hidden" name="material[{{ $i }}][id]"
                                                            value="{{ old("material.$i.id", $material['id'] ?? ($material->id ?? '')) }}">
                                                        <input type="text" name="material[{{ $i }}][dimension]"
                                                            class="form-control"
                                                            value="{{ old("material.$i.dimension", $material['dimension'] ?? ($material->dimension ?? '')) }}"
                                                            placeholder="Enter Dimension">
                                                    </td>

                                                    <td style="min-width: 190px;">
                                                        <input type="text" name="material[{{ $i }}][size]"
                                                            class="form-control"
                                                            value="{{ old("material.$i.size", $material['size'] ?? ($material->size ?? '')) }}"
                                                            placeholder="Enter Size">
                                                    </td>

                                                    <td style="min-width: 150px;">
                                                        <input type="text" name="material[{{ $i }}][ltl]"
                                                            class="form-control"
                                                            value="{{ old("material.$i.ltl", $material['ltl'] ?? ($material->ltl ?? '')) }}"
                                                            placeholder="Enter LTL">
                                                    </td>

                                                    <td style="min-width: 150px;">
                                                        <input type="text" name="material[{{ $i }}][utl]"
                                                            class="form-control"
                                                            value="{{ old("material.$i.utl", $material['utl'] ?? ($material->utl ?? '')) }}"
                                                            placeholder="Enter UTL">
                                                    </td>

                                                    <td style="min-width: 250px;">
                                                        <input type="text" name="material[{{ $i }}][instrument]"
                                                            class="form-control"
                                                            value="{{ old("material.$i.instrument", $material['instrument'] ?? ($material->instrument ?? '')) }}"
                                                            placeholder="Enter Instrument">
                                                    </td>

                                                    {{-- Fixed Size1 to Size10 using loop --}}
                                                    @for ($s = 1; $s <= 10; $s++)
                                                        <td style="min-width: 150px;">
                                                            <input type="text"
                                                                name="material[{{ $i }}][size{{ $s }}]"
                                                                class="form-control"
                                                                value="{{ old("material.$i.size$s", $material["size$s"] ?? ($material->{"size$s"} ?? '')) }}"
                                                                placeholder="Size {{ $s }}">
                                                        </td>
                                                    @endfor

                                                    <td style="min-width: 200px;">
                                                        <input type="text" name="material[{{ $i }}][remarks]"
                                                            class="form-control"
                                                            value="{{ old("material.$i.remarks", $material['remarks'] ?? ($material->remarks ?? '')) }}"
                                                            placeholder="Enter Remarks">
                                                    </td>

                                                    <td style="min-width: 80px;">
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger removeRow">X</button>
                                                    </td>
                                                </tr>
                                                @php $materialIndex = $i + 1; @endphp
                                            @endforeach
                                        </tbody>
                                     </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>



                    @php
                        $showButtons = !isset($edit) || !empty($edit->created_by);
                    @endphp

                    @if ($showButtons)
                    <div class="col-md-12 text-center mt-3">
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

    <div class="modal fade" id="qc_inspection_pending_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <form name="pendingQCInspectionForm" id="pendingQCInspectionForm" method="POST">
                    @csrf
                    <div class="modal-header w-100 d-block text-center">
                        <h5 class="modal-title" id="modalCenterTitle">Pending QC Inspection</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="{{ asset('software/custom/price_calculation.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>
    <script>
        let termsInstance;
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    @if (!isset($edit))
        @include('utils.generateQCInspectionNo')
    @endif
    @include('utils.getVendorSales')
    @include('utils.getIssueForProcess')
    @include('utils.getProductMaterialMap')
    @include('utils.getVendorInfo')
    <script>
        initializeCKEditor(".ckeditor_common_cls");

        flatpickr("#qc_inspection_date_display", {
            dateFormat: "d-m-Y",
            allowInput: true,
            defaultDate: "{{ old('qc_inspection_date')
                ? date('d-m-Y', strtotime(old('qc_inspection_date')))
                : (isset($edit->qc_inspection_date)
                    ? date('d-m-Y', strtotime($edit->qc_inspection_date))
                    : date('d-m-Y')) }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('qc_inspection_date').value = ymd;
            }
        });

        $(document).ready(function() {

            // Add new contact row
            $('#addMaterialDetailRow').click(function() {
                let materialIndex = $("#materialDetailTableBody tr").length + 1;

                let sizeInputs = '';
                for (let s = 1; s <= 10; s++) {
                    sizeInputs += `<td style="min-width: 150px;">
                        <input type="text" name="material[${materialIndex}][size${s}]"
                            class="form-control"
                            placeholder="Size ${s}">
                    </td>`;
                }



                    let row = `
                    <tr>
                        <td style="min-width: 300px;"><input type="text" name="material[${materialIndex}][dimension]" class="form-control" placeholder="Enter Dimension"></td>
                        <td style="min-width: 190px;"><input type="text" name="material[${materialIndex}][size]" class="form-control" placeholder="Enter Size"></td>
                        <td style="min-width: 150px;"><input type="text" name="material[${materialIndex}][ltl]" class="form-control" placeholder="Enter LTL"></td>
                        <td style="min-width: 150px;"><input type="text" name="material[${materialIndex}][utl]" class="form-control" maxlength="10" placeholder="Enter UTL"></td>
                        <td style="min-width: 250px;">
                            <input type="text"
                                name="material[${materialIndex}][instrument]"
                                class="form-control"
                                placeholder="Enter Instrument">
                        </td>
                        ${sizeInputs}
                        <td style="min-width: 200px;">
                            <input type="text" name="material[${materialIndex}][remarks]"
                                class="form-control"
                                placeholder="Enter Remarks">
                        </td>
                        <td><button type="button" class="btn btn-sm btn-danger removeRow">X</button></td>
                    </tr>
                `;
                $('#materialDetailTableBody').append(row);
                materialIndex++;
            });

            // Remove contact row
            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
            });
        });

        $(document).ready(function() {
            const submitBtn = $('button[type="submit"]');

            function checkOkWeight() {
                let okWeight = parseFloat($('#ok_weight').val()) || 0;
                let receivedWeight = parseFloat($('#received_weight').val()) || 0;

                if (okWeight > receivedWeight) {
                    toastr.error("Ok Weight cannot be greater than Received Weight!");
                    submitBtn.prop('disabled', true);
                } else {
                    submitBtn.prop('disabled', false);
                }
                let wt_pc = parseFloat($('#wt_pc').val()) || 0;
                if(okWeight > 0 && wt_pc > 0){
                    let qty = ((okWeight / wt_pc)  * 1000);
                    let finalQty = (receivedWeight / wt_pc) * 1000;
                    let rejection = (finalQty - qty);

                    //$('#rejection_qty').val(rejection.toFixed(2));
                    $('#ok_qty').val(qty.toFixed(2));
                }
                //checkRejectQty();

                // Check Ok Weight + Rejection Qty <= Weight
                checkOkWeightAndRejectionQty();
            }

            // Function to validate Ok Weight + Rejection Qty <= Weight
            function checkOkWeightAndRejectionQty() {
                let okWeight = parseFloat($('#ok_weight').val()) || 0;
                let rejectionQty = parseFloat($('#rejection_qty').val()) || 0;
                let receivedWeight = parseFloat($('#received_weight').val()) || 0;

                if (receivedWeight > 0 && (okWeight + rejectionQty) > receivedWeight) {
                    toastr.error("Ok Weight + Rejection Qty cannot be greater than Weight!");
                    $('#rejection_qty').val('');
                    submitBtn.prop('disabled', true);
                } else {
                    submitBtn.prop('disabled', false);
                }
            }

            $(document).on('keyup', '#ok_weight, #wt_pc', function() {
                checkOkWeight();
                checkOkWeightAndRejectionQty();
            });
            $(document).on('keyup', '#rejection_qty', checkOkWeightAndRejectionQty);
            checkOkWeight();
            checkOkWeightAndRejectionQty();
        });


        function checkOkQty() {
            let qty = parseFloat($('#ok_qty').val()) || 0;
            let receivedWeight = parseFloat($('#received_weight').val()) || 0;
            let wt_pc = parseFloat($('#wt_pc').val()) || 0;

            if (qty > 0 && wt_pc > 0) {
                let newOkWeight = (qty * wt_pc) / 1000;
                let totalQty = (receivedWeight / wt_pc) * 1000;

                $('#ok_weight').val(newOkWeight.toFixed(2));
                let rejectionQty = totalQty - qty;
                //$('#rejection_qty').val(rejectionQty.toFixed(2));
            }
        }
        $(document).on('keyup', '#ok_qty', checkOkQty);

        // $(document).ready(function() {
        //     const submitBtn = $('button[type="submit"]');
        //     function checkRejectQty() {
        //         let okWeight = parseFloat($('#ok_weight').val()) || 0;
        //         let receivedWeight = parseFloat($('#received_weight').val()) || 0;
        //         let wt_pc = parseFloat($('#wt_pc').val()) || 0;
        //         let rejection = parseFloat($('#rejection_qty').val()) || 0;

        //         if (okWeight > receivedWeight) {
        //             toastr.error("Ok Weight cannot be greater than Received Weight!");
        //             submitBtn.prop('disabled', true);
        //             return;
        //         }else {
        //             submitBtn.prop('disabled', false);
        //         }

        //         if (okWeight > 0 && wt_pc > 0) {
        //             let finalQty = (receivedWeight / wt_pc) * 1000;
        //             if (finalQty < 0) finalQty = 0;
        //             let qty = (finalQty - rejection);

        //             $('#ok_qty').val(qty.toFixed(2));

        //             let finalWeight = (qty * wt_pc) / 1000;
        //             $('#ok_weight').val(finalWeight.toFixed(2));
        //         }
        //     }
        //     $(document).on('keyup', '#rejection_qty', checkRejectQty);
        //     checkRejectQty();
        // });

        $(document).on('click', '#pendingListRowBtn', function() {
            let company_id = $(".search_by_company option:selected").val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }

            if (company_id == '') {
                toastr.error("Please select Company!");
                return;
            }

            let vendor_id = $(".search_by_vendor option:selected").val();
            let inward_id_param = '{{ $inward_id ?? '' }}';

            if (vendor_id == '' && !inward_id_param) {
                toastr.error("Please select Vendor!");
                return;
            }

            if(company_id && (vendor_id || inward_id_param)){
                $.ajax({
                    url: '{{ env('API_URL') }}get-pending-qc-inspection',
                    type: "POST",
                    datatype: 'json',
                    data: {
                        company_id: company_id,
                        vendor_id: vendor_id,
                        inward_id: inward_id_param
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        var modalBody = $('#qc_inspection_pending_modal .modal-body');

                        if (response.status == true) {
                            var resData = response.data;

                            if (resData && resData.length > 0) {
                                var innerTableHtml = `
                                    <table id="qc_inspection_pending_vw" class="table table-bordered">
                                        <thead style="background-color:#f1f1f1; font-weight:bold; color:#000;">
                                            <tr>
                                                <th>Weight</th>
                                                <th>Process Name</th>
                                                <th>Vendor Name</th>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Received QTY</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                                $.each(resData, function(index, item) {
                                    let weight = item.job_work_receive_item?.received_weight;

                                    innerTableHtml += '<tr data-item=\'' + JSON.stringify(item) + '\'>';
                                    innerTableHtml += '<td>' + (weight ? parseFloat(weight).toFixed(2) : '-') + '</td>';
                                    innerTableHtml += '<td>' + (item.process_name ?? '-') + '</td>';
                                    innerTableHtml += '<td>' + (item.vendor_name ?? '-') + '</td>';
                                    innerTableHtml += '<td>' + (item.job_work_receive_item?.product_code ?? '-') + '</td>';
                                    innerTableHtml += '<td>' + (item.job_work_receive_item?.product_name ?? '-') + '</td>';
                                    innerTableHtml += '<td>' + (item.job_work_receive_item?.quantity ?? '-') + '</td>';
                                    innerTableHtml += '</tr>';
                                });

                                innerTableHtml += '</tbody></table>';
                                modalBody.html(innerTableHtml);

                            } else {
                                // Display message when no data available
                                modalBody.html('<p class="text-center text-warning">No pending QC inspections available.</p>');
                            }

                            // Show modal
                            const assignModal = new bootstrap.Modal(document.getElementById('qc_inspection_pending_modal'));
                            assignModal.show();
                        } else {
                            modalBody.html('<p class="text-center text-danger">Failed to load data.</p>');
                        }
                    },
                    error: function() {
                        $('#qc_inspection_pending_modal .modal-body').html('<p class="text-center text-danger">Failed to load data.</p>');
                    }
                });
            }
        });


        $('#qc_inspection_pending_modal').on('click', 'tbody tr', function() {

            $(this).siblings().removeClass('table-active');
            $(this).addClass('table-active');

            let itemData = $(this).data('item');
            let productCode = itemData.job_work_receive_item?.product_code ?? '-';
            let productName = itemData.job_work_receive_item?.product_name ?? '-';
            let receivedWeight = itemData.job_work_receive_item?.received_weight ?? '-';
            let quantity = itemData.job_work_receive_item?.quantity ?? '-';
            let wt_pc = itemData.job_work_receive_item?.wt_pc ?? '-';
            let available_qty = itemData.job_work_receive_item?.available_qty ?? '-';
            let processName = itemData.process_name ?? '-';
            let processReceiveId = itemData.process_receive_id ?? '-';
            let warehouse_id = itemData.warehouse_id ?? '-';
            let warehouse_name = itemData.warehouse_name ?? '-';
            let is_size_required = itemData.is_size_required ?? '-';
            let job_work_receive_id = itemData.job_work_receive_item?.job_work_receive_id ?? '';
            let job_work_receive_item_id = itemData.job_work_receive_item?.id ?? '';
            let product_id = itemData.job_work_receive_item?.product_id ?? '-';

            let inward_id = itemData.inward_id ?? '';
            let inward_item_id = itemData.inward_item_id ?? '';

            if (itemData.type === 'inward') {
                job_work_receive_id = '';
                job_work_receive_item_id = '';
            }

            $('#inward_id').val(inward_id);
            $('#inward_item_id').val(inward_item_id);
            $('#product_id').val(product_id);
            $('#is_size_required').val(is_size_required);
            $('#job_work_receive_id').val(job_work_receive_id);
            $('#job_work_receive_item_id').val(job_work_receive_item_id);
            $('#warehouse_id').val(warehouse_id);
            $('#warehouse_name').val(warehouse_name);
            $('#product_code').val(productCode);
            $('#product_name').val(productName);
            $('#issue_for_process_name').val(processName);
            $('#issue_for_process_id').val(processReceiveId);
            $('#wt_pc').val(wt_pc);
            $('#available_qty').val(parseInt(available_qty));
            $('#received_weight').val(parseFloat(receivedWeight).toFixed(2));

            const modalEl = document.getElementById('qc_inspection_pending_modal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }

            if(product_id){
                fetch_ProductMaterialMap();

                // Fetch product attachments
                $.ajax({
                    url: '{{ env('API_URL') }}get-product-attachments',
                    type: "POST",
                    datatype: 'json',
                    data: {
                        product_id: product_id
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status == true && response.data.length > 0) {
                            let attachmentsHtml = '';
                            $.each(response.data, function(index, attach) {
                                attachmentsHtml += `
                                    <a href="${attach.file_url}" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-file-text"></i> ${attach.original_name}
                                    </a>`;
                            });
                            $('#product_attachments_list').html(attachmentsHtml);
                            $('#product_attachments_row').removeClass('d-none');
                        } else {
                            $('#product_attachments_list').html('');
                            $('#product_attachments_row').addClass('d-none');
                        }
                    },
                    error: function() {
                        $('#product_attachments_list').html('');
                        $('#product_attachments_row').addClass('d-none');
                    }
                });
            } else {
                $('#product_attachments_list').html('');
                $('#product_attachments_row').addClass('d-none');
            }
        });


        $(document).on('keydown', function(e) {
            const modal = $('#qc_inspection_pending_modal');
            const table = modal.find('#qc_inspection_pending_vw');
            const visible = modal.is(':visible');

            if (!visible || table.length === 0) return;

            const rows = table.find('tbody tr');
            if (rows.length === 0) return;

            let activeRow = rows.filter('.table-active');
            let currentIndex = activeRow.index();

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (currentIndex < rows.length - 1) {
                        rows.removeClass('table-active');
                        rows.eq(currentIndex + 1).addClass('table-active');
                    } else {
                        rows.removeClass('table-active');
                        rows.eq(0).addClass('table-active');
                    }
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    if (currentIndex > 0) {
                        rows.removeClass('table-active');
                        rows.eq(currentIndex - 1).addClass('table-active');
                    } else {
                        rows.removeClass('table-active');
                        rows.eq(rows.length - 1).addClass('table-active');
                    }
                    break;

                case 'Enter':
                    e.preventDefault();
                    const selectedRow = rows.filter('.table-active');
                    if (selectedRow.length > 0) {
                        selectedRow.trigger('click');

                        setTimeout(() => {
                            $('#ok_weight').focus();
                        }, 500);
                    }
                    break;
            }
        });

        $(document).ready(function() {
            let inward_id_param = '{{ $inward_id ?? '' }}';
            if (inward_id_param) {
                setTimeout(function() {
                    $('#pendingListRowBtn').click();
                }, 500);
            }
        });
    </script>
@endpush
