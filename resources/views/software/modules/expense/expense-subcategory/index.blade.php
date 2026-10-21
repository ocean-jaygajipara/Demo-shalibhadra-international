@extends('software.layout.app')

@php
    $page_title = $modules['title'] ?? '';
    $route = $modules['route'] ?? '';
    $company_id = $modules['company_id'] ?? null;
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
        {{-- Filter Section --}}
        <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="form-label">Filter by Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="Search..."
                                autofocus>
                        </div>

                        @if (!$company_id)
                            <div class="col-md-2 col-sm-12">
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
                                <label class="form-label">Filter by Team Person</label>
                                <select id="team_person_ids" name="team_person_ids"
                                    class="form-control select2 select_filter" data-append="search_by_company">
                                    <option value="">Filter by Team Person</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Filter by Category</label>
                            {{-- <select id="expense_category_id" class="form-control select_filter select2"> --}}
                            <select id="expense_category_id" class="form-control select_filter select2">

                                <option value="">All Categories</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end mb-2">
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

        {{-- Data Table --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-datatable text-nowrap mt-3">
                    <div class="table-responsive">
                        <table id="yajra-datatables" class="dt-responsive table table-hover w-100">
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
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let dtable;

        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route($route . '.index') }}",
                    type: "GET",
                    data: function(d) {
                        d.search = $('input[name="search"]').val();
                        d.filter_company = $('#company_id').val();
                        d.filter_team_person = $('select[name="team_person_ids"] option:selected')
                    .val();
                        d.filter_expense_category = $('#expense_category_id').val();
                        d.status = $('#status_filter').val();

                    }
                },
                columns: {!! json_encode($columns) !!},
                language: {
                    searchPlaceholder: 'Search...'
                },
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>'
              dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

            });
            $('#status_filter').on('change', function() {
                dtable.draw();
            });


            $(document).on('change', '.select_filter', function(event) {
                event.preventDefault();
                dtable.draw();
            });
            $('input[name="search"]').keyup(function() {
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

            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();
                let search = $('input[name="search"]').val();
                let status = $('#status_filter').val();
                let company = $('#company_id').val();
                let expense_category = $('#expense_category_id').val();
                let team_person_ids = $('#team_person_ids').val();
                let queryParams = $.param({
                    search: search,
                    status: status,
                    company: company,
                    expense_category: expense_category,
                    team_person_ids: team_person_ids
                });
                let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });


            // Print
            $('#print_btn').on('click', function(e) {
                e.preventDefault();
                let queryParams = $.param({
                    search: $('input[name="search"]').val(),
                    status: $('#status_filter').val(),
                    company: $('#company_id').val(),
                    expense_category: $('#expense_category_id').val(),
                    team_person_ids: $('#team_person_ids').val()
                });
                window.open("{{ route($route . '.print') }}?" + queryParams, '_blank');
            });
        });
    </script>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif

    @include('utils.getExpenseCategory')
    @include('utils.getTeamPerson')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
