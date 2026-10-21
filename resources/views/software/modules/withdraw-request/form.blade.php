@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

@endphp
@section('title', $page_title)

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

                    {{-- Company --}}
                    @if (!$company_id)
                        <div class="col-md-3">
                            <label class="form-label">Select Company</label>
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
                        <label class="form-label">Search Customer</label>
                        <select name="customer_id" id="customer_id"
                            class="form-control @error('customer_id') is-invalid @enderror search_by_customer select2"
                            data-append="search_by_customer"
                            data-selectedCustomerId="{{ isset($edit) && $edit?->customer_id ? $edit?->customer_id : old('customer_id') }}"
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

                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input type="text" name="amount" id="amount"
                            class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount', $edit->amount ?? old('amount')) }}" placeholder="Enter Amount"
                            data-min="{{ $minAmount ?? 0 }}" data-max="{{ $maxAmount ?? 999999 }}">
                        @error('amount')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                    <script>
                        $(document).ready(function() {
                            $('#amount').on('blur', function() {
                                let amount = parseFloat($(this).val());
                                let minAmount = parseFloat($(this).data('min'));
                                let maxAmount = parseFloat($(this).data('max'));

                                if (isNaN(amount)) {
                                    toastr.error('Please enter a valid number.');
                                    return;
                                }

                                if (amount < minAmount) {
                                    toastr.error('Minimum withdrawal amount is ₹' + minAmount);
                                } else if (amount > maxAmount) {
                                    toastr.error('Maximum withdrawal amount is ₹' + maxAmount);
                                }
                            });
                        });
                    </script>


                    <div class="col-md-3">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="attachment" id="attachment"
                            class="form-control @error('attachment') is-invalid @enderror">
                        @error('attachment')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>




                    {{-- Divider --}}
                    <div class="divider">
                        <hr />
                    </div>

                    {{-- Submit Buttons --}}
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
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getCustomerSales')

    <script>
        $(document).ready(function() {
            function toggleFields() {
                const status = $('#withdraw_request_status').val();
                if (status === 'Approve') {
                    $('#image_div').show();
                    $('#reason_div').hide();
                    $('#reason').removeAttr('required');
                } else if (status === 'Reject') {
                    $('#reason_div').show();
                    $('#image_div').hide();
                    $('#image').removeAttr('required');
                }
            }

            // Initial toggle on page load
            toggleFields();

            // Toggle on change
            $('#withdraw_request_status').change(function() {
                toggleFields();
            });
        });
    </script>


@endpush
