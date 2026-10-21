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

        .font-header {
            font-size: 15px !important;
        }

        th,
        td {
            white-space: nowrap;
        }

        div.dataTables_wrapper {
            width: 100%;
            margin: 0 auto;
        }

        .bg-month-group-1 {
            background-color: #f5f5f5 !important;
        }

        .bg-month-group-2 {
            background-color: #ffffff !important;
        }


        @media print {

            html,
            body {
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
            'show_export_btn' =>
                (isset($modules['excel_permission']) && $modules['excel_permission']) ||
                (isset($modules['print_permission']) && $modules['print_permission'])
                    ? true
                    : false,
            'show_excal_btn' => isset($modules['excel_permission']) ? $modules['excel_permission'] : false,
            'show_print_btn' => false,
        ])
    </div>

    <div class="row my-3">
        <div class="col-md-12 mb-5" id="filter_section">
            <div class="card">
                <div class="card-body">
                    <div class="row">

                        <div class="col-md-3 col-sm-12">
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
                            <input type="text" name="filter_sales_date"
                                class="form-control my_daterangepicker table_filter" value=""
                                placeholder="Filter by date range">
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
                                <label class="form-label">Filter by Customer</label>
                                <select id="customer_id" name="customer_id"
                                    class="form-control search_by_customer select2 select_filter"
                                    data-append="search_by_customer">
                                    <option value="">Filter by Customer</option>
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

                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Sub Category</label>
                                <select id="product_sub_category_id" name="product_sub_category_id"
                                    class="form-control search_by_product_subcategory select2 select_filter"
                                    data-append="search_by_product_subcategory">
                                    <option value="">Filter by Category</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Product</label>
                                <select id="product_variant_price_id" name="product_variant_price_id"
                                    class="form-control search_by_product select2 select_filter"
                                    data-append="search_by_product">
                                    <option value="">Filter by Product</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-12">
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
                    <h4 class="mb-3 font-weight-bold text-center">Product Sales Summary Chart</h4>
                    <canvas id="productCombinedChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-12" id="tableContainer">
            <div class="card">
                <div class="card-datatable text-nowrap">
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

            $('#filter_type, #company_id, #customer_id, #filter_sales_date, .select_filter, .table_filter, #display_format')
                .on('change', function() {
                    fetchAndDrawTable();
                });

            $('.applyBtn').on('click', function() {
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
                    filter_product_category: $('#product_category_id').val(),
                    filter_product_subcategory: $('#product_sub_category_id').val(),
                    filter_product_variant: $('#product_variant_price_id').val(),
                    filter_sales_date: $('input[name="filter_sales_date"]').val().replace(' - ', ' to '),
                },
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                success: function(response) {
                    if (!response.columns || !Array.isArray(response.columns)) {
                        alert("Invalid columns format from server");
                        return;
                    }

                    $('#chartContainer').toggle(display_format === 'graph' || display_format === 'both');
                    $('#tableContainer').toggle(display_format === 'table' || display_format === 'both');

                    if ($.fn.DataTable.isDataTable('#yajra-datatables')) {
                        $('#yajra-datatables').DataTable().clear().destroy();
                    }

                    const thead = $('#yajra-datatables thead');
                    const tfoot = $('#yajra-datatables tfoot');
                    thead.empty();
                    tfoot.empty();

                    const staticColumns = ['sr_no', 'customer_name', 'product_name', 'total_price',
                    'total_qty'];
                    const dynamicGroups = {};

                    response.columns.forEach(col => {
                        col = col.trim();
                        let groupKey = null;


                        let match = col.match(/^(\d{2})-(\d{2})-(\d{4})_(price|qty)$/i);
                        if (match) {
                            const [, day, month, year] = match;
                            groupKey = `${day}/${month}/${year}`;
                        }


                        match = col.match(/^(\d{2})-(\d{4})_(price|qty)$/i);
                        if (match) {
                            groupKey = `${match[1]}/${match[2]}`;
                        }


                        match = col.match(/^(\d{4})_(price|qty)$/i);
                        if (match) {
                            groupKey = match[1];
                        }

                        if (groupKey) {
                            if (!dynamicGroups[groupKey]) dynamicGroups[groupKey] = [];
                            dynamicGroups[groupKey].push(col);
                        } else {
                            console.warn("Unmatched column:", col);
                        }
                    });


                    const headerRow1 = $('<tr></tr>');
                    const headerRow2 = $('<tr></tr>');

                    const groupClasses = ['bg-month-group-1', 'bg-month-group-2'];

                    staticColumns.forEach(col => {
                        const label = col.replace(/_/g, ' ').toUpperCase();
                        headerRow1.append(`<th rowspan="2" class="bold-header">${label}</th>`);
                    });

                    const sortedDynamicCols = Object.entries(dynamicGroups)
                        .sort(([a], [b]) => {
                            const dateA = new Date(a.split('/').reverse().join('-'));
                            const dateB = new Date(b.split('/').reverse().join('-'));
                            return dateA - dateB;
                        });

                    let groupCounter = 0;

                    sortedDynamicCols.forEach(([period, cols]) => {
                        const groupClass = groupClasses[groupCounter % 2];
                        headerRow1.append(
                            `<th colspan="${cols.length}" class="text-center bold-header ${groupClass}">${period}</th>`
                            );

                        cols.forEach(col => {
                            const suffixMatch = col.match(/_(price|qty)$/i);
                            const suffix = suffixMatch ?
                                suffixMatch[1].charAt(0).toUpperCase() + suffixMatch[1].slice(1)
                                .toLowerCase() :
                                '-';

                            headerRow2.append(
                                `<th class="bold-header ${groupClass}">${suffix}</th>`);
                        });
                        groupCounter++;
                    });

                    thead.empty().append(headerRow1).append(headerRow2);
                    tfoot.empty().append(
                        $('<tr></tr>').append(
                            response.columns.map(() => '<th class="bold-header"></th>').join('')
                        )
                    );


                    const columns = response.columns
                        .filter(col => !/^(\d{2}-\d{4}|\d{2}-\d{2}-\d{4})$/i.test(col))
                        .map(col => {
                            const match = col.match(/.*_(price|qty)$/i);

                            const title = match ?
                                match[1].charAt(0).toUpperCase() + match[1].slice(1) :
                                col.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

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
                        scrollX: true,
                        scrollCollapse: true,
                        paging: true,
                        destroy: true,
                        // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                        dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',

                        order: [
                            [0, 'DESC']
                        ],
                        fixedColumns: {
                            leftColumns: 3
                        },
                        language: {
                            searchPlaceholder: 'Search...'
                        },
                        drawCallback: function() {
                            $('#yajra-datatables tbody tr').each(function() {
                                $(this).find('td').each(function(index) {
                                    if (index > 4) {
                                        var groupIndex = Math.floor((index - 5) /
                                        2);
                                        if (groupIndex % 2 === 0) {
                                            $(this).css('background-color',
                                                '#f5f5f5');
                                        } else {
                                            $(this).css('background-color',
                                                '#ffffff');
                                        }
                                    }
                                });
                            });
                        },
                        footerCallback: function(row, data, start, end, display) {
                            if (data && data.length > 0) {
                                $(".dataTables_scrollFoot").show();
                                var api = this.api();

                                api.columns().every(function(index) {
                                    var total = 0;
                                    if (index >= 3) {
                                        total = api
                                            .column(index, {
                                                search: 'applied'
                                            })
                                            .data()
                                            .reduce(function(a, b) {
                                                var x = parseFloat((a + '').replace(
                                                    /[^0-9.-]+/g, '')) || 0;
                                                var y = parseFloat((b + '').replace(
                                                    /[^0-9.-]+/g, '')) || 0;
                                                return x + y;
                                            }, 0);

                                        $(api.column(index).footer()).html(
                                            `${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`
                                            );
                                    } else if (index === 1) {
                                        $(api.column(index).footer()).html('Total Sales');
                                    } else {
                                        $(api.column(index).footer()).html('');
                                    }
                                });
                            } else {
                                $(".dataTables_scrollFoot").hide();
                            }
                        }
                    });

                    if (response.chartData && typeof response.chartData === 'object') {
                        if (response.chartData?.labels && response.chartData?.datasets) {
                            renderCombinedPriceQtyChart(response.chartData);
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON.message) {
                        // alert(xhr.responseJSON.message);
                    } else {
                        // alert('Something went wrong');
                    }
                }
            });
        }


        function renderCombinedPriceQtyChart(chartData) {
            const ctx = document.getElementById('productCombinedChart').getContext('2d');

            if (window.salesChartInstance) {
                window.salesChartInstance.destroy(); // Destroy previous instance
            }

            window.salesChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets.map((ds, index) => ({
                        ...ds,
                        borderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }))
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'nearest',
                        intersect: false
                    },
                    elements: {
                        point: {
                            radius: 3,
                            hitRadius: 10
                        }
                    },
                    plugins: {
                        tooltip: {
                            enabled: true,
                            filter: () => true,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw ?? '';
                                    return `${label}: ${value}`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        yPrice: {
                            type: 'linear',
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Price'
                            }
                        },
                        yQty: {
                            type: 'linear',
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Quantity'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
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
            if (picker) {
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
            let filter_product_category = $('#product_category_id').val();
            let filter_product_subcategory = $('#product_sub_category_id').val();
            let filter_product_variant = $('#product_variant_price_id').val();
            let filter_sales_date = $('input[name="filter_sales_date"]').val().replace(' - ', ' to ');

            let queryParams = $.param({
                filter_type: filter_type,
                display_format: display_format,
                filter_company: filter_company,
                filter_customer: filter_customer,
                filter_product_category: filter_product_category,
                filter_product_subcategory: filter_product_subcategory,
                filter_product_variant: filter_product_variant,
                filter_sales_date: filter_sales_date,
            });

            let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
            window.location.href = url;
        });
    </script>


    @include('utils.getCustomerSales')
    @include('utils.getCompanyByCustomerType')
    @include('utils.getProductCategory')
    @include('utils.getProductSubCategory')
    @include('utils.getProductVariantPrice')
@endpush
