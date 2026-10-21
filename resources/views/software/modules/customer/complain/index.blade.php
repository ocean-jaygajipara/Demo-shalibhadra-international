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
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Customer</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus autocomplete="off">
                        </div>

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
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Complain Assign To</label>
                                <select id="complain_assign_to_id" name="complain_assign_to_id"
                                    class="form-control search_by_created_by select2 select_filter"
                                    data-append="search_by_created_by">
                                    <option value="">Filter by Select Team person name</option>
                                </select>
                            </div>
                        </div>
                        <!-- Filter by Customer Type -->
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Customer Type</label>
                                <select name="customer_type_id" id="customer_type_id"
                                    class="form-control select2 select_filter search_by_customer_type"
                                    data-append="search_by_customer_type">
                                    <option value="">Filter by Customer Type</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter by Source of Complaint -->
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Source of Complaint</label>
                                <select name="source_complain_id" id="source_complain_id"
                                    class="form-control select2 select_filter search_by_source_of_complaint"
                                    data-append="search_by_source_of_complaint">
                                    <option value="">Filter by Source of Complaint</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter by Complaint Type -->
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Complaint Type</label>
                                <select name="complain_type_id" id="complain_type_id"
                                    class="form-control select2 select_filter search_by_complain_type"
                                    data-append="search_by_complain_type">
                                    <option value="">Filter by Complaint Type</option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter by Complaint Created By -->
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Complaint Created By</label>
                                <select name="complain_created_by_id" id="complain_created_by_id"
                                    class="form-control select2 select_filter search_by_created_by"
                                    data-append="search_by_created_by">
                                    <option value="">Filter by Created By</option>
                                </select>
                            </div>
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
        var dtable = null;
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
                    "url": "{{ route($route . '.index') }}",
                    'beforeSend': function(request) {
                        request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                            'content'));
                    },
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"]').val();
                        data.status = $('#status_filter').val();
                        data.complain_assign_to_id = $('select[name="complain_assign_to_id"]').val();
                        data.complain_created_by_id = $('select[name="complain_created_by_id"]').val();
                        data.customer_type_id = $('select[name="customer_type_id"]').val();
                        data.source_complain_id = $('select[name="source_complain_id"]').val();
                        data.complain_type_id = $('select[name="complain_type_id"]').val();
                    }

                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...'
                }

            });
        });

        $(document).on('change', '.select_filter', function(event) {
            event.preventDefault();
            dtable.draw();
        });

        $(document).on('change', '.date_filter', function(event) {
            event.preventDefault();
            dtable.draw();
        });
        $('.search').keyup(function() {
            dtable.draw();
        });

        $('#status_filter').on('change',
            function() {
                dtable.draw();
            });

        $(document).ready(function() {
            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change.select2');

                $('.search').val('');
                dtable.draw();
            });
        });
        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            // Collect filter values
            let search = $.trim($('input[name="search"]').val());
            let status = $('#status_filter').val();
            let company = $('#company_id').val();
            let complain_assign_to_id = $('#complain_assign_to_id').val();
            let complain_created_by_id = $('#complain_created_by_id').val();
            let customer_type_id = $('#customer_type_id').val();
            let complain_type_id = $('#complain_type_id').val();
            let source_complain_id = $('#source_complain_id').val();

            // Build query string
            let queryParams = $.param({
                search: search,
                status: status,
                company: company,
                complain_assign_to_id: complain_assign_to_id,
                complain_created_by_id: complain_created_by_id,
                customer_type_id: customer_type_id,
                complain_type_id: complain_type_id,
                source_complain_id: source_complain_id
            });

            // Construct export URL
            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;

            // Redirect to export route
            window.location.href = url;
        });

        $('#print_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('#company_id').val();
            let complain_assign_to_id = $('#complain_assign_to_id').val();
            let complain_created_by_id = $('#complain_created_by_id').val();
            let customer_type_id = $('#customer_type_id').val();
            let complain_type_id = $('#complain_type_id').val();
            let source_complain_id = $('#source_complain_id').val();

            let queryParams = $.param({
                search: search,
                status: status,
                company: company,
                complain_assign_to_id: complain_assign_to_id,
                complain_created_by_id: complain_created_by_id,
                customer_type_id: customer_type_id,
                complain_type_id: complain_type_id,
                source_complain_id: source_complain_id
            });

            let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank'); // open in new tab/window for print
        });
    </script>
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getCompanyByCustomerType')
    @include('utils.getCustomer')
    @include('utils.getSourceOfInquiry')
    @include('utils.getComplainType')
    @include('utils.getTeams')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
