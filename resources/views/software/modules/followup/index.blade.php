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

      <div class="px-1">
        <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
                // 'route' => $route,
                // 'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
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
                            <div class="col-md-3 mb-2 col-sm-12">
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
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Customer details</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus autocomplete="off">
                        </div>

                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Followup Status</label>
                                <select id="followup_status_id" name="followup_status_id"
                                    class="form-control search_by_followup_status select2 select_filter"
                                    data-append="search_by_followup_status">
                                    <option value="">Select Followup Status</option>
                                </select>
                            </div>
                        </div>
                        @if (!$parent_type_id && !$company_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Team Person Name</label>
                                    <select id="inquiry_created_by" name="team_person_name"
                                        class="form-control search_by_created_by select2 select_filter"
                                        data-append="search_by_created_by">
                                        <option value="">Select Team Person</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Follow Up Type</label>
                                <select id="filter_by_followup_type" name="filter_by_followup_type"
                                    class="form-control select2 select_filter filter_by_followup_type">
                                    <option value="">Filter by Follow Up Type</option>
                                    @foreach ($followFilterList as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ $key == 'today_all_follow_up' ? 'selected' : '' }}>{!! $value !!}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Filter by Followup Date</label>
                            {{-- <input type="date" class="form-control search" name="followup_date"> --}}

                            <input type="text" name="followup_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range" data-future-date="yes">
                        </div>
                        <div class="col-md-1 text-center">
                            <button type="button" title="Cilory Filter" id="cilory_filter"
                                class="btn btn-outline-danger btn-icon ms-75 me-75 mt-4"><i class="ti ti-x"></i></button>
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

    <script>
        const inputDateFormat = 'DD-MM-YYYY h:mm A';
    </script>
    @include('software._utils.inquiryFollowupModel')
    @include('software._utils.quotationFollowupModel')
    @include('software._utils.customerFollowupModel')
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
        // Set default value in input
        var picker = $('.my_daterangepicker').data('daterangepicker');
        if (picker) {
            var endOfYear = moment().endOf('year');
            picker.maxDate = endOfYear;
            picker.setStartDate(moment());
            picker.setEndDate(moment());
            picker.callback(moment(), moment(), 'Today');
        }
        var dtable = null;
        var followup_status = null;
        var followup_selected_status = null;

        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                                    dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                order: [
                    [0, 'ASC']
                ],
                ajax: {
                    url: "{{ route($route . '.index') }}",
                    beforeSend: function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    type: "GET",
                    data: function(data) {
                        let company_id = $('select[name="company_id"] option:selected').val();
                        if (!company_id) {
                            company_id = $('meta[name="company_id"]').attr('value');
                        }

                        let team_person_name = $('select[name="team_person_name"] option:selected')
                        .val();
                        if (!team_person_name) {
                            team_person_name = $('input[name="team_person_name"]').attr('value');
                        }

                        data.search = $('input[name="search"]').val();
                        data.filter_company = company_id;
                        data.filter_followup_status = $('select[name="followup_status_id"]').val();
                        data.filter_created_by = team_person_name;
                        data.filter_by_followup_type = $('select[name="filter_by_followup_type"]')
                    .val();
                        data.filter_followup_date = $('input[name="followup_date"]').val().replace(
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

        $('.search').keyup(function() {
            dtable.draw();
        });

        $(document).ready(function() {

            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('.search').val('');
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
        });

        let company_id = $('select[name="company_id"] option:selected').val();
        if (!company_id) {
            company_id = $('meta[name="company_id"]').attr('value');
        }


        let team_person_name = $('select[name="team_person_name"] option:selected').val();
        if (!team_person_name) {
            team_person_name = $('input[name="team_person_name"]').attr('value');
        }

        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let queryParams = $.param({
                search: $('input[name="search"]').val(),
                filter_company: company_id,
                filter_followup_status: $('select[name="followup_status_id"]').val(),
                filter_created_by: team_person_name,
                filter_by_followup_type: $('input[name="filter_by_followup_type"]').val(),
                filter_followup_date: $('input[name="followup_date"]').val()
            });

            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });



        $('#print_btn').on('click', function(e) {
            e.preventDefault();

            let queryParams = $.param({
                search: $('input[name="search"]').val(),
                filter_company: $('select[name="company_id"]').val(),
                filter_followup_status: $('select[name="followup_status_id"]').val(),
                filter_created_by: $('select[name="team_person_name"]').val(),
                filter_by_followup_type: $('select[name="filter_by_followup_type"]').val(),
                filter_followup_date: $('input[name="followup_date"]').val()
            });

            let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank');
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @if (!$parent_type_id)
        @include('utils.getTeams')
    @endif
    @include('utils.getFollowupStatus')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-update-status')
    @include('software.inlcudes.script-restore-record')
@endpush
