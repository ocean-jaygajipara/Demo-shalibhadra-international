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
                        <div class="col-md-3 col-sm-12 mb-2">
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Status Type <span class="text-danger">*</span></label>
                            <select class="form-control select2 w-100 @error('status_type') is-invalid @enderror" name="status_type"
                                id="status_type" required>
                                <option disabled selected>Select Type</option>
                                @foreach ($statusTypes as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('status_type', $edit->status_type ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Status Name <span class="text-danger">*</span></label>
                            <input id="status_name" type="text" class="form-control @error('status_name') is-invalid @enderror"
                                name="status_name" value="{{ old('status_name', $edit->status_name ?? '') }}" autocomplete="status_name"
                                placeholder="Status name">
                            @error('status_name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Background Color <span class="text-danger">*</span></label>
                            <input type="color" id="background_color"
                                class="form-control @error('background_color') is-invalid @enderror" name="background_color"
                                value="{{ old('background_color', $edit->background_color ?? '#000000') }}"
                                style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('background_color')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Gradient Color <span class="text-danger">*</span></label>
                            <input type="color" id="gradient_color"
                                class="form-control @error('gradient_color') is-invalid @enderror" name="gradient_color"
                                value="{{ old('gradient_color', $edit->gradient_color ?? '#000000') }}"
                                style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('gradient_color')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Font Color <span class="text-danger">*</span></label>
                            <input type="color" id="font_color"
                                class="form-control @error('font_color') is-invalid @enderror" name="font_color"
                                value="{{ old('font_color', $edit->font_color ?? '#000000') }}"
                                style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('font_color')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
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
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
@endpush
