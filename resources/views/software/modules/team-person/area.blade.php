@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;

@endphp

@section('title', $page_title)

@section('page_leavel_style')
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.12.4/css/dataTables.bootstrap5.min.css" /> --}}
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => $modules['breadcrumb'] ?? [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => isset($edit) && $edit?->id ? 'Edit ' . $page_title : 'Add ' . $page_title,
                    'url' => '',
                ],
            ],
            'show_back_btn' => true,
        ])

        </a>
    </div>

    <div class="card my-3 mb-4">
        <div class="card-body">
            <form action="{{ route('team-person.assign.area.store', $teamperson->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="row">
                    {{-- Company --}}
                    @if (!$company_id)
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select id="company_id" name="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
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
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Country <span class="text-danger">*</span></label>
                            <select id="country_id" name="country_id"
                                class="form-control select2 search_by_country @error('country_id') is-invalid @enderror"
                                data-append="search_by_country" data-filterByStatus="active"
                                data-selectedCountryId="{{ $edit->country_id ?? '' }}"
                                data-selectedStateId="{{ $edit->state_id ?? '' }}" autofocus>
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
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select State <span class="text-danger">*</span></label>
                            <select id="state_id" name="state_id"
                                class="form-control select2 search_by_state @error('state_id') is-invalid @enderror"
                                data-append="search_by_state" data-selectedCountryId="{{ $edit->country_id ?? '' }}"
                                data-selectedStateId="{{ $edit->state_id ?? '' }}"
                                data-selectedCityId="{{ $edit->city_id ?? '' }}">
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
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select City <span class="text-danger">*</span></label>
                            <select id="city_id" name="city_id"
                                class="form-control select2 search_by_city @error('city_id') is-invalid @enderror"
                                data-append="search_by_city" data-selectedCityId="{{ $edit->city_id ?? '' }}">
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

                    {{-- Area --}}
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Area <span class="text-danger">*</span></label>
                            <select id="area_id" name="area_id"
                                class="form-control select2 search_by_area @error('area_id') is-invalid @enderror"
                                data-append="search_by_area" data-selectedAreaId="{{ $edit->area_id ?? '' }}">
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

                    {{-- Submit --}}
                    <div class="text-end">
                        <button type="submit" name="action" value="add"
                            class="btn btn-primary mt-4 waves-effect waves-light">
                            Add
                        </button>
                        <button type="submit" name="action" value="delete"
                            class="btn btn-danger mt-4 waves-effect waves-light">
                            Delete
                        </button>
                    </div>

                </div>

            </form>
        </div>
    </div>
    {{-- Assigned Areas --}}
    <div class="card my-3">
        <div class="card-body">
            <h5 class="mb-3">
                Assigned Areas for: <span>{{ $teamperson->name ?? '-' }}</span>

            </h5>

            <div class="card-datatable text-nowrap mt-3">
                <div class="card-datatable table-responsive">
                    <table id="yajra-datatables" class="dt-responsive table table-hover">
                        <thead>
                            <tr>
                                <th>Sr NO</th>
                                <th>Country</th>
                                <th>State</th>
                                <th>City</th>
                                <th>Area</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach ($assignedAreas as $assign)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $assign->country->name ?? '-' }}</td>
                                    <td>{{ $assign->state->name ?? '-' }}</td>
                                    <td>{{ $assign->city->name ?? '-' }}</td>
                                    <td>{{ $assign->area->area_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

{{-- Scripts --}}
@push('page_scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

    <script>
        $(document).ready(function() {
            let table = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('team-person.assigned.areas.data', $teamperson->id) }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'country',
                        name: 'country'
                    },
                    {
                        data: 'state',
                        name: 'state'
                    },
                    {
                        data: 'city',
                        name: 'city'
                    },
                    {
                        data: 'area',
                        name: 'area'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                order: [
                    [0, 'asc']
                ]
            });

            // $('.select2').select2({
            //     width: 'resolve'
            // });


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
    @include('software.inlcudes.script-delete-record')

@endpush
