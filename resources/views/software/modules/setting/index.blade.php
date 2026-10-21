@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
    $i = 0;
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
                'route' => $route,
                'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
                'show_filter_btn' => false,
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

                    <div class="col-md-3 d-flex align-items-end mb-2">
                        <div class="flex-grow-1 me-2">
                            <label class="form-label">Filter by Dispatch Date</label>
                            <input type="text" name="dispatch_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
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
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
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
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                order: [
                    [0, 'ASC']
                ],
                ajax: {
                    "url": "{{ route($route . '.index') }}",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                        // console.log("Console 73");
                    },
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.filter_dispatch_date = $('input[name="dispatch_date"]').val().replace(
                            ' - ', ' to ');
                    },
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
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
    </script>
    @include('utils.getCompany')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
