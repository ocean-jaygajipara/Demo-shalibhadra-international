@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    if(isset($edit) && $edit?->company_id){

    }
@endphp

@section('title', $page_title)

@if (isset($edit) && $edit?->company_id)
@section('company_id', $edit?->company_id)
@endif

@section('page_leavel_style')
    <style>
        .ck-editor__editable_inline {
            min-height: 150px;
        }
        #toast-container.toast-center-center {
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: auto !important;
        }
        #toast-container.toast-center-center > div {
            width: 500px !important;
            padding: 20px 20px 20px 50px !important;
            opacity: 1 !important;
        }

        /* Proper Grid Alignment - No Color/Font Changes */
        #inward_items thead th {
            vertical-align: middle !important;
            padding: 10px 5px !important;
            text-align: center;
        }
        #inward_items tbody td {
            vertical-align: middle !important;
            padding: 8px 5px !important;
        }
        #inward_items .form-control {
            height: auto !important;
            padding: 5px 10px !important;
        }
        #inward_items label {
            margin-bottom: 0 !important;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')


    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => 'Create ' . $page_title,
                    'url' => '',
                ],
            ],
        ])
        <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}"> <i
                class="menu-icon ti ti-chevrons-left"></i> Back</a>
    </div>

    <div class="card my-3 mb-4">
        <div class="card-body">
            <form action="{{ route($route . '.store') }}" method="POST" id="form">
                @csrf
                <input type="hidden" name="purchase_order_id" id="purchase_order_id"
                    value="{{ isset($purchase_order_id) && $purchase_order_id ? $purchase_order_id : '' }}" />
                <input type="hidden" name="company_id"
                    value="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}" />
                <input type="hidden" name="vendor_id"
                    value="{{ isset($edit) && $edit?->vendor_id ? $edit?->vendor_id : '' }}" />
                <input type="hidden" name="vendor_name" id="vendor_name"
                    value=" {{ $edit->vendor_name ?? $edit->vendor->vendor_name ?? '-' }}" />
                <input type="hidden" name="phone" id="phone"
                    value="{{ isset($edit) && $edit?->phone ? $edit?->phone : '' }}" />
                <input type="hidden" name="state" id="state"
                    value="{{ isset($edit) && $edit?->state ? $edit?->state : '' }}" />
                <input type="hidden" name="address" id="address"
                    value="{{ isset($edit) && $edit?->address ? $edit?->address : '' }}" />


                <div class="row">
                    <div class="col-md-4">
                        {{-- company  --}}
                        <div class="col-8 mb-2" style="display: none;">
                            <div class="row">
                                <div class="col-4"><label><strong>Company</strong></label></div>
                                <div class="col-8">
                                    <p class="mb-0"> :
                                        {{ isset($edit) && $edit?->company_id ? $edit?->company?->company_name : 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        {{--  Vendor --}}
                        <div class="col-8 mb-2">
                            <div class="row">
                                <div class="col-4"><label><strong>Vendor</strong></label></div>
                                <div class="col-8">
                                    <p class="mb-0"> :
                                        {{ $edit->vendor_name ?? $edit->vendor->vendor_name ?? '-' }}</p>
                                </div>
                            </div>
                        </div>

                        {{--  Phone --}}
                        <div class="col-8 mt-2 mb-2">
                            <div class="row">
                                <div class="col-4"><label><strong>Phone</strong></label></div>
                                <div class="col-8">
                                    <p class="mb-0"> : {{ isset($edit) && $edit?->phone ? $edit?->phone : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        {{--  Address --}}
                        <div class="col-8 mt-2 mb-2">
                            <div class="row">
                                <div class="col-4"><label><strong>Address</strong></label></div>
                                <div class="col-8">
                                    <p class="mb-0"> : {{ isset($edit) && $edit?->address ? $edit?->address : 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        {{--  State --}}
                        <div class="col-8 mt-2 mb-2">
                            <div class="row">
                                <div class="col-4"><label><strong>State</strong></label></div>
                                <div class="col-8">
                                    <p class="mb-0"> : {{ isset($edit) && $edit?->state ? $edit?->state : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            {{-- Inward No --}}
                            <div class="col-md-4">
                                <label>Inward No <span class="text-danger">*</span></label>
                                <input type="text" readonly name="inward_no" id="inward_no"
                                    class="bg-light form-control @error('inward_no') is-invalid @enderror"
                                    value="{{ old('inward_no', '') }}">
                                @error('inward_no')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <input type="hidden" name="is_qc_required" id="is_qc_required" value="0">

                            {{-- Inward Date --}}
                            <div class="col-md-4">
                                <label>Inward Date <span class="text-danger">*</span></label>
                                <input type="date" name="inward_date"
                                    class="@error('inward_date') is-invalid @enderror form-control"
                                    value="{{ old('inward_date', date('Y-m-d')) }}" required>
                                @error('inward_date')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Transport By --}}
                            <div class="col-md-4">
                                <label>Transport By</label>
                                <select name="transport_by_id" id="transport_by_id"
                                    class="form-control @error('transport_by_id') is-invalid @enderror search_by_transport_by_id select2"
                                    data-append="search_by_transport_by_id"
                                    data-selectedTransportbyId="">
                                    <option value="" disabled
                                        {{ old('transport_by_id', '') ? '' : 'selected' }}>
                                        Select Transport By
                                    </option>
                                </select>
                                @error('transport_by_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Transporter Detail --}}
                            <div class="col-md-4 mt-3">
                                <label>Transporter Detail</label>
                                <select name="transport_details_id" id="transport_details_id"
                                    class="form-control @error('transport_details_id') is-invalid @enderror search_by_transport_details_id select2"
                                    data-append="search_by_transport_details_id"
                                    data-selectedTransportdetailsId=""
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('transport_details_id', '') ? '' : 'selected' }}>
                                        Select Transporter Detail
                                    </option>
                                </select>
                                @error('transport_details_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <!-- Right: Warehouse Select -->
                            <div class="col-md-4 mt-3">
                                <label>Select Warehouse <span class="text-danger">*</span></label>
                                <select name="warehouse_id" id="warehouse_id"
                                    class="form-control @error('warehouse_id') is-invalid @enderror search_by_warehouse select2"
                                    data-append="search_by_warehouse"
                                    data-selectedWarehouseId=""
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('warehouse_id', '') ? '' : 'selected' }}>
                                        Select Warehouse
                                    </option>
                                </select>
                                @error('warehouse_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    {{-- INWARD ITEMS --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-bordered" id="inward_items">
                                <thead>
                                    <tr>
                                        <th>Sr No</th>
                                        <th>Product Name</th>
                                        <th>Product Description</th>
                                        <th>Unit</th>
                                        <th style="min-width: 150px;">Wt/Pc</th>
                                        <th>GRS</th>
                                        <th style="min-width: 150px;">Purchase Order Remaining KG</th>
                                        <th style="min-width: 150px;">Purchase Order Remaining Qty</th>
                                        <th style="min-width: 150px;">Available Qty</th>
                                        <th style="min-width: 150px;">Available KG</th>
                                        <th style="min-width: 170px;">Inward Qty</th>
                                        <th>Delete</th>
                                        <th>Add Manual Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>

                            {{-- Remarks --}}
                            <div class="form-group mt-3">
                                <label>Remarks</label>
                                <textarea name="remarks" id="remarks" class="ckeditor_common_cls form-control" rows="2">{{ old('remarks', '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- SUBMIT BUTTON --}}



                    @php
                        $showButtons = true;
                    @endphp

                    @if ($showButtons)
                        <div class="text-center mb-3">
                            <button type="submit" class="btn btn-primary">Submit</button>

                            <button type="submit" name="submit_type" value="save_print" class="btn btn-primary mt-1 mb-1">
                                Submit & Print
                            </button>
                            <a href="{{ route($route . '.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    @endif


                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    {{-- @include('utils.getCompany') --}}
    {{-- @include('utils.getVendorSales') --}}
    @include('utils.getTransportby')
    @include('utils.getTransportDetails')
    @include('utils.getWarehouse')
    @if (isset($purchase_order_id))
        @include('utils.generateInwardNo')
    @endif

    <script>
        initializeCKEditor(".ckeditor_common_cls");

        $(document).ready(function() {
            function parseCommaFloat(val) {
                return parseFloat(String(val).replace(/,/g, '')) || 0;
            }

            $('#inward_items').hide();

            $(document).on('change', '#warehouse_id', function() {
                var warehouseId = $(this).val();
                var purchaseOrderId = $('#purchase_order_id').val();

                if (warehouseId != '') {
                    $.ajax({
                        url: "{{ route($route . '.get-purchase-order-items') }}",
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _token: "{{ csrf_token() }}",
                            purchase_order_id: purchaseOrderId,
                            warehouse_id: warehouseId,
                        },
                        success: function(response) {
                            var rows = '';
                            console.log("response 275", response);
                            if(response?.status !=true && response?.message){
                                toastr.error(response?.message);
                            }else if(response?.data){
                                $.each(response?.data, function(index, item) {
                                    let srNo = index + 1;

                                    rows += '<tr>';
                                    rows +=
                                        '<input type="hidden" name="purchase_order_item_id[]" value="' +
                                        item.id + '">';
                                    rows +=
                                        '<input type="hidden" name="hidden_remaining_quantity[]" class="hidden_remaining_quantity" value="' +
                                        item.remaining_quantity + '">';
                                    rows +=
                                        '<input type="hidden" name="product_id[]" value="' +
                                        item.product_id + '">';
                                    rows +=
                                        '<input type="hidden" name="product_variant_price_id[]" value="' +
                                        item.product_variant_price_id + '">';
                                    rows +=
                                        '<input type="hidden" name="grosse_per_qty[]" value="' +
                                        (item.grosse_per_qty || 1) + '">';
                                    rows += '<td class="text-center sr_no_txt">' + srNo + '</td>';
                                    rows +=
                                        '<td><input type="hidden" name="product_name[]" value="' +
                                        item.product_name + '"><label>' + item.product_name + '</label></td>';
                                    rows +=
                                        '<td><input type="text" name="product_description[]" class="form-control" value="' +
                                        (item.product_description || '') + '"></td>';
                                    rows +=
                                        '<td><input type="text" name="unit[]" class="form-control" value="' +
                                        item.unit + '" readonly></td>';
                                    rows += '<td><input type="text" step="any" name="product_weight[]" class="product_weight form-control" value="' + item.wt_pc + '"></td>';
                                    let grossePerQty = item.grosse_per_qty || 1;
                                    rows +=
                                        '<td><label class="grs_value">'+ parseInt(item.remaining_quantity / grossePerQty) +'</label>';
                                    rows +=
                                        '<td><input type="text" name="remaining_kg[]" class="remaining_kg bg-light form-control" value="' +
                                        item.remaining_kg + '" readonly></td>';
                                    rows +=
                                        '<td><input type="text" name="remaining_quantity[]" class="remaining_quantity bg-light form-control" value="' +
                                        item.remaining_quantity + '" readonly></td>';
                                    rows +=
                                        '<td><input type="number" name="available_quantity[]" class="available_quantity bg-light form-control" value="' +
                                        item.available_quantity + '" readonly></td>';
                                    rows +=
                                        '<td><input type="number" name="available_kg[]" class="available_kg bg-light form-control" value="' +
                                        item.available_kg + '" readonly></td>';
                                    rows +=
                                        '<td><input type="text" name="inward_quantity[]" max="' +
                                        item.remaining_quantity +
                                        '" class="inward_quantity form-control" value="' +
                                        item.remaining_quantity + '" onkeypress="return isDecimal(event)"></td>';
                                    rows +=
                                        '<td><button type="button" class="remove_inward_item btn btn-danger btn-sm">✖</button></td>';
                                    rows += '<td><a href="' + item.add_stock_url +
                                        '" target="_blank" type="button" class="btn btn-success btn-sm">ADD STOCK</a></td>';
                                    rows += '</tr>';
                                });
                                $('#inward_items tbody').html(rows);
                            }else{
                                toastr.error("Product not featch something want's wrong.");
                            }
                        }
                    });
                    $('#inward_items').show();
                }
            });

            $(document).on('click', '.remove_inward_item', function() {
                var rowCount = $('#inward_items tbody tr').length;
                if (rowCount > 1) {
                    $(this).closest('tr').remove();
                    $('#inward_items tbody tr').each(function(index){
                        $(this).find('.sr_no_txt').text(index + 1);
                    });
                } else {
                    toastr.error('At least one row is required.');
                }
            });

            $(document).on('input', '.inward_quantity, .product_weight', function() {
                var $row = $(this).closest('tr');
                var inwardQty = parseCommaFloat($row.find('.inward_quantity').val());
                var hiddenRemainingQty = parseCommaFloat($row.find('.hidden_remaining_quantity').val());
                var weight = parseCommaFloat($row.find('.product_weight').val());

                var currentKg = qtyToKgConvertJS(inwardQty, weight);
                var maxKg = qtyToKgConvertJS(hiddenRemainingQty, weight);


                if ($(this).hasClass('inward_quantity')) {
                    var orderQty = parseCommaFloat($row.find('.remaining_quantity').val());
                    if (inwardQty > orderQty) {
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }

                    let grossePerQty = $row.find('input[name="grosse_per_qty[]"]').val() || 1;
                    let grsValue = parseInt(inwardQty / grossePerQty);
                    $row.find('.grs_value').text(grsValue);
                } else {
                    // product_weight change logic
                    var remainingKg = parseCommaFloat($row.find('.remaining_kg').val());
                    var newRemainingQty = kgToQtyConvertJS(remainingKg, weight);
                    //$row.find('.remaining_quantity').val(newRemainingQty);
                    $row.find('.inward_quantity').val(newRemainingQty).attr('max', newRemainingQty);

                    let inwardQtyNew = parseCommaFloat($row.find('.inward_quantity').val());
                    let grossePerQty = $row.find('input[name="grosse_per_qty[]"]').val() || 1;
                    let grsValue = parseInt(inwardQtyNew / grossePerQty);
                    $row.find('.grs_value').text(grsValue);
                }
            });

            function qtyToKgConvertJS(qty, wt) {
                if (!wt || qty <= 0) return 0;
                let kg = (qty * wt) / 1000;
                let rounded = parseFloat(kg.toFixed(4));
                if (Math.abs(rounded - Math.round(rounded)) <= 0.005) {
                    return Math.round(rounded).toFixed(3);
                }
                return kg.toFixed(3);
            }

            function kgToQtyConvertJS(kg, wt) {
                if (!wt || kg <= 0) return 0;
                let qty = (kg * 1000) / wt;
                return Math.round(qty);
            }


            $('#form').on('submit', function(e) {
                var force = $(this).data('force-submit');
                var qc_selected = $(this).data('qc-selected');

                if (!qc_selected) {
                    e.preventDefault();
                    
                    Swal.fire({
                        title: 'Quality Control (QC)',
                        text: "Is QC required for this Inward?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'QC Required',
                        cancelButtonText: 'No QC',
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#is_qc_required').val('1');
                        } else if (result.dismiss === Swal.DismissReason.cancel) {
                            $('#is_qc_required').val('0');
                        } else {
                            return; // User closed modal without choice
                        }
                        
                        $(this).data('qc-selected', true);
                        $(this).submit();
                    });
                    return;
                }

                var isOver = false;
                $('.inward_quantity').each(function() {
                    var $row = $(this).closest('tr');
                    var inwardQty = parseCommaFloat($(this).val());
                    var hiddenRemainingQty = parseCommaFloat($row.find('.hidden_remaining_quantity').val());
                    if (inwardQty > hiddenRemainingQty) {
                        isOver = true;
                    }
                });

                if (isOver && !force) {
                    e.preventDefault();
                    toastr.remove();
                    var msg = 'Inward quantity is more than remaining quantity. Do you want to submit anyway?<br><br><button type="button" class="btn btn-sm btn-light confirm-yes">Yes</button> <button type="button" class="btn btn-sm btn-light confirm-no" style="margin-left:10px">No</button>';
                    
                    toastr.error(msg, 'Warning', {
                        closeButton: true,
                        progressBar: false,
                        timeOut: 0,
                        extendedTimeOut: 0,
                        tapToDismiss: false,
                        positionClass: 'toast-center-center'
                    });
                    
                    return;
                }

                var invalidFields = $('.is-invalid');
                if (force) {
                    invalidFields = invalidFields.not('.inward_quantity');
                }
                
                if (invalidFields.length > 0) {
                    e.preventDefault();
                    toastr.error('Please correct the invalid fields before submitting.');
                }
            });

            $(document).on('click', '.confirm-yes', function() {
                toastr.clear();
                $('#form').data('force-submit', true);
                $('#form').submit();
            });

            $(document).on('click', '.confirm-no', function() {
                toastr.remove();
            });
        });
    </script>
@endpush
