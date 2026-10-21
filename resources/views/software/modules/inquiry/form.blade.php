@extends('software.layout.app')

@php
    $i = 0;

    $now = \Carbon\Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d\TH:i');

    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;

    if (!isset($edit)) {
        $edit = isset($convertToInquiry) ? $convertToInquiry : null;
    }

@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css" />

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
            <form id="inquiryForm"
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @if (isset($edit) && $edit?->id)
                    @method('PUT')
                @endif

                <div class="row">
                    @if (!$company_id)
                        <div class="col-md-3 mb-2 col-sm-12">
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

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Inquiry No <span class="text-danger">*</span></label>
                            <input id="inquiry_no" type="text"
                                class="readonly-look form-control @error('inquiry_no') is-invalid @enderror"
                                name="inquiry_no" value="{{ old('inquiry_no', $edit->inquiry_no ?? '') }}"
                                autocomplete="inquiry_no" placeholder="Inquiry No">
                            @error('inquiry_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Inquiry Date <span class="text-danger">*</span></label>
                            <input id="inquiry_date" type="text"
                                class="form-control @error('inquiry_date') is-invalid @enderror" name="inquiry_date"
                                value="{{ old('inquiry_date', isset($edit->inquiry_date) ? \Carbon\Carbon::parse($edit->inquiry_date)->format('d-m-Y') : now()->format('d-m-Y')) }}"
                                placeholder="Inquiry Date" autocomplete="off">

                            @error('inquiry_date')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input id="customer_name" type="text"
                                class="form-control @error('customer_name') is-invalid @enderror" name="customer_name"
                                value="{{ old('customer_name', $edit?->customer_name ?? '') }}"
                                autocomplete="customer_name" placeholder="Customer Name"
                                onkeypress="return isAlphanumeric(event)">
                            @error('customer_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Contact Person Name</label>
                            <input id="contact_person_name" type="text"
                                class="form-control @error('contact_person_name') is-invalid @enderror"
                                name="contact_person_name"
                                value="{{ old('contact_person_name', $edit->contact_person_name ?? '') }}"
                                onkeypress="return isAlphanumeric(event)" autocomplete="contact_person_name"
                                placeholder="Contact Person Name">
                            @error('contact_person_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Mobile No <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input id="mobile_no" type="tel"
                                    class="form-control @error('mobile_no') is-invalid @enderror" name="mobile_no"
                                    value="{{ old('mobile_no', $edit->mobile_no ?? '') }}" autocomplete="mobile_no"
                                    placeholder="Mobile No" onkeypress="return isNumber(event)"
                                    data-mobile-min="{{ $mobile_min }}" data-mobile-max="{{ $mobile_max }}"
                                    minlength="{{ $mobile_min }}" maxlength="{{ $mobile_max }}">


                                <a class="btn btn-default d-none show-duplicate-inquiry">DUPLICATE</a>
                            </div>
                            @error('mobile_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror

                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">WhatsApp No </label>
                            <input class="form-check-input checkphone" type="checkbox" value=""
                                id="defaultCheck1">
                            <label class="form-check-label checkphone" for="defaultCheck1"> Same as mobile </label>
                            <input id="whatsapp_no" type="text"
                                class="form-control @error('whatsapp_no') is-invalid @enderror" name="whatsapp_no"
                                value="{{ old('whatsapp_no', $edit->whatsapp_no ?? '') }}" autocomplete="whatsapp_no"
                                placeholder="WhatsApp No" onkeypress="return isNumber(event)"
                                data-mobile-min="{{ $mobile_min }}" data-mobile-max="{{ $mobile_max }}"
                                minlength="{{ $mobile_min }}" maxlength="{{ $mobile_max }}">

                            @error('whatsapp_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                    value="{{ old('email', $edit->email ?? '') }}" placeholder="Email">

                                </a>
                            </div>
                            <span class="invalid-feedback d-none" id="email-error">
                                <strong>Please enter a valid email address.</strong>
                            </span>
                            @error('email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input id="website" type="text"
                                class="form-control @error('website') is-invalid @enderror" name="website"
                                value="{{ old('website', $edit->website ?? '') }}" autocomplete="website"
                                placeholder="Website">
                            @error('website')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Inquiry Status <span class="text-danger">*</span></label>
                            <select name="inquiry_status_id" id="inquiry_status_id"
                                class="form-control @error('inquiry_status_id') is-invalid @enderror search_by_inquiry_status select2"
                                data-append="search_by_inquiry_status"
                                data-selectedInquiryStatusId="{{ old('inquiry_status_id', $edit->inquiry_status_id ?? ($defaultInquiryStatus->id ?? '')) }}"
                                data-setIsDefault="yes">

                                <option value="" disabled
                                    {{ old('inquiry_status_id', $edit->inquiry_status_id ?? '') ? '' : 'selected' }}>
                                    Select Inquiry Status
                                </option>
                            </select>
                            @error('inquiry_status_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Followup Status <span class="text-danger">*</span></label>

                            <select name="followup_status_id" id="followup_status_id"
                                class="form-control @error('followup_status_id') is-invalid @enderror search_by_followup_status select2"
                                data-append="search_by_followup_status"
                                data-selectedFollowupStatusId="{{ old('followup_status_id', $edit->followup_status_id ?? ($defaultFollowupStatus->id ?? '')) }}"
                                data-setIsDefault="yes">

                                <option value="" disabled
                                    {{ old('followup_status_id', $edit->followup_status_id ?? '') ? '' : 'selected' }}>
                                    Select Followup Status
                                </option>
                            </select>

                            @error('followup_status_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Source of Inquiry </label>
                            <select name="source_of_inquiry_id" id="source_of_inquiry_id"
                                class="form-control @error('source_of_inquiry_id') is-invalid @enderror search_by_source_of_inquiry select2"
                                data-append="search_by_source_of_inquiry"
                                data-selectedSourceOfInquiryId="{{ old('source_of_inquiry_id', $edit?->source_of_inquiry_id ?? '') }}">
                                <option value="" disabled
                                    {{ old('source_of_inquiry_id', $edit->source_of_inquiry_id ?? '') ? '' : 'selected' }}>
                                    Select Source of Inquiry
                                </option>
                            </select>
                            @error('source_of_inquiry_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea id="address" class="form-control autosize @error('address') is-invalid @enderror" name="address"
                                rows="1" placeholder="Address" autocomplete="address">{{ old('address', $edit->address ?? '') }}</textarea>
                            @error('address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea id="address" class="form-control autosize @error('address') is-invalid @enderror" name="address"
                                placeholder="Address" autocomplete="address">{{ old('address', $edit->address ?? '') }}</textarea>
                            @error('address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div> --}}

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Pincode</label>
                            <input type="text" id="pincode"
                                class="form-control @error('pincode') is-invalid @enderror" name="pincode"
                                placeholder="Pincode" maxlength="6" pattern="\d{6}"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,6)"
                                value="{{ old('pincode', $edit->pincode ?? '') }}">

                            @error('pincode')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-12  mb-2">
                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <select name="country_id" id="country_id"
                                class="form-control @error('country_id') is-invalid @enderror search_by_country select2"
                                data-append="search_by_country" data-filterByStatus="active"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : old('state_id') }}">
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <select name="state_id" id="state"
                                class="form-control @error('state_id') is-invalid @enderror search_by_state select2"
                                data-append="search_by_state"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : old('state_id') }}">

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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <select id="city_id" name="city_id"
                                class="form-control @error('city_id') is-invalid @enderror search_by_city select2"
                                data-append="search_by_city"
                                data-selectedcityid="{{ old('city_id', $edit->city_id ?? old('city_id')) }}">
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Area</label>
                            <select name="area_id" id="area_id"
                                class="form-control @error('area_id') is-invalid @enderror search_by_area select2"
                                data-append="search_by_area"
                                data-selectedAreaId="{{ isset($edit) && $edit?->area_id ? $edit?->area_id : old('area_id') }}">
                                <option value="" disabled
                                    {{ old('area_id', $edit->area_id ?? '') ? '' : 'selected' }}>
                                    Select Area
                                </option>
                            </select>
                            @error('area_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    @if (!$company_id && !$parent_type_id)
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Select Created By <span class="text-danger">*</span></label>
                                <select name="inquiry_created_by" id="inquiry_created_by"
                                    class="form-control @error('inquiry_created_by') is-invalid @enderror search_by_created_by select2"
                                    data-append="search_by_created_by"
                                    data-selectedInquiryCreatedBy="{{ isset($edit) && $edit?->inquiry_created_by ? $edit?->inquiry_created_by : old('search_by_created_by', $loginUserId) }}">
                                    <option value="" disabled
                                        {{ old('inquiry_created_by', $edit->inquiry_created_by ?? '') ? '' : 'selected' }}>
                                        Select Created By
                                    </option>
                                </select>
                                @error('inquiry_created_by')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_created_by" name="inquiry_created_by"
                            value="{{ $loginUserId }}" />
                    @endif

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Assigned To <span class="text-danger">*</span></label>
                            <select name="inquiry_assigned_to[]" id="inquiry_assigned_to"
                                class="form-control @error('inquiry_assigned_to') is-invalid @enderror search_by_assigned_to select2"
                                multiple data-append="search_by_assigned_to"
                                data-selectedInquiryAssignedTo="{{ implode(',', old('inquiry_assigned_to', isset($edit) ? explode(',', $edit->inquiry_assigned_to) : (isset($loginUserId) ? [$loginUserId] : []))) }}">
                            </select>
                            @error('inquiry_assigned_to')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Attachment</label>
                            <input type="file" class="form-control @error('attachment') is-invalid @enderror"
                                name="attachment" accept="image/*">
                            @error('attachment')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror

                            @if (!empty($edit->attachment))
                                @php
                                    $company = $edit->company ?? null;
                                    $company_slug = \Illuminate\Support\Str::slug(
                                        $company->company_name ?? 'default-company',
                                    );
                                    $year = \Carbon\Carbon::now()->format('Y');
                                    $month = \Carbon\Carbon::now()->format('m');

                                    $relativePath =
                                        "uploads/{$edit->company_id}-{$company_slug}/inquiry/{$year}-{$month}/" .
                                        basename($edit->attachment);
                                    $fileUrl = asset($relativePath);
                                @endphp
                                <a href="{{ $fileUrl }}" download class="" target="_blank">
                                    Download Attachment
                                </a>
                            @endif



                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Requirement Details</label>
                            <textarea id="requirement_details" class="form-control autosize @error('requirement_details') is-invalid @enderror"
                                name="requirement_details" rows="1" autocomplete="requirement_details" placeholder="Requirement Details">{{ old('requirement_details', strip_tags(str_replace('<br>', "\n", $edit->requirement_details ?? ''))) }}</textarea>
                            @error('requirement_details')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    @if (!isset($edit))
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Followup Date</label>
                                <input id="followup_date"
                                    class="form-control datetimepicker @error('followup_date') is-invalid @enderror"
                                    name="followup_date"
                                    value="{{ old('followup_date', isset($edit->followup_date) ? \Carbon\Carbon::parse($edit->followup_date)->format('d-m-Y') : '') }}"
                                    placeholder="Followup Date" autocomplete="off" min="{{ now()->format('Y-m-d') }}">
                                @error('followup_date')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>



                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Followup Details</label>
                                <textarea id="followup_details" class="form-control autosize @error('followup_details') is-invalid @enderror"
                                    name="followup_details" rows="1" autocomplete="followup_details" placeholder="Followup Details">{{ old('followup_details', $edit->followup_details ?? '') }}</textarea>
                                @error('followup_details')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if (isset($modules['product_view']))
                        <div class="divider">
                            <hr />
                        </div>

                        <h5 class="card-header">Product Details</h5>

                        {{-- isset($modules['add_permission']) ? $modules['add_permission'] : false, --}}

                        {{-- <div class="row">
                            <div class="col-md-3">
                                <label class="form-label" for="product_id">Product</label>
                                <select name="product_id" id="product_variant_id"
                                    class="form-control @error('product_id') is-invalid @enderror search_by_product_variant select2"
                                    data-append="search_by_product_variant"
                                    data-selectedProductVariantId="{{ isset($edit) && $edit?->product_id ? $edit?->product_id : '' }}">
                                    <option value="" disabled
                                        {{ old('product_id', $edit->product_id ?? '') ? '' : 'selected' }}>
                                        Select Product
                                    </option>
                                </select>
                                @error('product_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="quantity">Quantity</label>
                                <input type="text" id="quantity" name="quantity" class="form-control"
                                    placeholder="Quantity" onkeypress="return isNumber(event)" maxlength="5">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label" for="requirements">Requirements</label>
                                <input type="text" id="requirements" name="requirements" class="form-control"
                                    placeholder="Requirements">
                            </div>

                            <div class="col-md-3  mt-4">
                                <button class="btn btn-label-info mt-xl-6 waves-effect" type="button"
                                    onclick="addProduct()">
                                    <span class="align-middle">Add</span>
                                </button>
                            </div>
                        </div> --}}

                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label" for="product_id">Product</label>
                                <select name="product_id" id="product_variant_id"
                                    class="form-control @error('product_id') is-invalid @enderror search_by_product_variant select2"
                                    data-append="search_by_product_variant"
                                    data-selectedProductVariantId="{{ isset($edit) && $edit?->product_id ? $edit?->product_id : '' }}">
                                    <option value="" disabled
                                        {{ old('product_id', $edit->product_id ?? '') ? '' : 'selected' }}>
                                        Select Product
                                    </option>
                                </select>
                                @error('product_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-3 product-fields d-none">
                                <label class="form-label" for="quantity">Quantity</label>
                                <input type="text" id="quantity" name="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror" placeholder="Quantity"
                                    onkeypress="return isNumber(event)" maxlength="5" required>
                                @error('quantity')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-3 product-fields d-none">
                                <label class="form-label" for="requirements">Requirements</label>
                                <input type="text" id="requirements" name="requirements"
                                    class="form-control @error('requirements') is-invalid @enderror"
                                    placeholder="Requirements" required>
                                @error('requirements')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-3 mt-4 product-fields d-none">
                                <button class="btn btn-label-info mt-xl-6 waves-effect" type="button"
                                    onclick="addProduct()">
                                    <span class="align-middle">Add</span>
                                </button>
                            </div>
                        </div>


                        <div class="row table-responsive text-nowrap">
                            <table class="table" id="inquiry-products-table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Requirements</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @if (isset($edit) && isset($inquiryProducts))

                                        @foreach ($inquiryProducts as $i => $product)
                                            <tr>
                                                <td>
                                                    @if ($product?->product)
                                                        {{ $product?->product?->product_name ?? 'N/A' }}
                                                        @if ($product->product && isset($product->product_variant_prices[0]))
                                                            -
                                                            {{ $product->product_variant_prices[0]->variant->name ?? '' }}
                                                            -
                                                            {{ $product->product_variant_prices[0]->product_code ?? '' }}
                                                        @endif
                                                    @else
                                                        <span class="text-danger">Invalid Product</span>
                                                    @endif
                                                </td>
                                                <td>{{ $product?->quantity ?? '0' }}</td>
                                                <td>{!! $product?->requirements ?? '-' !!}</td>
                                                <td>
                                                    <button type="button" onclick="removeProduct(this)"
                                                        style="padding: 0.5rem 0.75rem;"
                                                        class="btn btn-label-danger waves-effect btn-sm">Delete</button>
                                                </td>

                                                <input type="hidden" name="products[{{ $i }}][product_id]"
                                                    value="{{ $product->product_id }}">
                                                <input type="hidden" name="products[{{ $i }}][quantity]"
                                                    value="{{ $product->quantity }}">
                                                <input type="hidden" name="products[{{ $i }}][requirements]"
                                                    value="{{ $product->requirements }}">
                                            </tr>
                                        @endforeach

                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="divider">
                            {{-- <hr /> --}}
                        </div>

                    @endif
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
    @include('software.showCustomModel')
@endsection

@section('page_leavel_script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>

@endsection

@push('page_scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const errorMsg = document.getElementById('email-error');

            emailInput.addEventListener('blur', function(e) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (emailInput.value.trim() !== '' && !emailPattern.test(emailInput.value)) {
                    emailInput.classList.add('is-invalid');
                    errorMsg.classList.remove('d-none');

                    setTimeout(() => {
                        emailInput.focus();
                    }, 0);
                } else {
                    emailInput.classList.remove('is-invalid');
                    errorMsg.classList.add('d-none');
                }
            });
        });

        $(".datetimepicker").each(function() {
            $(this).datetimepicker({
                format: 'd-m-Y h:i A', // 12-hour format with AM/PM
                formatTime: 'h:i A', // explicitly control time format
                closeOnDateSelect: true,
                step: 10, // or 30 minutes if you want
                minDate: 0, // today,
                validateOnBlur: false
            });
        });

        $(document).on('change', '.datetimepicker', function() {

            const selected = moment($(this).val(), inputDateFormat);
            const minDate = moment($(this).attr('min'), inputDateFormat);
            const now = moment().subtract(30, 'minutes').format(inputDateFormat);

            if (!selected.isValid()) {
                toastr.error('Invalid date format.');
                return;
            }

            if (selected.isBefore(minDate)) {
                toastr.error('Selected datetime must be in the future: ' + minDate.format(inputDateFormat));
                // console.log("L-278", $(this).val(), $(this).attr('min'), minDate, selected.isAfter(minDate), selected.isBefore(minDate));
                $(this).val(''); // Optional: clear the invalid value
                $(this).attr('min', now);
            } else {
                console.log('Valid datetime selected:', selected.format(inputDateFormat));
                $(this).val(selected.format(inputDateFormat));
                // $(this).attr("value", selected.format(inputDateFormat));
            }
        });


        $(document).on('blur', '.datetimepicker', function() {
            const selected = moment($(this).val(), inputDateFormat, true);
            $(this).val(selected.format(inputDateFormat)); // Correct output format
            // $(this).attr("value", selected.format(inputDateFormat));
        });

        $(document).ready(function() {

            const mobileInput = document.getElementById('mobile_no');
            const whatsappInput = document.getElementById('whatsapp_no');

            function validateMobile(input) {
                const value = input.value.trim();
                return /^\d{10}$/.test(value);
            }

            function forceValid10Digit(input) {
                input.addEventListener('blur', function() {
                    if (!validateMobile(input)) {
                        input.classList.add('is-invalid');

                        let error = input.closest('.form-group').querySelector('.invalid-feedback');
                        if (!error) {
                            error = document.createElement('span');
                            error.className = 'invalid-feedback';
                            input.closest('.form-group').appendChild(error);
                        }
                        error.innerHTML = '<strong>Enter a valid number.</strong>';
                        error.style.display = 'block';

                        setTimeout(() => input.focus(), 0);
                    } else {
                        input.classList.remove('is-invalid');
                        const error = input.closest('.form-group').querySelector('.invalid-feedback');
                        if (error) error.style.display = 'none';
                    }
                });
            }

            // forceValid10Digit(mobileInput);
            // forceValid10Digit(whatsappInput);
            document.getElementById('defaultCheck1').addEventListener('change', function() {
                if (this.checked) {
                    whatsappInput.value = mobileInput.value;
                }
            });

            $(document).on('change', '#mobile_no', function() {

                if ($(this).val().length != 0 || $(this).val().length != 10) {
                    document.querySelectorAll(
                        '#inquiryForm input, #inquiryForm select, #inquiryForm textarea, #inquiryForm button'
                    ).forEach(function(field) {
                        field.disabled = false;
                    });
                }

            });
            $(document).on('blur', '#mobile_no', function() {
                console.log("L-671", $(this).val().length, validateMobile(mobileInput));

                if ($(this).val().length == 0) {
                    toastr.error("Mobile number can not empty.");
                    return true;
                } else if (!validateMobile(mobileInput)) {
                    // toastr.error("Enter valid mobile number.");
                    return true;
                }

                let apiPayload = {};
                apiPayload = {
                    ...apiPayload,
                    mobile_no: $(this).val()
                };

                if ($('meta[name="company_id"]').attr('value')) {
                    apiPayload = {
                        ...apiPayload,
                        company_id: $('meta[name="company_id"]').attr('value')
                    };
                } else if ($('.search_by_company').find('option:selected').val()) {
                    apiPayload = {
                        ...apiPayload,
                        company_id: $('.search_by_company').find('option:selected').val()
                    };
                }

                if (!apiPayload?.company_id) {
                    toastr.error("Company Id not found.");
                    return true;
                }
                /*
                $.ajax({
                    type: 'POST',
                    url: '{{ env('API_URL') }}inquiry-exists',
                    'beforeSend': function(request) {
                        $(".show-duplicate-inquiry").addClass("d-none");
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]')
                            .attr('content'));
                        // Disable all fields in the form
                        document.querySelectorAll(
                            '#inquiryForm input, #inquiryForm select, #inquiryForm textarea, #inquiryForm button'
                        ).forEach(function(field) {
                            field.disabled = true;
                        })
                    },
                    data: apiPayload,
                    success: function(response) {

                        if (response?.status == true) {
                            document.querySelectorAll(
                                '#inquiryForm input, #inquiryForm select, #inquiryForm textarea, #inquiryForm button'
                            ).forEach(function(field) {
                                field.disabled = false;
                            });
                        }
                    },
                    error: function(response) {
                        let responseJSON = response?.responseJSON;
                        let responseJSONExtraData = responseJSON?.extraData;
                        console.log("L-728", responseJSON, response?.status,
                            responseJSONExtraData, responseJSON?.status);



                        if (response?.status, responseJSON?.status == false) {
                            $('#mobile_no').removeAttr('disabled');
                            if (responseJSON?.extraData?.inquiryViewLink) {
                                $(".show-duplicate-inquiry").attr("href", responseJSON
                                    ?.extraData?.inquiryViewLink);
                                $(".show-duplicate-inquiry").removeClass("d-none");
                            }
                            if (responseJSON?.extraData?.modelTitle && responseJSON?.extraData
                                ?.exitingInquiryHtml) {
                                // console.log("response 725", responseJSON?.extraData?.modelTitle && responseJSON?.extraData?.exitingInquiryHtml);

                                // Target and update modal elements
                                let showCustomModel = $('#showCustomModel');
                                showCustomModel.find('#modelTitle').text(responseJSON?.extraData
                                    ?.modelTitle);
                                showCustomModel.find('#modelTitle').addClass('text-danger');
                                showCustomModel.find('.modelShowHtml').html(responseJSON
                                    ?.extraData?.exitingInquiryHtml);

                                // Optionally show the modal (if not already open)
                                showCustomModel.modal('show');

                            }

                            toastr.error(responseJSON?.message ?? "Something want to wrong");
                            return true;
                        }
                    }
                });
                */
            });
        });
    </script>

    <script>
        function removeProduct(button) {
            $(button).closest('tr').remove();
        }

        function addProduct() {
            let product = $('#product_variant_id option:selected').text();
            let product_id = $('#product_variant_id').val();
            let quantity = $('#quantity').val();

            if (quantity == '') {
                toastr.error('Quantity field is required!');
                $('#quantity').focus();
                return;
            }
            let requirements = $('#requirements').val();

            if (product && quantity) {
                let index = Date.now();
                $('#inquiry-products-table').append(
                    `<tr>
                    <td>${product}</td>
                    <td>${quantity}</td>
                    <td>${requirements}</td>
                    <td>
                        <button type="button" onclick="removeProduct(this)" style="padding: 0.5rem 0.75rem;" class="btn btn-label-danger waves-effect btn-sm">Delete</button>
                    </td>

                    <input type="hidden" name="products[${index}][product_id]" value="${product_id}">
                    <input type="hidden" name="products[${index}][quantity]" value="${quantity}">
                    <input type="hidden" name="products[${index}][requirements]" value="${requirements}">
                </tr>`
                );
            }

            $('.product-fields').addClass('d-none');
            $('#quantity, #requirements').removeAttr('required').val('');

            $('#product_variant_id').val('').trigger('change');
        }

        $(document).ready(function() {
            $(".checkphone").click(function() {
                if ($(this).is(":checked")) {
                    var val = $("#mobile_no").val();
                    $('#whatsapp_no').val(val);
                } else {
                    $('#whatsapp_no').val('');
                }
            });
        });

        flatpickr('#inquiry_date', {
            dateFormat: "d-m-Y",
            maxDate: "today"
        });


        // flatpickr('#followup_date', {
        //     dateFormat: "d-m-Y",
        //     minDate: "today",
        //     onChange: function(selectedDates) {
        //         // if (toDateField.style.display !== 'none') {
        //         //     toDateTimePicker.set('minDate', selectedDates[0]);
        //         // }
        //     }
        // });
    </script>
    <script>
        $(document).ready(function() {
            $('#product_variant_id').on('change', function() {
                const selectedProduct = $(this).val();
                if (selectedProduct) {
                    // Show the fields and make them required
                    $('.product-fields').removeClass('d-none');
                    $('#quantity, #requirements').attr('required', true);
                } else {
                    // Hide the fields and remove required validation
                    $('.product-fields').addClass('d-none');
                    $('#quantity, #requirements').removeAttr('required').val('');
                }
            });

            // Trigger change on page load if editing
            $('#product_variant_id').trigger('change');
        });
        $('#mobile_no').on('blur', function() {
            const val = $(this).val().trim();
            const min = parseInt($(this).data('mobile-min'));
            const max = parseInt($(this).data('mobile-max'));

            toastr.clear();

            if (val.length > 0 && (val.length < min || val.length > max)) {
                toastr.error(`Mobile number must be between ${min} and ${max} digits.`);
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        $('#whatsapp_no').on('blur', function() {
            const val = $(this).val().trim();
            const min = parseInt($(this).data('mobile-min'));
            const max = parseInt($(this).data('mobile-max'));

            toastr.clear();

            if (val.length > 0 && (val.length < min || val.length > max)) {
                toastr.error(`Whatsapp number must be between ${min} and ${max} digits.`);
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            autosize($('.autosize'));
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#pincode').on('blur', function() {
                let pincode = $(this).val();

                if (pincode.length === 6) {
                    $.ajax({
                        url: "{{ route('get-location-by-pincode') }}",
                        type: "GET",
                        data: {
                            pincode: pincode
                        },
                        success: function(response) {
                            console.log(response);
                            if (response.status) {
                                $('#country_id').val(response.data.country_id).trigger(
                                    'change');

                                setTimeout(() => {
                                    $('#state').val(response.data.state_id).trigger(
                                        'change');
                                }, 300);

                                setTimeout(() => {
                                    let cities = response.data.cities || [];
                                    let selectedCityId = response.data.selected_city_id;

                                    $('#city_id').empty();
                                    $('#city_id').append(
                                        '<option value="" disabled selected>Select City</option>'
                                    );

                                    cities.forEach((city) => {
                                        $('#city_id').append(
                                            `<option value="${city.id}">${city.name}</option>`
                                        );
                                    });

                                    if (selectedCityId) {
                                        $('#city_id').val(selectedCityId).trigger(
                                            'change');
                                    } else if (cities.length === 1) {
                                        $('#city_id').val(cities[0].id).trigger(
                                            'change');
                                    }

                                }, 700);
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert("Something went wrong!");
                        }
                    });
                }
            });
        });
    </script>






    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getTeams')

    @if (!isset($edit) || !$edit?->id)
        @include('utils.generateInquiryNo')
    @endif

    @include('utils.getInquiryStatus')
    @include('utils.getFollowupStatus')
    @include('utils.getSourceOfInquiry')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
    @include('utils.getAreaByCity')
    @include('utils.getProductAndVariant')

@endpush
