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
@endsection

@section('content')

    <div class="px-1">
        <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
                'route' => $route,
            
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
                            <label class="form-label">Filter by Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>

                        @if (!$company_id)
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Company</label>
                                    <select name="company_id" id="company_id"
                                        class="form-control select_filter search_by_company select2"
                                        data-append="search_by_company"
                                        data-selectedCompanyId="{{ isset($edit) && $edit?->company_id ? $edit?->company_id : '' }}"
                                        autofocus>
                                        <option value="" disabled selected>
                                            Select Company
                                        </option>
                                    </select>
                                </div>
                            </div>
                        @else
                            <input type="hidden" class="form-control search_by_company" name="company_id"
                                value="{{ $company_id }}" />
                        @endif

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="warehouse_id" class="form-label">Filter by Warehouse</label>
                                <select name="warehouse_id" id="warehouse_id"
                                    class="form-select select_filter search_by_warehouse select2"
                                    data-append="search_by_warehouse" autofocus>
                                    <option value="" disabled selected>Select Warehouse</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Category</label>
                                <select id="product_category_id" name="product_category_id"
                                    class="form-control search_by_product_category select2 select_filter"
                                    data-append="search_by_product_category">
                                    <option value="">Filter by Category</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Prpduct</label>
                                <select name="product_variant_price_id" id="product_variant_price_id"
                                    class="form-control search_by_product_variant select2 select_filter"
                                    data-append="search_by_product_variant"
                                    data-selectedProductVariantId="{{ isset($edit) && $edit?->product_variant_price_id ? $edit?->product_variant_price_id : '' }}"
                                    autofocus>
                                    <option value="" disabled
                                        {{ old('product_variant_price_id', $edit->product_variant_price_id ?? '') ? '' : 'selected' }}>
                                        Select Product
                                    </option>
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
                <div class="card-body">
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
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getWarehouse')
    @include('utils.getProductCategory')
    @include('utils.getProductAndVariant')

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
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                ajax: {
                    "url": "{{ route($route . '.index') }}",
                    type: "GET",
                    data: function(data) {
                        let company_id = $('select[name="company_id"] option:selected').val();
                        if (!company_id) {
                            company_id = $('meta[name="company_id"]').attr('value');
                        }

                        data.search = $('input[name="search"]').val();
                        data.filter_warehouse = $('select[name="warehouse_id"] option:selected').val();
                        data.filter_company = company_id;
                        data.filter_product_category = $(
                            'select[name="product_category_id"] option:selected').val();
                        data.filter_product_variant_price_id = $(
                            'select[name="product_variant_price_id"] option:selected').val();
                    },
                },
                columns: {!! isset($columns) ? json_encode($columns) : '[]' !!},
                language: {
                    emptyTable: '<strong>Use above filters to display data</strong>',
                    searchPlaceholder: 'Search...',
                }
            });

            dtable.on('draw', function() {
                $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                $('[data-bs-toggle="tooltip"]').tooltip();
            });

            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('.search').val('');
                $('#warehouse_id').html('<option value="" disabled selected>Select Warehouse</option>')
                    .trigger('change');
                fetch_warehouse();
                dtable.draw();
            });

            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();
                let filters = {
                    filter_search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"] option:selected').val(),
                    filter_warehouse_id: $('select[name="warehouse_id"] option:selected').val(),
                    filter_product_category: $('select[name="product_category_id"] option:selected')
                        .val(),
                    filter_product_variant_price_id: $(
                        'select[name="product_variant_price_id"] option:selected').val(),
                };

                var queryParams = $.param(filters);
                var url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });

            $('#print_btn').on('click', function(e) {
                e.preventDefault();

                let filters = {
                    filter_search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"] option:selected').val(),
                    filter_warehouse_id: $('select[name="warehouse_id"] option:selected').val(),
                    filter_product_category: $('select[name="product_category_id"] option:selected')
                        .val(),
                    filter_product_variant_price_id: $(
                        'select[name="product_variant_price_id"] option:selected').val(),
                };

                let queryParams = $.param(filters);
                let url = "{{ route($route . '.print') }}" + "?" + queryParams;
                window.open(url, '_blank');
            });



            $(document).on('change', '.select_filter', function(event) {
                let filter_value = $(this).val();
                if (filter_value != '') {
                    event.preventDefault();
                    dtable.draw();
                }
            });

            $('input[name="search"]').keyup(function() {
                dtable.draw();
            });
        });
    </script>
@endpush
