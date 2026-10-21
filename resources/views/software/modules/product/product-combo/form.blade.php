@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

    // dd($modules);

@endphp
@section('title', $page_title)


@section('content')
    <style>
        .add-btn-focus {
            border: 2px solid #0d6efd !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.4);
        }
    </style>
     <div class="px-1">
    <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                ['title' => (isset($edit) && $edit?->id) ? "Edit ".$page_title : "Create ".$page_title , 'url' => ''],
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
                    @if (!$company_id)
                        <div class="col-md-3 col-sm-12 mb-3">
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

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input id="product_combo_name" type="text"
                                class="form-control @error('product_combo_name') is-invalid @enderror"
                                name="product_combo_name"
                                value="{{ isset($edit) ? $edit->product_combo_name : old('product_combo_name') }}"
                                autocomplete="product_combo_name" placeholder="Enter Product Name">
                            @error('product_combo_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Product Combo Price</label>
                            <input type="number" step="0.01" id="price" class="form-control @error('price') is-invalid @enderror" name="price"
                                value="{{ isset($edit) ? $edit->price : old('price', 0) }}" placeholder="Enter Price">
                            @error('price')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Part No.<span class="text-danger">*</span></label>
                            <input id="part_no" type="text"
                                class="form-control @error('part_no') is-invalid @enderror"
                                name="part_no"
                                value="{{ isset($edit) ? $edit->part_no : old('part_no') }}"
                                autocomplete="part_no" placeholder="Enter Part No.">
                            @error('part_no')
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

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Display Unit <span class="text-danger">*</span></label>
                            <select name="display_unit_id" id="display_unit_id"
                                class="form-control @error('display_unit_id') is-invalid @enderror search_by_display_unit select2"
                                data-append="search_by_display_unit"
                                data-selectedDisplayUnitId="{{ isset($edit) && $edit?->display_unit_id ? $edit?->display_unit_id : old('display_unit_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('display_unit_id', $edit->display_unit_id ?? '') ? '' : 'selected' }}>
                                    Select Display Unit
                                </option>
                            </select>
                            @error('display_unit_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Gross Wt. (gms) </label>
                            <input id="gross_wt" type="text"
                                class="form-control @error('gross_wt') is-invalid @enderror"
                                name="gross_wt"
                                value="{{ isset($edit) ? $edit->gross_wt : old('gross_wt') }}"
                                autocomplete="gross_wt" placeholder="Enter Gross Wt. (gms)">
                            @error('gross_wt')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Net Wt. (gms) <span class="text-danger">*</span></label>
                            <input id="net_wt" type="text"
                                class="form-control @error('net_wt') is-invalid @enderror"
                                name="net_wt"
                                value="{{ isset($edit) ? $edit->net_wt : old('net_wt') }}"
                                autocomplete="net_wt" placeholder="Enter Net Wt. (gms)">
                            @error('net_wt')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Grosse Per Qty</label>
                            <input id="grosse_per_qty" type="text"
                                class="form-control @error('grosse_per_qty') is-invalid @enderror"
                                name="grosse_per_qty"
                                value="{{ isset($edit) ? $edit->grosse_per_qty : old('grosse_per_qty') }}"
                                autocomplete="grosse_per_qty" placeholder="Enter Grosse Per Qty">
                            @error('grosse_per_qty')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-3 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror" name="status"
                                required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                    @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif @endif> {{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-repeater">
                        <!-- Product Variant + Add Button Row -->
                        <div class="row align-items-end mb-3">

                            <div class="col-md-4 mb-2">
                                <label class="form-label">Product <span
                                        class="text-danger">*</span></label>
                                <select id="product_as_variant_price_id"
                                    class="form-control product_as_variant_price_id select2"
                                    data-append="product_as_variant_price_id">
                                    <option value="" disabled> Select Product </option>
                                </select>
                            </div>


                            <!-- + Add Button -->
                            <div class="col-md-1 mb-2">
                                {{-- <button type="button" class="btn btn-primary  w-100" id="add-row" data-repeater-create> --}}
                                <button type="button" id="add-row" class="btn btn-primary">
                                    <i class="ti ti-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>




                        <div data-repeater-list="dynamic_fields" id="dynamic_fields">

                            @php
                                $isEdit = false;
                                $comboItems = [];
                                if (old('dynamic_fields')) {
                                    $isEdit = true;
                                    $comboItems = old('dynamic_fields', []);
                                } elseif (isset($edit) && isset($edit?->combo_items) && count($edit?->combo_items)) {
                                    $isEdit = true;
                                    $comboItems = $edit?->combo_items;
                                }
                                // dd($comboItems);
                            @endphp

                            <!-- Template (initially hidden) -->
                            @if ($isEdit)
                                @foreach ($comboItems as $key => $item)
                                    @php
                                        $item = is_array($item) ? (object) $item : $item;
                                        if (isset($item->product)) {
                                            $product_name =
                                                ($item->product->part_no ?? '') .
                                                ' - ' .
                                                ($item->product->product_name ?? '');
                                        }
                                        else {
                                            $product_name = $item->product_variant_label ?? '';
                                        }
                                    @endphp

                                    <div data-repeater-item class="template-item combo-item"
                                        data-key="{{ $key }}">
                                        <input type="hidden" class="product_variant_price_id"
                                            name="dynamic_fields[][product_combo_item_id]" value="{{ $item?->id ?? '' }}">

                                        <div class="row">
                                            <div class="mb-4 col-lg-4 col-xl-3 col-12">
                                                <label class="form-label">Product</label>
                                                <input type="text" class="readonly-look form-control display_product"
                                                    name="dynamic_fields['{{ $key }}'][product_variant_label]"
                                                    placeholder="Product Info" value="{{ $product_name }}" readonly>
                                                <input type="hidden"
                                                    class="readonly-look form-control product_variant_price_id"
                                                    name="dynamic_fields['{{ $key }}'][product_variant_price_id]"
                                                    value="{{ $item?->product_variant_price_id ?? ($item?->product_variant_price_id ?? 0) }}">
                                            </div>

                                            <div class="mb-4 col-lg-4 col-xl-3 col-12 d-none">
                                                <label class="form-label">Price</label>
                                                <input type="number" step="0.01"
                                                    class="readonly-look form-control display_price"
                                                    name="dynamic_fields['{{ $key }}'][display_price]"
                                                    placeholder="Enter Price" readonly
                                                    value="{{ $item?->display_price ?? ($item?->price ?? 0) }}">

                                            </div>

                                            <div class="mb-4 col-lg-4 col-xl-3 col-12">
                                                <label for="order_type">Order Type</label>
                                                <select name="dynamic_fields['{{ $key }}'][order_type]" class="form-control form-control select2 order_type">
                                                    @foreach ($modules['order_type'] as $orderTypeKey => $label)
                                                        @php
                                                            $oldOrderType = old("dynamic_fields.$key.order_type", $item?->order_type ?? '');
                                                        @endphp
                                                        <option value="{{ $orderTypeKey }}"
                                                            {{ $oldOrderType == $orderTypeKey ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>


                                            <div class="mb-4 col-lg-4 col-xl-3 col-12">
                                                <label class="form-label">Quantity <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" step="0.01"
                                                    name="dynamic_fields['{{ $key }}'][quantity]"
                                                    class="form-control quantity" name="quantity"
                                                    placeholder="Enter Quantity"
                                                    value="{{ $item?->quantity ?? ($item?->quantity ?? 0) }}">
                                            </div>


                                            <div class="mb-3 col-lg-12 col-xl-2 col-12 d-flex align-items-center">
                                                <button type="button" class="btn btn-danger mt-3" data-repeater-delete>
                                                    <i class="ti ti-x me-1"></i>
                                                </button>
                                            </div>

                                        </div>
                                        <hr>
                                    </div>
                                @endforeach
                            @else

                            @endif

                            <div data-repeater-item class="template-item combo-item" style="display: none;">
                                    <div class="row">
                                        <!-- Product Info -->
                                        <div class="mb-4 col-lg-4 col-xl-3 col-12">
                                            <label class="form-label">Product</label>
                                            <input type="text" class="readonly-look form-control display_product"
                                                name="dynamic_fields[0][product_variant_label]" placeholder="Product Info"
                                                readonly>
                                        </div>
                                        <input type="hidden" class="product_variant_price_id"
                                            name="dynamic_fields[0][product_variant_price_id]" value="">

                                        <!-- Hidden Variant ID -->
                                        <div class="mb-4 col-lg-4 col-xl-3 col-12 d-none">
                                            <label class="form-label">Variant Price</label>
                                            <input type="number" step="0.01"
                                                class="readonly-look form-control display_price"
                                                name="dynamic_fields[0][display_price]" placeholder="Enter Price"
                                                readonly>
                                        </div>

                                        <div class="mb-4 col-lg-4 col-xl-3 col-12">
                                            <label for="order_type">Order Type</label>
                                            <select name="dynamic_fields[0][order_type]" class="form-control form-control select2 order_type">
                                                <option value="">Select Order Type</option>
                                                @foreach ($modules['order_type'] as $key => $label)
                                                    <option value="{{ $key }}"
                                                        {{ old('order_type', $edit->order_type ?? '') == $key ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Quantity -->
                                        <div class="mb-4 col-lg-4 col-xl-3 col-12">
                                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="dynamic_fields[0][quantity]"
                                                class="form-control quantity" name="quantity"
                                                placeholder="Enter Quantity">
                                        </div>

                                        <!-- Delete Button -->
                                        <div class="mb-3 col-lg-12 col-xl-2 col-12 d-flex align-items-center">
                                            <button type="button" class="btn btn-danger mt-3" data-repeater-delete>
                                                <i class="ti ti-x me-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <hr>
                                </div>
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
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="{{ asset('software/vendor/libs/bootstrap-maxlength/bootstrap-maxlength.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.min.js"></script>
    <script>
        let selectedProductData = {};
        let addedProducts = [];
        let variantPrices = null;
        window.id = 0;


        function formRepeator() {
            $('.form-repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'id': window.id,
                    'text-input': ''
                },
                show: function() {
                    $(this).slideDown();
                    console.log("L-300", $(this).find('input')[1]);
                },
                hide: function(deleteElement) {
                    const row = $(this);
                    const label = row.find('.display_product').val()?.trim();

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'You will not be able to recover this data!',
                        icon: 'warning',
                        iconColor: '#f1c40f',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'No, cancel please!',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.id--;
                            addedProducts = addedProducts.filter(item => item !== label);
                            row.slideUp(deleteElement);
                        }
                    });
                },
                // isFirstItemUndeletable: true
            });
        }

        formRepeator();

       $(document).ready(function () {
            $(document).on('change', '#product_as_variant_price_id', function () {
                if ($(this).val()) {
                    setTimeout(function () {
                        $('#add-row').focus();
                    }, 100);
                }
            });
            $(document).on('keydown', '#add-row', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    if (!$('#product_as_variant_price_id').val()) {
                        alert('Please select a product first');
                        return;
                    }

                    $(this).trigger('click');
                }
            });
            $(document).on('focus', '#add-row', function () {
                $(this).addClass('add-btn-focus');
            });

            $(document).on('blur', '#add-row', function () {
                $(this).removeClass('add-btn-focus');
            });
        });

        $(document).on('change', '#hsn_code', function () {
            let selectedOption = $(this).find('option:selected');
            let productTax = selectedOption.data('product_tax') || 0;
            $('#product_tax_id').val(productTax);
        });

        $(document).on('click', '#add-row', function() {

            let selectedId = $("#product_as_variant_price_id option:selected").val();
            if (!selectedId) {
                toastr.error('Please select a product first.');
                return;

            }
            let selectedProductData = variantPrices.find(function(item) {
                return item.id == selectedId;
            })


            let isDuplicate = false;
            $('.display_product').each(function() {
                if ($(this).closest('[data-repeater-item]').is(':visible')) {
                    if ($(this).val().trim() === selectedProductData.label) {
                        isDuplicate = true;
                        return false; // break the loop
                    }
                }
            });

            if (!selectedProductData.id) {
                toastr.error('Please select a product first.');
                return;
            }

            if (isDuplicate) {
                toastr.error('This product is already added');
                return;
            }

            const $templateItem = $('[data-repeater-item]:first');
            $templateItem.find('select.select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            const newItem = $templateItem.clone();

            let index = $('.form-repeater [data-repeater-item]').length;
            newItem.find('input').val('');
            newItem.find('.display_product').val(
                selectedProductData.label);
            newItem.find('.product_variant_price_id').val(selectedProductData.id);
            newItem.find('.display_price').val(
                selectedProductData.price);
            newItem.find('.quantity').val();


            $('[data-repeater-list]').append(newItem);
            newItem.show();

            newItem.find('.select2-container').remove();
            newItem.find('select.select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
                $(this).select2({
                    width: '100%'
                });
                $(this).trigger('change');
            });
            $templateItem.find('select.select2').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({ width: '100%' });
                }
            });

            addedProducts.push(selectedProductData.label);

            formRepeator();

            setTimeout(function () {
                let $lastRow = $('[data-repeater-item]').last();
                $lastRow.find('.order_type').focus();
                $lastRow.find('.order_type').css({
                    'border': '2px solid #0d6efd',
                    'box-shadow': '0 0 0 0.2rem rgba(13,110,253,.4)'
                });
            }, 150);

            $('#product_as_variant_price_id')
            .val(null)
            .trigger('change');
        });
    </script>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getProductCategory')
    @include('utils.getProductHSN')
    @include('utils.getProductUnit')
    @include('utils.getProductAsVariant')
@endpush


