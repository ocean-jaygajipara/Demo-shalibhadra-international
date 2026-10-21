@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
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
            <form action="{{ isset($edit) ? route($route . '.update', $edit->id) : route($route . '.store') }}" method="POST"
                id="forminfo">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <input type="hidden" name="id" value="{{ $edit->id ?? '' }}">

                <div class="row">
                    {{-- Company --}}
                    @if (!$company_id)
                        <div class="col-md-3 mb-2 col-sm-12">
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

                    <!-- Parent ID -->
                    {{-- <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label for="parent_id">Parent</label>
                            <select name="parent_id" id="parent_id"
                                class="form-control select2 @error('parent_id') search_by_team_role is-invalid @enderror"
                                data-append="search_by_team_role"
                                data-selectedRoleId="{{ old('parent_id') ?? ($edit->parent_id ?? '') }}">
                            </select>
                            @error('parent_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div> --}}

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Parent @if (!$company_id)
                                    <span class="text-danger">*</span>
                                @endif </label>
                            <select name="parent_id" id="parent_id" placeholder="Select Parent"
                                class="form-control @error('parent_id') is-invalid @enderror search_by_team_role select2"
                                data-append="search_by_team_role"
                                data-selectedRoleId="{{ old('parent_id') ?? ($edit->parent_id ?? '') }}"
                                @if (!$company_id) required @endif>
                                <option value="" disabled
                                    {{ old('parent_id', $edit->parent_id ?? '') ? '' : 'selected' }}>
                                    Select Parent
                                </option>
                            </select>
                            @error('parent_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $edit->name ?? '') }}" placeholder="Enter Name">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control select2 @error('status') is-invalid @enderror">
                                <option disabled selected>Select Status</option>
                                @foreach (['active', 'inactive'] as $status)
                                    <option value="{{ $status }}"
                                        {{ old('status', $edit->status ?? 'active') === $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 text-center mt-3">
                        <button type="submit" class="btn btn-success">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger">Cancel</a>
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
    {{-- @include('utils.getTeams') --}}
    @include('utils.getTeamRole')
@endpush
