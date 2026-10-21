@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    // dd($modules);
@endphp
@section('title', $page_title)

@section('page_leavel_style')
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.12.4/css/dataTables.bootstrap5.min.css" /> --}}
    {{-- <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css"> --}}
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
    <div class="px-1">
        <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
                'route' => $route,
                'show_add_btn' => $modules['addPermission'],
                'show_filter_btn' => true,
                'show_back_btn' => false,
            ])
        </div>
    </div>

    <div class="row my-3">
        <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Name</label>
                                <input type="search" class="form-control search" name="search" placeholder="Search..."
                                    autofocus>
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
                {{-- <div class="card-body"> --}}
                    <div class="card-datatable text-nowrap mt-3">
                        <div class="card-datatable table-responsive">
                            <table id="yajra-datatables" class="dt-responsive table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sr No.</th>
                                        <th>Name</th>
                                        <th>Plan Valid Day</th>
                                        <th>Max Team User Count</th>
                                        <th>Max Customer Count</th>
                                        <th>Max Inquiry Count</th>
                                        <th>plan Type</th>
                                        <th>Status</th>
                                        <th width="15%" class="no-sort text-start">Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                {{-- </div> --}}
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
            console.log("Console 62");

            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                order: [
                    [0, 'DESC']
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
                        // data.search_by_number = $('input[name="search_by_number"]').val();
                        // data.search_by_opportunity_type = $(".search_by_opportunity_type option:selected").val();
                        // data.search_by_industry = $(".search_by_industry option:selected").val();
                        // data.search =  $(".filter_by_product_name").val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'plan_valid_day',
                        name: 'plan_valid_day',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'max_team_user_count',
                        name: 'max_team_user_count',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'max_customer_count',
                        name: 'max_customer_count',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'max_inquiry_count',
                        name: 'max_inquiry_count',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'plan_type',
                        name: 'plan_type',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-start'
                    },
                ],
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
    </script>
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
