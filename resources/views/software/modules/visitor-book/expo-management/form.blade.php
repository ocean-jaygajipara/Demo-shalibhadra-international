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
                        {{-- Company --}}
                        <div class="col-md-4 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Select Company <span class="text-danger">*</span></label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id', $edit->company_id ?? '') }}">
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


                    {{-- Expo Category --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expo Category <span class="text-danger">*</span></label>
                            <select id="expo_category_id"
                                class="form-control select2 search_by_expo_category @error('expo_category_id') is-invalid @enderror"
                                name="expo_category_id"
                                data-selectedcategoryid="{{  isset($edit) ? old('expo_category_id', $edit->expo_category_id) : old('expo_category_id') }}">
                                <option value="">Select Expo Category</option>
                            </select>
                            @error('expo_category_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expo Name <span class="text-danger">*</span></label>
                            <input id="name" type="text" step="0.01"
                                class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ isset($edit) ? old('name', $edit->name) : old('name') }}"
                                data-oldValue="{{ isset($edit) ? old('name', $edit->name) : old('name') }}"
                                placeholder="Enter Expo Name">
                            @error('name')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-4 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Add Expo Image </label>
                            <div class="input-group">
                                <input type="file" name="attachment" id="attachment"
                                    class="form-control @error('attachment') is-invalid @enderror" accept="image/*">
                            </div>

                            {{-- Preview Image (Edit or Live Selected) --}}
                            <div class="mt-2">
                                <img id="preview-image"
                                    src="{{ isset($edit) && $edit->attachment_url ? $edit->attachment_url : '#' }}"
                                    alt="Expo Image Preview" class="img-thumbnail"
                                    style="max-height: 100px; {{ isset($edit) && $edit->attachment_url ? '' : 'display:none;' }}">
                            </div>

                            @error('attachment')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>



                    <div class="col-md-4 col-sm-12 mb-2">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="text" id="date_display" name="start_date"
                            class="form-control @error('start_date') is-invalid @enderror"
                            value="{{ old('start_date', isset($edit->start_date) ? date('d-m-Y', strtotime($edit->start_date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="start_date" id="start_date"
                            value="{{ old('start_date', isset($edit->start_date) ? date('Y-m-d', strtotime($edit->start_date)) : date('Y-m-d')) }}" />
                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="text" id="end_date_display" name="end_date"
                            class="form-control @error('end_date') is-invalid @enderror"
                            value="{{ old('end_date', isset($edit->end_date) ? date('d-m-Y', strtotime($edit->end_date)) : date('d-m-Y')) }}"
                            placeholder="DD-MM-YYYY" readonly />

                        <input type="hidden" name="end_date" id="end_date"
                            value="{{ old('end_date', isset($edit->end_date) ? date('Y-m-d', strtotime($edit->end_date)) : date('Y-m-d')) }}" />
                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Expo URL </label>
                            <input id="expo_url" type="text" step="0.01"
                                class="form-control @error('expo_url') is-invalid @enderror" name="expo_url"
                                value="{{ isset($edit) ? old('expo_url', $edit->expo_url) : old('expo_url') }}"
                                data-oldValue="{{ isset($edit) ? old('expo_url', $edit->expo_url) : old('expo_url') }}"
                                placeholder="Enter Expo URL">
                            @error('expo_url')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Location </label>
                            <input id="location" type="text" step="0.01"
                                class="form-control @error(' ') is-invalid @enderror" name="location"
                                value="{{ isset($edit) ? old('location', $edit->location) : old('location') }}"
                                data-oldValue="{{ isset($edit) ? old('location', $edit->location) : old('location') }}"
                                placeholder="Enter Location">
                            @error('location')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-4 col-sm-12">
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
                    {{-- Divider --}}
                    <div class="divider">
                        <hr />
                    </div>

                    {{-- Submit  --}}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/autosize@4.0.2/dist/autosize.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getExpoCategory')
    <script>
        document.getElementById('attachment').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview-image');

            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        });
    </script>

    <script>
        // Start Date
        flatpickr("#date_display", {
            altInput: true,
            altFormat: "d-m-Y", // What user sees
            dateFormat: "Y-m-d", // What goes in hidden input
            minDate: "today",
            defaultDate: "{{ isset($edit->start_date) ? date('Y-m-d', strtotime($edit->start_date)) : date('Y-m-d') }}",
            onChange: function(selectedDates, dateStr) {
                document.getElementById('start_date').value = dateStr;
                endDatePicker.set('minDate', dateStr);
            }
        });

        // End Date
        let endDatePicker = flatpickr("#end_date_display", {
            altInput: true,
            altFormat: "d-m-Y",
            dateFormat: "Y-m-d",
            minDate: "{{ isset($edit->start_date) ? date('Y-m-d', strtotime($edit->start_date)) : date('Y-m-d') }}",
            defaultDate: "{{ isset($edit->end_date) ? date('Y-m-d', strtotime($edit->end_date)) : date('Y-m-d') }}",
            onChange: function(selectedDates, dateStr) {
                document.getElementById('end_date').value = dateStr;
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            autosize($('.autosize'));
        });
    </script>
@endpush
