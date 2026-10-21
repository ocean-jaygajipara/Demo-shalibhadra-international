@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    // dd($modules);
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
        {{-- <h5 class="card-header"></h5> --}}
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
                    <div class="col-md-6 col-sm-12 mb-2">
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

                    {{-- module --}}
                    <div class="col-md-6 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Module <span class="text-danger">*</span></label>
                            <select name="module" class="form-control select2 @error('module') is-invalid @enderror"
                                required>
                                <option value="">Select Module</option>

                                @foreach ($ManageForEmail as $key => $label)
                                    <option value="{{ $label['name'] }}"
                                        {{ old('module', $edit->module ?? '') == $label['name'] ? 'selected' : '' }}>
                                        {{ $label['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('module')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Type --}}
                    <div class="col-md-6 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <input type="text" name="ntype" class="form-control @error('ntype') is-invalid @enderror"
                                value="{{ old('ntype', isset($edit) ? $edit->ntype : '') }}" placeholder="Enter Type"
                                required {{ isset($edit) ? 'readonly' : ''}}>
                            @error('ntype')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                     {{-- Type --}}
                    <div class="col-md-6 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', isset($edit) ? $edit->name : '') }}" placeholder="Enter Template Name"
                                required>
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- Subject --}}
                    <div class="col-md-8 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Template Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror"
                                value="{{ old('subject', isset($edit) ? $edit->subject : '') }}" placeholder="Enter subject"
                                required>
                            @error('subject')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4 col-sm-12 mb-2">
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
                    {{--  Body  --}}
                    <div class="col-md-8 col-sm-12 mb-2" id="full-editor">
                        <label class="form-label" for="body">Template Body</label>
                        <textarea name="body" id="body" class="ckeditor_common_cls form-control @error('body') is-invalid @enderror"
                            placeholder="Enter Body">{{ old('body', isset($edit) ? $edit->body : '') }}</textarea>
                        @error('body')
                            <span class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    {{-- suggetion  --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <label class="form-label">Suggestions</label>
                        <ul class="list-unstyled suggestion-list">
                            @foreach ($ManageForSuggetion as $key => $value)
                                <li class="suggestion-item" data-value="{{ $value }}" style="cursor: pointer;">
                                    <strong>{{ $value }}</strong>
                                </li>
                            @endforeach
                        </ul>
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
@include('utils.getCompany')
<script>
    initializeCKEditor(".ckeditor_common_cls");
</script>
@endpush
