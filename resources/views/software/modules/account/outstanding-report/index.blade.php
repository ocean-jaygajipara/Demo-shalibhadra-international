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
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
            'show_filter_btn' => true,
            // 'show_export_btn' => true,
            // 'show_excal_btn' => true,
            // 'show_print_btn' => true,
        ])
    </div>

    <div class="row my-3">
        <div class="col-md-12 mb-5">
            <div class="card">
                <div class="card-body">
                    <div class="row">

                        @if (!$company_id)
                            <div class="col-md-2 mb-2 col-sm-12">
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
                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Customer Type</label>
                                <select id="customer_type_id" name="customer_type_id"
                                    class="form-control select2 select_filter search_by_customer_type"
                                    data-append="search_by_customer_type" data-personName="yes">
                                    <option value="">Filter by Customer Type</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Customer</label>
                                <select id="customer_id" name="customer_id"
                                    class="form-control select2 select_filter search_by_customer"
                                    data-append="search_by_customer" data-module="outstanding-report">
                                    <option value="">Select Customer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Filter by Date</label>
                            <input type="text" name="fromdate_time" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div>
                        <div class="col-md-2 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary" id="view_report_btn">
                                <i class="fa fa-plus me-1"></i> View Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12" id="outstanding_report_container"></div>
        </div>
    </div>

    {{-- <div class="col-md-12"> --}}
    {{-- <div class="card"> --}}
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


@endsection

@section('page_leavel_script')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')

    <script>
        $(function() {
            var picker = $('.my_daterangepicker').data('daterangepicker');
            if (picker) {
                picker.maxDate = false;
                var today = moment();
                var financialYearStart = moment().month() < 3 ?
                    moment().subtract(1, 'year').startOf('year').month(3).startOf('month') // April 1st of last year
                    :
                    moment().startOf('year').month(3).startOf('month'); // April 1st of current year
                picker.setStartDate(financialYearStart);
                picker.setEndDate(today);
                picker.callback(financialYearStart, today, 'Financial Year Range');
            }
        });
    </script>


    <script>
        $('#view_report_btn').on('click', function() {
            let fromdate_time = $('input[name="fromdate_time"]').val().replace(' - ', ' to ');
            let company_id = $('#company_id').val();

            let metaCompanyId = $('meta[name="company_id"]').attr('value');
            if (!company_id && metaCompanyId) {
                company_id = metaCompanyId;
            }

            let formData = {
                fromdate_time: fromdate_time,
                company_id: company_id,
                customer_type_id: $('#customer_type_id').val(),
                customer_id: $('#customer_id').val(),
                _token: '{{ csrf_token() }}',
            };

            $.ajax({
                url: "{{ route('outstanding-report.fetch') }}",
                type: "POST",
                dataType: 'json',
                data: formData,
                beforeSend: function() {
                    $('#outstanding_report_container').html('<p class="text-center">Loading...</p>');
                },
                success: function(res) {
                    $('#outstanding_report_container').html(res.html);
                },
                error: function() {
                    $('#outstanding_report_container').html(
                        '<p class="text-danger">Something went wrong.</p>');
                }
            });
        });
    </script>



    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var dtable = null;
        var followup_status = null;
        var followup_selected_status = null;

        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
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

        $('.search').keyup(function() {
            dtable.draw();
        });

        $(document).ready(function() {

            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('.search').val('');
                $('.my_daterangepicker').val('').trigger('change');
                dtable.draw();
            });

        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @if (!$parent_type_id)
        @include('utils.getTeams')
    @endif
    @include('utils.getCustomer')
    @include('utils.getCompanyByCustomerType')

    @include('utils.getFollowupStatus')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-update-status')
    @include('software.inlcudes.script-restore-record')
@endpush
