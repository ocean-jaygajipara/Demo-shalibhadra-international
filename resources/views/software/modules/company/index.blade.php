@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
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
                        <div class="col-md-3">
                            <label class="form-label">Filter by Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>

                        <div class="col-md-6 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Plan</label>
                                <select id="plan_id" name="plan_id"
                                    class="form-control search_by_plan select2 select_filter" data-append="search_by_plan">
                                    <option value="">Filter by Plan</option>
                                </select>
                            </div>
                            <div class="flex-grow-1 me-2">
                                <label for="status_filter" class="form-label">Filter by Status</label>
                                <select id="status_filter" name="status" class="form-select select2">
                                    <option value="all">All</option>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="expired">Expired</option>
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
                            <thead>
                                <tr>
                                    <th>Sr No.</th>
                                    <th>Company Details</th>
                                    <th>Contact Info</th>
                                    <th>Plan & App Key</th>
                                    <th>Prefix Title</th>
                                    {{-- <th>OTP</th> --}}
                                    {{-- <th>Plan Name</th> --}}
                                    <th>Created Date</th>
                                    <th>Status</th>
                                    <th class="no-sort text-start">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
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
    @include('utils.getPlans')

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
                responsive: true,
                processing: true,
                serverSide: true,
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>     ',
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',


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
                        data.search = $('input[type="search"]').val();
                        data.filter_plan = $('select[name="plan_id"] option:selected').val();
                        data.status = $('#status_filter').val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'company_details',
                        name: 'company_details'
                    },
                    // {
                    //     data: 'company_name',
                    //     name: 'company_name',
                    // },
                    {
                        data: 'contact_info',
                        name: 'contact_info',
                    },
                    {
                        data: 'plan_info',
                        name: 'plan_info',
                    },
                    {
                        data: 'prefix_info',
                        name: 'prefix_info',
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
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
    </script>
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-update-status')
@endpush
