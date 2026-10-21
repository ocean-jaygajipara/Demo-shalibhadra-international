@extends('software.layout.app')

@php
    $i = 0;
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
            <form id="forminfo"
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">

                <input type="hidden" id="edit_id" name="id"
                    value="{{ isset($edit) && $edit?->id ? $edit?->id : '' }}" />

                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">
                    @if (!$company_id)
                        <div class="col-md-3 col-sm-12 mb-2">
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
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name', $edit->name ?? '') }}" autocomplete="name"
                                placeholder="Name">
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Parent Type</label>
                            <select name="parent_type_id" id="parent_type_id"
                                class="form-control @error('parent_type_id') is-invalid @enderror search_by_created_by select2"
                                data-append="search_by_created_by"
                                data-selectedinquirycreatedby="{{ isset($edit) && $edit?->parent_type_id ? $edit?->parent_type_id : '' }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : '' }}"
                                autofocus>
                                <option value=""> Select parent type</optixon>
                            </select>
                            @error('parent_type_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Mobile No <span class="text-danger">*</span></label>
                            <input id="mobile_no" type="text"
                                class="form-control @error('mobile_no') is-invalid @enderror" name="mobile_no"
                                value="{{ old('mobile_no', $edit->mobile_no ?? '') }}" autocomplete="name"
                                placeholder="Mobile No" maxlength="10" onkeypress="return isNumber(event)">
                            @error('mobile_no')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                            <input id="employee_code" type="text"
                                class="form-control readonly-look  @error('employee_code') is-invalid @enderror"
                                name="employee_code"
                                value="{{ old('employee_code', $edit->employee_code ?? ($nextEmployeeCode ?? '')) }}"
                                placeholder="Employee Code" readonly>
                            @error('employee_code')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input id="username" type="text"
                                class="form-control @error('username') is-invalid @enderror" name="username"
                                value="{{ old('username', $edit->username ?? '') }}" autocomplete="name"
                                placeholder="Username">
                            @error('username')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Password @if (!isset($edit)) <span class="text-danger">*</span>
                                @endif </label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="Password" aria-describedby="password" />
                                <span class="input-group-text cursor-pointer toggle-password" onclick="togglePassword()">
                                    <i class="ti ti-eye-off" id="togglePasswordIcon"></i>
                                </span>
                            </div>
                            @error('password')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Min Working Start Time <span class="text-danger">*</span></label>
                            <input id="min_working_start_time" type="time"
                                class="form-control @error('min_working_start_time') is-invalid @enderror"
                                name="min_working_start_time"
                                value="{{ old('min_working_start_time', $edit->min_working_start_time ?? '05:30') }}"
                                autocomplete="name" placeholder="Min Working Start Time">
                            @error('min_working_start_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Max Working Start Time <span class="text-danger">*</span></label>
                            <input id="max_working_start_time" type="time"
                                class="form-control @error('max_working_start_time') is-invalid @enderror"
                                name="max_working_start_time"
                                value="{{ old('max_working_start_time', $edit->max_working_start_time ?? '05:30') }}"
                                autocomplete="name" placeholder="Max Working Start Time">
                            @error('max_working_start_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Working End Time <span class="text-danger">*</span></label>
                            <input id="working_end_time" type="time"
                                class="form-control @error('working_end_time') is-invalid @enderror"
                                name="working_end_time"
                                value="{{ old('working_end_time', $edit->working_end_time ?? '05:30') }}"
                                autocomplete="name" placeholder="Working End Time">
                            @error('working_end_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input id="address" type="text"
                                class="form-control @error('address') is-invalid @enderror" name="address"
                                value="{{ old('address', $edit->address ?? '') }}" autocomplete="name"
                                placeholder="Address">
                            @error('address')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <select name="country_id" id="country_id"
                                class="form-control @error('country_id') is-invalid @enderror search_by_country select2"
                                data-append="search_by_country" data-filterByStatus="active"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : old('country_id') }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : old('state_id') }}"
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">State <span class="text-danger">*</span></label>
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
                            <label class="form-label">City <span class="text-danger">*</span></label>
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Area <span class="text-danger">*</span></label>
                            <select name="area_id" id="area_id"
                                class="form-control @error('area_id') is-invalid @enderror search_by_area select2"
                                data-append="search_by_area"
                                data-selectedAreaId="{{ isset($edit) && $edit?->area_id ? $edit?->area_id : old('area_id') }}"
                                autofocus>
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Email </label>
                            <input id="email" type="email"
                                class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email', $edit->email ?? '') }}" autocomplete="name" placeholder="Email">
                            @error('email')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Birth Date </label>
                            <input id="birth_date" type="date"
                                class="form-control @error('birth_date') is-invalid @enderror" name="birth_date"
                                value="{{ old('birth_date', $edit->birth_date ?? '') }}" autocomplete="name"
                                placeholder="Birth Date">
                            @error('birth_date')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Designation </label>
                            <select name="designation_id" id="designation_id"
                                class="form-control @error('designation_id') is-invalid @enderror search_by_designation select2"
                                data-append="search_by_designation"
                                data-selectedDesignationId="{{ isset($edit) && $edit?->designation_id ? $edit?->designation_id : old('designation_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('designation_id', $edit->designation_id ?? '') ? '' : 'selected' }}>
                                    Select Designation
                                </option>
                            </select>
                            @error('designation_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Team Role </label>
                            <select name="team_role_id" id="team_role_id" placeholder="Team Role"
                                class="form-control @error('team_role_id') is-invalid @enderror search_by_team_role select2"
                                data-append="search_by_team_role"
                                data-selectedRoleId="{{ isset($edit) && $edit?->team_role_id ? $edit?->team_role_id : old('team_role_id') }}"
                                autofocus>
                                <option value="" disabled
                                    {{ old('team_role_id', $edit->team_role_id ?? '') ? '' : 'selected' }}>
                                    Select Team Role
                                </option>
                            </select>
                            @error('team_role_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
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
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('togglePasswordIcon');
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('ti-eye-off', !isPassword);
            icon.classList.toggle('ti-eye', isPassword);
        }
    </script>



    @include('utils.getCompany')
    @include('utils.getDesignation')
    @include('utils.getTeamRole')
    @include('utils.getTeams')


    @if (!isset($edit))
        @include('utils.generateEmployeeCode')
    @endif
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
    @include('utils.getAreaByCity')
@endpush
