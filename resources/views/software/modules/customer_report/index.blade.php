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
    <style>
        .table-responsive {
            overflow-x: visible !important;
        }
        #customer-report-title {
            font-size: 20px;
            font-weight: 600;
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
            'route' => $route,
            'show_add_btn' => false,
            'show_filter_btn' => true,
            'show_back_btn' => false,
            'show_export_btn' => ((isset($modules['excel_permission']) && $modules['excel_permission']) || (isset($modules['print_permission']) &&  $modules['print_permission'])) ? true : false,
            'show_excal_btn' => (isset($modules['excel_permission'])) ? $modules['excel_permission'] : false,
            'show_print_btn' => false,
        ])
    </div>

    <div class="row my-3">
         <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @if (!$company_id)
                        <div class="col-md-3">
                        @else
                        <div class="col-md-2">
                        @endif
                            <label class="form-label">Filter by Name</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus>
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Date</label>
                            <input type="text" name="filter_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
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
                                <label class="form-label">Filter by Customer Type</label>
                                <select id="customer_type_id" name="customer_type_id"
                                    class="form-control search_by_customer_type select2 select_filter" data-append="search_by_customer_type">
                                    <option value="">Filter by Customer Type</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Customer</label>
                                <select id="customer_id" name="customer_id"
                                    class="form-control search_by_customer select2 select_filter"
                                    data-append="search_by_customer">
                                    <option value="">Filter by Customer</option>
                                </select>
                            </div>
                        </div>


                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label for="form-label">Display Format</label>
                                <select id="display_format" class="form-control select2">
                                    <option value="both" selected>Both</option>
                                    <option value="graph">Graph</option>
                                    <option value="table" selected>Table</option>
                                </select>
                            </div>
                        </div>


                        <div class="col-md-1 text-center">
                            <button type="button" title="Cilory Filter" id="cilory_filter"
                                class="btn btn-outline-danger btn-icon ms-75 me-75 mt-4"><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mb-4" id="chartContainer">
            <div class="card my-3 mb-4">
                <div class="card-body">
                    <h4 class="mb-3 font-weight-bold text-center">Customer Summary Chart</h4>
                    <canvas id="customerChart" height="150"></canvas>
                </div>
            </div>
        </div>


        <div class="col-md-12" id="tableContainer">
            <div class="card">
                <div id="customer-report-title" class="text-center fw-bold fs-5 mt-3 mb-2">

                </div>
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



<div class="modal fade" id="order_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Company Name : </strong><span id="modal_company_name"></span>
                             <strong>Customer Mobile : </strong><span id="modal_customer_mobile"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Customer Name : </strong><span id="modal_customer_name"></span>
                            <strong>Customer Email : </strong><span id="modal_customer_email"></span>
                        </div>
                    </div>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>

@endsection

@section('page_leavel_script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        $(function() {
            let picker = $('.my_daterangepicker').data('daterangepicker');
            if (picker) {
                let startDate = moment().subtract(5, 'months').startOf('month');
                let endDate = moment().endOf('month');

                let formattedRange = startDate.format('DD/MM/YYYY') + ' to ' + endDate.format('DD/MM/YYYY');
                picker.setStartDate(startDate);
                picker.setEndDate(endDate);
                $('.my_daterangepicker').val(formattedRange);
                picker.callback(startDate, endDate, formattedRange);
            }
        });

        var dtable = null;
        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
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
                        data.filter_customer = $('select[name="customer_id"] option:selected').val();
                        data.filter_customer_type = $('select[name="customer_type_id"] option:selected').val();
                        data.filter_date = $('input[name="filter_date"]').val().replace(' - ', ' to ');
                    },
                    dataSrc: function(response) {
                        renderCustomerChart(response.chartData || {});
                        return response.data || [];
                    }
                },
                columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                language: {
                    searchPlaceholder: 'Search...',
                },
                initComplete: function () {
                    let range = $('input[name="filter_date"]').val().replace(' - ', ' TO ');
                    const heading = `CUSTOMER REPORT - ${range}`;
                    $('#customer-report-title').html(heading);

                    let display_format = $('#display_format').val();
                    $('#chartContainer').toggle(display_format === 'graph' || display_format === 'both');
                    $('#tableContainer').toggle(display_format === 'table' || display_format === 'both');
                },
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>'
            });

            $(document).on('change', '.select_filter, .table_filter, #display_format', function(event) {
                event.preventDefault();
                if (dtable) {

                    dtable.draw();
                      let display_format = $('#display_format').val();
                    $('#chartContainer').toggle(display_format === 'graph' || display_format === 'both');
                    $('#tableContainer').toggle(display_format === 'table' || display_format === 'both');
                }
            });

            $(document).on('click', '.applyBtn', function(event) {
                event.preventDefault();
                if (dtable) {
                    dtable.draw();
                }
            });

            function renderCustomerChart(chartData) {
                const ctx = document.getElementById('customerChart').getContext('2d');

                if (window.customerChartInstance) {
                    window.customerChartInstance.destroy();
                }

                window.customerChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Order Value',
                                data: chartData.order_values,
                                backgroundColor: '#81C784',
                                stack: 'stack1',
                                borderWidth: 1,
                            },
                            {
                                label: 'Total Visit',
                                data: chartData.total_visits,
                                backgroundColor: '#7986CB',
                                stack: 'stack1',
                                borderWidth: 1,
                            },
                            {
                                label: 'Invoice Value',
                                data: chartData.invoice_values,
                                backgroundColor: '#E57373',
                                stack: 'stack1',
                                borderWidth: 1,
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            tooltip: {
                                mode: 'nearest',
                                intersect: true,
                                position: 'nearest',
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw || 0;
                                        return `${context.dataset.label}: ₹ ${Number(value).toLocaleString('en-IN', {
                                            minimumFractionDigits: 2
                                        })}`;
                                    }
                                }
                            },
                            legend: {
                                position: 'bottom'
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                }
                            },
                            y: {
                                stacked: true
                            }
                        }
                    }
                });
            }


            $('input[name="search"]').keyup(function() {
                dtable.draw();
            });

            $("#cilory_filter").click(function() {
                $('.select_filter').val(null).trigger('change');
                $('#status_filter').val('all').trigger('change');
                $('.search').val('');

                var picker = $('.my_daterangepicker').data('daterangepicker');
                if(picker){
                    let startDate = moment().subtract(5, 'months').startOf('month');
                    let endDate = moment().endOf('month');

                    let formattedRange = startDate.format('DD/MM/YYYY') + ' to ' + endDate.format('DD/MM/YYYY');
                    picker.setStartDate(startDate);
                    picker.setEndDate(endDate);
                    $('.my_daterangepicker').val(formattedRange);
                    picker.callback(startDate, endDate, formattedRange);
                }

                dtable.draw();
            });
        });

        $(document).on('click', '.view_sales_orders', function () {
            let customer_id = $(this).data('customer-id');
            let company_id = $(this).data('company-id');
            let from_date = $(this).data('from-date');
            let to_date = $(this).data('to-date');

            $.ajax({
                url: "{{ route($route . '.get_sales_order_report') }}",
                type: "POST",
                datatype: 'json',
                data: {
                    customer_id: customer_id,
                    company_id: company_id,
                    from_date: from_date,
                    to_date: to_date
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if(response.status == true) {
                        var innerTableHtml = '';
                        var resData = response.data;
                        if (resData && resData.length > 0) {

                            $("#modal_company_name").html(resData[0].company_name);
                            $("#modal_customer_name").html(resData[0].customer_name);
                            $("#modal_customer_mobile").html(resData[0].customer_mobile);
                            $("#modal_customer_email").html(resData[0].customer_email);
                            innerTableHtml += '<table class="table table-bordered"><thead><tr><th>Sales Order No</th><th>Sales Order Date</th><th>Total Amount</th><th>Status</th></tr></thead><tbody>';

                            $.each(resData, function(index, item) {
                                innerTableHtml += '<tr>';
                                        innerTableHtml += '<td>'+ item.sales_order_no ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.sales_order_date ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.grand_total ?? '-' +'</td>';
                                        innerTableHtml += '<td><span class="badge bg-glow m-2" style="color:white !important;background-color: '+item.sales_status_color+'">'+ item.sales_status ?? '-' +'</span></td>';
                                innerTableHtml += '</tr>';
                            });
                            innerTableHtml += '</tbody></table>';

                            $('#order_modal .modal-body').html(innerTableHtml);
                            const assignModal = new bootstrap.Modal(document.getElementById('order_modal'));
                            assignModal.show();
                        }
                    }
                },
                error: function () {
                    $('#order_modal .modal-body').html('<p class="text-danger">Failed to load data.</p>');
                }
            });
        });

        $(document).on('click', '.view_quotation', function () {
            let customer_id = $(this).data('customer-id');
            let company_id = $(this).data('company-id');
            let from_date = $(this).data('from-date');
            let to_date = $(this).data('to-date');

            $.ajax({
                url: "{{ route($route . '.get_quotation_report') }}",
                type: "POST",
                datatype: 'json',
                data: {
                    customer_id: customer_id,
                    company_id: company_id,
                    from_date: from_date,
                    to_date: to_date
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if(response.status == true) {
                        var innerTableHtml = '';
                        var resData = response.data;
                        if (resData && resData.length > 0) {

                            $("#modal_company_name").html(resData[0].company_name);
                            $("#modal_customer_name").html(resData[0].customer_name);
                            $("#modal_customer_mobile").html(resData[0].customer_mobile);
                            $("#modal_customer_email").html(resData[0].customer_email);
                            innerTableHtml += '<table class="table table-bordered"><thead><tr><th>Quotation No</th><th>Quotation Date</th><th>Total Amount</th><th>Status</th></tr></thead><tbody>';

                            $.each(resData, function(index, item) {
                                innerTableHtml += '<tr>';
                                        innerTableHtml += '<td>'+ item.quotation_no ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.quotation_date ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.grand_total ?? '-' +'</td>';
                                        innerTableHtml += '<td><span class="badge bg-glow m-2" style="color:white !important;background-color: '+item.sales_status_color+'">'+ item.sales_status ?? '-' +'</span></td>';
                                innerTableHtml += '</tr>';
                            });
                            innerTableHtml += '</tbody></table>';

                            $('#order_modal .modal-body').html(innerTableHtml);
                            const assignModal = new bootstrap.Modal(document.getElementById('order_modal'));
                            assignModal.show();
                        }
                    }
                },
                error: function () {
                    $('#order_modal .modal-body').html('<p class="text-danger">Failed to load data.</p>');
                }
            });
        });

        $(document).on('click', '.view_dispatch', function () {
            let customer_id = $(this).data('customer-id');
            let company_id = $(this).data('company-id');
            let from_date = $(this).data('from-date');
            let to_date = $(this).data('to-date');

            $.ajax({
                url: "{{ route($route . '.get_dispatch_report') }}",
                type: "POST",
                datatype: 'json',
                data: {
                    customer_id: customer_id,
                    company_id: company_id,
                    from_date: from_date,
                    to_date: to_date
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if(response.status == true) {
                        var innerTableHtml = '';
                        var resData = response.data;
                        if (resData && resData.length > 0) {

                            $("#modal_company_name").html(resData[0].company_name);
                            $("#modal_customer_name").html(resData[0].customer_name);
                            $("#modal_customer_mobile").html(resData[0].customer_mobile);
                            $("#modal_customer_email").html(resData[0].customer_email);
                            innerTableHtml += '<table class="table table-bordered"><thead><tr><th>Dispatch No</th><th>Dispatch Date</th><th>Dispatch QTY</th></tr></thead><tbody>';

                            $.each(resData, function(index, item) {
                                innerTableHtml += '<tr>';
                                        innerTableHtml += '<td>'+ item.dispatch_no ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.dispatch_date ?? '-' +'</td>';
                                        innerTableHtml += '<td>'+ item.dispatch_quantity ?? '-' +'</td>';
                                innerTableHtml += '</tr>';
                            });
                            innerTableHtml += '</tbody></table>';

                            $('#order_modal .modal-body').html(innerTableHtml);
                            const assignModal = new bootstrap.Modal(document.getElementById('order_modal'));
                            assignModal.show();
                        }
                    }
                },
                error: function () {
                    $('#order_modal .modal-body').html('<p class="text-danger">Failed to load data.</p>');
                }
            });
        });

        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let filter_search =  $('input[name="search"]').val();
            let filter_company = $('select[name="company_id"] option:selected').val();
            let filter_customer = $('select[name="customer_id"] option:selected').val();
            let filter_customer_type =  $('select[name="customer_type_id"] option:selected').val();
            let filter_date = $('input[name="filter_date"]').val().replace(' - ', ' to ');

            let queryParams = $.param({
                filter_search: filter_search,
                filter_company: filter_company,
                filter_customer: filter_customer,
                filter_customer_type: filter_customer_type,
                filter_date: filter_date,
            });

            let url = "{{ route($route . '.export.excel') }}"  + "?" + queryParams;
            window.location.href = url;
        });
    </script>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getCustomerSales')
    @include('utils.getCompanyByCustomerType')

    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
    @include('software.inlcudes.script-clone-record')
@endpush


