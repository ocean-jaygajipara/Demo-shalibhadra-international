@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $product_bom_route = 'product-bom';
    $product_custom_item_route = 'product-customer-item';
    $product_supplier_detail_route = 'product-supplier-detail';
    $product_image_route = 'product-image';
    $product_rm_detail_route = 'product-rm-detail';

@endphp
@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <style>
        .select2-container--open .select2-dropdown {
            z-index: 9999 !important;
        }

        .modal-dialog {
            overflow: visible !important;
        }
    </style>
@endsection


@section('content')
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
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset
                <div class="row">
                    <input type="hidden" name="product_id" id="product_id" value="{{ $edit?->id ?? '' }}" />

                    @if (!$company_id)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
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
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif

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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input id="product_name" type="text"
                                class="form-control @error('product_name') is-invalid @enderror" name="product_name"
                                value="{{ isset($edit) ? $edit->product_name : old('product_name') }}"
                                autocomplete="product_name" placeholder="Enter Product Name">
                            @error('product_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Part No. <span class="text-danger">*</span></label>
                            <input id="part_no" type="text" class="form-control @error('part_no') is-invalid @enderror"
                                name="part_no" value="{{ isset($edit) ? $edit->part_no : old('part_no') }}"
                                autocomplete="part_no" placeholder="Enter Part No">
                            @error('part_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Grade</label>
                            <select name="product_grade_id" id="product_grade_id"
                                class="form-control @error('product_grade_id') is-invalid @enderror search_by_product_grade select2"
                                data-append="search_by_product_grade"
                                data-selectedGradeId="{{ isset($edit) && $edit?->product_grade_id ? $edit?->product_grade_id : old('product_grade_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('product_grade_id', $edit->product_grade_id ?? '') ? '' : 'selected' }}>
                                    Select Grade
                                </option>
                            </select>
                            @error('product_grade_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select name="unit_id" id="unit_id"
                                class="form-control @error('unit_id') is-invalid @enderror search_by_display_unit select2"
                                data-append="search_by_display_unit"
                                data-selectedDisplayUnitId="{{ isset($edit) && $edit?->unit_id ? $edit?->unit_id : old('unit_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('unit_id', $edit->unit_id ?? '') ? '' : 'selected' }}>
                                    Select Display Unit
                                </option>
                            </select>
                            @error('unit_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">HSN Code <span class="text-danger">*</span></label>
                            <select name="hsn_code" id="hsn_code"
                                class="form-control @error('hsn_code') is-invalid @enderror search_by_product_hsn select2"
                                data-append="search_by_product_hsn"
                                data-selectedHSNId="{{ isset($edit) && $edit?->hsn_code ? $edit?->hsn_code : old('hsn_code') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('hsn_code', $edit->hsn_code ?? '') ? '' : 'selected' }}>
                                    Select HSN Code
                                </option>
                            </select>
                            @error('hsn_code')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <input type="hidden" name="product_tax_id" id="product_tax_id" value="{{ isset($edit) && $edit?->product_tax_id ? $edit?->product_tax_id : old('product_tax_id') }}"/>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Gross Wt. (gms)</label>
                            <input id="gross_wt" type="text"
                                class="form-control @error('gross_wt') is-invalid @enderror" name="gross_wt"
                                value="{{ isset($edit) ? $edit->gross_wt : old('gross_wt') }}" autocomplete="gross_wt"
                                placeholder="Enter Gross Wt">
                            @error('gross_wt')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Net Wt. (gms)</label>
                            <input id="net_wt" type="text"
                                class="form-control @error('net_wt') is-invalid @enderror" name="net_wt"
                                value="{{ isset($edit) ? $edit->net_wt : old('net_wt') }}" autocomplete="net_wt"
                                placeholder="Enter Net Wt">
                            @error('net_wt')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Min Stock</label>
                            <input id="min_stock" type="text"
                                class="form-control @error('min_stock') is-invalid @enderror" name="min_stock"
                                value="{{ isset($edit) ? $edit->min_stock : old('min_stock') }}" autocomplete="min_stock"
                                placeholder="Enter Min Stock">
                            @error('min_stock')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Opening (Kg)</label>
                            <input id="opening_kg" type="text"
                                class="form-control @error('opening_kg') is-invalid @enderror" name="opening_kg"
                                value="{{ isset($edit) ? $edit->opening_kg : old('opening_kg') }}"
                                autocomplete="opening_kg" placeholder="Enter Opening (Kg)">
                            @error('opening_kg')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Opening (Pcs)</label>
                            <input id="opening_pcs" type="text"
                                class="form-control @error('opening_pcs') is-invalid @enderror" name="opening_pcs"
                                value="{{ isset($edit) ? $edit->opening_pcs : old('opening_pcs') }}"
                                autocomplete="opening_pcs" placeholder="EnterOpening (Pcs)">
                            @error('opening_pcs')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Grosse Per Qty</label>
                            <input id="grosse_per_qty" type="text"
                                class="form-control @error('grosse_per_qty') is-invalid @enderror" name="grosse_per_qty"
                                value="{{ isset($edit) ? $edit->grosse_per_qty : old('grosse_per_qty') }}"
                                autocomplete="grosse_per_qty" placeholder="Grosse Per Qty">
                            @error('grosse_per_qty')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Per Bag Quantity</label>
                            <input id="per_bag_quantity" type="number"
                                class="form-control @error('per_bag_quantity') is-invalid @enderror" name="per_bag_quantity"
                                value="{{ isset($edit) ? $edit->per_bag_quantity : old('per_bag_quantity') }}"
                                autocomplete="per_bag_quantity" placeholder="Per Bag Quantity">
                            @error('per_bag_quantity')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror"
                                name="status" required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                    @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif @endif> {{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Attachments (PDF / Images)</label>
                            <input type="file" class="form-control @error('attachments') is-invalid @enderror"
                                name="attachments[]" accept="image/*,application/pdf" multiple>
                            @error('attachments')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror

                            @if (isset($edit) && !empty($edit->attachments_list))
                                <div class="mt-2" id="attachments-container">
                                    <label class="form-label d-block small fw-bold mb-1">Existing Attachments:</label>
                                    <ul class="list-group list-group-flush border rounded p-2 bg-light">
                                        @foreach ($edit->attachments_list as $index => $attach)
                                            @php
                                                $company = $edit->company ?? null;
                                                $company_slug = \Illuminate\Support\Str::slug(
                                                    $company->company_name ?? 'default-company',
                                                );
                                                $relativePath = "uploads/{$edit->company_id}-{$company_slug}/product-attachments/{$attach['file_path']}";
                                                $fileUrl = asset($relativePath);
                                            @endphp
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-1 py-2 bg-transparent" id="attach-item-{{ $index }}">
                                                <div class="text-truncate" style="max-width: 65%;">
                                                    <a href="{{ $fileUrl }}" target="_blank" class="fw-semibold text-primary" title="{{ $attach['original_name'] ?? basename($attach['file_path']) }}">
                                                        {{ $attach['original_name'] ?? basename($attach['file_path']) }}
                                                    </a>
                                                    <span class="d-block text-muted" style="font-size: 0.72rem;">
                                                        Uploaded: {{ $attach['uploaded_at'] ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-primary py-1 px-2"><i class="ti ti-eye"></i> View</a>
                                                    <button type="button" class="btn btn-sm btn-danger py-1 px-2 remove-existing-attach-btn" data-filepath="{{ $attach['file_path'] }}" data-target="#attach-item-{{ $index }}"><i class="ti ti-trash"></i> Remove</button>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>


                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-success mt-1 mb-1">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>
                </div>
            </form>
            @if (isset($edit))
                <div class="col-xl-12 col-lg-12 col-md-12 order-0 order-md-1">
                    <div class="nav-align-top">
                        <ul class="nav nav-pills flex-column flex-md-row mb-6 row-gap-2 flex-wrap" id="productTabs">
                            <li class="nav-item">
                                <a class="nav-link active waves-effect waves-light" data-table="bom"
                                    href="javascript:;">BOM</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link waves-effect waves-light" data-table="customer_item_description"
                                    href="javascript:;">Customer Item Description</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link waves-effect waves-light" data-table="image"
                                    href="javascript:;">Image</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link waves-effect waves-light" data-table="supplier_details"
                                    href="javascript:;">Supplier Details</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link waves-effect waves-light" data-table="rm_details"
                                    href="javascript:;">R. M. Details</a>
                            </li>

                        </ul>
                    </div>
                </div>
            @endif

            {{-- Product BOM Form/Table --}}
            @include('software.modules.product.product-bom.form')
            @include('software.modules.product.product-bom.index')

            {{-- Product customer item Form/Table --}}
            @include('software.modules.product.product-customer-item.form')
            @include('software.modules.product.product-customer-item.index')

            {{-- Product Supplier Details Form/Table --}}
            @include('software.modules.product.product-supplier-detail.form')
            @include('software.modules.product.product-supplier-detail.index')

            {{-- Product Image Form/Table --}}
            @include('software.modules.product.product-image.form')
            @include('software.modules.product.product-image.index')

            {{-- Product RM Detail Form/Table --}}
            @include('software.modules.product.product-rm-detail.form')
            @include('software.modules.product.product-rm-detail.index')
        </div>
    </div>


@endsection

@section('page_leavel_script')
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            autosize($('.autosize'));
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).on('change', '#hsn_code', function () {
            let selectedOption = $(this).find('option:selected');
            let productTax = selectedOption.data('product_tax') || 0;

            // Update the input field
            $('#product_tax_id').val(productTax);
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    @include('utils.getProductCategory')
    @include('utils.getProductGrade')
    @include('utils.getProductHSN')
    @include('utils.getProductUnit')
    @include('utils.getProductAlloy')
    @include('utils.getProductVariantPrice')
    @include('utils.getBOMProductCategory')
    @include('utils.getBOMProductVariantPrice')

    @if (isset($edit))
        <script>
            const routes = {
                bom: "{{ route('product-bom.index') }}",
                customer_item_description: "{{ route('product-customer-item.index') }}",
                supplier_details: "{{ route('product-supplier-detail.index') }}",
                image: "{{ route('product-image.index') }}",
                rm_details: "{{ route('product-rm-detail.index') }}",
            };
            $(document).ready(function() {
                loadBomTabData();

                $('#productTabs .nav-link').click(function(e) {
                    e.preventDefault();

                    $('#productTabs .nav-link').removeClass('active');
                    $(this).addClass('active');

                    const table = $(this).data('table');
                    const functionName = `load${toCamelCase(table)}TabData`;

                    $(".product-bom-section").hide();
                    $(".product-customer-item-section").hide();
                    $(".product-supplier-section").hide();
                    $(".product-images-section").hide();
                    $(".product-rm-detail-section").hide();
                    if (typeof window[functionName] === 'function') {
                        window[functionName]();
                    } else {
                        $('#tabContent').html(
                            `<p class="text-danger">No loader function defined for "${table}"</p>`);
                    }
                });
            });

            function toCamelCase(str) {
                return str.replace(/(^|_)([a-z])/g, (_, __, letter) => letter.toUpperCase());
            }
        </script>
        @include('utils.productBOMScript')
        @include('utils.productCustomerItemScript')
        @include('utils.productSupplierDetailScript')
        @include('utils.productImageScript')
        @include('utils.productRmDetailScript')
        @include('software.inlcudes.script-delete-record')
        @include('software.inlcudes.script-restore-record')
    @endif

    <script>
        // function calculateNetWt() {
        //     let openingKg = parseFloat($("#opening_kg").val()) || 0;
        //     let openingPcs = parseFloat($("#opening_pcs").val()) || 0;

        //     if (openingKg > 0 && openingPcs > 0) {
        //         let netWt = (openingKg * 1000) / openingPcs;
        //         $("#net_wt").val(netWt.toFixed(2)); // 2 decimal places
        //     } else {
        //         $("#net_wt").val("");
        //     }
        // }

        // $(document).on("keyup", "#opening_kg, #opening_pcs", function () {
        //     calculateNetWt();
        // });

        // calculateNetWt();
    </script>
    <script>
        $(document).on('click', '.remove-existing-attach-btn', function() {
            var filePath = $(this).data('filepath');
            var targetId = $(this).data('target');
            
            Swal.fire({
                title: "Are you sure?",
                text: "Do you want to remove this attachment?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $(targetId).remove();
                    $('form').append('<input type="hidden" name="removed_attachments[]" value="' + filePath + '">');
                    Swal.fire(
                        "Deleted!",
                        "Attachment has been queued for removal. Save the form to complete deletion.",
                        "success"
                    );
                }
            });
        });
    </script>
@endpush
