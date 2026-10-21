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
            <form
                action="{{ isset($edit) && $edit?->id ? route($route . '.update', [$edit?->id]) : route($route . '.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">
                    @if (!$company_id)
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
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
                    {{-- Country --}}
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Country <span class="text-danger">*</span></label>
                            <select name="country_id" id="country_id"
                                class="form-control @error('country_id') is-invalid @enderror search_by_country select2"
                                data-append="search_by_country"
                                data-filterByStatus="active"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : '' }}"
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

                    {{-- State --}}
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label" for="state_id">Select State <span class="text-danger">*</span></label>
                            <select name="state_id" id="state_id"
                                class="form-control @error('state_id') is-invalid @enderror search_by_state select2"
                                data-append="search_by_state"
                                data-selectedCountryId="{{ isset($edit) && $edit?->country_id ? $edit?->country_id : '' }}"
                                data-selectedStateId="{{ isset($edit) && $edit?->state_id ? $edit?->state_id : '' }}"
                                data-selectedCityId="{{ isset($edit) && $edit?->city_id ? $edit?->city_id : '' }}">

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


                    {{-- City --}}
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select City</label>
                            <select id="city_id" name="city_id"
                                class="form-control @error('city_id') is-invalid @enderror search_by_city select2"
                                data-append="search_by_city"
                                data-selectedCityId="{{ isset($edit) && $edit?->city_id ? $edit?->city_id : '' }}">
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


                    {{-- Area Name --}}
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label"> Area Name </label>
                            <input id="area_name" type="text"
                                class="form-control @error('area_name') is-invalid @enderror" name="area_name"
                                value="{{ isset($edit) && $edit?->area_name ? $edit?->area_name : old('area_name') }}"
                                placeholder="Enter Area name">
                            @error('area_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select class="form-control select2 w-100 @error('status') is-invalid @enderror" name="status"
                                required>
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        @if (isset($edit)) @if ($edit->status == $status) {{ 'selected' }} @endif
                                    @else @if (old('status', 'active') == $status) {{ 'selected' }} @endif @endif> {{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
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
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
@endpush
