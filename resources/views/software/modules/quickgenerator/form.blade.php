@extends('software.layout.app')

@php
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
            <form action="{{ isset($edit) ? route($route . '.update', [$edit->id]) : route($route . '.store') }}"
                method="POST">
                @csrf
                @isset($edit)
                    @method('PUT')
                @endisset

                <div class="row">


                    {{-- Company Name --}}

                    @if (!$company_id)
                        <div class="col-md-4 mb-4">
                            <label class="form-label">Select Company</label>
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
                    @else
                        <input type="hidden" class="form-control search_by_company" name="company_id"
                            value="{{ $company_id }}" />
                    @endif


                    {{-- Lable --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">QR Label <span class="text-danger">*</span></label>
                            <input type="text" name="label" value="{{ old('label', $edit->label ?? '') }}"
                                class="form-control @error('label') is-invalid @enderror" placeholder="Enter Label">
                            @error('label')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Max Customers --}}
                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label"> QR Code Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" value="{{ old('quantity', $edit->quantity ?? '') }}"
                                class="form-control @error('quantity') is-invalid @enderror" placeholder="Enter Quantity">
                            @error('quantity')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 mb-4">
                        <div class="form-group">
                            <label class="form-label">Per Qr Point Value <span class="text-danger">*</span></label>
                            <input type="number" name="collect_points"
                                value="{{ old('collect_points', $edit->collect_points ?? '') }}"
                                class="form-control @error('collect_points') is-invalid @enderror"
                                placeholder="Enter Qr Point Value">
                            @error('collect_points')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>



                    {{-- Status --}}
                    <div class="col-md-4 mb-4">
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
                    <div class="divider col-12">
                        <hr />
                    </div>

                    {{-- Submit --}}
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-success">{{ isset($edit) ? 'Update' : 'Submit' }}</button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page_scripts')
    @include('utils.getCompany')

    <script>
        $(document).ready(function() {
            $('#app_right').select2({
                placeholder: "Select Application Rights",
                allowClear: true
            });
            $('#panel_right').select2({
                placeholder: "Select Panel Rights",
                allowClear: true
            });
        });
    </script>
@endpush
