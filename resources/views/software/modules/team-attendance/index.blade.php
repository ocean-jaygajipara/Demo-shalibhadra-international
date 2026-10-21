@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
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
            // 'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
            'show_filter_btn' => true,
            // 'show_back_btn' => false,
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
                // 'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
                'show_filter_btn' => true,
                // 'show_back_btn' => false,
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
                            <div class="col-md-3 col-sm-12">
                                <label class="form-label">Filter by Company</label>
                                <select id="company_id" name="company_id"
                                    class="form-control search_by_company select2 select_filter"
                                    data-append="search_by_company">
                                    <option value="">Filter by Company</option>
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Team Person Detail</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>
                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Date Range</label>
                                <input type="text" name="filter_by_date_range"
                                    class="form-control my_daterangepicker table_filter" value=""
                                    placeholder="Filter by date range">
                            </div>
                            <div>
                                <button type="button" title="Clear Filter" id="clear_filter"
                                    class="btn btn-outline-danger btn-icon mt-2">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0">
                    <div class="modal-body text-center">
                        <img id="modalImage" src="" class="img-fluid rounded" alt="Preview">
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
                            {{-- <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Team Person Detail</th>
                                    <th>Punch In detail</th>
                                    <th>Punch Out Detail</th>
                                    <th>Total Working Time</th>
                                </tr>
                            </thead> --}}
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
    <!-- Moment.js -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

    <!-- Daterangepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

@endsection

@push('page_scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize DataTable
            var dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                order: [
                    [0, 'DESC']
                ],
                ajax: {
                    url: "{{ route($route . '.index') }}",
                    type: "GET",
                    data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.company_id = $('#company_id').val();

                        var selectedType = $('#in_out').val();
                        data.in_out = selectedType;

                        var rangePicker = $('.my_daterangepicker').data('daterangepicker');

                        if (rangePicker) {
                            var start = rangePicker.startDate.format('YYYY-MM-DD HH:mm');
                            var end = rangePicker.endDate.format('YYYY-MM-DD HH:mm');

                            if (selectedType === 'in') {
                                data.punch_in_start = start;
                                data.punch_in_end = end;
                                data.punch_out_start = null;
                                data.punch_out_end = null;
                            } else if (selectedType === 'out') {
                                data.punch_in_start = null;
                                data.punch_in_end = null;
                                data.punch_out_start = start;
                                data.punch_out_end = end;
                            } else {
                                data.punch_in_start = start;
                                data.punch_in_end = end;
                                data.punch_out_start = start;
                                data.punch_out_end = end;
                            }
                        } else {
                            data.punch_in_start = null;
                            data.punch_in_end = null;
                            data.punch_out_start = null;
                            data.punch_out_end = null;
                        }



                        // console.log("Filter Range: ", data.punch_in_start, data.punch_in_end);
                    }
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                /*
                columns: [{
                        data: 'id',
                        name: 'id',
                        orderable: true,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'team_person_detail',
                        name: 'team_person_detail',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'punch_in_data',
                        name: 'punch_in_time',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'punch_out_data',
                        name: 'punch_out_time',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'working_time',
                        name: 'working_time',
                        orderable: false,
                        searchable: false
                    }
                ]
                    */
            });


            $('#clear_filter').on('click', function() {
                // var picker = $('.my_daterangepicker').data('daterangepicker');
                // if (picker) {
                //     var today = moment();
                //     picker.setStartDate(today);
                //     picker.setEndDate(today);
                //     $('.my_daterangepicker').val('').trigger('change');
                // }

                $('.select_filter').val('in_out').trigger('change.select2');

                $('input[name="search"]').val('');

                dtable.draw();
            });



            $('.my_daterangepicker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' to ' + picker.endDate.format(
                    'DD-MM-YYYY'));
                $(this).trigger('change');
            });

            $('.my_daterangepicker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('').trigger('change');
            });
            $('input[name="search"], .my_daterangepicker').on('change keyup', function() {
                dtable.draw();
            });

            $('#company_id').on('change keyup', function() {
                let filter_company = $('select[name="company_id"] option:selected').val();
                if ($.trim(filter_company || '') !== '') {
                    dtable.draw();
                }
            });

            // Export to Excel
            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();

                let search = $('input[name="search"]').val();
                let status = $('#status_filter').val();
                let company = $('#company_id').val();


                let queryParams = $.param({
                    search: search,
                    status: status,
                    company: company,

                });

                let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });

            // Print Button
            $('#print_btn').on('click', function(e) {
                e.preventDefault();

                let search = $('input[name="search"]').val();
                let status = $('#status_filter').val();
                let company = $('#company_id').val();

                let queryParams = $.param({
                    search: search,
                    status: status,
                    company: company,

                });

                let url = "{{ route($route . '.print') }}" + "?" + queryParams;
                window.location.href = url;
            });
        });
    </script>

    <script>
        function showImageModal(src) {
            const modalImage = document.getElementById('modalImage');
            modalImage.src = src;
            const myModal = new bootstrap.Modal(document.getElementById('imageModal'));
            myModal.show();
        }
    </script>


    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
