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
                <div class="col-md-3 col-sm-12">
                    <div class="form-group">
                        <label class="form-label"> Country Name <span class="text-danger">*</span> </label>
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                            name="name" value="{{ isset($edit?->name) ? $edit?->name : old('name') }}"
                            autocomplete="name" autofocus placeholder="Enter country name">

                        @error('name')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3 col-sm-12">
                    <div class="form-group">
                        <label class="form-label"> Country Code <span class="text-danger">*</span> </label>
                        <input id="code" type="number" class="form-control @error('code') is-invalid @enderror"
                            name="code" value="{{ isset($edit?->code) ? $edit?->code : old('code') }}"
                            autocomplete="code" autofocus placeholder="Enter country code" oninput="this.value=this.value.slice(0,4)"
                            >

                        @error('code')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3 col-sm-12">
                    <div class="form-group">
                        <label class="form-label"> Short Name <span class="text-danger">*</span> </label>
                        <input id="short_name" type="text"
                            class="form-control @error('short_name') is-invalid @enderror" name="short_name"
                            value="{{ isset($edit?->short_name) ? $edit?->short_name : old('short_name') }}"
                            autocomplete="short_name" autofocus placeholder="Enter short name">

                        @error('short_name')
                        <span class="invalid-feedback">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3 col-sm-12">
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
