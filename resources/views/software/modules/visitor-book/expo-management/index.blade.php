@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@section('content')
    {{-- <div class="d-flex justify-content-lg-between px-1">
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
    </div> --}}
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
                            <label class="form-label">Filter by Expo Name,Location and Url</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
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



                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Expo Category</label>
                                <select id="expo_category_id" name="expo_category_id"
                                    class="form-control search_by_expo_category select2 select_filter"
                                    data-append="search_by_expo_category">
                                    <option value="">Filter by Category</option>
                                </select>
                            </div>
                        </div>


                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Date</label>
                            <input type="text" name="start_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div>

                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label for="status_filter" class="form-label">Filter by Status</label>
                                <select id="status_filter" name="status" class="form-select select2">
                                    <option value="all">All</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
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



        <!-- DataTable -->
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
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection

@push('page_scripts')

    <script>
        var startDate = moment().startOf('year');
        var endDate = moment().endOf('year');
        $(function() {
            var picker = $('.my_daterangepicker').data('daterangepicker');
            if (picker) {
                picker.maxDate = false;

                // var endDate = moment().add('month').endOf('month');

                picker.setStartDate(startDate);
                picker.setEndDate(endDate);
                picker.callback(startDate, endDate, 'Initial range');
            }
        });
    </script>
    <script>
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
                order: [
                    [0, 'DESC']
                ],
                ajax: {
                    url: "{{ route($route . '.index') }}",
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"]').val();
                        data.expo_category_id = $('select[name="expo_category_id"]').val();
                        data.status = $('#status_filter').val();

                        data.filter_expo_date = $('input[name="start_date"]').val().replace(
                            ' - ', ' to ');
                    },

                },

                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
                },
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>'
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

            });

            $(document).on('change', '.select_filter', function(event) {
                event.preventDefault();
                dtable.draw();
            });
            $('input[name="start_date"]').on('change apply.daterangepicker', function() {
                dtable.draw();
            });


            $('input[name="search"]').keyup(function() {
                dtable.draw();
            });
            $('#status_filter').on('change', function() {
                dtable.ajax.reload();
            });
            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');
                // const defaultStartDate = moment().startOf('month');
                // const defaultEndDate = moment().add('month').endOf('month');
                const defaultStartDate = startDate;
                const defaultEndDate = endDate;
                const picker = $('input[name="start_date"]').data('daterangepicker');

                picker.setStartDate(defaultStartDate);
                picker.setEndDate(defaultEndDate);

                $('input[name="start_date"]').val(
                    defaultStartDate.format('DD/MM/YYYY') + ' - ' + defaultEndDate.format('DD/MM/YYYY')
                );
                filterDateRange = [];
                dtable.draw();
            });


            // Excel Export Button for Expo Management
            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();
                const filterExpoDate = $('input[name="start_date"]').val().replace(' - ', ' to ');
                let filters = {
                    search: $('input[name="search"]').val(),
                    company: $('select[name="company_id"]').val(),
                    expo_category_id: $('select[name="expo_category_id"]').val(),
                    status: $('select[name="status"]').val(),
                    filter_expo_date: filterExpoDate
                };

                const queryParams = $.param(filters);
                const url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });


            // Print button
            $('#print_btn').on('click', function(e) {
                e.preventDefault();
                const filterExpoDate = $('input[name="start_date"]').val().replace(' - ', ' to ');
                let filters = {
                    search: $('input[name="search"]').val(),
                    company_id: $('select[name="company_id"]').val(),
                    expo_category_id: $('select[name="expo_category_id"]').val(),
                    status: $('select[name="status"]').val(),
                    filter_expo_date: filterExpoDate
                };

                let queryParams = $.param(filters);
                let url = "{{ route($route . '.print') }}?" + queryParams;
                window.open(url, '_blank');

            });

            $(document).on('click', '.file-preview', function() {
                const url = $(this).data('url');
                if (!url) {
                    toastr.error('No file URL found');
                    return;
                }

                const isImage = /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(url);

                if (isImage) {
                    $('#fileIframe').hide().attr('src', '');
                    $('#fileImage').attr('src', url).show();
                } else {
                    $('#fileImage').hide().attr('src', '');
                    $('#fileIframe').attr('src', url).show();
                }

                $('#fileViewModal').modal('show');
            });

            $('#fileViewModal').on('hidden.bs.modal', function() {
                $('#fileIframe').attr('src', '').hide();
                $('#fileImage').attr('src', '').hide();
            });

        });
    </script>

    <!-- File View Modal -->
    <div class="modal fade" id="fileViewModal" tabindex="-1" aria-labelledby="fileViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body d-flex justify-content-center align-items-center" style="height: 80vh;">
                    <iframe id="fileIframe" src="" frameborder="0"
                        style="width: 100%; height: 100%; display: none;"></iframe>
                    <img id="fileImage" src="" alt="Image Preview"
                        style="max-width: 100%; max-height: 100%; display: none;" />
                </div>

            </div>
        </div>
    </div>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getExpoCategory')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
