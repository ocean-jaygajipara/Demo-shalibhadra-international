@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <style>
        .table-responsive {
            overflow-x: visible !important;
        }
        tfoot th {
            color: #000 !important;
            font-weight: 700;
            font-size: 18px;
            background-color: #f8f9fa;
        }
        .dataTables_info {
            display: none !important;
        }
    </style>
@endsection

@section('content')
    <div class="px-1">
        <div class="d-lg-flex justify-content-lg-between flex-column flex-lg-row">
                @include('software.inlcudes.breadcrumb', [
                    'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
                    'route' => $route,
                    'show_add_btn' => false,
                    'show_filter_btn' => true,
                    'show_back_btn' => false,
                    'show_export_btn' => isset($modules['excel_permission']) && $modules['excel_permission'] ? true : false,
                    'show_excal_btn' => isset($modules['excel_permission']) ? $modules['excel_permission'] : false,
                ])
        </div>
        
        <div class="row my-3" id="filter_section">
            <div class="col-md-12 mb-5">
                <div class="card">
                    <div class="card-body">
                         <div class="row">
                             <div class="col-md-3">
                                <label class="form-label">Filter by Name</label>
                                <input type="search" class="form-control search" name="search" placeholder="search..." autofocus>
                             </div>
                             
                             @if (!$company_id)
                            <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Company</label>
                                    <select id="company_id" name="company_id" class="form-control search_by_company select2 select_filter">
                                        <option value="">Filter by Company</option>
                                    </select>
                                </div>
                            </div>
                            @endif

                             <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Job Work Type</label>
                                    <select id="job_work_type_id" name="job_work_type_id" class="form-control search_by_job_work_type_id select2 select_filter">
                                        <option value="">Job Work Type</option>
                                    </select>
                                </div>
                            </div>

                             <div class="col-md-3 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Vendor</label>
                                    <select id="vendor_id" name="vendor_id" class="form-control search_by_vendor select2 select_filter">
                                        <option value="">Filter by Vendor</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Filter by Date</label>
                                <input type="text" name="job_work_issue_date" class="form-control my_daterangepicker table_filter" value="" placeholder="Filter by date range">
                            </div>
                            
                             <div class="col-md-3 d-flex align-items-end mb-2">
                                <div>
                                    <button type="button" title="Clear Filter" id="cilory_filter" class="btn btn-outline-danger btn-icon mt-2">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                            </div>

                         </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row my-3">
            <div class="col-md-12">
                <div class="card">
                    <h5 class="card-header text-center text-primary fw-bold">{{ $modules['title'] ?? '' }}</h5>
                    <div class="card-datatable text-nowrap">
                        <div class="card-datatable table-responsive">
                            <table id="yajra-datatables" class="table table-hover">
                                @if (isset($columns))
                                    <thead>
                                        <tr>
                                            @foreach ($columns as $item)
                                                <th class="{{ $item->className ?? '' }}">{{ $item->td_label }}</th>
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

        // Initialize Daterangepicker
         $(document).ready(function() {
            var startDate = moment().subtract(29, 'days');
            var endDate = moment();
            var formattedRange = startDate.format('DD/MM/YYYY') + ' to ' + endDate.format('DD/MM/YYYY');
            $('.my_daterangepicker').val(formattedRange);

            setTimeout(function() {
                var picker = $('.my_daterangepicker').data('daterangepicker');
                if (picker) {
                    picker.maxDate = false;
                    picker.setStartDate(startDate);
                    picker.setEndDate(endDate);
                    picker.callback(startDate, endDate, 'Last 30 Days');
                }
            }, 100);
            
            
            var dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true, // Enable horizontal scrolling
                ajax: {
                     "url": "{{ route($route . '.index') }}",
                     type: "GET",
                     data: function(data) {
                        data.search = $('input[name="search"]').val();
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.filter_vendor = $('select[name="vendor_id"] option:selected').val();
                        data.filter_job_work_type_id = $('select[name="job_work_type_id"] option:selected').val();
                        data.filter_job_work_issue_date = $('input[name="job_work_issue_date"]').val().replace(' - ', ' to ');
                     }
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                dom: 't<"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',
            });

             $(document).on('change', '.select_filter, .table_filter', function(event) {
                event.preventDefault();
                dtable.draw();
            });

            $('input[name="search"]').keyup(function() {
                dtable.draw();
            });
            
             $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('.search').val('');
                 var picker = $('.my_daterangepicker').data('daterangepicker');
                if (picker) {
                     let startOfMonth = moment().startOf('month');
                     let today = moment();
                    picker.setStartDate(startOfMonth);
                    picker.setEndDate(today);
                    picker.callback(startOfMonth, today, 'This Month');
                }
                dtable.draw();
            });

            // Handle Excel Export (The button in breadcrumb needs to point to this controller's export method)
             $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();
                let search = $('input[name="search"]').val();
                let company = $('#company_id').val();
                let vendor = $('select[name="vendor_id"] option:selected').val();
                let job_work_type_id = $('#job_work_type_id').val();
                let job_work_issue_date = $('input[name="job_work_issue_date"]').val().replace(' - ', ' to ');

                let queryParams = $.param({
                    search: search,
                    filter_company: company,
                    filter_vendor: vendor,
                    filter_job_work_type_id: job_work_type_id,
                    filter_job_work_issue_date: job_work_issue_date,
                });
                
                let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });
        });
    </script>
    
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getJobWorkType')
    @include('utils.getVendorSales')
@endpush
