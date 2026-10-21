@extends('software.layout.app')

@php
    use App\Models\Company;
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
    $loginUserId = ($modules['authLoginUserDetail'] && $modules['authLoginUserDetail']?->id) ? $modules['authLoginUserDetail']?->id : null;


@endphp
@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css" />
@endsection

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
                    @php

                        $companies = Company::pluck('company_name', 'id'); // [id => name]
                        $selectedCompanyId = old('company_id', $edit->company_id ?? '');
                    @endphp

                     @if (!$company_id)
                        <div class="col-md-3">
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

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                            <label class="form-label"> Title <span class="text-danger">*</span> </label>
                            <input id="title" type="text" class="form-control @error('title') is-invalid @enderror"
                                name="title" value="{{ isset($edit?->title) ? $edit?->title : old('title') }}"
                                autocomplete="title" autofocus placeholder="Enter Title">

                            @error('title')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    {{-- Department --}}
                    <div class="col-md-3 mb-2">
                        <label for="department_id">Department</label>
                        <select name="department_id"
                            class="form-control search_by_department select2 @error('department_id') is-invalid @enderror"
                            data-selecteddepartmentid="{{ old('department_id', $edit->department_id ?? '') }}">
                            <option value="">Select Department</option>
                        </select>
                        @error('department_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>



                      @if (!$company_id && !$parent_type_id)
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Task Created By <span class="text-danger">*</span></label>
                                <select name="task_created_by" id="task_created_by"
                                    class="form-control @error('task_created_by') is-invalid @enderror search_by_created_by select2"
                                    data-append="search_by_created_by"
                                    data-selectedTaskCreatedBy="{{ isset($edit) && $edit?->task_created_by ? $edit?->task_created_by : old('search_by_created_by', $loginUserId) }}">
                                    <option value="" disabled
                                        {{ old('task_created_by', $edit->task_created_by ?? '') ? '' : 'selected' }}>
                                        Select Created By
                                    </option>
                                </select>
                                @error('task_created_by')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control search_by_created_by" name="task_created_by"
                            value="{{ $loginUserId }}" />
                    @endif


                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Task Assigned To <span class="text-danger">*</span></label>
                            <select name="task_assigned_to[]" id="task_assigned_to"
                                class="form-control @error('task_assigned_to') is-invalid @enderror search_by_assigned_to select2"
                                multiple data-append="search_by_assigned_to"
                                data-selectedTaskAssignedTo="{{ implode(',', old('task_assigned_to', isset($edit) ? explode(',', $edit->getRawOriginal('task_assigned_to')) : (isset($loginUserId) ? [$loginUserId] : []))) }}">
                            </select>
                            @error('task_assigned_to')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select id="priority" class="form-select select2 @error('priority') is-invalid @enderror"
                                        name="priority">
                                @foreach ($task_priority_arr as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('priority', $edit->priority ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('priority')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Task Arrive Date and Time</label>
                            <input id="arrival_date_time"
                                class="form-control datetimepicker @error('arrival_date_time') is-invalid @enderror"
                                name="arrival_date_time"
                                value="{{ old('arrival_date_time', isset($edit->arrival_date_time) ? \Carbon\Carbon::parse($edit->arrival_date_time)->format('d-m-Y h:i A') : now()->format('d-m-Y h:i A')) }}"
                                placeholder="Task Arrive Date and Time" autocomplete="off" min="{{ now()->format('Y-m-d') }}">
                            @error('arrival_date_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3 mb-2 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Due Date and Time</label>
                            <input id="due_date_time"
                                class="form-control datetimepicker @error('due_date_time') is-invalid @enderror"
                                name="due_date_time"
                                value="{{ old('due_date_time', isset($edit->due_date_time) ? \Carbon\Carbon::parse($edit->due_date_time)->format('Y-m-d H:i') : '') }}"
                                placeholder="Due Date and Time" autocomplete="off" min="{{ now()->format('Y-m-d') }}">
                            @error('due_date_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
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



                    <div class="col-md-6 col-sm-12 mb-3">
                        <div class="form-group">
                            <label class="form-label">Descriptiopn</label>
                            <textarea rows="3" cols="3" class="form-control @error('description') is-invalid @enderror" name="description" placeholder="Enter Descriptiopn" id="description_detail">{{ isset($edit) && $edit?->description ? $edit?->description : old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
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

@section('page_leavel_script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js"></script>
@endsection

@push('page_scripts')
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getTeams')
    @include('utils.getDepartment')
<script>
$(".datetimepicker").each(function() {
    $(this).datetimepicker({
        format: 'd-m-Y h:i A', // 12-hour format with AM/PM
        formatTime: 'h:i A', // explicitly control time format
        closeOnDateSelect: true,
        step: 10, // or 30 minutes if you want
        minDate: 0, // today,
        validateOnBlur: false
    });
});

$(document).on('change', '.datetimepicker', function() {

    const selected = moment($(this).val(), inputDateFormat);
    const minDate = moment($(this).attr('min'), inputDateFormat);
    const now = moment().subtract(30, 'minutes').format(inputDateFormat);

    if (!selected.isValid()) {
        toastr.error('Invalid date format.');
        return;
    }

    if (selected.isBefore(minDate)) {
        toastr.error('Selected datetime must be in the future: ' + minDate.format(inputDateFormat));
        $(this).val(''); // Optional: clear the invalid value
        $(this).attr('min', now);
    } else {
        console.log('Valid datetime selected:', selected.format(inputDateFormat));
        $(this).val(selected.format(inputDateFormat));
    }
});


$(document).on('blur', '.datetimepicker', function() {
    const selected = moment($(this).val(), inputDateFormat, true);
    $(this).val(selected.format(inputDateFormat)); // Correct output format
});
</script>
@endpush
