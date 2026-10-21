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
    // dd($modules);
@endphp
@section('title', $page_title)

@section('page_leavel_style')
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.12.4/css/dataTables.bootstrap5.min.css" /> --}}
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"> --}}
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection


@section('content')
    <div class="px-1">
        <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
                'route' => $route,
                'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
                'show_filter_btn' => true,
                'show_back_btn' => false,
                'show_export_btn' =>
                    isset($modules['excel_permission']) &&
                    isset($modules['print_permission']) &&
                    ($modules['excel_permission'] || $modules['print_permission'])
                        ? true
                        : false,
                'show_excal_btn' => isset($modules['excel_permission']) ? $modules['excel_permission'] : false,
                'show_print_btn' => isset($modules['print_permission']) ? $modules['print_permission'] : false,
            ])
        </div>
    </div>
    <div class="row my-3">
        <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @if (!$company_id)
                            <!-- Company Filter -->
                            <div class="col-md-3 col-sm-12">
                                <label class="form-label">Filter by Company</label>
                                <select id="company_id" name="company_id"
                                    class="form-control search_by_company select2 select_filter"
                                    data-append="search_by_company" data-append-leaveType="#filter_leave_type">
                                    <option value="">Filter by Company</option>
                                    <!-- Options for Company will be dynamically loaded here -->
                                </select>
                            </div>
                        @endif
                        <!-- Team Person Filter -->
                        @if (!$parent_type_id)
                            <div class="col-md-3 col-sm-12">
                                <label class="form-label">Filter by Team Person</label>
                                <select id="team_person_ids" name="team_person_ids"
                                    class="form-control search_by_team_person select2 select_filter"
                                    data-append="search_by_team_person">
                                    <option value="">Filter by Team Person</option>
                                    <!-- Options for Team Person will be dynamically loaded here -->
                                </select>
                            </div>
                        @endif


                        <!-- Search Input -->
                        <div class="col-md-3 col-sm-12">
                            <label class="form-label">Search</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>

                        {{-- Leave Type --}}
                        <div class="col-md-3 col-sm-12">
                            <label class="form-label">Filter by Leave Type</label>
                            <select id="filter_leave_type" name="filter_leave_type"
                                class="form-control search_by_leave_type select2 select_filter"
                                data-append="data-append-leaveType">
                                <option value="">Select Leave Type</option>

                            </select>
                        </div>

                        {{-- Filter by Halfday / Fullday --}}
                        <div class="col-md-3 col-sm-12">
                            <label class="form-label">Filter by Day</label>
                            <select id="filter_halfday_fullday" name="halfday_fullday"
                                class="form-control select2 mb-2 select_filter" style="width: 100%;">
                                <option value="">Select Day Type</option>
                                @foreach ($leaveForDay as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>

                        </div>



                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Date</label>
                            <input type="text" name="fromdate_time" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div>




                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label for="status_filter" class="form-label">Filter by Status</label>
                                <select id="status_filter" name="status" class="form-select select2">
                                    <option value="all">All</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approve</option>
                                    <option value="Rejected">Reject</option>
                                </select>
                            </div>
                            <div>
                                <button type="button" title="Clear Filter" id="cilory_filter"
                                    class="btn btn-outline-danger btn-icon mt-2"><i class="ti ti-x"></i></button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-datatable text-nowrap mt-3">
                    <div class="card-datatable table-responsive">
                        <table id="yajra-datatables" class="dt-responsive table table-hover">
                            @if (isset($columns))
                                <thead>
                                    <tr>
                                        @foreach ($columns as $item)
                                            <th class="{{ $item?->className ?? '' }}">
                                                {{ ucfirst($item?->td_label ?? $item?->name) ?? '' }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="modal fade show" id="leaveApplicationAcutionModel" tabindex="-1" aria-hidden="true" style="display: block"> --}}
    <div class="modal fade" id="leaveApplicationAcutionModel" tabindex="-1" aria-labelledby="leaveModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-sm border-0">

                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white text-center" id="leaveModalLabel">Person Details</h5>
                    <button type="button" class="btn-close btn-close-white text-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">

                    <!-- Leave Details Card -->
                    <div class="card border-light mb-4">
                        <div class="card-body p-3">
                            <div class="row">
                                <input type="hidden" id="leave_application_id" value="" />
                                <input type="hidden" id="company_id" value="" />
                                <!-- Leave details here... -->
                                <div class="col-md-6 mb-2"><strong>Company Name:</strong> <span id="compay_name"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>Team Person Name:</strong> <span id="team_person_name"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>Leave Type:</strong> <span id="leave_type"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>Leave For Day:</strong> <span id="leave_for_day"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>Leave By Days:</strong> <span id="leave_by_days"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>From Date:</strong> <span id="fromdate_time"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>To Date:</strong> <span id="todate_time"
                                        class="text-muted"></span></div>
                                <div class="col-md-6 mb-2"><strong>Reason:</strong> <span id="leave_reason"
                                        class="text-muted"></span></div>
                            </div>
                        </div>
                    </div>
                    {{-- status --}}
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label fw-semibold">Select Status</label>
                        <select id="statusSelect" name="status" class="form-select" required>
                            <option disabled selected value="">Select Status</option>
                            @foreach (['approved', 'rejected'] as $status)
                                <option value="{{ $status }}"
                                    @if (isset($edit) && $edit->status == $status) selected
                                @elseif (old('status') == $status) selected @endif>
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" id="leave_request_id" name="leave_request_id" value="">


                    <!-- Rejection Reason -->
                    <div id="rejectBoxContainer" class="mb-3" style="display: none;">
                        <label for="rejectTextarea" class="form-label fw-semibold">Rejection Reason</label>
                        <textarea id="rejectTextarea" name="reject" class="form-control" rows="4"
                            placeholder="Provide a reason for rejection" required>{{ old('reject', isset($edit) ? $edit->reject : '') }}</textarea>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer justify-content-center w-100 text-center">
                    <button type="button" id="submitBtn" class="btn btn-primary">
                        Submit
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    </div>



@endsection


@section('page_leavel_script')
    <!-- Data tables -->
    {{-- <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.4/js/dataTables.bootstrap5.min.js"></script> --}}
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection

@push('page_scripts')
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @if (!$parent_type_id)
        @include('utils.getTeamPerson')
    @endif
    @include('utils.getLeaveType')

    <script type="text/javascript">
        let filterParams = {};

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });


        $(function() {
            var picker = $('.my_daterangepicker').data('daterangepicker');
            if (picker) {
                picker.maxDate = false;

                var startDate = moment().startOf('month');
                // var endDate = moment().add(1, 'month').endOf('month');
                const endDate = moment().endOf('year');
                // console.log("L-281", moment().add(1, 'month').endOf('month'), moment().endOf('year'));


                picker.setStartDate(startDate);
                picker.setEndDate(endDate);
                picker.callback(startDate, endDate, 'Initial range');
            }
        });


        let dtable = null;

        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, 'ASC']
                ],
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                    dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                ajax: {
                    url: "{{ route($route . '.index') }}",
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.company_id = $('#company_id').val();
                        data.team_person_ids = $('#team_person_ids').val();
                        data.leave_type = $('#filter_leave_type').val();
                        data.halfday_fullday = $('#filter_halfday_fullday').val();
                        data.status = $('#status_filter').val();
                        data.filter_quotation_date = $('input[name="fromdate_time"]').val().replace(
                            ' - ', ' to ');
                    }
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...'
                }
            });

            $('.select_filter').on('change', function(event) {
                event.preventDefault();
                dtable.draw();
            });

            $('input[name="fromdate_time"]').on('change apply.daterangepicker', function() {
                dtable.draw();
            });

            $('#status_filter').on('change', function() {
                dtable.ajax.reload();
            });

            $('input[name="search"]').keyup(function() {
                filterParams.search = $(this).val();
                dtable.draw();
            });

            $('#cilory_filter').on('click', function() {
                const defaultStartDate = moment().startOf('month');
                // const defaultEndDate = moment().add(1, 'month').endOf('month');
                const defaultEndDate = moment().endOf('year');
                // console.log("L-340", moment().add(1, 'month').endOf('month'), moment().endOf('year'));

                const picker = $('input[name="fromdate_time"]').data('daterangepicker');

                picker.setStartDate(defaultStartDate);
                picker.setEndDate(defaultEndDate);

                $('input[name="fromdate_time"]').val(
                    defaultStartDate.format('DD/MM/YYYY') + ' - ' + defaultEndDate.format('DD/MM/YYYY')
                );

                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');

                dtable.draw();
            });

            function getDateRange() {
                let dateRangeVal = $('input[name="fromdate_time"]').val();
                if (!dateRangeVal) return [null, null];
                let [from_date, to_date] = dateRangeVal.split(' - ');
                return [from_date, to_date];
            }

            // $('#export_excel_btn').on('click', function(e) {
            //     e.preventDefault();

            //     const [from_date, to_date] = getDateRange();

            //     let queryParams = $.param({
            //         search: $('input[name="search"]').val(),
            //         status: $('#status_filter').val(),
            //         company: $('#company_id').val(),
            //         leave_type: $('#filter_leave_type').val(),
            //         halfday_fullday: $('#filter_halfday_fullday').val(),
            //         from_date: from_date,
            //         to_date: to_date
            //     });

            //     let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            //     window.location.href = url;
            // });
            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();
                const filterQuotationDate = $('input[name="fromdate_time"]').val().replace(' - ', ' to ');
                let queryParams = $.param({
                    search: $('input[name="search"]').val(),
                    status: $('#status_filter').val(),
                    company_id: $('#company_id').val(),
                    team_person_ids: $('#team_person_ids').val(),
                    leave_type: $('#filter_leave_type').val(),
                    halfday_fullday: $('#filter_halfday_fullday').val(),
                    filter_quotation_date: filterQuotationDate
                });

                let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;

                window.location.href = url;
            });


            $('#print_btn').on('click', function(e) {
                e.preventDefault();

                const filterQuotationDate = $('input[name="fromdate_time"]').val().replace(' - ', ' to ');

                let queryParams = $.param({
                    search: $('input[name="search"]').val(),
                    status: $('#status_filter').val(),
                    company_id: $('#company_id').val(),
                    team_person_ids: $('#team_person_ids').val(),
                    leave_type: $('#filter_leave_type').val(),
                    halfday_fullday: $('#filter_halfday_fullday').val(),
                    filter_quotation_date: filterQuotationDate
                });

                let url = "{{ route($route . '.print') }}" + "?" + queryParams;
                window.open(url, '_blank');
            });

            // Leave Application Modal Handling
            $(document).on("click", ".leaveApplicationAcutionModel", function() {
                const modal = $("#leaveApplicationAcutionModel");
                modal.find('#leave_application_id').val($(this).data("id"));
                modal.find('#company_id').val($(this).data("company_id"));
                modal.find('#compay_name').text($(this).data("company_name"));
                modal.find('#team_person_name').text($(this).data("team_person_name"));
                modal.find('#leave_type').text($(this).data("leave_type"));
                modal.find('#fromdate_time').text($(this).data("fromdate_time"));
                modal.find('#todate_time').text($(this).data("todate_time") || '-');
                modal.find('#leave_for_day').text($(this).data("leave_for_day"));
                modal.find('#leave_by_days').text($(this).data("leave_by_days"));
                modal.find('#leave_for_half').text($(this).data("leave_for_half"));
                modal.find('#leave_reason').text($(this).data("leave_reason"));

                modal.find('#statusSelect').val('');
                toggleRejectBox();

                modal.modal('show');
            });

            function toggleRejectBox() {
                const status = $('#statusSelect').val();
                $('#rejectBoxContainer').toggle(status === 'reject');
                $('#rejectTextarea').prop('required', status === 'reject');
            }

            $('#statusSelect').change(toggleRejectBox);

            $('#submitBtn').on('click', function() {
                const modal = $("#leaveApplicationAcutionModel");
                const leave_application_id = modal.find("#leave_application_id").val();
                const status = modal.find('#statusSelect').val();
                const rejectReason = modal.find('#rejectTextarea').val();

                if (!leave_application_id) return toastr.error("Leave Application ID is required.");
                if (!status) return toastr.error("Please select a status.");
                if (status === 'reject' && !rejectReason) return toastr.error(
                    "Rejection reason is required.");

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('id', leave_application_id);
                formData.append('status', status);
                formData.append('reject', rejectReason);

                $.ajax({
                    url: "{{ route('submit-leavel-application') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                        if (data.success) {
                            modal.modal('hide');
                            toastr.success("Leave request submitted successfully!");
                            dtable.ajax.reload(null, false);
                        } else {
                            toastr.error('Error submitting leave request.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        toastr.error('An error occurred. Please try again later.');
                    }
                });
            });

            $(document).on('click', '.file-preview', function() {
                const url = $(this).data('url');
                if (!url) return toastr.error('No file URL found');

                const isImage = /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(url);
                $('#fileIframe, #fileImage').hide().attr('src', '');

                if (isImage) {
                    $('#fileImage').attr('src', url).show();
                } else {
                    $('#fileIframe').attr('src', url).show();
                }

                $('#fileViewModal').modal('show');
            });

            $('#fileViewModal').on('hidden.bs.modal', function() {
                $('#fileIframe, #fileImage').hide().attr('src', '');
            });
        });
    </script>

    <!-- File View Modal -->
    <div class="modal fade" id="fileViewModal" tabindex="-1" aria-labelledby="fileViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex justify-content-center align-items-center" style="height: 80vh;">
                    <iframe id="fileIframe" src="" frameborder="0"
                        style="width: 100%; height: 100%; display: none;"></iframe>
                    <img id="fileImage" src="" alt="Image Preview"
                        style="max-width: 100%; max-height: 100%; display: none;" />
                </div>
            </div>
        </div>
    </div>

    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
