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
                        <div class="col-md-3">
                            <label class="form-label">Filter by Status Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>
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

                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Sales Type</label>
                                <select id="salestype" name="salestype"
                                    class="form-control search_by_salestype select2 select_filter"
                                    data-append="search_by_salestype">
                                    <option value="">Filter by Sales Type</option>
                                    <option value="Quotation" {{ old('salestype') == 'Quotation' }}>
                                        Quotation</option>
                                    <option value="Sales Orders" {{ old('salestype') == 'Sales Orders' }}>
                                        Sales Orders</option>
                                    <option value="Invoice" {{ old('salestype') == 'Invoice' }}>
                                        Invoice</option>
                                    <option value="Dispatch Orders" {{ old('salestype') == 'Dispatch Orders' }}>
                                        Dispatch Orders</option>
                                    <option value="Packing Slip" {{ old('salestype') == 'Packing Slip' }}>
                                        Packing Slip</option>
                                    <option value="Purchase Order" {{ old('salestype') == 'Purchase Order' }}>
                                        Purchase Order</option>
                                    <option value="Job Work Issue" {{ old('salestype') == 'Job Work Issue' }}>
                                        Job Work Issue</option>
                                    <option value="GRN" {{ old('salestype') == 'GRN' }}>
                                        GRN</option>
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
                                    <th>Company Name</th>
                                    <th>Sales Type</th>
                                    <th>Status Name</th>
                                    <th>Status Color</th>
                                    <th>Display Order</th>
                                    <th width="10%" class="text-start">Status</th>
                                    <th width="15%" class="no-sort text-start">Actions</th>
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
                        data.filter_salestype = $('select[name="salestype"] option:selected').val();
                    },
                },
                columns: [
                    {
                        data: 'company.company_name',
                        name: 'company_id',

                    },
                    {
                        data: 'salestype',
                        name: 'salestype',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'statusname',
                        name: 'statusname',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'color_stastus',
                        name: 'color_stastus',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'displayorder',
                        name: 'displayorder'
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

        $(document).on('focusout', '.displayorder-input', function() {
            let id = $(this).data('id');
            let displayorder = $(this).val();
            var _token = '{{ csrf_token() }}';

            $.ajax({
                url: `{{ route('sales-status.update-displayorder') }}`,
                type: 'POST',
                data: {
                    _token: _token,
                    displayorder: displayorder,
                    id: id
                },
                success: function(res) {
                    console.log(res.message);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Update failed: ' + xhr.statusText); // Shows "Not Found"
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
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');
                dtable.draw();
            });
        });
    </script>
    @include('utils.getCompany')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
