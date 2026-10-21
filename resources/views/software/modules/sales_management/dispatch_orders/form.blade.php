@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $i = 0;
@endphp
@section('title', $page_title)

@section('content')
<style>
    .ck-editor__editable_inline {
        min-height: 150px;
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

            @if ($errors->any())
    <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
            <form action="{{ route($route . '.store') }}" method="POST" id="form">
                <input type="hidden" name="sales_order_id" id="sales_order_id" value="{{ isset($sales_order_id) && $sales_order_id ? $sales_order_id : '' }}" />
                <input type="hidden" name="company_id" value="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}" />
                <input type="hidden" name="customer_id" value="{{ isset($edit) && $edit?->customer_id ? $edit?->customer_id : '' }}" />
                <input type="hidden" name="customer_name" id="customer_name" value="{{ isset($edit) && $edit?->customer_id ? $edit?->customer->customer_name : '' }}" />
                <input type="hidden" name="contact_person_name" id="contact_person_name" value="{{ isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : '' }}" />
                <input type="hidden" name="phone" id="phone" value="{{ isset($edit) && $edit?->phone ? $edit?->phone : '' }}" />
                <input type="hidden" name="state" id="state" value="{{ isset($edit) && $edit?->state ? $edit?->state : '' }}" />
                <input type="hidden" name="address" id="address" value="{{ isset($edit) && $edit?->address ? $edit?->address : '' }}" />
                <input type="hidden" name="price_list_id" id="price_list_id" value="{{ isset($edit) && $edit?->price_list_id ? $edit?->price_list_id : '' }}" />
                <input type="hidden" name="stock_check" id="stock_check" value="{{ isset($edit) && $edit?->company->stock_check ? $edit?->company->stock_check : '' }}" />


                @csrf

                {{-- DISPATCH DETAIL --}}
                <div class=" mb-3">
                    <div class="card-body row">
                        {{-- Company --}}

                        <div class="row g-3">
                            @if (!$company_id)
                            <div class="col-md-3">
                                <label>Select Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id" disabled
                                    class="form-control @error('company_id') is-invalid @enderror search_by_company select2"
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


                            {{-- Dispatch No --}}
                            <div class="col-md-3 mt-3">
                                <label>Dispatch No <span class="text-danger">*</span></label>
                                <input type="text" readonly name="dispatch_no" id="dispatch_no" class="bg-light form-control @error('dispatch_no') is-invalid @enderror" value="{{ old('dispatch_no', $edit->dispatch_no ?? '') }}">
                                @error('dispatch_no')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Sales Order No --}}
                            <div class="col-md-3 mt-3">
                                <label>Sales Order No <span class="text-danger">*</span></label>
                                <input type="text" readonly name="sales_order_no" id="sales_order_no" class="bg-light form-control @error('sales_order_no') is-invalid @enderror" value="{{ old('sales_order_no', $edit->sales_order_no ?? '') }}">
                                @error('sales_order_no')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Dispatch Date --}}
                            <div class="col-md-3">
                                <label>Dispatch Date <span class="text-danger">*</span></label>
                                <input type="date" name="dispatch_date" class="@error('dispatch_date') is-invalid @enderror form-control" value="{{ old('dispatch_date', $edit->dispatch_date ?? date('Y-m-d')) }}" required>
                                @error('dispatch_date')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                        </div>


                        <div class="row g-3">
                            {{-- Expected Dispatch Date --}}
                            <div class="col-md-3">
                                <label>Expected Dispatch Date</label>
                                <input type="date" name="expected_dispatch_date" class="@error('expected_dispatch_date') is-invalid @enderror form-control" value="{{ old('expected_dispatch_date', $edit->expected_dispatch_date ?? '') }}">
                                @error('expected_dispatch_date')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Transport By --}}
                            <div class="col-md-3 mt-3">
                                <label>Transport By</label>
                                <select name="transport_by_id" id="transport_by_id"
                                    class="form-control @error('transport_by_id') is-invalid @enderror search_by_transport_by_id select2"
                                    data-append="search_by_transport_by_id"
                                    data-selectedTransportbyId="{{ isset($edit) && $edit?->transport_by_id ? $edit?->transport_by_id : '' }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('transport_by_id', $edit->transport_by_id ?? '') ? '' : 'selected' }}>
                                        Select Transport By
                                    </option>
                                </select>
                                @error('transport_by_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            {{-- Transporter Detail --}}
                            <div class="col-md-3 mt-3">
                                <label>Transporter Detail</label>
                                <select name="transport_details_id" id="transport_details_id"
                                    class="form-control @error('transport_details_id') is-invalid @enderror search_by_transport_details_id select2"
                                    data-append="search_by_transport_details_id"
                                    data-selectedTransportdetailsId="{{ isset($edit) && $edit?->transport_details_id ? $edit?->transport_details_id : '' }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('transport_details_id', $edit->transport_details_id ?? '') ? '' : 'selected' }}>
                                        Select Transporter Detail
                                    </option>
                                </select>
                                @error('transport_details_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <!-- Right: Warehouse Select -->
                            <div class="col-md-3 mt-3">
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
                                @error('warehouse_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>

                        </div>



                        {{-- Customer --}}
                        {{-- <div class="col-md-3">
                            <label>Select Customer <span class="text-danger">*</span></label>
                            <select disabled id="customer_id"
                                class="bg-light form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                                data-append="search_by_customer"
                                data-selectedCustomerId="{{ isset($edit) && $edit?->customer_id ? $edit?->customer_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('customer_id', $edit->customer_id ?? '') ? '' : 'selected' }}>
                                    Select Customer
                                </option>
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div> --}}


                        <!-- Left: Customer Details -->
                        <div class="col-md-6 mt-4">
                            <div class="mt-3">
                                <!-- Customer -->
                                <div class="col-8 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Customer:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->customer ? $edit?->customer->customer_name : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                                <!-- Name -->
                                <div class="col-8 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Name:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->contact_person_name ? $edit?->contact_person_name : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                                <!-- Phone -->
                                <div class="col-8 mt-2 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Phone:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->phone ? $edit?->phone : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                                <!-- Address -->
                                <div class="col-8 mt-2 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Address:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->address ? $edit?->address : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                                <!-- State -->
                                <div class="col-8 mt-2 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>State:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->state ? $edit?->state : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                                <!-- Pricelist -->
                                <div class="col-8 mt-2 ">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Pricelist:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit?->price_list ? $edit?->price_list->name : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- right: shipping billing Details -->
                        <div class="col-md-6 mt-4">
                            <div class="mt-3">

                                <div class="col-8 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Shipping Address:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit ? $edit?->shipping_address : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>

                                <div class="col-8 mb-2">
                                    <div class="row">
                                        <div class="col-4"><label><strong>Billing Address:</strong></label></div>
                                        <div class="col-8"><p class="mb-0"><strong>{{ isset($edit) && $edit ? $edit?->billing_address : 'N/A' }}</strong></p></div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>

                    {{-- DISPATCH ITEMS --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-bordered" id="dispatch_items">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Product Description</th>
                                        <th>Unit</th>
                                        <th>Order Remaining Qty</th>
                                        <th>Available Qty</th>
                                        <th>Dispatch Qty</th>
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
                                <textarea name="remarks" id="remarks" class="ckeditor_common_cls form-control" rows="2">{{ old('remarks', $edit->remarks ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- SUBMIT BUTTON --}}
                    <div class="text-center mb-3">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    @if (!$company_id)
    @include('utils.getCompany')
    @endif
    @include('utils.getCustomerSales')
    @include('utils.getTransportby')
    @include('utils.getTransportDetails')
    @include('utils.getWarehouse')
    @if(isset($sales_order_id)) @include('utils.generateDispatchNo') @endif

    <script>
        initializeCKEditor(".ckeditor_common_cls");

        $(document).ready(function() {
           $('#dispatch_items').hide();

           $(document).on('change', '#warehouse_id', function() {
                var warehouseId = $(this).val();
                var salesOrderId = $('#sales_order_id').val();

                $.ajax({
                    url: "{{ route($route.'.get-sales-order-items') }}",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                       sales_order_id: salesOrderId,
                       warehouse_id: warehouseId,
                       _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        var rows = '';
                        $.each(response, function(index, item) {
                            let productName = item.product_name;
                            productName = productName.replaceAll('"', '\'');
                            rows += '<tr>';
                            rows += '<input type="hidden" name="sales_order_item_id[]" value="' + item.id + '">';
                            rows += '<input type="hidden" name="product_id[]" value="' + item.product_id + '">';
                            rows += '<input type="hidden" name="product_variant_price_id[]" value="' + item.product_variant_price_id + '">';
                            // rows += '<td><input type="text" name="product_name[]" class="form-control" value="' + productName  + '" readonly></td>';
                            rows += '<td><label class="product_name_txt">'+productName+'</label><input type="hidden" name="product_name[]" value="' + productName  + '" readonly></td>';

                            rows += '<td><input type="text" name="product_description[]" class="form-control" value="' + (item.product_description || '') + '"></td>';
                            rows += '<td><input type="text" name="unit[]" class="form-control" value="' + item.unit + '" readonly></td>';
                            rows += '<td><input type="number" name="remaining_quantity[]" class="remaining_quantity bg-light form-control" value="' + item.remaining_quantity + '" readonly></td>';
                            rows += '<td><input type="number" name="available_quantity[]" class="available_quantity bg-light form-control ' + (item.available_quantity <= 0 ? 'is-invalid' : '') + '" value="' + item.available_quantity + '" readonly></td>';
                            rows += '<td><input type="number" name="dispatch_quantity[]" max="' + item.remaining_quantity + '" class="dispatch_quantity form-control" value="' + item.remaining_quantity + '"></td>';
                            rows += '<td><button type="button" class="remove_dispatch_item btn btn-danger btn-sm">✖</button></td>';
                            rows += '<td><a href="' + item.add_stock_url + '" target="_blank" type="button" class="btn btn-success btn-sm">ADD STOCK</a></td>';
                            rows += '</tr>';
                        });
                        $('#dispatch_items tbody').html(rows);
                    }
                });
                $('#dispatch_items').show();
            });

            $(document).on('click', '.remove_dispatch_item', function() {
                var rowCount = $('#dispatch_items tbody tr').length;
                if (rowCount > 1) {
                    $(this).closest('tr').remove();
                } else {
                    toastr.error('At least one row is required.');
                }
            });

            $(document).on('input', '.dispatch_quantity', function() {
                var $row = $(this).closest('tr');
                var dispatchQty = parseFloat($(this).val()) || 0;
                var orderQty = parseFloat($row.find('.remaining_quantity').val()) || 0;
                var availableQty = parseFloat($row.find('.available_quantity').val()) || 0;
                var stock_check = $('#stock_check');
                if(stock_check == 'yes'){
                    if (dispatchQty > orderQty) {
                        $(this).addClass('is-invalid');
                        toastr.error('Dispatch quantity cannot be greater than order remaining quantity.');
                    } else if (dispatchQty > availableQty) {
                        $(this).addClass('is-invalid');
                        toastr.error('Dispatch quantity cannot be greater than available quantity.');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                }
            });

           $('#form').on('submit', function(e) {
                const $invalidFields = $('.is-invalid');
                const $invalidAvailableQty = $('.available_quantity.is-invalid');
                var stock_check = $('#stock_check');
                if(stock_check == 'yes'){
                    if ($invalidFields.length > 0) {
                        e.preventDefault();

                        if ($invalidAvailableQty.length > 0) {
                            toastr.error('Stock is not available. please add stock.');
                        } else {
                            toastr.error('Please correct the invalid fields before submitting.');
                        }
                    }
                }
            });
        });
    </script>
@endpush
