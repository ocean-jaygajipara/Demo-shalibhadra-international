@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
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

                    {{-- Company --}}
                         @if (!$company_id)
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label"> Select Company <span class="text-danger">*</span> </label>
                            <select id="company_id"
                                class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                name="company_id" required
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


                    {{-- Transport By --}}
                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Select Transport By <span class="text-danger">*</span></label>
                            <select name="transport_by_id" id="transport_by_id"
                                class="form-control @error('transport_by_id') is-invalid @enderror search_by_transport_by_id select2"
                                data-append="search_by_transport_by_id"
                                data-selectedTransportbyId="{{ isset($edit) && $edit?->transport_by_id ? $edit?->transport_by_id : old('transport_by_id') }}">

                                <option value="" disabled
                                    {{ old('transport_by_id', $edit->transport_by_id ?? '') ? '' : 'selected' }}>
                                    Select Transport By
                                </option>
                            </select>
                            @error('transport_by_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label"> Name <span class="text-danger">*</span> </label>
                            <input id="name" type="text"
                                class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ isset($edit) && $edit?->name ? $edit?->name : old('name') }}"
                                placeholder="Enter Name">
                            @error('name')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label"> Transport GSTID <span class="text-danger">*</span> </label>
                            <input id="transport_gst_id" type="text"
                                class="form-control @error('transport_gst_id') is-invalid @enderror" name="transport_gst_id"
                                value="{{ isset($edit) && $edit?->transport_gst_id ? $edit?->transport_gst_id : old('transport_gst_id') }}"
                                placeholder="Enter Transport GSTID">
                            @error('transport_gst_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>



                    {{-- Status --}}
                    <div class="col-md-4 col-sm-12 mb-3">
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
    @include('utils.getTransportby')
@endpush

