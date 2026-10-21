
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
    #yajra-datatables tbody tr {
        height: 56px;
    }
    .bold-header {
        font-weight: bold !important;
    }
    .font-header{
        font-size: 15px !important;
    }
    th, td {
        white-space: nowrap;
    }
    div.dataTables_wrapper {
        width: 100%;
        margin: 0 auto;
    }

    @media print {
        html, body {
            overflow: visible !important;
        }

        table {
            overflow: visible !important;
            display: table !important;
            width: 100% !important;
        }

        .table-container {
            overflow: visible !important;
        }
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

                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Type</label>
                                <select id="filter_type" name="filter_type" class="form-control select2">
                                    <option value="day">Day Wise</option>
                                    <option value="month" selected>Month Wise</option>
                                    <option value="year">Year Wise</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Date</label>
                            <input type="text" name="filter_sales_date" class="form-control my_daterangepicker table_filter"
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
        <div class="col-md-12 mb-4" id="chartContainer" style="display:none;">
            <div class="card my-3 mb-4">
                <div class="card-body">
                    <h4 class="mb-3 font-weight-bold text-center">Sales Summary Chart</h4>
                    <canvas id="salesBarChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-12" id="tableContainer">
            <div class="card">
                <div class="card-datatable text-nowrap mt-3">
                    <div>
                        <table id="yajra-datatables" class="table table-hover w-100">
                            <thead>
                                <tr>

                                </tr>
                            </thead>
                            <tfoot>
                                <tr>

                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_leavel_script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
    <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
@endsection

@push('page_scripts')
    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });



        var dtable = null;
        let salesChart = null;

        $(function() {
            // Set default date range (last 6 months)
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

            fetchAndDrawTable();
            $('#filter_type, #company_id, #customer_id, #filter_sales_date, .select_filter, .table_filter, #display_format').on('change', function () {
                fetchAndDrawTable();
            });
            $('.applyBtn').on('click', function () {
                fetchAndDrawTable();
            });
        });

        function fetchAndDrawTable() {

            let display_format = $('#display_format').val();
            $.ajax({
                url: "{{ route($route . '.index') }}",
                type: "GET",
                data: {
                    filter_type: $('#filter_type').val(),
                    filter_company: $('#company_id').val(),
                    filter_customer: $('#customer_id').val(),
                    filter_customer_type: $('#customer_type_id').val(),
                    filter_sales_date: $('input[name="filter_sales_date"]').val().replace(' - ', ' to '),
                },
                beforeSend: function (request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                success: function (response) {

                    if (!response.columns || !Array.isArray(response.columns)) {
                        alert("Invalid columns format from server");
                        return;
                    }

                    $('#chartContainer').toggle(display_format === 'graph' || display_format === 'both');
                    $('#tableContainer').toggle(display_format === 'table' || display_format === 'both');

                    if ($.fn.DataTable.isDataTable('#yajra-datatables')) {
                        $('#yajra-datatables').DataTable().clear().destroy();
                        $('#yajra-datatables thead').empty(); // clear headers
                    }

                    const thead = $('#yajra-datatables thead');
                    const tfoot = $('#yajra-datatables tfoot');
                    thead.empty();
                    tfoot.empty();

                    const headerRow = $('<tr></tr>');
                    const footerRow = $('<tr></tr>');

                    response.columns.forEach(col => {
                        if (/^\d{2}-\d{4}$/.test(col) || /^\d{2}-\d{2}-\d{4}$/.test(col)) {
                            headerRow.append('<th class="bold-header font-header">' + col + '</th>');
                        } else {
                            headerRow.append('<th class="bold-header">' + col + '</th>');
                        }
                        footerRow.append('<th class="bold-header"></th>');
                    });

                    thead.append(headerRow);
                    tfoot.append(footerRow);


                    // const columns = response.columns.map(col => ({
                    //     data: col,
                    //     title: col
                    // }));

                    const columns = response.columns.map(col => {
                    const title = col.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                        return {
                            data: col,
                            title: title
                        };
                    });

                    dtable = $('#yajra-datatables').DataTable({
                        data: response.data,
                        columns: columns,
                        processing: true,
                        serverSide: false,
                        scrollX: true, // Enable horizontal scroll
                        scrollCollapse: true,
                        paging: true,
                        destroy: true,
                        dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                        order: [[0, 'DESC']],
                        fixedColumns: {
                            leftColumns: 3
                        },
                        language: {
                            searchPlaceholder: 'Search...'
                        },
                        footerCallback: function (row, data, start, end, display) {
                            if (data && data.length > 0) {
                                $(".dataTables_scrollFoot").show();
                                var api = this.api();

                                api.columns().every(function (index) {
                                    var total = 0;
                                    if (index >= 2) {
                                        total = api
                                            .column(index, { search: 'applied' })
                                            .data()
                                            .reduce(function (a, b) {
                                                var x = parseFloat((a + '').replace(/[^0-9.-]+/g, '')) || 0;
                                                var y = parseFloat((b + '').replace(/[^0-9.-]+/g, '')) || 0;
                                                return x + y;
                                            }, 0);

                                        $(api.column(index).footer()).html(`${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`);
                                    } else if (index === 1) {
                                        $(api.column(index).footer()).html('Total Sales');
                                    } else {
                                        $(api.column(index).footer()).html('');
                                    }
                                });
                            }else{
                                $(".dataTables_scrollFoot").hide();
                            }
                        }
                    });

                     if (response.chartData && Array.isArray(response.chartData)) {
                        renderSalesChart(response.chartData);
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.message) {
                        alert(xhr.responseJSON.message);
                    } else {
                        alert('Something went wrong');
                    }
                }
            });
        }

        function renderSalesChart(chartData) {
            const ctx = document.getElementById('salesBarChart').getContext('2d');

            const labels = chartData.map(item => item.label);
            const values = chartData.map(item => item.value);

            if (salesChart) {
                salesChart.destroy();
            }

            salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Sales',
                        data: values,
                        backgroundColor: '#4e73df',
                        barThickness: 20,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: true,
                        }
                    }
                }
            });
        }

        $("#cilory_filter").click(function() {
            $('#filter_type').val('month').trigger('change');
            $('#display_format').val('table').trigger('change');
            $('.select_filter').val(null).trigger('change');
            $('#filter_by_date').val('');


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

        $('#export_excel_btn').on('click', function(e) {
            e.preventDefault();

            let filter_type = $('#filter_type').val();
            let display_format = $('#display_format').val();
            let filter_company = $('#company_id').val();
            let filter_customer = $('#customer_id').val();
            let filter_customer_type = $('#customer_type_id').val();
            let filter_sales_date = $('input[name="filter_sales_date"]').val().replace(' - ', ' to ');

            let queryParams = $.param({
                filter_type: filter_type,
                display_format: display_format,
                company: filter_company,
                customer: filter_customer,
                customer_type: filter_customer_type,
                sales_date: filter_sales_date,
            });

            let url = "{{ route($route . '.export.excel') }}"  + "?" + queryParams;
            window.location.href = url;
        });

        $('#print_btn').on('click', function(e) {
            e.preventDefault();

            let filter_type = $('#filter_type').val();
            let display_format = $('#display_format').val();
            let filter_company = $('#company_id').val();
            let filter_customer = $('#customer_id').val();
            let filter_customer_type = $('#customer_type_id').val();
            let filter_sales_date = $('input[name="filter_sales_date"]').val().replace(' - ', ' to ');

            let queryParams = $.param({
                filter_type: filter_type,
                display_format: display_format,
                filter_company: filter_company,
                filter_customer: filter_customer,
                filter_customer_type: filter_customer_type,
                filter_sales_date: filter_sales_date,
            });

            let url = "{{ route($route . '.print') }}"  + "?" + queryParams;
            window.open(url, '_blank');
        });

    </script>


    @include('utils.getCustomerSales')
    @include('utils.getCompanyByCustomerType')
@endpush


