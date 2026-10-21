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
                // 'show_add_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
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

                        @if (!$parent_type_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Team Person Name</label>
                                    <select id="inquiry_created_by" name="team_person_name"
                                        class="form-control search_by_created_by select2 select_filter"
                                        data-append="search_by_created_by">
                                        <option value="">Filter by Select Team person name</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Date</label>
                                <input type="text" class="form-control search  visit_my_daterangepicker  table_filter"
                                    name="date" placeholder="Filter by date range" autofocus>
                            </div>
                            {{-- <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by Date</label>
                                <input type="date" class="form-control search  date_filter"
                                    name="date" placeholder="Filter by date range" autofocus>
                            </div> --}}
                            <div>
                                <button type="button" title="Clear Filter" id="cilory_filter"
                                    class="btn btn-outline-danger btn-icon mt-2">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>
                        {{-- <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Quotation Date</label>
                            <input type="text" name="quotation_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div> --}}
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

@endsection

@section('page_leavel_script')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
@endsection

@push('page_scripts')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        // var picker = $('.my_daterangepicker').data('daterangepicker');
        // if (picker) {
        //     picker.maxDate = false;
        //     picker.setStartDate(moment());
        //     picker.setEndDate(moment());
        //     picker.callback(moment(), moment(), 'Today');
        // }
        $(function() {
            if ($('.visit_my_daterangepicker').length > 0) {
                $('.visit_my_daterangepicker').daterangepicker({
                    autoUpdateInput: false, // ⛔ blank rakhe first time
                    locale: {
                        cancelLabel: 'Clear',
                        format: 'DD/MM/YYYY'
                    },
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'),
                            moment().subtract(1, 'month').endOf('month')
                        ]
                    }
                });
                $('.visit_my_daterangepicker').attr("placeholder", "dd/mm/yyyy to dd/mm/yyyy");

                // Clear button
                $('.visit_my_daterangepicker').on('cancel.daterangepicker', function(ev, picker) {
                    $(this).val('');
                    dtable.draw();
                });

                // Apply button → custom format
                $('.visit_my_daterangepicker').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(
                        picker.startDate.format('DD/MM/YYYY') + ' to ' + picker.endDate.format(
                            'DD/MM/YYYY')
                    );
                    dtable.draw();
                });
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
                        data.filter_team_person_name = $(
                            'select[name="team_person_name"] option:selected').val();
                        // data.filter_date = $('input[name="date"]').val();
                        data.filter_date = $('input[name="date"]').val().replace(
                            ' - ', ' to ');
                    },
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},

            });
        });

        $(document).on('change', '.select_filter', function(event) {
            event.preventDefault();
            dtable.draw();
        });

        $(document).on('change', '.table_filter', function(event) {
            event.preventDefault();
            dtable.draw();
        });
        $('.search').keyup(function() {
            dtable.draw();
        });

        $(document).ready(function() {
            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('.search').val('');
                // var picker = $('.my_daterangepicker').data('daterangepicker');
                // if (picker) {
                //     picker.maxDate = false;
                //     let startOfMonth = moment().startOf('month');
                //     let today = moment();

                //     picker.setStartDate(startOfMonth);
                //     picker.setEndDate(today);
                //     picker.callback(startOfMonth, today, 'This Month');
                // }
                // 🔹 just clear daterangepicker
                var picker = $('.visit_my_daterangepicker').data('daterangepicker');
                if (picker) {
                    picker.setStartDate(moment());
                    picker.setEndDate(moment());
                    $('.visit_my_daterangepicker').val(''); // input clear karo
                }
                dtable.draw();
            });
        });
        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let search = $('input[name="search"]').val();
            let filter_company = $('#company_id').val();
            let filter_team_person_name = $('#inquiry_created_by').val();
            let filter_date = $('#filter_date').val();

            let queryParams = $.param({
                search: search,
                filter_company: filter_company,
                filter_team_person_name: filter_team_person_name,
                filter_date: filter_date
            });

            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
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
    @if (!$parent_type_id)
        @include('utils.getTeams')
    @endif
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
