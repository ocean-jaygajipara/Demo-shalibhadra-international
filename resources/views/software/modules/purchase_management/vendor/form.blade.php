@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
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
                        <div class="col-md-4 col-sm-12 mb-2">
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

                    <!-- Vendor Code -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Vendor Code <span class="text-danger">*</span></label>
                            <input type="text" class="bg-light form-control @error('vendor_code') is-invalid @enderror"
                                name="vendor_code" readonly id="vendor_code"
                                value="{{ isset($edit) ? $edit->vendor_code : (isset($vendorCode) ? $vendorCode : old('vendor_code')) }}"
                                placeholder="Enter Vendor Code">
                            @error('vendor_code')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Vendor Name -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('vendor_name') is-invalid @enderror"
                                name="vendor_name" value="{{ isset($edit) ? $edit->vendor_name : old('vendor_name') }}"
                                placeholder="Enter Vendor Name">
                            @error('vendor_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <!-- Email ID -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Email ID</label>
                            <input type="email" class="form-control @error('email_id') is-invalid @enderror"
                                name="email_id" value="{{ isset($edit) ? $edit->email_id : old('email_id') }}"
                                placeholder="Enter Email ID">
                            @error('email_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Mobile Number </label>
                            <input type="text" id="mobile_number" onkeypress="return isNumber(event)"
                                class="form-control @error('mobile_number') is-invalid @enderror" name="mobile_number"
                                value="{{ isset($edit) ? $edit->mobile_number : old('mobile_number') }}"
                                placeholder="Enter Mobile Number">
                            @error('mobile_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Whatsapp Number -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">WhatsApp No </label>
                            <input class="form-check-input checkphone" type="checkbox" value="" id="defaultCheck1">
                            <label class="form-check-label checkphone" for="defaultCheck1"> Same as mobile </label>
                            <input id="whatsapp_number" type="text"
                                class="form-control @error('whatsapp_number') is-invalid @enderror" name="whatsapp_number"
                                value="{{ old('whatsapp_number', $edit->whatsapp_number ?? '') }}"
                                autocomplete="whatsapp_number" placeholder="WhatsApp No" onkeypress="return isNumber(event)"
                                maxlength="10">
                            @error('whatsapp_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">PAN Number</label>
                            <input type="text" id="pan_number" name="pan_number"
                                class="form-control @error('pan_number') is-invalid @enderror"
                                value="{{ old('pan_number', $edit->pan_number ?? '') }}"
                                placeholder="Enter PAN Number">
                            @error('pan_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">UAN Number</label>
                            <input type="text" id="uan_number" name="uan_number"
                                class="form-control @error('uan_number') is-invalid @enderror"
                                value="{{ old('uan_number', $edit->uan_number ?? '') }}"
                                placeholder="Enter UAN Number">
                            @error('uan_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                     <!-- GST Number -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">GST Number <span class="text-danger"></span></label>
                            <input type="text" class="form-control @error('gst_number') @enderror" name="gst_number"
                                value="{{ isset($edit) ? $edit->gst_number : old('gst_number') }}"
                                placeholder="Enter GST number">
                            @error('gst_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <!-- Country -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Select Country <span class="text-danger">*</span></label>
                            <select name="country_id" id="country_id"
                                class="form-control @error('country_id') is-invalid @enderror search_by_country select2"
                                data-append="search_by_country" data-filterByStatus="active"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : '' }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('country_id', $edit->country_id ?? '') ? '' : 'selected' }}>
                                    Select Country
                                </option>
                            </select>
                            @error('country_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- State -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="state_id">Select State <span
                                    class="text-danger">*</span></label>
                            <select name="state_id" id="state_id"
                                class="form-control @error('state_id') is-invalid @enderror search_by_state select2"
                                data-append="search_by_state"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : old('state_id') }}"
                                data-selectedCityId="{{ isset($edit) && $edit?->city_id ? $edit?->city_id : old('city_id') }}">

                                <option value="" disabled
                                    {{ old('state_id', $edit->state_id ?? '') ? '' : 'selected' }}>
                                    Select State
                                </option>
                            </select>
                            @error('state_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- City -->
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select City</label>
                            <select id="city_id" name="city_id"
                                class="form-control @error('city_id') is-invalid @enderror search_by_city select2"
                                data-append="search_by_city"
                                data-selectedCityId="{{ isset($edit) && $edit?->city_id ? $edit?->city_id : old('city_id') }}">
                                <option value="" disabled
                                    {{ old('city_id', $edit->city_id ?? '') ? '' : 'selected' }}>
                                    Select City
                                </option>
                            </select>
                            @error('city_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Area</label>
                            <select id="area_id" name="area_id"
                                class="form-control @error('area_id') is-invalid @enderror search_by_area select2"
                                data-append="search_by_area"
                                data-selectedAreaId="{{ isset($edit) && $edit?->area_id ? $edit?->area_id : old('area_id') }}">
                                <option value="" disabled
                                    {{ old('area_id', $edit->area_id ?? '') ? '' : 'selected' }}>
                                    Select Area
                                </option>
                                <!-- Add area options dynamically -->
                            </select>
                            @error('area_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Pincode -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Pincode</label>
                            <input type="text" class="form-control @error('pincode') is-invalid @enderror"
                                name="pincode" value="{{ isset($edit) ? $edit->pincode : old('pincode') }}"
                                placeholder="Enter Pincode" maxlength="6" minlength="6" pattern="\d{6}"
                                title="Please enter exactly 6 digits.">
                            @error('pincode')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Address</label><span class="text-danger">*</span>
                            <textarea id="address" class="form-control autosize @error('address') is-invalid @enderror" name="address"
                                rows="1" placeholder="Address" autocomplete="address">{{ old('address', $edit->address ?? old('address')) }}</textarea>
                            @error('address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    {{-- <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <textarea id="shipping_address" rows="1"
                                class="form-control autosize @error('shipping_address') is-invalid @enderror" name="shipping_address"
                                placeholder="Enter Shipping Address">{{ isset($edit) ? $edit->shipping_address : old('shipping_address') }}</textarea>
                            @error('shipping_address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div> --}}

                    <!-- Billing Address -->
                    {{-- <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Billing Address</label>
                            <textarea id="billing_address" rows="1"
                                class="form-control autosize @error('billing_address') is-invalid @enderror" name="billing_address"
                                placeholder="Enter Billing Address">{{ isset($edit) ? $edit->billing_address : old('billing_address') }}</textarea>
                            @error('billing_address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div> --}}



                    {{-- <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Birth Date</label>
                            <input type="text" id="birth_date_display"
                                class="form-control @error('birth_date') is-invalid @enderror"
                                value="{{ old('birth_date', isset($edit->birth_date) ? date('d-m-Y', strtotime($edit->birth_date)) : '') }}"
                                placeholder="DD-MM-YYYY" readonly />

                            <input type="hidden" name="birth_date" id="birth_date"
                                value="{{ old('birth_date', isset($edit->birth_date) ? date('Y-m-d', strtotime($edit->birth_date)) : '') }}" />

                            @error('birth_date')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div> --}}

                    <div class="col-md-4 col-sm-12 mb-3">
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>



    @include('utils.getCompany')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
    @include('utils.getAreaByCity')
    @if (!isset($edit))
        @include('utils.generateVendorNo')
    @endif
    <script>
        flatpickr("#birth_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('birth_date', isset($edit->birth_date) ? date('d-m-Y', strtotime($edit->birth_date)) : '') }}",
            maxDate: "{{ date('Y-m-d') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('birth_date').value = ymd;
            }
        });
    </script>

    <script>
        document.getElementById('address').addEventListener('input', function() {
            var addressValue = this.value;
            document.getElementById('shipping_address').value = addressValue;
            document.getElementById('billing_address').value = addressValue;
        });

        $(document).ready(function() {
            $(".checkphone").click(function() {
                if ($(this).is(":checked")) {
                    var val = $("#mobile_number").val();
                    $('#whatsapp_number').val(val);
                } else {
                    $('#whatsapp_number').val('');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            autosize($('.autosize'));
        });
    </script>
@endpush
