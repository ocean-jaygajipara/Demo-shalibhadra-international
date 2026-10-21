@extends('software.layout.app')

@php
    use App\Models\Company;

    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
    $team_personLimitReached = false;

    if ($company_id) {
        $company = Company::find($company_id);
        // Missing current inquiry count calculation
        $team_personLimitReached = $company && $currentTeamPersonCount >= $company->max_team_user_count;
    }

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
                        <div class="col-md-3 col-sm-12 mb-2">
                            <label class="form-label">Filter by Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>
                        @if (!$company_id)
                            <div class="col-md-3 col-sm-12 mb-2">
                                <div class="form-group">
                                    <label class="form-label">Filter by Company</label>
                                    <select id="company_id" name="company_id"
                                        class="form-control search_by_company select2 select_filter"
                                        data-append="search_by_company" data-selectedCompanyId="{{ $company_id }}">
                                        <option value="">Filter by Company</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Filter by Country</label>
                                <select id="country_id" name="country_id"
                                    class="form-control search_by_country select2 select_filter"
                                    data-filterByStatus="active" data-append="search_by_country">
                                    <option value="">Filter by Country</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Filter by State</label>
                                <select id="state_id" name="state_id"
                                    class="form-control search_by_state select2 select_filter"
                                    data-append="search_by_state">
                                    <option value="">Filter by State</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Filter by City</label>
                                <select id="city_id" name="city_id"
                                    class="form-control search_by_city select2 select_filter" data-append="search_by_city">
                                    <option value="">Filter by City</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Filter by Designation</label>
                                <select id="designation_id" name="designation_id"
                                    class="form-control search_by_designation select2 select_filter"
                                    data-append="search_by_designation">
                                    <option value="">Filter by Designation</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Team Role</label>
                                <select id="team_role_id" name="team_role_id"
                                    class="form-control search_by_team_role select2 select_filter"
                                    data-append="search_by_team_role">
                                    <option value="">Filter by Team Role</option>
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
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.filter_country = $('select[name="country_id"] option:selected').val();
                        data.filter_state = $('select[name="state_id"] option:selected').val();
                        data.filter_city = $('select[name="city_id"] option:selected').val();
                        data.filter_designation = $('select[name="designation_id"] option:selected')
                            .val();
                        data.filter_team_role = $('select[name="team_role_id"] option:selected').val();
                    },
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
                }
            });
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
                $('.search').val('');
                dtable.draw();
            });
        });
        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('#company_id').val();
            let country_id = $('#country_id').val();
            let state_id = $('#state_id').val();
            let city_id = $('#city_id').val();
            let designation_id = $('#designation_id').val();
            let team_role_id = $('#team_role_id').val();


            let queryParams = $.param({
                search: search,
                status: status,
                company: company,
                country_id: country_id,
                state_id: state_id,
                city_id: city_id,
                designation_id: designation_id,
                team_role_id: team_role_id
            });

            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });
        $('#print_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let status = $('#status_filter').val();
            let company = $('#company_id').val();
            let country_id = $('#country_id').val();
            let state_id = $('#state_id').val();
            let city_id = $('#city_id').val();
            let designation_id = $('#designation_id').val();
            let team_role_id = $('#team_role_id').val();

            let queryParams = $.param({
                search: search,
                status: status,
                company: company,
                country_id: country_id,
                state_id: state_id,
                city_id: city_id,
                designation_id: designation_id,
                team_role_id: team_role_id
            });

            let url = "{{ route($route . '.print') }}" + "?" + queryParams;
            window.open(url, '_blank');
        });
    </script>
    @include('utils.getCompany')
    @include('utils.getDesignation')
    @include('utils.getTeamRole')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
    @include('utils.getAreaByCity')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-update-status')
@endpush
