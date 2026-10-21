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
            overflow-x: visible !important;
        }
        #show_add_btn:focus {
            outline: 3px solid #003dff !important;
            outline-offset: 3px;
            box-shadow: 0 0 8px rgba(0, 61, 255, 0.5);
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
                            <label class="form-label">Filter by Vendor</label>
                            <select id="vendor_id" name="vendor_id"
                                class="form-control search_by_vendor select2 select_filter"
                                data-append="search_by_vendor">
                                <option value="">Filter by Vendor</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 mb-2">
                        <label class="form-label">Filter by Job Work Order Date</label>
                        <input type="text" name="job_work_order_date"
                            class="form-control my_daterangepicker table_filter" value=""
                            placeholder="Filter by date range">
                    </div>


                    <div class="col-md-1 d-flex align-items-end mb-2">
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
@endsection

@section('page_leavel_script')
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

            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, 'ASC']
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
                        data.filter_vendor = $('select[name="vendor_id"] option:selected').val();
                        data.filter_job_work_order_date = $('input[name="job_work_order_date"]').val()
                            .replace(' - ', ' to ');
                    },
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
                },
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

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
            let filter_company = $('select[name="company_id"] option:selected').val();
            let filter_vendor = $('select[name="vendor_id"] option:selected').val();
            let filter_job_work_order_date = $('input[name="job_work_order_date"]').val().replace(' - ', ' to ');

            let queryParams = $.param({
                search: search,
                filter_company: filter_company,
                filter_vendor: filter_vendor,
                filter_job_work_order_date: filter_job_work_order_date
            });
            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });
        $('#print_btn').on('click', function(e) {
            e.preventDefault();
            let search = $('input[name="search"]').val();
            let filter_company = $('select[name="company_id"] option:selected').val();
            let filter_vendor = $('select[name="vendor_id"] option:selected').val();
            let filter_job_work_order_date = $('input[name="job_work_order_date"]').val().replace(' - ', ' to ');

            let queryParams = $.param({
                search: search,
                filter_company: filter_company,
                filter_vendor: filter_vendor,
                filter_job_work_order_date: filter_job_work_order_date
            });
            let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank');
        });

        $(document).ready(function() {
            localStorage.removeItem("productTable");
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                $('#show_add_btn').focus();
            }
        });

        $('#show_add_btn').on('keydown', function (e) {
            if (e.key === "Enter") {
                window.location.href = $(this).attr('href');
            }
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getVendorSales')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
