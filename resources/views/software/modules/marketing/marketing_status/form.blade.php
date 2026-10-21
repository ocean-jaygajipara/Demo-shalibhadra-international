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
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-control select2 w-100 @error('type') is-invalid @enderror" name="type"
                                id="type" required>
                                <option disabled selected>Select Type</option>
                                @foreach (['Inquiry', 'Followup'] as $type)
                                    <option value="{{ $type }}"
                                        @if (isset($edit)) @if ($edit->type == $type) {{ 'selected' }} @endif
                                    @else @if (old('type', 'Inquiry') == $type) {{ 'selected' }} @endif @endif> {{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name', $edit->name ?? '') }}" autocomplete="name"
                                placeholder="Status name">
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Status Color <span class="text-danger">*</span></label>
                            <input type="color" id="status_color"
                                class="form-control @error('status_color') is-invalid @enderror" name="status_color"
                                value="{{ old('status_color', $edit->status_color ?? '#000000') }}"
                                style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('status_color')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Status Color 2 (For Gradiant Use) <span class="text-danger">*</span></label>
                            <input type="color" id="status_color_2"
                                class="form-control @error('status_color_2') is-invalid @enderror" name="status_color_2"
                                value="{{ old('status_color_2', $edit->status_color_2 ?? '#000000') }}"
                                style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('status_color_2')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Text Color <span class="text-danger">*</span></label>
                            <input type="color" id="text_color"
                                class="form-control @error('text_color') is-invalid @enderror" name="text_color"
                                value="{{ old('text_color', $edit->text_color ?? '#000000') }}"
                                style="height: 38px; width: 100%; padding: 2px 10px;">
                            @error('text_color')
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

                    <div class="col-md-3 col-sm-12 mb-2">
                        <div class="form-check mt-4">
                            {{-- <input class="form-check-input" type="checkbox" value="{{ (isset($edit) && isset($edit?->is_default)) ? $edit?->is_default : old('is_default', $edit?->is_default ?? '1') }}" id="setAsDefault" name="is_default" {{ (isset($edit) && $edit?->is_default == '1') ? "checked" : '' }}> --}}
                            <input class="form-check-input" type="checkbox" value="1" id="setAsDefault" name="is_default" {{ old('is_default', $edit->is_default ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="setAsDefault"> Set As Default </label>
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
