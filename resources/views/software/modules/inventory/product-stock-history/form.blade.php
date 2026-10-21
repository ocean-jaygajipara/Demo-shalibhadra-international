@extends('software.layout.app')

@php
    $page_title = $modules['title'] ?? null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
@endphp

@section('title', $page_title)

@section('content')
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
            <form id="product-form"
                action="{{ isset($edit) ? route($route . '.update', $edit->id) : route($route . '.store') }}" method="POST">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">
                    {{-- Company --}}
                    @if (!$company_id)
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Company <span class="text-danger">*</span></label>
                            <select name="company_id" id="company_id"
                                class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
                                data-append="search_by_company"
                                data-selectedCompanyId="{{ old('company_id', $data->company_id ?? '') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('company_id', $data->company_id ?? '') ? '' : 'selected' }}>
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

                    {{-- Warehouse --}}
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id"
                            class="form-control @error('warehouse_id') is-invalid @enderror search_by_warehouse select2"
                            data-append="search_by_warehouse"
                            data-selectedWarehouseId="{{ isset($data) && $data?->warehouse_id ? $data?->warehouse_id : '' }}"
                            autofocus>
                            <option value="" disabled
                                {{ old('warehouse_id', $data->warehouse_id ?? '') ? '' : 'selected' }}>
                                Select Warehouse
                            </option>
                        </select>
                        @error('warehouse_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Product Category --}}
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Product Category <span class="text-danger">*</span></label>
                        <select name="product_category_id" id="product_category_id"
                            class="form-control @error('product_category_id') is-invalid @enderror search_by_product_category select2"
                            data-append="search_by_product_category"
                            data-selectedCategoryId="{{ isset($data) && $data?->product_category_id ? $data?->product_category_id : '' }}"
                            autofocus>
                            <option value="" disabled
                                {{ old('product_category_id', $data->product_category_id ?? '') ? '' : 'selected' }}>
                                Select Product Category
                            </option>
                        </select>
                        @error('product_category_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>


                    {{-- Product with Variant --}}
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_variant_price_id" id="product_variant_price_id"
                            class="form-control @error('product_variant_price_id') is-invalid @enderror search_by_product select2"
                            data-append="search_by_product"
                            data-selectedProductVariantPriceId="{{ isset($data) && $data?->product_variant_price_id ? $data?->product_variant_price_id : '' }}"
                            autofocus>
                            <option value="" disabled
                                {{ old('product_variant_price_id', $data->product_variant_price_id ?? '') ? '' : 'selected' }}>
                                Select Product
                            </option>
                        </select>
                        @error('product_variant_price_id')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Quantity --}}
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Quantity <span class="text-danger">*</span> ( Total Qty : <span class="product_total_qty"></span>)</label>
                        <input type="text" name="total_quantity" id="total_quantity" class="form-control @error('total_quantity') is-invalid @enderror"
                            value="{{ old('total_quantity') }}" placeholder="Enter Quantity" onkeypress="return allowSignedNumber(event)">
                        @error('total_quantity')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kg --}}
                    <div class="col-md-4 mb-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label mb-0">Kg <span class="text-danger">*</span> ( Total Kg : <span class="product_total_kg"></span>)</label>
                            <div class="d-flex align-items-center">
                                <input type="checkbox" id="is_kg_checkbox" class="form-check-input me-1">
                                <label for="is_kg_checkbox" class="form-check-label mb-0">kg</label>
                            </div>
                        </div>
                        <input type="text" name="total_kg" id="total_kg" class="form-control @error('total_kg') is-invalid @enderror mt-1"
                            value="{{ old('total_kg') }}" placeholder="Enter Kg" onkeypress="return allowSignedDecimalNumber(event)">
                        @error('total_kg')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- date --}}
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="text" id="date_display" class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', isset($edit->date) ? date('d-m-Y', strtotime($edit->date)) : '') }}"
                            placeholder="DD-MM-YYYY" readonly />
                        <input type="hidden" name="date" id="date"
                            value="{{ old('date', isset($edit->date) ? date('Y-m-d', strtotime($edit->date)) : '') }}" />
                        @error('date')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Remark --}}
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Remark</label>
                        <textarea name="remark" class="form-control @error('remark') is-invalid @enderror" placeholder="Enter Remark">{{ old('remark', $data->remark ?? '') }}</textarea>
                        @error('remark')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="col-md-12 text-center mt-3">
                        <button type="submit" class="btn btn-success">{{ isset($edit) ? 'Update' : 'Submit' }}</button>
                        @if (!isset($data))
                            <input type="hidden" name="save_and_next" id="save_and_next_input" value="0">
                            <button type="button" class="btn btn-primary" id="save_and_next_btn">Save & Next</button>
                        @endif
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @if (!$company_id)
    @include('utils.getCompany')
    @endif
    @include('utils.getProductCategory')
    @include('utils.getWarehouse')
    @include('utils.getProductVariantPrice')
    <script>
        flatpickr("#date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('date', isset($edit->date) ? date('d-m-Y', strtotime($edit->date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('date').value = ymd; // hidden input for submission
            }
        });
    </script>

    <script>
        function allowSignedNumber(e) {
            let char = String.fromCharCode(e.which);
            if (!char.match(/[0-9+\-]/)) {
                return false;
            }

            let value = e.target.value;
            if ((char === '+' || char === '-') && value.length > 0) {
                return false;
            }
            return true;
        }

        function allowSignedDecimalNumber(e) {
            let char = String.fromCharCode(e.which);
            let value = e.target.value;

            if (!char.match(/[0-9+\-.]/)) return false;
            if ((char === '+' || char === '-') && value.length > 0) return false;
            if (char === '.' && value.includes('.')) return false;
            return true;
        }

        $(document).on('change', '#company_id,#warehouse_id,#product_category_id,#product_variant_price_id', function() {
            let company_id = $("#company_id").val();
            let warehouse_id = $("#warehouse_id").val();
            let product_category_id = $("#product_category_id").val();
            let product_variant_price_id = $("#product_variant_price_id").val();

            if(company_id && warehouse_id && product_category_id && product_variant_price_id){
                $.ajax({
                    url: '{{ env('API_URL') }}get-product-stock-total',
                    type: "POST",
                    datatype: 'json',
                    data: {
                        company_id: company_id,
                        warehouse_id: warehouse_id,
                        product_category_id: product_category_id,
                        product_variant_price_id: product_variant_price_id,
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response && response.status === true && response.data) {
                            $(".product_total_kg").text(response.data.product_total_kg ?? 0);
                            $(".product_total_qty").text(response.data.product_total_qty ?? 0);
                        } else {
                            $(".product_total_kg").text(0);
                            $(".product_total_qty").text(0);
                        }
                    },
                    error: function(xhr) {
                        $(".product_total_kg").text(0);
                        $(".product_total_qty").text(0);
                        console.error(xhr.responseText);
                    }
                });
            }
        });

        $('#save_and_next_btn').on('click', function(e) {
            e.preventDefault();

            let form = $('#product-form');
            let formData = new FormData(form[0]);
            formData.append('save_and_next', 1);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success('Product stock create successfully!');
                    $('#product_variant_price_id').val('').trigger('change');
                    $('input[name="total_quantity"]').val('');
                    $('input[name="total_kg"]').val('');
                    $('textarea[name="remark"]').val('');
                    $('input[name="date"]').val(new Date().toISOString().slice(0, 10));
                    $('html, body').animate({
                        scrollTop: 0
                    }, 'fast');
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON?.errors;
                    if (errors) {
                        for (const key in errors) {
                            toastr.error(errors[key][0]);
                        }
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                }
            });
        });

        function roundKgSmart(value) {
            let rounded = parseFloat(value.toFixed(4));
            if (Math.abs(rounded - Math.round(rounded)) <= 0.005) {
                return Math.round(rounded);
            }
            return parseFloat(value.toFixed(3));
        }

        $(document).on('input', '#total_quantity', function () {
            let total_qty = parseFloat($(this).val());
            let selectedOption = $('#product_variant_price_id').find(':selected');
            let grossWt = parseFloat(selectedOption.attr('data-gross-wt')) || 0;
            let netWt = parseFloat(selectedOption.attr('data-net-wt')) || 0;
            let weightGram = netWt > 0 ? netWt : grossWt; // Weight is in grams

            if (weightGram > 0 && !isNaN(total_qty)) {
                // qty → kg: (qty * weightGram) / 1000
                let total_kg = (total_qty * weightGram) / 1000;
                $('#total_kg').val(roundKgSmart(total_kg));
            } else if ($(this).val() === '') {
                 $('#total_kg').val('');
            }
        });

        $(document).on('input', '#total_kg', function () {
            if ($('#is_kg_checkbox').is(':checked')) {
                return;
            }
            let total_kg = parseFloat($(this).val());
            let selectedOption = $('#product_variant_price_id').find(':selected');
            let grossWt = parseFloat(selectedOption.attr('data-gross-wt')) || 0;
            let netWt = parseFloat(selectedOption.attr('data-net-wt')) || 0;
            let weightGram = netWt > 0 ? netWt : grossWt; // Weight is in grams

            if (weightGram > 0 && !isNaN(total_kg)) {
                 // kg → qty: (kg * 1000) / weightGram
                 let total_qty = (total_kg * 1000) / weightGram;
                 $('#total_quantity').val(Math.round(total_qty));
            } else if ($(this).val() === '') {
                 $('#total_quantity').val('');
            }
        });
    </script>
@endpush
