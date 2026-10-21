@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    if (!isset($edit)) {
        $edit = isset($convert_to_customer) ? $convert_to_customer : null;
    }
    $isProspect = request()->routeIs('prospected-customer.create');

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
                <input type="hidden" name="redirect_to_quotation" id="redirect_to_quotation" value="0">

                {{-- <input type="hidden" name="is_prospect" value="1"> --}}
                <input type="hidden" name="is_prospect"
                    value="{{ request()->is('software/prospected-customer/convert/*')
                        ? 0
                        : (request()->routeIs('prospected-customer.*')
                            ? 1
                            : 0) }}">

                @if (isset($edit) && $edit?->id)
                    @method('PUT')
                @endif
                <div class="row">
                    {{-- Company --}}
                    @if (!$company_id)
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company @if (!$isProspect)
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}">
                                    <option value="">Select Company</option>
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


                    <!-- Client Code -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Client Code @if (!$isProspect)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <input id="client_code" type="text"
                                class="readonly-look form-control @error('client_code') is-invalid @enderror"
                                name="client_code"
                                value="{{ old('client_code', $edit->client_code ?? ($clientCode ?? '')) }}"
                                autocomplete="client_code" placeholder="Client Code" readonly disabled>

                            @error('client_code')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    {{-- @php
    dd($edit->customer_type_id);
@endphp --}}

                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Customer Type @if (!$isProspect)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
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

                    <!-- Country -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Select Country @if (!$isProspect)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <select name="country_id" id="country_id"
                                class="form-control @error('country_id') is-invalid @enderror search_by_country select2"
                                data-append="search_by_country" data-filterByStatus="active" data-auto-selected="yes"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
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

                    <div class="col-md-4 col-sm-12" id="parent_type_div" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Select Parent @if (!$isProspect)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <select id="parent_type_id" name="parent_id"
                                class="form-control select2 @error('parent_id') is-invalid @enderror"
                                data-selectedParentId="{{ old('parent_id', $edit->parent_id ?? '') }}">
                                <option value=""> Select Parent </option>
                            </select>

                            @error('parent_id')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    <!-- GST Number -->
                    <div class="col-md-4 col-sm-12 mb-3 gst_number_div">
                        <div class="form-group">
                            <label class="form-label" for="gst_number">
                                GST Number
                                <span class="text-danger" id="gst_star" style="display: none;">*</span>
                            </label>

                            <input type="text" class="form-control @error('gst_number') is-invalid @enderror"
                                name="gst_number" value="{{ isset($edit) ? $edit->gst_number : old('gst_number') }}"
                                placeholder="Enter GST number" maxlength="15" pattern="[0-9A-Z]{15}">
                            @error('gst_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- PAN Number -->
                    <div class="col-md-4 col-sm-12 mb-3 pan_number_div">
                        <div class="form-group">
                            <label class="form-label">PAN Number <span class="text-danger"></span></label>

                            <input type="text" class="form-control @error('pan_number') is-invalid @enderror"
                                name="pan_number" value="{{ isset($edit) ? $edit->pan_number : old('pan_number') }}"
                                placeholder="Enter PAN number" maxlength="10" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}">
                            @error('pan_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Customer Name -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">
                                Customer/Company Name
                                @if ($isProspect)
                                    <span class="text-danger">*</span>
                                @else
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                name="customer_name"
                                value="{{ isset($edit) ? $edit->customer_name : old('customer_name') }}"
                                placeholder="Enter Customer Name">
                            @error('customer_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Contact Person Name -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Contact Person Name </label>
                            <input type="text" class="form-control @error('contact_person_name') is-invalid @enderror"
                                name="contact_person_name"
                                value="{{ isset($edit) ? $edit->contact_person_name : old('contact_person_name') }}"
                                placeholder="Enter Contact Person Name">
                            @error('contact_person_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Mobile Number / Whatsapp Number </label>
                            <input type="tel" id="mobile_number"
                                class="form-control @error('mobile_number') is-invalid @enderror" name="mobile_number"
                                value="{{ isset($edit) ? $edit->mobile_number : old('mobile_number') }}"
                                placeholder="Enter Mobile Number" data-mobile-min="{{ $mobile_min }}"
                                data-mobile-max="{{ $mobile_max }}" minlength="{{ $mobile_min }}"
                                maxlength="{{ $mobile_max }}">
                            @error('mobile_number')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Email ID -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Email ID</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ isset($edit) ? $edit->email : old('email') }}"
                                placeholder="Enter Email ID">
                            @error('email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    <!-- State -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label" for="state_id">Select State @if (!$isProspect)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            <select name="state_id" id="state_id"
                                class="form-control @error('state_id') is-invalid @enderror search_by_state select2"
                                data-append="search_by_state"
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

                    <!-- City -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Select City @if (!$isProspect)
                                    <span class="text-danger">*</span>
                                @endif
                            </label>
                            {{-- @php
                                dd($edit->city_id);
                            @endphp --}}
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
                            <label class="form-label">Area </label>
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
                            <label class="form-label">Pincode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('pincode') is-invalid @enderror"
                                name="pincode" value="{{ isset($edit) ? $edit->pincode : old('pincode') }}"
                                placeholder="Enter Pincode" maxlength="6" minlength="6" pattern="\d{6}"
                                title="Please enter exactly 6 digits.">
                            @error('pincode')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <!-- Payment Terms -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Payment Terms</label>
                            <select id="payment_term_id" name="payment_term_id"
                                class="form-control select2 @error('payment_term_id') is-invalid @enderror"
                                data-selectedPaymentTermId="{{ old('payment_term_id', $edit->payment_term_id ?? '') }}">
                                <option value="">Select Payment Term</option>
                                @foreach ($paymentTerms ?? [] as $term)
                                    <option value="{{ $term->id }}"
                                        {{ isset($edit) && $edit->payment_term_id == $term->id ? 'selected' : (old('payment_term_id') == $term->id ? 'selected' : '') }}>
                                        {{ $term->payment_terms }}
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_term_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- @php
                        dd($edit->credit_days);
                    @endphp --}}
                    <!-- Credit Days -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Credit Days</label>
                            <input type="number" id="credit_days"
                                class="form-control @error('credit_days') is-invalid @enderror" name="credit_days"
                                min="0" value="{{ old('credit_days', $edit->credit_days ?? '') }}"
                                placeholder="Enter Credit Days">

                            @error('credit_days')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <!-- Opening Balance -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group d-flex align-items-center">
                            <label class="form-label me-2 mb-0">Opening</label>

                            <input type="number" class="form-control me-2" name="opening_balance"
                                value="{{ isset($edit) ? $edit->opening_balance : old('opening_balance', 0) }}"
                                placeholder="0" style="max-width: 120px;">

                            <select class="form-select me-2" name="balance_type" style="max-width: 80px;">
                                <option value="Cr"
                                    {{ isset($edit) && $edit->balance_type == 'Cr' ? 'selected' : '' }}>
                                    Cr</option>
                            </select>

                            <div class="form-check ms-2">
                                <!-- hidden default value -->
                                <input type="hidden" name="is_active" value="0">

                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active"
                                    value="1" {{ isset($edit) && $edit->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>

                        </div>
                    </div>


                </div>
                <div class="row">

                    <!--  Address -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">
                                Address
                                @if ($isProspect)
                                    <span class="text-danger">*</span>
                                @else
                                    <span class="text-danger">*</span>
                                @endif
                            </label>


                            <textarea id="address" class="form-control autosize @error('address') is-invalid @enderror" name="address"
                                rows="1" autocomplete="address" placeholder="Enter Address">{{ isset($edit) ? $edit->address : old('address') }}</textarea>
                            @error('address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Shipping Address</label>
                            <textarea id="shipping_address" class="form-control autosize @error('shipping_address') is-invalid @enderror"
                                name="shipping_address" rows="1" autocomplete="shipping_address" placeholder="Enter Shipping Address">{{ isset($edit) ? $edit->shipping_address : old('shipping_address') }}</textarea>
                            @error('shipping_address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Billing Address</label>
                            <textarea id="billing_address" class="form-control autosize @error('billing_address') is-invalid @enderror"
                                name="billing_address" rows="1" autocomplete="billing_address" placeholder="Enter Billing Address">{{ isset($edit) ? $edit->billing_address : old('billing_address') }}</textarea>
                            @error('billing_address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control @error('latitude') is-invalid @enderror"
                                name="latitude" value="{{ isset($edit) ? $edit->latitude : old('latitude') }}"
                                placeholder="Enter Latitude">
                            @error('latitude')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control @error('longitude') is-invalid @enderror"
                                name="longitude" value="{{ isset($edit) ? $edit->longitude : old('longitude') }}"
                                placeholder="Enter Longitude">
                            @error('longitude')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror"
                                name="status" required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) selected @endif
                                    @else @if (old('status', 'active') == $status) selected @endif @endif
                                        >
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <hr>
                    <h5 class="mb-3 border-bottom pb-2">
                        Other Details
                    </h5>
                    <!-- Other Details Tabs -->
                    <div class="card my-3">
                        <div>
                            <ul class="nav nav-tabs" id="otherDetailsTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" id="contact-tab" data-bs-toggle="tab" href="#contact"
                                        role="tab" aria-controls="contact" aria-selected="true">Contact Details</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="bank-tab" data-bs-toggle="tab" href="#bank"
                                        role="tab" aria-controls="bank" aria-selected="false">Bank Details</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" id="sales-item-tab" data-bs-toggle="tab" href="#sales_item"
                                        role="tab" aria-controls="sales_item" aria-selected="false">Sales Item
                                        Details</a>
                                </li>

                            </ul>
                            <div class="tab-content p-3" id="otherDetailsTabsContent">

                                <!-- Contact Details -->
                                <div class="tab-pane fade show active" id="contact" role="tabpanel"
                                    aria-labelledby="contact-tab">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Department</th>
                                                <th>Name</th>
                                                <th>Designation</th>
                                                <th>Mobile</th>
                                                <th>Email ID</th>
                                                <th>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        id="addContactRow">+ Add</button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="contactTableBody">
                                            @php
                                                $oldContacts = old('contacts', $contacts ?? []);
                                                $contactIndex = count($oldContacts);
                                                if ($contactIndex == 0) {
                                                    $oldContacts[] = [];
                                                }
                                                // dd($oldContacts);
                                            @endphp

                                            @foreach ($oldContacts as $i => $contact)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="contacts[{{ $i }}][id]"
                                                            value="{{ $contact->id ?? '' }}">
                                                        <input type="text"
                                                            name="contacts[{{ $i }}][department]"
                                                            class="form-control"
                                                            value="{{ $contact['department'] ?? ($contact->department ?? '') }}"
                                                            placeholder="Enter Department">
                                                    </td>

                                                    <td>
                                                        <input type="text" name="contacts[{{ $i }}][name]"
                                                            class="form-control"
                                                            value="{{ $contact['name'] ?? ($contact->name ?? '') }}"
                                                            placeholder="Enter Name">

                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="contacts[{{ $i }}][designation]"
                                                            class="form-control"
                                                            value="{{ $contact['designation'] ?? ($contact->designation ?? '') }}"
                                                            placeholder="Enter Designation">
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="contacts[{{ $i }}][mobile]"
                                                            class="form-control"
                                                            value="{{ $contact['mobile'] ?? ($contact->mobile ?? '') }}"
                                                            maxlength="10" placeholder="Enter numbers only">
                                                    </td>
                                                    <td>
                                                        <input type="email" name="contacts[{{ $i }}][email]"
                                                            class="form-control"
                                                            value="{{ $contact['email'] ?? ($contact->email ?? '') }}"
                                                            placeholder="Enter email ID">
                                                    </td>

                                                    <td>
                                                        <button type="button"
                                                            class="btn btn-sm btn-danger removeRow">X</button>
                                                    </td>
                                                </tr>
                                                @php $contactIndex = $i + 1; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- BANK DETAILS --}}
                                <div class="tab-pane fade" id="bank" role="tabpanel" aria-labelledby="bank-tab">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Bank Name</th>
                                                <th>Account Number</th>
                                                <th>Branch</th>
                                                <th>IFSC</th>
                                                <th>Swift Code</th>
                                                <th>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        id="addBankRow">+ Add</button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="bankTableBody">
                                            @php
                                                $oldBanks = old('banks', $banks ?? []);
                                                $bankIndex = count($oldBanks);
                                                if ($bankIndex == 0) {
                                                    $oldBanks[] = [];
                                                }
                                            @endphp

                                            @foreach ($oldBanks as $i => $bank)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="banks[{{ $i }}][id]"
                                                            value="{{ $bank->id ?? '' }}">
                                                        <input type="text"
                                                            name="banks[{{ $i }}][bank_name]"
                                                            class="form-control"
                                                            value="{{ $bank['bank_name'] ?? ($bank->bank_name ?? '') }}"
                                                            placeholder="Enter Bank Name">

                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                            name="banks[{{ $i }}][account_number]"
                                                            class="form-control"
                                                            value="{{ $bank['account_number'] ?? ($bank->account_number ?? '') }}"
                                                            placeholder="Enter Account Number">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="banks[{{ $i }}][branch]"
                                                            class="form-control"
                                                            value="{{ $bank['branch'] ?? ($bank->branch ?? '') }}"
                                                            placeholder="Enter Branch">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="banks[{{ $i }}][ifsc]"
                                                            class="form-control"
                                                            value="{{ $bank['ifsc'] ?? ($bank->ifsc ?? '') }}"
                                                            placeholder="Enter IFSC Code">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="banks[{{ $i }}][swift_code]"
                                                            class="form-control"
                                                            value="{{ $bank['swift_code'] ?? ($bank->swift_code ?? '') }}"
                                                            placeholder="Enter Swift Code">
                                                    </td>

                                                    <td><button type="button"
                                                            class="btn btn-sm btn-danger removeBankRow">X</button></td>
                                                </tr>
                                                @php $bankIndex = $i + 1; @endphp
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- SALES ITEM --}}
                                <div class="tab-pane fade" id="sales_item" role="tabpanel"
                                    aria-labelledby="sales-item-tab">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item Code</th>
                                                <th>Item Name</th>
                                                <th>Rate</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                                <th>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        id="addSalesRow">+ Add</button>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody id="salesTableBody">
                                            @php
                                                $oldSales = old('sales', $salesItems ?? []);
                                                $salesIndex = count($oldSales);
                                                if ($salesIndex == 0) {
                                                    $oldSales[] = [];
                                                }

                                            @endphp

                                            @foreach ($oldSales as $i => $sale)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="sales[{{ $i }}][id]"
                                                            value="{{ $sale->id ?? '' }}">
                                                        <input type="text"
                                                            name="sales[{{ $i }}][item_code]"
                                                            class="form-control"
                                                            value="{{ $sale['item_code'] ?? ($sale->item_code ?? '') }}">
                                                    </td>
                                                    <td><input type="text"
                                                            name="sales[{{ $i }}][item_name]"
                                                            class="form-control"
                                                            value="{{ $sale['item_name'] ?? ($sale->item_name ?? '') }}">
                                                    </td>
                                                    <td><input type="number" step="0.01"
                                                            name="sales[{{ $i }}][rate]" class="form-control"
                                                            value="{{ $sale['rate'] ?? ($sale->rate ?? '') }}"></td>
                                                    <td><input type="text" name="sales[{{ $i }}][type]"
                                                            class="form-control"
                                                            value="{{ $sale['type'] ?? ($sale->type ?? '') }}"></td>
                                                    <td><input type="date" name="sales[{{ $i }}][date]"
                                                            class="form-control"
                                                            value="{{ $sale['date'] ?? ($sale->date ?? '') }}"></td>
                                                    <td><button type="button"
                                                            class="btn btn-sm btn-danger removeSalesRow">X</button></td>
                                                </tr>
                                            @endforeach


                                            @if (empty($oldSales))
                                                <tr>
                                                    <td><input type="text" name="sales[0][item_code]"
                                                            class="form-control" placeholder="Enter Item Code"></td>
                                                    <td><input type="text" name="sales[0][item_name]"
                                                            class="form-control" placeholder="Enter Item Name"></td>
                                                    <td><input type="number" step="0.01" name="sales[0][rate]"
                                                            class="form-control" placeholder="Enter Rate"></td>
                                                    <td><input type="text" name="sales[0][type]" class="form-control"
                                                            placeholder="Enter Type"></td>
                                                    <td><input type="date" name="sales[0][date]" class="form-control"
                                                            placeholder="Select Date"></td>
                                                    <td><button type="button"
                                                            class="btn btn-sm btn-danger removeSalesRow">X</button></td>
                                                </tr>
                                                @php $salesIndex = 1; @endphp
                                            @endif

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>




                    <!-- Submit and Cancel Buttons -->
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

    <script>
        $(document).ready(function() {
            // Contact Details
            let contactIndex = {{ $contactIndex }};

            // Add new contact row
            $('#addContactRow').click(function() {
                let row = `
                <tr>
                   <td><input type="text" name="contacts[${contactIndex}][department]" class="form-control" placeholder="Enter Department"></td>
        <td><input type="text" name="contacts[${contactIndex}][name]" class="form-control" placeholder="Enter Name"></td>
        <td><input type="text" name="contacts[${contactIndex}][designation]" class="form-control" placeholder="Enter Designation"></td>
                    <td><input type="number" name="contacts[${contactIndex}][mobile]" class="form-control" maxlength="10" placeholder="Enter numbers only"></td>
                <td>
    <input type="email"
           name="contacts[${contactIndex}][email]"
           class="form-control"
           placeholder="Enter email ID">
</td>

                    <td><button type="button" class="btn btn-sm btn-danger removeRow">X</button></td>
                </tr>
            `;
                $('#contactTableBody').append(row);
                contactIndex++;
            });

            // Remove contact row
            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
            });
            // BANK DETAILS
            let bankIndex = {{ $bankIndex }};

            // Add new bank row
            $('#addBankRow').click(function() {
                let row = `
        <tr>
            <td><input type="text" name="banks[${bankIndex}][bank_name]" class="form-control" placeholder="Enter Bank Name"></td>
            <td><input type="number" name="banks[${bankIndex}][account_number]" class="form-control" placeholder="Enter Account Number"></td>
            <td><input type="text" name="banks[${bankIndex}][branch]" class="form-control" placeholder="Enter Branch"></td>
            <td><input type="text" name="banks[${bankIndex}][ifsc]" class="form-control" placeholder="Enter IFSC Code"></td>
            <td><input type="text" name="banks[${bankIndex}][swift_code]" class="form-control" placeholder="Enter Swift Code"></td>
            <td><button type="button" class="btn btn-sm btn-danger removeBankRow">X</button></td>
        </tr>
        `;
                $('#bankTableBody').append(row);
                bankIndex++;
            });

            // Remove bank row
            $(document).on('click', '.removeBankRow', function() {
                $(this).closest('tr').remove();
            });
            // SALES ITEM
            let salesIndex = {{ $salesIndex ?? 1 }};

            // Add new sales row
            $('#addSalesRow').click(function() {
                let row = `
            <tr>
    <td><input type="text" name="sales[${salesIndex}][item_code]" class="form-control" placeholder="Enter Item Code"></td>
    <td><input type="text" name="sales[${salesIndex}][item_name]" class="form-control" placeholder="Enter Item Name"></td>
    <td><input type="number" step="0.01" name="sales[${salesIndex}][rate]" class="form-control" placeholder="Enter Rate"></td>
    <td><input type="text" name="sales[${salesIndex}][type]" class="form-control" placeholder="Enter Type"></td>
    <td><input type="date" name="sales[${salesIndex}][date]" class="form-control" placeholder="Select Date"></td>
    <td><button type="button" class="btn btn-sm btn-danger removeSalesRow">X</button></td>
</tr>

        `;
                $('#salesTableBody').append(row);
                salesIndex++;
            });

            // Remove sales row
            $(document).on('click', '.removeSalesRow', function() {
                $(this).closest('tr').remove();
            });

        });
    </script>

    <script>
        flatpickr("#birth_date_display", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('birth_date', isset($edit->birth_date) ? date('d-m-Y', strtotime($edit->birth_date)) : '') }}",
            maxDate: "{{ date('Y-m-d') }}", // Prevent selection of today or any future date
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('birth_date').value = ymd;
            }
        });
    </script>


    {{-- <script>
    window.onload = function() {

    function generateClientCode() {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    let clientCode = '';
    for (let i = 0; i < 3; i++) {
    clientCode += letters.charAt(Math.floor(Math.random() * letters.length));
    }
    clientCode += '-';
    for (let i = 0; i < 4; i++) {
    clientCode += numbers.charAt(Math.floor(Math.random() * numbers.length));
    }
    return clientCode;
    }


    const clientCodeInput = document.querySelector('input[name="client_code"]');
    if (clientCodeInput && clientCodeInput.readOnly) {
    clientCodeInput.value = generateClientCode();
    }
    };
</script> --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileInput = document.getElementById('mobile_number');
            const errorDisplay = document.getElementById('mobile_error');
            let isValidMobile = false;
            let previousCheckedNumbers = new Set();

            mobileInput.addEventListener('blur', function() {
                const number = mobileInput.value.trim();

                // Clear previous error
                clearError();

                // Check for empty input
                if (number === "") {
                    showError("Mobile number is required.");
                    isValidMobile = false;
                    return;
                }

                // Check if the number is exactly 10 digits and numeric
                const digitOnly = /^\d{10}$/;
                if (!digitOnly.test(number)) {
                    showError("Please enter a valid 10-digit mobile number.");
                    isValidMobile = false;
                    return;
                }

                // Check if the number was already checked and invalid
                if (previousCheckedNumbers.has(number)) {
                    showError("This mobile number has already been used.");
                    isValidMobile = false;
                    return;
                }

                // Server-side check
                fetch("{{ route('customer.mobile') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({
                            mobile_number: number
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            isValidMobile = false;
                            previousCheckedNumbers.add(number);
                            showError("This mobile number is already registered. Please try another.");
                        } else {
                            isValidMobile = true;
                            clearError();
                        }
                    })
                    .catch(() => {
                        showError("Unable to verify mobile number. Please try again.");
                    });
            });

            function showError(message) {
                errorDisplay.textContent = message;
            }

            function clearError() {
                errorDisplay.textContent = "";
            }
        });
    </script>

    <script>
        document.getElementById('address').addEventListener('input', function() {

            var addressValue = this.value;


            document.getElementById('shipping_address').value = addressValue;
            document.getElementById('billing_address').value = addressValue;
        });
    </script>
    <!-- JavaScript -->
    <script>
        $(document).ready(function() {
            function getCompanyId() {
                let companyId = $('#company_id').val();
                if (!companyId && $('meta[name="company_id"]').attr('value')) {
                    companyId = $('meta[name="company_id"]').attr('value');
                }
                return companyId;
            }

            $(document).on("change", "#customer_type_id", function() {
                const selectedOption = $(this).find("option:selected");
                const parentId = selectedOption.data("parent_id");
                const customerTypeId = selectedOption.val();
                const companyId = getCompanyId(); // Use utility function
                const selectedParentId = $('#parent_type_id').data('selectedparentid'); // For edit

                if (parentId && parentId !== "0") {
                    $('#parent_type_div').show();

                    $.ajax({
                        url: '{{ env('API_URL') }}customer-list',
                        method: 'POST',
                        data: {
                            customer_parent_id: parentId,
                            company_id: companyId
                        },
                        success: function(response) {
                            const parentSelect = $('#parent_type_id');
                            parentSelect.empty().append(
                                '<option value="">Select Parent</option>');

                            if (Array.isArray(response.data.items) && response.data.items
                                .length > 0) {
                                response.data.items.forEach(function(customer) {
                                    // console.log( response.data.items);

                                    const isSelected = selectedParentId == customer.id ?
                                        'selected' : '';
                                    parentSelect.append(
                                        `<option value="${customer.id}" data-parent_id="${customer.parent_id}" ${isSelected}>
                                        ${customer.customer_name} - ${customer.contact_person_name}
                                    </option>`
                                    );
                                });
                            }
                        },
                        error: function(error) {
                            console.error("Error fetching customer names:", error);
                        }
                    });
                } else {
                    $('#parent_type_div').hide();
                    $('#parent_type_id').val('');
                }
            });

            const selectedCustomerTypeId = $('#customer_type_id').data('selectedcustomertypeid');
            if (selectedCustomerTypeId) {
                $('#customer_type_id').val(selectedCustomerTypeId).trigger('change');
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#mobile_number').on('blur', function() {
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
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>

    <script>
        $(document).ready(function() {
            autosize($('.autosize'));

            $('.autosize').on('input', function() {
                autosize.update($('.autosize'));
            });
        });
    </script>
@endpush

@push('page_scripts')

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
    @include('utils.getAreaByCity')
    @include('utils.getCompanyByCustomerType')
    @include('utils.getCompanyByPricelist')
    @include('utils.getPaymentTerms')
    @if (!isset($edit))
        @include('utils.generateClientCode')
    @endif
    {{-- @include('utils.getCustomerType') --}}

    <script>
        $(document).on("change", "#customer_type_id", function() {
            let typeId = $(this).val();
            let isProspect = {{ $isProspect ? 'true' : 'false' }};

            if (typeId == "1") {
                // Show GST star only if NOT a prospect
                if (!isProspect) {
                    $("#gst_star").show();
                } else {
                    $("#gst_star").hide();
                }

                // Always show the fields for customer type 1
                $(".gst_number_div").show();
                $(".pan_number_div").show();
            } else {
                $('#country_id').val('').trigger('change');
                $("#gst_star").hide();
                $(".gst_number_div").hide();
                $(".pan_number_div").hide();
            }
            fetch_company();
            setTimeout(function() {
                $('.search_by_country').trigger('change');
            }, 1000);
        });

        $(document).ready(function() {
            $("#customer_type_id").trigger("change");
        });
        if (sessionStorage.getItem('redirect_to_quotation') === '1') {
            $('#redirect_to_quotation').val('1');
            // Clear the flag
            sessionStorage.removeItem('redirect_to_quotation');
        }
    </script>


@endpush
