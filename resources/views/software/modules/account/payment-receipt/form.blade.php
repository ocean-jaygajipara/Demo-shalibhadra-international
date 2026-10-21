@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
@endphp
@section('page_leavel_style')
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> --}}
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css" />
@endsection
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
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">

                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">
                    @if (!$company_id)
                        <div class="col-md-4 col-sm-12 mb-3">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select name="company_id" id="company_id"
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
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif

                    {{-- Auto Generate Receipt No --}}
                    <div class="col-md-4 col-sm-4">
                        <label class="form-label">Receipt No</label>
                        <input type="text" class="form-control readonly-look" name="receipt_no" id="receipt_no"
                            placeholder="Enter Receipt No" readonly
                            value="{{ isset($edit) && $edit?->receipt_no ? $edit?->receipt_no : '' }}">
                    </div>

                    {{-- To Date --}}
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="date">Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datetimepicker @error('date') is-invalid @enderror"
                                name="date"
                                value="{{ old('date', isset($edit) && !empty($edit->date) ? date('d-m-Y', strtotime($edit->date)) : date('d-m-Y')) }}"
                                placeholder="DD-MM-YYYY" id="date" autocomplete="off" />
                            @error('date')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    {{-- Customer Type --}}
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Customer Type <span class="text-danger">*</span></label>
                            <select id="customer_type_id" name="customer_type_id"
                                data-selectedCustomerTypeId="{{ old('customer_type_id', $edit->customer_type_id ?? '') }}"
                                class="form-control select2  @error('customer_type_id') is-invalid @enderror search_by_customer_type">
                                <option value="">Select Customer Type</option>
                            </select>


                            @error('customer_type_id')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- Customer Name -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id"
                                class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                                data-append="search_by_customer"
                                data-selectedCustomerId="{{ old('customer_id', $edit->customer_id ?? '') }}"
                                autofocus>
                                <option value=""> Select Customer </option>
                            </select>
                            @error('customer_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <!-- Receipt Type -->
                    <div class="col-md-4 col-sm-4">
                        <label class="form-label">Receipt Type <span class="text-danger">*</span></label>
                        <select id="receipt_type" name="receipt_type"
                            class="form-select select2 @error('receipt_type') is-invalid @enderror" data-selectedReceiptTypeId="{{ old('receipt_type', $edit->receipt_type ?? '') }}" autofocus>
                            <option value="">Select Receipt Type</option>

                        </select>
                        @error('receipt_type')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>


                    <!-- Quotation Dropdown -->

                    <div class="col-md-4 col-sm-12 mb-3 dependent-field" id="quotation_dropdown" style="display: none;">
                        <label class="form-label">Quotation No <span class="text-danger">*</span></label>
                        <select name="quotation_id" id="quotation_id"
                            class="form-control select2 @error('quotation_id') is-invalid @enderror"
                            data-selectedquotationid="{{ old('quotation_id', $edit->quotation_id ?? '') }}" data-payment_receipt_id="{{ $edit->id ?? ''}}">
                            <option value="">Select Quotation</option>
                        </select>
                        @error('quotation_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>


                    <!-- Sales Order Dropdown -->
                    <div class="col-md-4 col-sm-12 mb-3 dependent-field" id="order_dropdown" style="display: none;">
                        <label class="form-label">Sales Order No <span class="text-danger">*</span></label>
                        <select name="sales_order_id" id="sales_order_id"
                            class="form-control select2 @error('sales_order_id') is-invalid @enderror"
                            data-selectedsalesorderid="{{ old('sales_order_id', $edit->sales_order_id ?? '') }}" data-payment_receipt_id="{{ $edit->id ?? ''}}">
                            <option value="">Select Sales Order</option>
                        </select>

                        @error('sales_order_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    {{-- Team Person --}}
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Team Person <span class="text-danger">*</span></label>
                            <select id="team_person_ids"
                                class="form-control search_by_team_person select2 @error('team_person_ids') is-invalid @enderror"
                                name="team_person_ids"
                                data-selectedids="{{ old('team_person_ids') ?? ($edit?->team_person_ids ?? '') }}">
                                <option value="">Select Team Person</option>
                            </select>
                            @error('team_person_ids')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Payment By --}}
                    <div class="col-md-4 col-sm-4">
                        <label class="form-label" for="plan">Payment By <span class="text-danger">*</span></label>
                        <select id="payment_by" class="form-select select2 @error('payment_by') is-invalid @enderror"
                            name="payment_by">
                            <option value=""> Select Payment By</option>
                            @foreach ($payment_by as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('payment_by', $edit->payment_by ?? '') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach

                        </select>
                        @error('payment_by')
                            <span class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Cheque No --}}
                    <div class="col-md-4 mb-3 col-sm-4 payment-field payment-cheque d-none">
                        <label class="form-label">Cheque No <span class="text-danger">*</span></label>
                        <input type="number" name="cheque_no" value="{{ old('cheque_no', $edit->cheque_no ?? '') }}"
                            class="form-control">
                    </div>

                    {{-- UPI No --}}
                    <div class="col-md-4 mb-3 col-sm-4 payment-field payment-upi d-none">
                        <label class="form-label">UPI No <span class="text-danger">*</span></label>
                        <input type="text" name="upi_no" value="{{ old('upi_no', $edit->upi_no ?? '') }}"
                            class="form-control">
                    </div>

                    {{-- Reason (Other) --}}
                    <div class="col-md-4 mb-3 col-sm-4 payment-field payment-other d-none">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="payment_reason" class="form-control" rows="2">{{ old('payment_reason', $edit->payment_reason ?? '') }}</textarea>
                    </div>

                    {{-- Payment Mode --}}
                    <div class="col-md-4 col-sm-4 mb-3">
                        <label class="form-label" for="plan">Payment Mode <span class="text-danger">*</span></label>
                        <select id="payment_mode" class="form-select select2 @error('payment_mode') is-invalid @enderror"
                            name="payment_mode">
                            <option value=""> Select Payment By</option>
                            @foreach ($payment_mode as $key => $value)
                                <option value="{{ $key }}"
                                    {{ old('payment_mode', $edit->payment_mode ?? '') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach

                        </select>
                        @error('payment_mode')
                            <span class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    {{-- Amount autofetch by Receipt type --}}
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" id="amount" name="amount"
                                value="{{ old('amount', $edit->amount ?? '') }}"
                                class="form-control @error('amount') is-invalid @enderror amount" step="0.01"
                                min="0">
                            <input type="hidden" id="max_allowed_amount" value="">

                            <small id="amount_error" class="text-danger d-none">Amount cannot be greater than
                                allowed.</small>

                            @error('amount')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    {{-- Remark --}}
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Remark</label>
                            <textarea rows="3" cols="3" class="form-control @error('remark') is-invalid @enderror" name="remark"
                                placeholder="Enter Leave Reason" id="remark">{{ old('remark', $edit->remark ?? '') }}</textarea>
                            @error('remark')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="divider">
                        <hr />
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js">
    </script>
    <script type="text/javascript">
        $(".datetimepicker").each(function() {
            $(this).datetimepicker({
                format: 'd-m-Y',
                closeOnDateSelect: true,
                maxDate: 0,
            });
        });
    </script>



    <script>
        $(document).ready(function() {
            const $amount = $('#amount');
            const $maxAllowed = $('#max_allowed_amount');
            const $amountError = $('#amount_error');
            const $salesOrderSelect = $('#sales_order_id');
            $salesOrderSelect.on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const remainingAmount = parseFloat(selectedOption.data('remaining'));

                if (!isNaN(remainingAmount)) {
                    const formattedAmount = remainingAmount.toFixed(2);
                    $amount.val(formattedAmount);
                    $maxAllowed.val(formattedAmount);
                    $amount.removeClass('is-invalid');
                    $amountError.addClass('d-none');
                } else {
                    $amount.val('');
                    $maxAllowed.val('');
                }
            });

            $amount.on('input', function() {
                const enteredAmount = parseFloat($(this).val());
                const maxAmount = parseFloat($maxAllowed.val());

                if (!isNaN(maxAmount) && enteredAmount > maxAmount) {
                    $(this).addClass('is-invalid');
                    $amountError.removeClass('d-none');
                } else {
                    $(this).removeClass('is-invalid');
                    $amountError.addClass('d-none');
                }
            });


            $amount.on('blur', function() {
                const enteredAmount = parseFloat($(this).val());
                const maxAmount = parseFloat($maxAllowed.val());

                if (!isNaN(maxAmount) && enteredAmount > maxAmount) {
                    $(this).val(maxAmount.toFixed(2));
                    $(this).removeClass('is-invalid');
                    $amountError.addClass('d-none');
                }
            });
        });
    </script>


    <script>
        $(document).ready(function() {
            const $amount = $('#amount');
            const $maxAllowed = $('#max_allowed_amount');
            const $amountError = $('#amount_error');
            const $quotationSelect = $('#quotation_id');


            $quotationSelect.on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const remainingAmount = parseFloat(selectedOption.data('remaining'));

                if (!isNaN(remainingAmount)) {
                    const formattedAmount = remainingAmount.toFixed(2);
                    $amount.val(formattedAmount);
                    $maxAllowed.val(formattedAmount);
                    $amount.removeClass('is-invalid');
                    $amountError.addClass('d-none');
                } else {
                    $amount.val('');
                    $maxAllowed.val('');
                }
            });


            $amount.on('input', function() {
                const enteredAmount = parseFloat($(this).val());
                const maxAmount = parseFloat($maxAllowed.val());

                if (!isNaN(maxAmount) && enteredAmount > maxAmount) {
                    $(this).addClass('is-invalid');
                    $amountError.removeClass('d-none');
                } else {
                    $(this).removeClass('is-invalid');
                    $amountError.addClass('d-none');
                }
            });


            $amount.on('blur', function() {
                const enteredAmount = parseFloat($(this).val());
                const maxAmount = parseFloat($maxAllowed.val());

                if (!isNaN(maxAmount) && enteredAmount > maxAmount) {
                    $(this).val(maxAmount.toFixed(2));
                    $(this).removeClass('is-invalid');
                    $amountError.addClass('d-none');
                }
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            const $quotationSelect = $('#quotation_id');
            const $orderSelect = $('#sales_order_id');
            const selectedQuotationId = $quotationSelect.data('selectedquotationid');
            const selectedOrderId = $orderSelect.data('selectedsalesorderid');
            const payment_receipt_id = $orderSelect.data('payment_receipt_id');

            function toggleDependentDropdowns() {
                let receiptType = $('#receipt_type').val();
                let companyId = $('#company_id').val();
                let customerId = $('#customer_id').val();

                let metaCompanyId = $('meta[name="company_id"]').attr('value');
                if (!companyId && metaCompanyId) {
                    companyId = metaCompanyId;
                }

                $('#quotation_dropdown').hide();
                $('#order_dropdown').hide();

                if (!receiptType || !companyId || !customerId) return;

                if (receiptType === 'quotation') {
                    loadCompanyQuotations(companyId, customerId, payment_receipt_id, selectedQuotationId);
                } else if (receiptType === 'order') {
                    loadCompanySalesOrders(companyId, customerId, payment_receipt_id, selectedOrderId);
                }
            }


            function loadCompanyQuotations(companyId, customerId, payment_receipt_id = null, selectedId = null) {
                $.ajax({
                    url: '{{ route('payment-receipt.get-quotation-dropdown') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        company_id: companyId,
                        customer_id: customerId,
                        quotation_selected_id: selectedId,
                        payment_receipt_id: payment_receipt_id,
                    },
                    success: function(response) {

                            let options = '<option value="">Select Quotation</option>';
                            let remainingMap = {};

                            if (response.status && response.data.length > 0) {
                                response.data.forEach(item => {
                                    const isSelected = selectedId == item.id ? 'selected' : '';
                                    options +=
                                        `<option value="${item.id}" data-remaining="${item.remaining_amount}" ${isSelected}>${item.text}</option>`;
                                    remainingMap[item.id] = item.remaining_amount;
                                });
                            }

                            $quotationSelect.html(options).select2();
                            $('#quotation_dropdown').show();

                            // If a quotation was selected, trigger change
                            if (selectedId && remainingMap[selectedId]) {
                                $('#quotation_id').val(selectedId).trigger('change');
                            }

                            if (selectedId) {
                                $quotationSelect.val(selectedId).trigger('change');
                                $quotationSelect.select2();
                                $quotationSelect.next('.select2-container').css({
                                    'pointer-events': 'none',
                                    'background-color': '#f9f9f9',
                                    'opacity': '0.9'
                                });
                                $('#quotation_dropdown').show();
                            } else {
                                $quotationSelect.prop('disabled', false).select2();
                                $quotationSelect.next('.select2-container').css({
                                    'pointer-events': '',
                                    'background-color': '',
                                    'opacity': ''
                                });
                                $('#quotation_dropdown').show();
                            }
                    },
                    error: function() {
                        alert('Failed to load quotations.');
                    }
                });
            }


            function loadCompanySalesOrders(companyId, customerId, payment_receipt_id = null, selectedId = null) {
                $.ajax({
                    url: '{{ route('payment-receipt.get-sales-order-dropdown') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        company_id: companyId,
                        customer_id: customerId,
                        order_selected_id: selectedId,
                        payment_receipt_id: payment_receipt_id,
                    },
                    success: function(response) {

                            let options = '<option value="">Select Sales Order</option>';
                            let remainingMap = {};

                            if (response.status && response.data.length > 0) {
                                response.data.forEach(item => {
                                    const isSelected = selectedId == item.id ? 'selected' : '';
                                    options +=
                                        `<option value="${item.id}" data-remaining="${item.remaining_amount}" ${isSelected}>${item.text}</option>`;
                                    remainingMap[item.id] = item.remaining_amount;
                                });
                            }

                            $('#sales_order_id').html(options).select2();
                            $('#order_dropdown').show();

                            if (selectedId && remainingMap[selectedId]) {
                                $('#sales_order_id').val(selectedId).trigger('change');
                            }

                            if (selectedId) {
                                $('#sales_order_id').val(selectedId).trigger('change');
                                $('#sales_order_id').select2();
                                $('#sales_order_id').next('.select2-container').css({
                                    'pointer-events': 'none',
                                    'background-color': '#f9f9f9',
                                    'opacity': '0.9'
                                });
                                $('#order_dropdown').show();
                            } else {
                                $('#sales_order_id').prop('disabled', false).select2();
                                $('#sales_order_id').next('.select2-container').css({
                                    'pointer-events': '',
                                    'background-color': '',
                                    'opacity': ''
                                });
                                $('#order_dropdown').show();
                            }

                    },
                    error: function() {
                        alert('Failed to load sales orders.');
                    }
                });
            }

            $('#receipt_type, #company_id, #customer_id').on('change', function() {
                toggleDependentDropdowns();
            });
        });
    </script>


    <script>
        function togglePaymentFields(paymentBy) {
            $('.payment-field').addClass('d-none');
            if (paymentBy === 'cheque') {
                $('.payment-cheque').removeClass('d-none');
            } else if (paymentBy === 'upi') {
                $('.payment-upi').removeClass('d-none');
            } else if (paymentBy === 'other') {
                $('.payment-other').removeClass('d-none');
            }
        }

        $(document).ready(function() {
            togglePaymentFields($('#payment_by').val());
            $('#payment_by').on('change', function() {
                togglePaymentFields($(this).val());
            });
        });
    </script>



    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getPaymentReceiptType')
    @include('utils.getTeamPerson')
    @include('utils.getCompanyByCustomerType')
    @include('utils.getCustomer')
    @include('utils.generateReceiptNo')
@endpush
