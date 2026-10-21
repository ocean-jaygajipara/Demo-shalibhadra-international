@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    // dd($modules);

@endphp
@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
     <style>
        .dt-assigned {
            width: 250px;
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
        }
    </style>
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
                // 'show_export_btn' =>
                //     isset($modules['excel_permission']) &&
                //     isset($modules['print_permission']) &&
                //     ($modules['excel_permission'] || $modules['print_permission'])
                //         ? true
                //         : false,
                // 'show_excal_btn' => isset($modules['excel_permission']) ? $modules['excel_permission'] : false,
                // 'show_print_btn' => isset($modules['print_permission']) ? $modules['print_permission'] : false,
            ])
        </div>
    </div>
    <div class="row my-3">
        <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Filter by Title</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>
                        @if (!$company_id)
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Company</label>
                                    <select id="company_id" name="company_id"
                                        class="form-control search_by_company select2 select_filter"
                                        data-append="search_by_company">
                                        <option value="">Filter by Company</option>
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3 mb-2 col-sm-12">
                            <label class="form-label">Filter by Department</label>
                            <select name="department_id"
                                class="form-control search_by_department select2 select_filter @error('department_id') is-invalid @enderror"
                                data-selecteddepartmentid="{{ old('department_id', $edit->department_id ?? '') }}">
                                <option value="">Select Department</option>
                            </select>
                        </div>

                        @if (!$parent_type_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Created By</label>
                                    <select id="task_created_by" name="task_created_by"
                                        class="form-control search_by_created_by select2 select_filter"
                                        data-append="search_by_created_by"
                                        data-selectedTaskCreatedBy="{{ $loginUserId ? $loginUserId : '' }}">
                                        <option value="">Filter by Select Created By</option>
                                    </select>
                                </div>
                            </div>
                        @else
                            <input type="hidden" class="form-control search_by_created_by" name="task_created_by"
                                value="{{ $loginUserId }}" />
                        @endif

                        @if (!$parent_type_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Assigned To</label>
                                    <select id="task_assigned_to" name="task_assigned_to"
                                        class="form-control search_by_assigned_to select2 select_filter"
                                        data-append="search_by_assigned_to"
                                        data-selectedTaskAssignedTo="{{ $loginUserId ? $loginUserId : '' }}">
                                        <option value="">Filter by Select Assigned To</option>
                                    </select>
                                </div>
                            </div>
                        @else
                            <input type="hidden" class="form-control inquiry_assigned_to" name="inquiry_created_by"
                                value="{{ $loginUserId }}" />
                        @endif

                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select id="priority" class="form-select select2 select_filter" name="priority">
                                 <option value="">All</option>
                                @foreach ($task_priority_arr as $key => $value)
                                    <option value="{{ $key }}">
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            </div>
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Task Date</label>
                            <input type="text" name="task_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div>

                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label for="status_filter" class="form-label">Filter by Status</label>
                                <select id="status_filter" name="status" class="form-select select2">
                                    <option value="all">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
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


    <!-- Pause Reason Modal -->
    <div class="modal fade" id="pauseReasonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md" role="document">   <!-- 👈 Medium modal -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pauseReasonLabel">Pause Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="pauseReasonForm">
                        @csrf
                        <input type="hidden" id="pause_task_id" name="id">
                        <input type="hidden" id="pause_next_status_id" name="next_status_id">

                        <div class="mb-3">
                            <label for="pause_reason" class="form-label">Reason for Pause</label>
                            <textarea rows="3" class="form-control" id="pause_reason" name="remarks" required></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="pauseReasonSubmitBtn" class="btn btn-primary">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="taskStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header text-center" style="display: inline;">
                 <p class="text-center fw-bold fs-5 mt-3">Task Status Logs</p>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <strong>Company Name : </strong><span id="modal_company_name"></span><br/>
                    </div>
                    <div class="col-md-6">
                        <strong>Task Name : </strong><span id="modal_task_name"></span>
                    </div>
                </div>
                <table class="table table-bordered">
                <thead>
                    <tr>
                    <th>Status Name</th>
                    <th>Changed By</th>
                    <th>Date & Time</th>
                    <th>Remark</th>
                    </tr>
                </thead>
                <tbody id="taskStatusLogBody">
                    <tr><td colspan="3" class="text-center">Loading...</td></tr>
                </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>


@endsection

@section('page_leavel_script')
    <!-- Data tables -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var picker = $('.my_daterangepicker').data('daterangepicker');
        if (picker) {
            picker.maxDate = false;
            picker.setStartDate(moment());
            picker.setEndDate(moment());
            picker.callback(moment(), moment(), 'Today');
        }


        var dtable = null;
        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',
                order: [
                    [0, 'ASC']
                ],
                ajax: {
                    "url": "{{ route($route . '.index') }}",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.filter_department_id = $('select[name="department_id"]').val();
                        data.status = $('#status_filter').val();
                        data.filter_priority = $('#priority').val();
                        data.filter_created_by= $('select[name="task_created_by"] option:selected').val();
                        data.filter_assigned_to= $('select[name="task_assigned_to"] option:selected').val();
                        data.filter_task_date = $('input[name="task_date"]').val().replace(
                            ' - ', ' to ');
                    },
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
                }
            });
        });
        $(document).on('change', '.select_filter, .table_filter', function(event) {
            event.preventDefault();
            if (dtable) {
                dtable.draw();
            }
        });

        $('input[name="search"]').keyup(function() {
            dtable.draw();
        });

        $('#status_filter').on('change', function() {
            dtable.draw();
        });

        $(document).ready(function() {
            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');
                dtable.draw();
            });
        });

        $(document).on('click', '.change_status', function() {
            var id = $(this).data('id');
            var next_status_id = $(this).data('next-status-id');
            var statusName = $(this).text().trim();

            var _token = '{{ csrf_token() }}';
            var _actionUrl = $(this).attr("data-url");
            var _dataId = $(this).attr("data-id");
            const urlParams = new URLSearchParams(_actionUrl);

            let pauseKeywords = ["pause", "stop", "hold", "halt", "wait", "waiting"];
            if (pauseKeywords.some(keyword => statusName.toLowerCase().includes(keyword))) {
                $("#pause_task_id").val(id);
                $("#pause_next_status_id").val(next_status_id);
                $("#pauseReasonModal").modal('show');
                return;
            }

            if (_token && _actionUrl && _dataId && next_status_id) {
                $.ajax({
                    type: "POST",
                    url: _actionUrl,
                    data: {
                        _token: _token,
                        _method: 'POST',
                        id: _dataId,
                        next_status_id: next_status_id,
                    },
                    success: function(data) {
                        if (data?.status == true) {
                            toastr.success(data?.message);
                            $("#yajra-datatables").DataTable().ajax.reload();
                        } else {
                            toastr.error(data?.message);
                        }
                    },
                    error: function() {
                        toastr.error('Something Went wrong process failed.');
                    }
                });
            } else {
                console.log("Update Script", _token, _actionUrl, urlParams, _dataId, next_status_id);
            }
        });


        $(document).on("click", "#pauseReasonSubmitBtn", function (e) {
            e.preventDefault();
            var _token = '{{ csrf_token() }}';
            var _actionUrl = '{{ route($modules["route"] . ".change-task-status") }}';
            var _dataId = $("#pause_task_id").val();
            var next_status_id = $("#pause_next_status_id").val();
            const urlParams = new URLSearchParams(_actionUrl);

            $.ajax({
                type: "POST",
                url: _actionUrl,
                data: {
                    _token: _token,
                    _method: 'POST',
                    id: _dataId,
                    next_status_id: next_status_id,
                    remarks: $("#pause_reason").val()
                },
                success: function(data) {
                    if (data?.status == true) {
                        toastr.success(data?.message);
                        $("#pauseReasonModal").modal('hide');
                        $("#pauseReasonForm")[0].reset();
                        $("#yajra-datatables").DataTable().ajax.reload();
                    } else {
                        toastr.error(data?.message);
                    }
                },
                error: function() {
                    toastr.error('Something Went wrong process failed.');
                }
            });
        });

        $(document).on("click", ".viewButton", function() {
            let taskId = $(this).data("id");
            let companyId = $(this).data("company_id");
            var _actionUrl = '{{ route($modules["route"] . ".get-task-status-log") }}';

            $("#taskStatusLogBody").html('<tr><td colspan="3" class="text-center">Loading...</td></tr>');
            $("#taskStatusModal").modal("show");

            $.ajax({
                url: _actionUrl,
                type: "POST",
                datatype: 'json',
                data: { task_id: taskId, company_id: companyId },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    var innerTableHtml = '';
                    if(response.status == true) {
                        var resData = response.data;
                        if (resData && resData.length > 0) {
                            $("#modal_company_name").html(resData[0].company_name);
                            $("#modal_task_name").html(resData[0].task_name);
                            $.each(resData, function(index, item) {
                                innerTableHtml += '<tr>';
                                        innerTableHtml += '<td>'+ item.status_name ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.created_by ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.status_date ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.remarks ?? '-' +'</td>';
                                innerTableHtml += '</tr>';
                            });
                        }
                    }
                    $("#taskStatusLogBody").html(innerTableHtml);
                }
            });
        });


        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('#company_id').val();
            let department_id = $('select[name="department_id"]').val();

            let queryParams = $.param({
                search: search,
                status: status,
                company: company,
                department_id: department_id
            });

            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });
        $('#print_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('#company_id').val();
            let department_id = $('select[name="department_id"]').val();

            let queryParams = $.param({
                search: search,
                status: status,
                company: company,
                department_id: department_id
            });

            let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank');
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getTeams')
    @include('utils.getDepartment')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
