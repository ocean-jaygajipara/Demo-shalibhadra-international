@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
@endphp
@section('title', $page_title)

@section('page_leavel_style')
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> --}}
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css" />
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
                                    data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}">
                                    <option value="">Select Company</option>
                                </select>

                                @error('company_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control" name="company_id" value="{{ $company_id }}" />
                    @endif

                    {{-- Team Person --}}
                    @if (!$parent_type_id && $loginUserId)
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Team Person <span class="text-danger">*</span></label>
                                <select id="team_person_ids"
                                    class="form-control search_by_team_person select2 @error('team_person_ids') is-invalid @enderror"
                                    name="team_person_ids"
                                    data-selectedids="{{ old('team_person_ids') ?? ($edit?->team_person_ids ?? '') }}">
                                    {{-- {{ isset($edit) && isset($edit->team_person_ids) ? $edit->team_person_ids : null }} --}}
                                    <option value="">Select Team Person</option>
                                </select>

                                @error('team_person_ids')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control" name="team_person_ids"
                            value="{{ $loginUserId ?? '' }}" />
                    @endif

                    {{-- leave Name --}}
                      <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> Select Leave Type <span class="text-danger">*</span> </label>
                            <select id="filter_leave_type"
                                class="form-control select2 @error('leave_type_id') is-invalid @enderror"
                                name="leave_type_id">
                                <option value="">Select Leave Type</option>
                                @foreach ($leaveTypes as $leaveType)
                                    <option value="{{ $leaveType->id }}"
                                        {{ old('leave_type_id', $edit->leave_type_id ?? '') == $leaveType->id ? 'selected' : '' }}>
                                        {{ $leaveType->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('leave_type_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- halfday / fullday --}}

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> Select Day <span class="text-danger">*</span> </label>
                            <select id="halfday_fullday"
                                class="form-control select2 @error('halfday_fullday') is-invalid @enderror"
                                name="halfday_fullday">
                                <option value="">Select Day</option>
                                @foreach ($leaveForDay as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('halfday_fullday', $edit->halfday_fullday ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('halfday_fullday')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    {{-- single day /multiple day --}}

                    {{-- <div id="single_multiple_day" class="col-md-4 col-sm-12 mb-2" style="display:none;">
                        <div class="form-group">
                            <label class="form-label">Single Day / Multiple Day <span class="text-danger">*</span></label>
                            <select id="singleday_multipleday"
                                class="form-control @error('singleday_multipleday') is-invalid @enderror"
                                name="singleday_multipleday"
                                data-selectedValue="{{ old('singleday_multipleday') ?? ($edit->singleday_multipleday ?? '') }}">
                                <option value="">Select Day</option>
                                @foreach ($leaveByDays as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('singleday_multipleday', $edit->singleday_multipleday ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('singleday_multipleday')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div> --}}

                    {{-- first half / second half --}}
                    <div id="first_second_half" class="col-md-4 col-sm-12 mb-2" style="display:none;">
                        <div class="form-group">
                            <label class="form-label"> First Half / Second Half <span class="text-danger">*</span>
                            </label>
                            <select id="firsthalf_secondhalf"
                                class="form-control  @error('firsthalf_secondhalf') is-invalid @enderror"
                                name="firsthalf_secondhalf">
                                <option value="">Select Day</option>
                                @foreach ($leaveForHalfdays as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('firsthalf_secondhalf', $edit->firsthalf_secondhalf ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('firsthalf_secondhalf')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- From Date --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label" for="fromdate_time">From Date <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control datetimepicker @error('fromdate_time') is-invalid @enderror"
                                name="fromdate_time"
                                value="{{ old('fromdate_time', isset($edit) && !empty($edit->fromdate_time) ? date('d-m-Y H:i', strtotime($edit->fromdate_time)) : '') }}"
                                placeholder="DD-MM-YYYY HH:MM" id="fromdate_time" autocomplete="off" />

                            @error('fromdate_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    {{-- To days  --}}
                    <div class="col-md-4 col-sm-12 mb-2" id="toDateContainer"
                        style="display: {{ old('singleday_multipleday', $edit->singleday_multipleday ?? '') == 'multiple' ? 'block' : 'none' }}">
                        <div class="form-group">
                            <label class="form-label" for="todate_time">To Date <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control datetimepicker @error('todate_time') is-invalid @enderror"
                                name="todate_time"
                                value="{{ old('todate_time', isset($edit) && !empty($edit->todate_time) ? date('d-m-Y H:i', strtotime($edit->todate_time)) : '') }}"
                                placeholder="DD-MM-YYYY HH:MM" id="todate_time" autocomplete="off" />

                            @error('todate_time')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    @if (!$company_id)
                        {{-- status --}}
                        <div class="col-md-4 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select class="form-control select2 w-100 @error('status') is-invalid @enderror"
                                    name="status" required id="statusSelect">
                                    <option disabled>Select Status</option>
                                    <option value="pending" style="color: orange;" selected
                                        @if ((isset($edit) && $edit->status == 'pending') || old('status') == 'pending') selected @endif>
                                        Pending
                                    </option>
                                </select>
                            </div>
                        </div>
                    @else
                        <input type="hidden" class="form-control" name="status"
                            value="{{ old('status', 'pending') }}" />
                    @endif



                    {{-- Leave Reason --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label" for="leave_reason">Leave Reason <span
                                    class="text-danger">*</span></label>

                            <textarea rows="3" cols="3" class="form-control @error('leave_reason') is-invalid @enderror"
                                name="leave_reason" placeholder="Enter Leave Reason" id="leave_reason">{{ old('leave_reason', $edit->leave_reason ?? '') }}</textarea>

                            @error('leave_reason')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="form-group">
                            <label class="form-label">Attachment</label>
                            <div class="input-group">
                                <input type="file" name="attachment" id="attachment"
                                    class="form-control @error('attachment') is-invalid @enderror">
                                @if (isset($edit) && $edit?->attachment && $edit?->attachment_url)
                                    <a href="{{ $edit?->attachment_url ?? '#' }}" target="_blank"
                                        class="input-group-text" for="inputGroupFile02">View File</a>
                                @endif
                            </div>
                            @error('attachment')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
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
    {{-- <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> --}}
    <!-- datetimepicker jQuery CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js">
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            function initializeDateTimePicker(selector, minDate) {
                $(selector).datetimepicker('destroy');
                $(selector).datetimepicker({
                    format: 'd-m-Y H:i',
                    closeOnDateSelect: true,
                    minDate: minDate || new Date()
                });
            }

            initializeDateTimePicker('#fromdate_time');
            initializeDateTimePicker('#todate_time');

            (function setInitialMinDateForEdit() {
                var fromDateVal = $('#fromdate_time').val();
                if (fromDateVal) {
                    var parts = fromDateVal.split(/[- :]/);
                    var fromDate = new Date(parts[2], parts[1] - 1, parts[0], parts[3], parts[4]);
                    initializeDateTimePicker('#todate_time', fromDate);
                }
            })();

            $('#fromdate_time').on('change', function() {
                var fromDate = $(this).val();
                if (fromDate) {
                    var parts = fromDate.split(/[- :]/);
                    var dateObj = new Date(parts[2], parts[1] - 1, parts[0], parts[3], parts[4]);
                    initializeDateTimePicker('#todate_time', dateObj);
                }
            });

            $('#todate_time').on('blur', function() {
                var fromVal = $('#fromdate_time').val();
                var toVal = $('#todate_time').val();

                if (fromVal && toVal) {
                    var f = fromVal.split(/[- :]/);
                    var t = toVal.split(/[- :]/);

                    var fromDate = new Date(f[2], f[1] - 1, f[0], f[3], f[4]);
                    var toDate = new Date(t[2], t[1] - 1, t[0], t[3], t[4]);

                    if (toDate < fromDate) {
                        showToastError('To Date cannot be earlier than From Date.');

                        var formatted = ('0' + f[0]).slice(-2) + '-' +
                            ('0' + f[1]).slice(-2) + '-' +
                            f[2] + ' ' +
                            ('0' + f[3]).slice(-2) + ':' +
                            ('0' + f[4]).slice(-2);

                        $('#todate_time').val(formatted);
                    }
                }
            });


            $('form').on('submit', function(e) {
                var fromVal = $('#fromdate_time').val();
                var toVal = $('#todate_time').val();

                if (fromVal && toVal) {
                    var f = fromVal.split(/[- :]/);
                    var t = toVal.split(/[- :]/);

                    var fromDate = new Date(f[2], f[1] - 1, f[0], f[3], f[4]);
                    var toDate = new Date(t[2], t[1] - 1, t[0], t[3], t[4]);

                    if (toDate < fromDate) {
                        e.preventDefault();
                        showToastError('To Date must be greater than or equal to From Date.');
                    }
                }
            });


            function showToastError(message) {
                toastr.error(message);
            }

        });
    </script>



    <script>
        function previewImage(event, previewId) {
            const reader = new FileReader();
            const imageField = document.getElementById(previewId);

            reader.onload = function() {
                imageField.src = reader.result;
                imageField.style.display = 'block';
            }

            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>


    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    @if (!$parent_type_id && $loginUserId)
        @include('utils.getTeamPerson')
    @endif

    @include('utils.getLeaveType')



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const fromDateInput = document.getElementById('fromdate_time');
            const toDateInput = document.getElementById('todate_time');
            const daySelect = document.getElementById('halfday_fullday');
            const toDateField = document.getElementById('toDateField');

            /*
            const fromDateTimePicker = flatpickr(fromDateInput, {
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                minDate: "today",
                time_24hr: true,
                onChange: function(selectedDates) {
                    if (toDateField.style.display !== 'none') {
                        toDateTimePicker.set('minDate', selectedDates[0]);
                    }
                }
            });

            const toDateTimePicker = flatpickr(toDateInput, {
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                minDate: "today",
                time_24hr: true,
                onChange: function(selectedDates, dateStr, instance) {
                    const fromDate = flatpickr.parseDate(fromDateInput.value, "d-m-Y H:i");
                    const toDate = flatpickr.parseDate(dateStr, "d-m-Y H:i");

                    if (fromDate && toDate && toDate < fromDate) {
                        alert("To Date cannot be earlier than From Date.");
                        instance.clear();
                    }
                }
            });
            */

            // function  () {
            //     const selectedValue = daySelect.value;
            //     if (selectedValue === 'halfday') {
            //         toDateField.style.display = 'none';
            //     } else {
            //         toDateField.style.display = 'block';
            //         toDateTimePicker.open();
            //     }
            // }




        });
    </script>

    <!-- Optional jQuery version (only if you want both) -->
    <script>
        $(document).ready(function() {
            function toggleToDateField() {
                var selectedValue = $('#halfday_fullday').val();
                if (selectedValue.toLowerCase() === 'halfday') {
                    $('#toDateContainer').hide();
                } else {
                    $('#toDateContainer').show();
                }
            }

            // Run on page load
            toggleToDateField();

            // Run on change
            $('#halfday_fullday').on('change', function() {
                toggleToDateField();
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const halfDaySelect = document.getElementById('halfday_fullday');
            const singleMultipleDay = document.getElementById('single_multiple_day');
            const firstSecondHalf = document.getElementById('first_second_half');

            function toggleLeaveInputs() {
                const selectedValue = halfDaySelect.value;

                if (selectedValue === 'half') {

                    singleMultipleDay.style.display = 'none';
                    firstSecondHalf.style.display = 'block';
                } else if (selectedValue === 'full') {

                    firstSecondHalf.style.display = 'none';
                    singleMultipleDay.style.display = 'block';
                } else {

                    singleMultipleDay.style.display = 'block';
                    firstSecondHalf.style.display = 'block';
                }
            }

            toggleLeaveInputs();

            halfDaySelect.addEventListener('change', toggleLeaveInputs);
        });

        $(document).ready(function() {
            function toggleLeaveFields() {
                var selectedValue = $('#halfday_fullday').val();

                if (selectedValue === 'halfday') {
                    $('#first_second_half').show();
                    $('#single_multiple_day').hide();
                    $('#todate_time').hide();
                } else if (selectedValue === 'fulalday') {
                    $('#first_second_half').hide();
                    $('#single_multiple_day').show();
                    $('#fromdate_time').show();
                } else {

                    $('#first_second_half').hide();
                    $('#single_multiple_day').hide();
                }
            }

            toggleLeaveFields();

            $('#halfday_fullday').on('change', function() {
                toggleLeaveFields();
            });
        });

        $(document).ready(function() {
            function toggleToDateField() {
                var selectedValue = $('#halfday_fullday').val();
                if (selectedValue === 'halfday') {
                    $('#todate_time').hide().val('');
                } else {
                    $('#todate_time').show();
                }
            }

            function toggleLeaveFields() {
                var selectedValue = $('#halfday_fullday').val();

                // Show/hide relevant fields
                if (selectedValue === 'halfday') {
                    $('#first_second_half').show();
                    $('#single_multiple_day').hide();
                } else if (selectedValue === 'fullday') {
                    $('#first_second_half').hide();
                    $('#single_multiple_day').show();
                } else {
                    $('#first_second_half').hide();
                    $('#single_multiple_day').hide();
                }

                // Check single/multiple day selection
                var dayType = $('#singleday_multipleday').val();
                if (dayType === 'singleday') {
                    $('#toDateContainer').hide();
                    $('#todate_time').val('');
                } else if (dayType === 'multiple') {
                    $('#toDateContainer').show();
                }
            }

            // Bind change events
            $('#halfday_fullday, #singleday_multipleday').change(function() {
                toggleLeaveFields();
                toggleToDateField();
            });

            // Call on page load
            toggleLeaveFields();
            toggleToDateField();


        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            function updateToDateVisibility() {
                const halfdayOrFullday = halfdayFullDaySelect.value;
                const singleOrMultiple = singleMultipleDaySelect.value;

                if (halfdayOrFullday === 'halfday') {
                    toDateContainer.style.display = 'none';
                    singleMultipleDayContainer.style.display = 'none';
                } else if (halfdayOrFullday === 'fullday') {
                    singleMultipleDayContainer.style.display = 'block';

                    // if (singleOrMultiple === 'singleday') {
                    //     toDateContainer.style.display = 'none';
                    // } else if (singleOrMultiple === 'multipleday') {
                    //     toDateContainer.style.display = 'block';
                    // } else {
                    //     toDateContainer.style.display = 'none';
                    // }
                } else {

                    toDateContainer.style.display = 'none';
                    singleMultipleDayContainer.style.display = 'none';
                }
            }

            updateToDateVisibility();


            halfdayFullDaySelect.addEventListener('change', updateToDateVisibility);
            // singleMultipleDaySelect.addEventListener('change', updateToDateVisibility);


            // const selectedValue = singleMultipleDaySelect.dataset.selectedvalue;
            // if (selectedValue) {
            //     singleMultipleDaySelect.value = selectedValue;
            // }
        });
    </script>

@endpush
