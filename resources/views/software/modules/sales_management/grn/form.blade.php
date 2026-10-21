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
            @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
            ['title' => $page_title, 'url' => route($route . '.index')],
            ['title' => (isset($edit) && $edit?->id) ? "Edit ".$page_title : "Create ".$page_title , 'url' => ''],
            ],
            ])
            <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}"> <i
                    class="menu-icon ti ti-chevrons-left"></i> Back</a>
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
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

            <input type="hidden" name="customer_name" id="customer_name"
                value="{{ isset($edit) && $edit?->id ? $edit?->customer_name : '' }}" />
            <input type="hidden" name="contact_person_name" id="contact_person_name"
                value="{{ isset($edit) && $edit?->id ? $edit?->contact_person_name : '' }}" />
            <input type="hidden" name="state" id="state"
                value="{{ isset($edit) && $edit?->id ? $edit?->state : '' }}" />
            <input type="hidden" name="price_list_id" id="price_list_id"
                value="{{ isset($edit) && $edit?->id ? $edit?->price_list_id : '' }}" />

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
                                data-selectedCompanyId="{{ old('company_id', isset($edit) && $edit?->company_id ? $edit?->company_id : '') }}"
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
                        <select name="customer_id" id="customer_id"
                            class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                            data-append="search_by_customer"
                            data-selectedCustomerId="{{ old('customer_id', isset($edit) && $edit?->customer_id ? $edit?->customer_id : '') }}"
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


                    <div class="col-md-2">
                        <label class="form-label">GRN No <span class="text-danger">*</span></label>
                        <input type="text" class="form-control readonly-look" name="grn_no"
                            id="grn_no" placeholder="Enter GRN No" readonly
                            value="{{ old('grn_no', isset($edit) && $edit?->grn_no ? $edit?->grn_no : '') }}">
                    </div>


                    <div class="col-md-2">
                        <label class="form-label">GRN Date <span class="text-danger">*</span></label>
                        <div style="position: relative;">
                            <input type="text"
                                id="grn_date_display"
                                class="form-control @error('grn_date') is-invalid @enderror"
                                value="{{ old('grn_date')
                                    ? date('d-m-Y', strtotime(old('grn_date')))
                                    : (isset($edit->grn_date)
                                            ? date('d-m-Y', strtotime($edit->grn_date))
                                            : date('d-m-Y')) }}"
                                placeholder="DD-MM-YYYY"
                                style="padding-right: 30px;"
                                readonly />

                            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                                <i class="fa fa-calendar" aria-hidden="true"></i>
                            </span>
                        </div>

                        <input type="hidden" name="grn_date" id="grn_date"
                            value="{{ old('grn_date', isset($edit->grn_date) ? date('Y-m-d', strtotime($edit->grn_date)) : date('Y-m-d')) }}" />
                    </div>


                    <div class="col-md-2">
                        <label class="form-label">DOS Date</label>
                        <input type="text" id="dos_date_display"
                            class="form-control @error('dos_date') is-invalid @enderror"
                            value="{{ old('dos_date', isset($edit->dos_date) ? date('d-m-Y', strtotime($edit->dos_date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="dos_date" id="dos_date"
                            value="{{ old('dos_date', isset($edit->dos_date) ? date('Y-m-d', strtotime($edit->dos_date)) : date('Y-m-d')) }}" />

                    </div>

                </div>

                @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
                <div class="row">
                    <!-- Left Side: Customer Details -->
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



                        </div>
                    </div>

                </div>
                @else
                    <input type="hidden" name="phone" id="phone"
                        value="{{ isset($edit) && $edit?->id ? $edit?->phone : '' }}" />

                    <input type="hidden" name="address" id="address"
                        value="{{ isset($edit) && $edit?->id ? $edit?->address : '' }}" />
                @endif

                <hr class="my-4">
                <h4 class="mb-3">GRN Items</h4>

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
                                    Add Qty
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
                                <th style="min-width: 200px;">Item Code</th>
                                <th style="min-width: 200px;">Item Name</th>
                                <th style="min-width: 200px;">Last Process</th>
                                <th style="min-width: 200px;">Stock</th>
                                <th style="min-width: 200px;">Kg / Qty / Grosse</th>
                                <th style="min-width: 200px;">Unit</th>
                                <th style="min-width: 200px;">Wt/Pcs</th>
                                <th style="min-width: 200px;">Alt Qty</th>
                                <th style="min-width: 50px;">Action</th>
                            </tr>
                        </thead>

                        <tbody id="productBody">
                            @if (isset($edit) && $edit?->grn_item)
                                @foreach ($edit?->grn_item as $item)
                                    <tr>
                                        <input type="hidden" class="form-control" name="item_id[]"
                                            value="{{ $item->quotation_item_id }}">
                                        <input type="hidden" class="form-control" name="product_id[]"
                                            value="{{ $item->product_id }}">
                                        <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]"
                                            value="{{ $item->product->grosse_per_qty }}">

                                        <td><label class="sr_no_txt">{{ $loop->iteration }}</label></td>
                                        <td><input type="hidden" name="product_code[]" readonly><label class="product_code_txt">{{ $item->product_code ?? '' }}</label></td>
                                        <td><label class="product_name_txt">{{ $item->product_name ?? '' }}</label><input
                                                type="hidden" name="product_name[]"
                                                value="{{ $item->product_name ?? '' }}" readonly></td>

                                        <td><select name="last_process_id[]" data-selectedLastProcessId="{{ $item->last_process_id ?? '' }}" data-edit="yes" class="form-control select2 search_by_last_process" data-placeholder="Select Last Process"><option value="">Select Last Process</option></select></td>
                                        <td><input type="text" class="form-control bg-light" name="product_stock[]"
                                                value="{{ $item->product_stock ?? '' }}" readonly></td>

                                        <td><input type="text" class="form-control quantity"
                                                onchange="updateInnerAndOuterFromQty(this)" name="quantity[]"
                                                value="{{ strtolower($item->unit) === 'kg'
                                                    ? number_format($item->quantity ?? 0, 3, '.', '')
                                                    : (int) ($item->quantity ?? 0)
                                                }}" onkeypress="{{ strtolower($item->unit) === 'kg' ? 'return isSignedDecimal(event)' : 'return isSignedNumber(event)' }}">
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


                                        <td><input type="text" class="form-control bg-light" name="wt_pc[]"
                                                onkeypress="return isDecimal(event)" value="{{ $item->wt_pc ?? '' }}"
                                                readonly></td>
                                        <td><input type="text"
                                                class="form-control bg-light" name="alt_qty[]"
                                                onkeypress="return isDecimal(event)"
                                                value="{{ $item->alt_qty ?? '' }}" readonly></td>
                                        <td><button type="button" class="btn btn-danger deleteRow"
                                                onclick="removeRow(this)">X</button></td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>

                    </table>
                </div>

                <hr class="my-4">
                <h4 class="mb-3">Extra Details</h4>


                <div class="col-md-12 col-sm-12 mb-3">
                    <div class="form-group">
                        <label class="form-label">Remarks </label>
                        <textarea rows="3" cols="3"
                            class="ckeditor_common_cls form-control @error('remark') is-invalid @enderror" name="remark"
                            placeholder="Enter Remarks" id="remark_detail">
                                {{ isset($edit) && $edit?->remark ? $edit?->remark : old('remark') }}
                            </textarea>
                        @error('remark')
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
        flatpickr("#dos_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('dos_date', isset($edit->dos_date) ? date('d-m-Y', strtotime($edit->dos_date)) : date('d-m-Y')) }}",
            // maxDate: "{{ date('d-m-Y') }}",
            minDate: "1900-01-01",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('dos_date').value = ymd;
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

    @if (!$company_id)
        @include('utils.getCompany')
    @endif

     @if ($generateGRNNo)
        @include('utils.generateGRNNo')
    @endif

    @include('utils.getCustomerSales')
    @include('utils.getCustomerInfo')
    @include('utils.getProductAndVariantWithOutCategory')
    @include('utils.getLastProcess')

    <script>
        initializeCKEditor(".ckeditor_common_cls");

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
                <input type="hidden" class="form-control grosse_per_qty" name="grosse_per_qty[]">

                <td class="sr_no_txt"></td>
                <td><input type="hidden" name="product_code[]" readonly><label class="product_code_txt"></label></td>
                <td><input type="hidden" name="product_name[]" readonly><label class="product_name_txt"></label></td>
                <td>
                    <select name="last_process_id[]" data-edit="yes"
                            class="form-control select2 search_by_last_process"
                            data-placeholder="Select Last Process">
                        <option value="">Select Process</option>
                    </select>
                </td>
                <td><input type="text" class="form-control bg-light" name="product_stock[]" readonly></td>
                <td><input type="text" class="form-control quantity" name="quantity[]" value="1"></td>
                <td><input type="text" class="form-control unit bg-light" name="unit[]" readonly></td>
                <td><input type="text" class="form-control wt_pc bg-light" name="wt_pc[]" readonly></td>
                <td><input type="text" class="form-control alt_qty bg-light" name="alt_qty[]" readonly></td>
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

                    $("#productBody tr:last").find('input[name="product_id[]"]').val(data.product_id);

                    $("#productBody tr:last").find('input[name="product_code[]"]').val(data.part_no);
                    $("#productBody tr:last").find('input[name="product_stock[]"]').val(data.product_stock);
                    $("#productBody tr:last").find('.product_code_txt').text(data.part_no);
                    $("#productBody tr:last").find('input[name="product_description[]"]').val(data
                        .description);
                    $("#productBody tr:last").find('input[name="product_variant_price_id[]"]').val(data
                        .product_variant_price_id);

                    $("#productBody tr:last").find('input[name="wt_pc[]"]').val(data.net_wt);
                    $("#productBody tr:last").find('input[name="unit[]"]').val(orderTypeValue);
                    $("#productBody tr:last").find('input[name="quantity[]"]').val(orderPrice);
                    $("#productBody tr:last").find('input[name="grosse_per_qty[]"]').val(data.grosse_per_qty);


                    let unitVal = orderTypeValue.toLowerCase();
                    let qtyInput = $("#productBody tr:last").find('input[name="quantity[]"]');

                    if (unitVal === "kg") {
                        qtyInput.attr("onkeypress", "return isSignedDecimal(event)");
                    } else {
                        qtyInput.attr("onkeypress", "return isSignedNumber(event)");
                    }

                    let wt_pc = parseFloat(data.net_wt) || 0;
                    let qty = parseFloat(orderPrice) || 0;
                    let alt_qty = ((wt_pc) / 100).toFixed(3);
                    $lastRow.find('input[name="alt_qty[]"]').val(alt_qty);


                    $("#productBody tr:last").find('input[name="quantity[]"]').change();
                    $('#product_id').val('').trigger('change');
                    $('#product_variant_price_id').val('').trigger('change');

                    if (orderTypeValue.toLowerCase() === "grosse") {
                        let $unitCell = $("#productBody tr:last").find('input[name="unit[]"]').closest('td');

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
                    fetch_lastProcess($lastRow);
                },
                error: function(xhr, status, error) {
                    console.error("Error: ", error);
                }
            });
        });

        $(document).on('input', 'input[name="quantity[]"]', function() {
            let $row = $(this).closest('tr');
            let wt_pc = parseFloat($row.find('input[name="wt_pc[]"]').val()) || 0;
            let qty = parseFloat($(this).val()) || 0;
            let alt_qty = ((wt_pc) / 100).toFixed(3);
            $row.find('input[name="alt_qty[]"]').val(alt_qty);
        });

        function isSignedNumber(evt) {
            evt = evt || window.event;
            const key = evt.key;
            const input = evt.target;

            if (['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Delete'].includes(key)) {
                return true;
            }

            if (/^\d$/.test(key)) {
                return true;
            }

            if (key === '+' || key === '-') {
                const value = input.value || '';
                const hasSign = value.includes('+') || value.includes('-');
                return !hasSign && input.selectionStart === 0;
            }

            return false;
        }

        function isSignedDecimal(evt) {
            evt = evt || window.event;
            const key = evt.key;
            const input = evt.target;

            if (['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'Delete'].includes(key)) {
                return true;
            }

            if (/^\d$/.test(key)) {
                return true;
            }

            if (key === '.') {
                return !input.value.includes('.');
            }

            if (key === '+' || key === '-') {
                const value = input.value || '';
                const hasSign = value.includes('+') || value.includes('-');
                return !hasSign && input.selectionStart === 0;
            }

            return false;
        }

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("deleteRow")) {
                e.target.closest("tr").remove();
                recalculateRow(e.target);
            }
        });
        $('#order_type').val('').trigger('change');
    </script>
@endpush
