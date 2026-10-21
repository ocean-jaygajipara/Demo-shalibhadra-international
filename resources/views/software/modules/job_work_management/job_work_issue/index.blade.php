@extends('software.layout.app')

@php
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
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <style>
        .table-responsive {
            overflow-x: auto !important;
        }
        #yajra-datatables {
            width: 100% !important;
        }
        #yajra-datatables th,
        #yajra-datatables td {
            box-sizing: border-box;
            vertical-align: middle;
        }
        #yajra-datatables td {
            white-space: normal;
            word-break: break-word;
        }
        table.dataTable.dtr-column > tbody > tr > td.dtr-control:before, 
        table.dataTable.dtr-column > tbody > tr > th.dtr-control:before {
            content: "\25B6" !important;
            margin-right: 2px;
            display: inline-block;
            color: rgba(0, 0, 0, 0.5);
            font-size: 15px;
        }
        table.dataTable.dtr-column > tbody > tr.parent td.dtr-control:before, 
        table.dataTable.dtr-column > tbody > tr.parent th.dtr-control:before {
            content: "\25BC" !important;
        }
    </style>
    <style>
        .timeline {
            position: relative;
            padding-left: 0;
            list-style: none;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 30px;
            padding-left: 50px;
            border-left: 2px solid #e9ecef;
        }

        .timeline-item:last-child {
            border-left: 2px solid transparent;
            padding-bottom: 0;
        }

        .timeline-badge {
            position: absolute;
            left: -13px;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            background-color: #fff;
            border: 2px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
        }

        .timeline-badge.current {
            border-color: #28a745;
            background-color: #28a745;
            color: white;
        }

        .timeline-badge.past {
            border-color: #6c757d;
            background-color: #6c757d;
            color: white;
        }

        .timeline-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .timeline-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }

        .timeline-card.current-vehicle {
            background: #d4edda;
            border-color: #28a745;
        }

        .vehicle-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #495057;
        }

        .badge-current {
            background-color: #28a745;
            font-size: 0.7rem;
            padding: 3px 8px;
            margin-left: 8px;
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
                    'show_export_btn' =>
                        isset($modules['excel_permission']) &&
                        isset($modules['print_permission']) &&
                        ($modules['excel_permission'] || $modules['print_permission'])
                        ? true
                        : false,
                    'show_excal_btn' => isset($modules['excel_permission']) ? $modules['excel_permission'] : false,
                    'show_print_btn' => isset($modules['print_permission']) ? $modules['print_permission'] : false,
                    'show_bulk_download_btn' => true,
                ])
          
                </div>
        </div>
        <div class="row my-3">
            <div class="col-md-12 mb-5" id="filter_section">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            @if (!$company_id)
                                <div class="col-md-3">
                            @else
                                    <div class="col-md-2">
                                @endif
                            <label class="form-label">Filter by Name</label>
                                <input type="search" class="form-control search" name="search" placeholder="search..." autofocus>
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

                    <div class="col-md-3 col-sm-12">
                        <div class="form-group">
                                    <label class="form-label">Job Work Type</label>
                                    <select id="job_work_type_id" name="job_work_type_id"
                                        class="form-control search_by_job_work_type_id select2 select_filter"
                                    data-append="search_by_job_work_type_id">
                                        <option value="">Job Work Type</option>
                                    </select>
                                </div>
                        </div>

                             <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Vendor</label>
                                    <select id="vendor_id" name="vendor_id"
                                        class="form-control search_by_vendor select2 select_filter" data-append="search_by_vendor">
                                        <option value="">Filter by Vendor</option>
                                    </select>
                                                               </div>
                            </div>

                    <div class="col-md-3 mb-2">
                        <label class="form-label">Filter by Job Work Issue Date</label>
                        <input type="text" name="job_work_issue_date"
                            class="form-control my_daterangepicker table_filter" value=""
                                    placeholder="Filter by date range">
                    </div>


                            <div class="col-md-3 d-flex align-items-end mb-2">
                                <div class="flex-grow-1 me-2">
                                    <label for="parent_status_id" class="form-label">Filter by Status</label>
                                    <select id="parent_status_id" name="parent_status_id" class="form-select select2 select_filter">
                                        <option value="">Select Sales Status</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="button" title="Clear Filter" id="cilory_filter"
                                        class="btn btn-outline-danger btn-icon mt-2">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>

                                                        </div>
                </div>
            </div>
            </div>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-datatable text-nowrap">
                        <div class="card-datatable table-responsive">
                            <table id="yajra-datatables" class="dt-responsive table table-hover">
                            @if (isset($columns))
                                <thead>
                                <tr>
                                        @foreach ($columns as $item)
                                            <th class="{{ $item?->className ?? '' }}">
                                                {!! $item?->td_label ?? $item?->name ?? '' !!}</th>
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



    <!-- E-Way Bill Modals -->
    <!-- Update Vehicle Modal -->
    <div class="modal fade" id="updateVehicleModal" tabindex="-1" aria-labelledby="updateVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="updateVehicleForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateVehicleModalLabel">
                             Update Vehicle Number
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="job_work_issue_id" id="modal_job_work_issue_id">

                        <div class="mb-3">
                            <label class="form-label">E-Way Bill No</label>
                            <input type="text" class="form-control readonly-look" id="modal_eway_bill_no" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Vehicle No</label>
                            <input type="text" class="form-control readonly-look" id="modal_current_vehicle" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Vehicle No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="vehicle_no" id="modal_new_vehicle" required placeholder="GJ01AB1234">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                            <select name="vehicle_type" class="form-control">
                                <option value="">Select</option>
                                <option value="R">Regular</option>
                                <option value="RW">Rail / Water</option>
                                <option value="O">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Change <span class="text-danger">*</span></label>
                            <select class="form-select" name="reason_code" required>
                                <option value="1">Break down</option>
                                <option value="2">Transhipment</option>
                                <option value="3">First Time</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason Remarks</label>
                            <textarea class="form-control" name="reason_remark" rows="2" placeholder="Enter reason for vehicle change"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">LR No (Optional)</label>
                                <input type="text" class="form-control" name="lr_no" placeholder="LR Number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">LR Date (Optional)</label>
                                <input type="text" class="form-control" name="lr_date" id="lr_date" placeholder="DD-MM-YYYY">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                             Update Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Vehicle History Modal -->
    <div class="modal fade" id="vehicleHistoryModal" tabindex="-1" aria-labelledby="vehicleHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="vehicleHistoryModalLabel" style="color: white;">
                        <i class="fa-solid fa-clock-rotate-left"></i> Vehicle Update History
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="vehicle-history-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading vehicle history...</p>
                    </div>

                    <div id="vehicle-history-content" style="display: none;">
                        <!-- E-Way Bill Info -->
                        <div class="alert alert-info mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>E-Way Bill No:</strong> <span id="history_eway_bill_no">-</span>
                                </div>
                                <div class="col-md-6 text-end">
                                    <strong>Total Changes:</strong> <span id="history_total_changes" class="badge bg-primary">0</span>
                                </div>
                            </div>
                        </div>

                        <!-- History Timeline -->
                        <div id="vehicle-history-list" class="timeline">
                            <!-- History items will be inserted here -->
                        </div>

                        <!-- No History Message -->
                        <div id="no-history-message" class="alert alert-warning" style="display: none;">
                            <i class="fa-solid fa-info-circle"></i> No vehicle update history found.
                        </div>
                    </div>

                    <div id="vehicle-history-error" class="alert alert-danger" style="display: none;">
                        <i class="fa-solid fa-exclamation-triangle"></i> <span id="error-message"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('page_leavel_script')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });


        flatpickr("#lr_date", {
            dateFormat: "d-m-Y",
            defaultDate: "{{ old('lr_date', isset($edit->lr_date) ? date('d-m-Y', strtotime($edit->lr_date)) : '') }}",
            onChange: function(selectedDates, dateStr, instance) {
                const ymd = instance.formatDate(selectedDates[0], "Y-m-d");
                document.getElementById('lr_date').value = ymd; // hidden input for submission
            }
        });

        // var picker = $('.my_daterangepicker').data('daterangepicker');
        // if (picker) {
        //     picker.maxDate = false;
        //     picker.setStartDate(moment());
        //     picker.setEndDate(moment());
        //     picker.callback(moment(), moment(), 'Today');
        // }

        var dtable = null;
        $(document).ready(function() {
            // Set default date range to last 30 days
            var startDate = moment().subtract(29, 'days'); // Last 30 days (29 days ago to today = 30 days)
            var endDate = moment();
            var formattedRange = startDate.format('DD/MM/YYYY') + ' to ' + endDate.format('DD/MM/YYYY');

            // Set the input value immediately so table loads with correct date
            $('.my_daterangepicker').val(formattedRange);

            // Update the date picker object when it's available
            setTimeout(function() {
                var picker = $('.my_daterangepicker').data('daterangepicker');
                if (picker) {
                    picker.maxDate = false;
                    picker.setStartDate(startDate);
                    picker.setEndDate(endDate);
                    picker.callback(startDate, endDate, 'Last 30 Days');
                }
            }, 100);

            const dtColumns = {!! isset($columns) ? json_encode($columns) : [] !!};
            const widthByName = {
                select_all: '42px',
                company_id: '240px',
                job_work_issue_no: '130px',
                job_work_issue_date: '120px',
                job_work_type: '210px',
                job_work_order_status: '200px',
                vendor_name: '300px',
                grand_total: '130px',
                team_person_name: '150px',
                eway_bill_no: '170px',
                action: '80px'
            };
            const dynamicColumnDefs = dtColumns.map(function(col, index) {
                const def = {
                    targets: index
                };
                if (widthByName[col.name]) {
                    def.width = widthByName[col.name];
                }
                if (col.name === 'select_all') {
                    def.orderable = false;
                }
                return def;
            });

             dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                scrollX: true,
                autoWidth: false,
                order: [
                    [3, 'DESC']
                ],
                ajax: {
                    "url": "{{ route($route . '.index') }}",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                        console.log("Console 73");
                    },
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.filter_job_work_type_id = $(
                            'select[name="job_work_type_id"] option:selected').val();
                        data.filter_vendor = $('select[name="vendor_id"] option:selected').val();
                        data.filter_job_work_issue_date = $('input[name="job_work_issue_date"]').val()
                            .replace(' - ', ' to ');
                        data.filter_parent_status_id = $(
                            'select[name="parent_status_id"] option:selected').val();
                    },
                },
                columns: dtColumns,
                language: {
                    searchPlaceholder: 'Search...',
                },
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',
                columnDefs: dynamicColumnDefs,
                initComplete: function() {
                    const api = this.api();
                    setTimeout(function() {
                        api.columns.adjust();
                    }, 0);
                },
                drawCallback: function() {
                    const api = this.api();
                    setTimeout(function() {
                        api.columns.adjust();
                    }, 0);
                }

            });

            $(window).on('resize', function() {
                if (dtable) {
                    dtable.columns.adjust();
                }
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

            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');
                $('#filter_by_date').val('');
                $("#parent_status_id").html("<option value=''>Select Sales Status</option>");

                var picker = $('.my_daterangepicker').data('daterangepicker');
                if (picker) {
                    picker.maxDate = false;
                    let startOfMonth = moment().startOf('month');
                    let today = moment();

                    picker.setStartDate(startOfMonth);
                    picker.setEndDate(today);
                    picker.callback(startOfMonth, today, 'This Month');
                }
                dtable.draw();
            });

            $(document).on('click', '.change_status', function() {
                var id = $(this).data('id');
                var next_status_id = $(this).data('next-status-id');

                var _token = '{{ csrf_token() }}';
                var _actionUrl = $(this).attr("data-url");
                var _dataId = $(this).attr("data-id");
                const urlParams = new URLSearchParams(_actionUrl);

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
        });


        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let company = $('#company_id').val();
            let job_work_type_id = $('#job_work_type_id').val();
            let vendor = $('select[name="vendor_id"] option:selected').val();
            let job_work_issue_date = $('input[name="job_work_issue_date"]').val().replace(' - ', ' to ');
            let parent_status_id = $('select[name="parent_status_id"] option:selected').val();

            let queryParams = $.param({
                search: search,
                filter_company: company,
                filter_job_work_type_id: job_work_type_id,
                filter_vendor: vendor,
                filter_job_work_issue_date: job_work_issue_date,
                filter_parent_status_id: parent_status_id,
            });

            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });

        $('#print_btn').on('click', function(e) {
            e.preventDefault();


            let search = $('input[name="search"]').val();
            let company = $('#company_id').val();
            let job_work_type_id = $('#job_work_type_id').val();
            let vendor = $('select[name="vendor_id"] option:selected').val();
            let job_work_issue_date = $('input[name="job_work_issue_date"]').val().replace(' - ', ' to ');
            let parent_status_id = $('select[name="parent_status_id"] option:selected').val();


             let queryParams = $.param({
                search: search,
                filter_company: company,
                filter_job_work_type_id: job_work_type_id,
                filter_vendor: vendor,
                filter_job_work_issue_date: job_work_issue_date,
                filter_parent_status_id: parent_status_id,
            });


             let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank');
        });

        $(document).ready(function() {
            localStorage.removeItem("productTable");
        });

        $(document).on('click', '#select_all', function(e) {
            e.stopPropagation();
            $('.row-checkbox').prop('checked', $(this).prop('checked'));
        });

        $(document).on('click', '.row-checkbox', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', '#bulk_download_btn', function() {
            var selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

             if (selectedIds.length === 0) {
                toastr.error('Please select at least one item.');
                return;
            }

            var form = $('<form>', {
                action: "{{ route($route . '.bulk-download') }}",
                method: 'POST'
            }).append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: "{{ csrf_token() }}"
            }));
            $.each(selectedIds, function(i, id) {
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'ids[]',
                    value: id
                }));
            });

            $('body').append(form);
            form.submit();
            form.remove();
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getJobWorkType')
    @include('utils.getVendorSales')
    @include('utils.getSalesStatus')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')

  
    <script>
        $(document).on('click', '.update-vehicle-btn', function() {
            const jobWorkIssueId = $(this).attr('data-job-work-issue-id');
            const ewayBillNo = $(this).attr('data-eway-bill-no');
            const currentVehicle = $(this).attr('data-current-vehicle');

            $('#modal_job_work_issue_id').val(jobWorkIssueId);
            $('#modal_eway_bill_no').val(ewayBillNo);
            $('#modal_current_vehicle').val(currentVehicle && currentVehicle !== 'null' ? currentVehicle : 'N/A');
            $('#modal_new_vehicle').val('');
            $('#updateVehicleForm').attr('action', '{{ route("job-work-issue.update-vehicle-details") }}');
            $('#updateVehicleModal').modal('show');
        });

        // Update Vehicle Form Submit
        $('#updateVehicleForm').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const actionUrl = $(this).attr('action');

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#updateVehicleForm button[type="submit"]').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Updating...');
                },
                success: function(response) {
                    if(response.status) {
                        toastr.success(response.message || 'Vehicle updated successfully!');
                        $('#updateVehicleModal').modal('hide');
                        $('#updateVehicleForm')[0].reset();

                        // Reload DataTable
                        if (typeof dtable !== 'undefined') {
                            dtable.ajax.reload(null, false);
                        } else if (typeof dataTable !== 'undefined') { // Check for both var names just in case
                            dataTable.ajax.reload(null, false);
                        } else {
                            // Fallback
                            $('#yajra-datatables').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        toastr.error(response.message || 'Failed to update vehicle');
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Something went wrong!';
                    toastr.error(error);
                },
                complete: function() {
                    $('#updateVehicleForm button[type="submit"]').prop('disabled', false).html('Update Vehicle');
                }
            });
        });

        $(document).on('click', '.view-vehicle-history-btn', function() {
            const jobWorkIssueId = $(this).data('job-work-issue-id');
            const ewayBillNo = $(this).data('eway-bill-no');

            // Reset modal
            $('#vehicle-history-loading').show();
            $('#vehicle-history-content').hide();
            $('#vehicle-history-error').hide();
            $('#vehicle-history-list').empty();
            
            $('#vehicleHistoryModal').modal('show');

            // Fetch vehicle history
            $.ajax({
                url: '{{ route("job-work-issue.vehicle-history-details", ":id") }}'.replace(':id', jobWorkIssueId),
                type: 'GET',
                success: function(response) {
                    if(response.success) {
                        displayVehicleHistory(response.data);
                    } else {
                        showError(response.message || 'Failed to load vehicle history');
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Something went wrong!';
                    showError(error);
                }
            });
        });

        function displayVehicleHistory(data) {
            $('#vehicle-history-loading').hide();
            $('#vehicle-history-content').show();

            // Set E-Way Bill info
            $('#history_eway_bill_no').text(data.eway_bill_no || '-');
            $('#history_total_changes').text(data.total_changes || 0);

            // Check if history exists
            if (!data.history || data.history.length === 0) {
                $('#no-history-message').show();
                return;
            }

            // Build history timeline
            let historyHtml = '';
            data.history.forEach(function(item, index) {
                const isCurrent = item.is_current;
                const badgeClass = isCurrent ? 'current' : 'past';
                const cardClass = isCurrent ? 'current-vehicle' : '';
                const currentBadge = isCurrent ? '<span class="badge badge-current">CURRENT</span>' : '';

                historyHtml += `
                    <div class="timeline-item">
                        <div class="timeline-badge ${badgeClass}">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                        <div class="timeline-card ${cardClass}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="vehicle-number">${item.vehicle_no}</span>
                                    ${currentBadge}
                                </div>
                                <small class="text-muted">
                                    <i class="fa-solid fa-calendar"></i> ${item.updated_at}
                                </small>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Vehicle Type:</small><br>
                                    <span class="badge bg-secondary">${item.vehicle_type}</span>
                                </div>
                                <div class="col-md-6">
                                     <small class="text-muted">Updated By:</small><br>
                                     <span class="text-dark">${item.updated_by}</span>
                                </div>
                            </div>

                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted d-block">Reason:</small>
                                <strong>${item.reason}</strong>
                                ${item.reason_remark ? `<p class="mb-0 mt-1 small text-muted">"${item.reason_remark}"</p>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            $('#vehicle-history-list').html(historyHtml);
        }

        function showError(message) {
            $('#vehicle-history-loading').hide();
            $('#vehicle-history-error').show();
            $('#error-message').text(message);
        }
    </script>
@endpush
