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

@section('title', 'Dashboard')

@section('page_leavel_style')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
@endsection

@push('push_style')
    <style>
        #map {
            height: 100vh;
            border-radius: 2px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
        }


        .nav-pills .nav-link.active {
            background-color: #5c6bc0;
            color: white;
        }

        canvas {
            max-height: 400px;
        }

        .marker-cluster-small {

            background-color: orange !important;
            color: white !important;
            border: 2px solid #FF9900;
            /* Border slightly darker orange */

        }

        .marker-cluster-small div {

            background-color: orange !important;

        }
    </style>
@endpush
@php
    $columns = [
        ['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
        /*
        [
            'data' => 'DT_RowIndex',
            'name' => 'DT_RowIndex',
            'title' => 'Sr No.',
            'orderable' => false,
            'searchable' => false,
        ],
        */
        // ['data' => 'sales_order_no', 'name' => 'sales_order_no', 'title' => 'OA No'],
        ['data' => 'sales_order_date', 'name' => 'sales_order_date', 'title' => 'Date'],
        ['data' => 'party_code', 'name' => 'party_code', 'title' => 'Item Code', 'className' => 'w-30 text-wrap'],
        // ['data' => 'item_code', 'name' => 'item_code', 'title' => 'Item Code', 'className' => 'w-25 text-wrap'],
        // ['data' => 'item_name', 'name' => 'item_name', 'title' => 'Item Name', 'className' => 'w-100 text-wrap'],
        ['data' => 'item', 'name' => 'item_name', 'title' => 'Item', 'className' => 'w-100'],
        // ['data' => 'item_name', 'name' => 'item_name', 'title' => 'Item Name', 'className' => 'w-100 text-wrap'],
        ['data' => 'master_quantity', 'name' => 'master_quantity', 'title' => 'Pending Qty'],
        ['data' => 'fg_stock_qty', 'name' => 'fg_stock_qty', 'title' => 'FG Stock (Qty)'],
        ['data' => 'fg_stock_kg', 'name' => 'fg_stock_kg', 'title' => 'FG Stock (KG)'],
        ['data' => 'packing_slip_date', 'name' => 'packing_slip_date', 'title' => 'Del. Date'],
    ];
@endphp


@section('content')
    <div class="row">
        <div class="mb-0">
            <div class="card-header py-2 px-4 d-flex flex-wrap justify-content-between align-items-center">
                <h5 class="card-title mb-2 mb-md-0">Statistics</h5>
                <div class="d-flex flex-wrap gap-3">
                    <div class="d-flex flex-wrap gap-3 align-items-end flex-grow-1">
                        @if (!$company_id)
                            <div class="form-group flex-grow-1" style="min-width: 200px;">
                                <label class="form-label">Filter by Company</label>
                                <select id="company_id"
                                    class="form-control select2 search_by_company @error('company_id') is-invalid @enderror"
                                    name="company_id"
                                    data-selectedCompanyId="{{ old('company_id') ?? ($edit->company_id ?? '') }}">
                                </select>
                            </div>
                        @endif
                        <div class="form-group mb-0 flex-grow-1" style="min-width: 250px;">
                            <label class="form-label">Filter by Date Range</label>
                            <input type="text" name="filter_by_date_range"
                                class="form-control my_daterangepicker table_filter" value=""
                                placeholder="Filter by date range">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class=" h-100">
                <div class="mt-3" id="inquiryStatistics">
                    <div class="p-4 text-center">
                        <p class="mb-0 fs-5">No data available. Please apply filters to see statistics.</p>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($modules) && isset($modules['sales_order_view']) && $modules['sales_order_view'])
            <div class="col-md-12 mb-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0 text-center">Pending Sales Order</h4>
                        <div class="mx-auto" style="width: 200px; height: 2px; background-color: #7367f0; margin-top: 2px;">
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label" for="filter_oa_no">OA No</label>
                                <input type="text" id="filter_oa_no" name="filter_oa_no" class="form-control table_filter"
                                    placeholder="Filter by OA No">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="filter_item_code">Item Code</label>
                                <input type="text" id="filter_item_code" name="filter_item_code"
                                    class="form-control table_filter" placeholder="Filter by Item Code">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="filter_item_name">Item Name</label>
                                <input type="text" id="filter_item_name" name="filter_item_name"
                                    class="form-control table_filter" placeholder="Filter by Item Name">
                            </div>
                            {{-- @if (isset($modules) && isset($modules['customer_view']) && $modules['customer_view']) --}}
                            <div class="col-md-3">
                                <label class="form-label" for="filter_customer_number">Party Code</label>
                                <input type="text" id="filter_customer_number" name="filter_customer_number"
                                    class="form-control table_filter" placeholder="Filter by Customer Number">
                            </div>
                            {{-- @endif --}}
                        </div>
                    </div>
                    <div class="card-datatable text-nowrap">
                        <div class="card-datatable table-responsive">
                            <table id="yajra-datatables" class="dt-responsive table table-hover">
                                @if (isset($columns))
                                    <thead>
                                        <tr>
                                            @foreach ($columns as $item)
                                                <th class="{{ $item['className'] ?? '' }}">
                                                    {{ ucfirst($item['td_label'] ?? $item['name']) ?? '' }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Minimum Stock Alert Section -->

        @if (isset($modules) && isset($modules['product_stock_history_view']) && $modules['product_stock_history_view'])
            <div class="col-md-12 mb-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0 text-center">Finished Goods Minimum Stock Alert</h4>
                        <div class="mx-auto" style="width: 200px; height: 2px; background-color: #ea5455; margin-top: 2px;">
                        </div>
                    </div>
                    <div class="card-datatable text-nowrap">
                        <div id="minimumStockProducts">
                            <div class="p-4 text-center">
                                <p class="mb-0 fs-5">Loading minimum stock data...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- <div class="col-lg-8 col-md-8 mb-4">
            <div class="card-section">
                <div class="card shadow-sm p-4">
                    <!-- Title and Nav on the Same Line -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <div>
                            <h5 class="mb-0">Earning Reports</h5>
                            <small class="text-muted">Yearly Earnings Overview</small>
                        </div>
                        <ul class="nav nav-pills" id="reportTabs">
                            <li class="nav-item">
                                <button class="nav-link active" data-type="quotations">Quotation</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-type="orders">Order</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-type="dispatches">Dispatch</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-type="purchase_orders">Purchase Order</button>
                            </li>
                        </ul>
                    </div>

                    <!-- Chart Canvas -->
                    <canvas id="earningChart" width="600" height="300"></canvas>
                </div>
            </div>
        </div>

        @if (isset($modules) && isset($modules['customer_view']) && $modules['customer_view'])
        <div class="col-lg-4 col-md-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Customer Location</h5>
                    <h6 class="card-title mb-0"><b>Total Customer:</b> <span class="total_customer"></span></h6>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 420px;"></div>
                </div>
            </div>
        </div>
        @endif --}}
    </div>


@endsection

@section('page_leavel_script')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>

@endsection

@push('page_scripts')
    @php
        $dateRangeArray = request('date_range') ? explode(' to ', request('date_range')) : null;
    @endphp

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function () {
            var currentAjaxRequest = null;
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                dom: '<"table-responsive"t><"datatable-footer d-flex justify-content-between align-items-center flex-wrap px-3 py-2"l i p>',
                order: [
                    [0, 'ASC']
                ],
                ajax: function (data, callback, settings) {
                    if (currentAjaxRequest) {
                        currentAjaxRequest.abort();
                    }
                    let postData = {
                        ...data,
                        filter_oa_no: $('input[name="filter_oa_no"]').val(),
                        filter_item_code: $('input[name="filter_item_code"]').val(),
                        filter_item_name: $('input[name="filter_item_name"]').val(),
                        filter_customer_number: $('input[name="filter_customer_number"]').val(),
                    };
                    currentAjaxRequest = $.ajax({
                        url: "{{ route('dashboard.order_stock') }}",
                        type: "GET",
                        data: postData,
                        beforeSend: function (request) {
                            request.setRequestHeader("X-CSRF-TOKEN", $(
                                'meta[name="csrf-token"]').attr('content'));
                        },
                        success: function (response) {
                            callback(response);
                        },
                        error: function (xhr, error, code) {
                            console.error("DataTable AJAX Error:", xhr, error, code);
                            console.log("Response:", xhr.responseText);
                        },
                        complete: function () {
                            currentAjaxRequest = null;
                        }
                    })
                },
                lengthMenu: [
                    [5, 10, 25, 50, 75, 100, 250, 500],
                    [5, 10, 25, 50, 75, 100, 250, 500]
                ],
                pageLength: 100,
                columns: {!! json_encode($columns ?? []) !!},
                rowCallback: function (row, data, index) {

                    let value = data.packing_slip_date;
                    if (!value || value == '-') return;

                    let today = new Date();
                    let psDate = new Date(value.split('-').reverse().join('-'));
                    let diffDays = (psDate - today) / (1000 * 60 * 60 * 24);
                    if (diffDays >= 0 && diffDays <= 7) {
                        $(row).css({
                            'background-color': '#d9e9ff',
                            'color': 'blue',
                            'font-weight': '600'
                        });
                    }
                    if (diffDays < 0) {
                        $(row).css({
                            'background-color': '#ffd6d6',
                            'color': 'red',
                            'font-weight': '600'
                        });
                    }
                },

                language: {
                    searchPlaceholder: 'Search...'
                }
            });

            // Reload table when filter inputs change
            $(document).on('keyup', '.table_filter', function () {
                dtable.ajax.reload();
            });
        });
    </script>

    <script>
        $(document).on('click', '.inquiry-status-link', function () {
            var statusId = $(this).data('selectedinquirystatusid');
            var selected_inquiry_date = $('input[name="filter_by_date_range"]').val();
            localStorage.setItem('selectedInquiryStatusId', statusId);
            localStorage.setItem('selected_inquiry_date', selected_inquiry_date);
        });

        function fetchChartData() {
            fetch("{{ route('dashboard.chart-data') }}")
                .then(res => res.json())
                .then(data => {
                    console.log('✅ Full Chart Data:', data);

                    // Log individual datasets
                    console.log('📅 Labels:', data.labels);
                    console.log('📊 Quotations:', data.datasets.quotations);
                    console.log('🛒 order_totals:', data.datasets.order_totals);
                    console.log('🚚 Dispatches:', data.datasets.dispatches);
                    console.log('📦 Purchase Orders:', data.datasets.purchase_orders);
                    console.log('💰 Sales:', data.datasets.sales);
                    console.log('📈 Profit:', data.datasets.profit);
                    console.log('💸 Income:', data.datasets.income);

                    chartData = data;
                    renderChart('quotations'); // Default chart
                })
                .catch(err => console.error('❌ Fetch error:', err));
        }
    </script>

    <script>
        /*
                                                                $(function() {
                                                                    // var start = moment(); // Today
                                                                    var start = moment().startOf('month'); // Today
                                                                    var end = moment().endOf('month'); // Today

                                                                    $('#selected_date').daterangepicker({
                                                                        startDate: start,
                                                                        endDate: end,
                                                                        autoUpdateInput: true, // Automatically fills the input field
                                                                        locale: {
                                                                            format: 'DD/MM/YYYY'
                                                                        },
                                                                        ranges: {
                                                                            'Today': [moment()],
                                                                            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                                                                            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                                                                            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                                                                            'This Month': [moment().startOf('month'), moment().endOf('month')],
                                                                            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                                                                                'month').endOf('month')]
                                                                        }
                                                                    }, function(start, end) {
                                                                        $('#selected_date').val(start.format('DD/MM/YYYY') + ' to ' + end.format('DD/MM/YYYY'));
                                                                        $('#selectedStartDate').val(start.format('YYYY-MM-DD'));
                                                                        $('#selectedEndDate').val(end.format('YYYY-MM-DD'));

                                                                        var selectedStartDate = start.format('YYYY-MM-DD');
                                                                        var selectedEndDate = end.format('YYYY-MM-DD');

                                                                    });

                                                                    // Set default value in input
                                                                    $('#selected_date').val(start.format('DD/MM/YYYY') + ' to ' + end.format('DD/MM/YYYY'));
                                                                    $('#selectedStartDate').val(start.format('YYYY-MM-DD'));
                                                                    $('#selectedEndDate').val(end.format('YYYY-MM-DD'));
                                                                });
                                                            */

        $(document).ready(function () {
            // Set default value in input
            var picker = $('.my_daterangepicker').data('daterangepicker');
            picker.setStartDate(moment().startOf('month'));
            picker.setEndDate(moment().endOf('month'));
            picker.callback(moment().startOf('month'), moment().endOf('month'), 'This Month');

            // Get login type from backend blade variable (set in your controller/view)
            var loginType = @json($loginType); // e.g. 'admin' or 'team'

            if (loginType === 'admin') {
                // Initialize Select2 for company filter only for admin
                $('#company_id').select2({
                    placeholder: 'Select Company',
                    allowClear: true
                });
            } else {
                // For team or others: disable or hide company filter (optional)
                $('#company_id').prop('disabled', true);
                // or $('#company_id').closest('.form-group').hide();
            }


            function fetchDashboardStatistics() {
                let filterPayload = {
                    filter_by_daterange: $('input[name="filter_by_date_range"]').val().replace(' - ', ' to '),
                    start_date: $('#selectedStartDate').val(),
                    end_date: $('#selectedEndDate').val(),
                };
                if ($('meta[name="company_id"]').attr('value')) {
                    filterPayload = {
                        ...filterPayload,
                        company_id: $('meta[name="company_id"]').attr('value')
                    }
                } else if ($('select[id="company_id"]').find('option:selected').val()) {
                    filterPayload = {
                        ...filterPayload,
                        company_id: $('select[id="company_id"]').find('option:selected').val()
                    }
                }

                $.ajax({
                    url: "{{ route('software.dashboard') }}",
                    method: "GET",
                    data: filterPayload,
                    success: function (response) {
                        if (response.status == true && response?.data?.inquiryStatistics) {
                            $('#inquiryStatistics').html(response?.data?.inquiryStatistics);
                        }

                        // Update minimum stock products
                        if (response.status == true && response?.data?.minimumStockProducts) {
                            updateMinimumStockProducts(response.data.minimumStockProducts);
                        }

                    },
                    error: function (xhr) {
                        console.error("Error: ", xhr.responseText);
                    }
                });
            }

            function loadMinimumStockProducts() {
                let filterPayload = {
                    filter_by_daterange: $('input[name="filter_by_date_range"]').val().replace(' - ', ' to '),
                    start_date: $('#selectedStartDate').val(),
                    end_date: $('#selectedEndDate').val(),
                };
                if ($('meta[name="company_id"]').attr('value')) {
                    filterPayload = {
                        ...filterPayload,
                        company_id: $('meta[name="company_id"]').attr('value')
                    }
                } else if ($('select[id="company_id"]').find('option:selected').val()) {
                    filterPayload = {
                        ...filterPayload,
                        company_id: $('select[id="company_id"]').find('option:selected').val()
                    }
                }

                $.ajax({
                    url: "{{ route('software.dashboard') }}",
                    method: "GET",
                    data: filterPayload,
                    success: function (response) {
                        if (response.status == true && response?.data?.minimumStockProducts) {
                            updateMinimumStockProducts(response.data.minimumStockProducts);
                        }
                    },
                    error: function (xhr) {
                        console.error("Error loading minimum stock: ", xhr.responseText);
                    }
                });
            }

            function updateMinimumStockProducts(products) {
                let html = '';
                if (products && products.length > 0) {
                    html = '<div class="card-datatable table-responsive"><table class="dt-responsive table table-hover">';
                    html += '<thead class="table-light"><tr>';
                    html += '<th>Product Name</th>';
                    html += '<th>Part No</th>';
                    html += '<th>Warehouse</th>';
                    html += '<th class="text-center">Current Stock</th>';
                    html += '<th class="text-center">Min Stock</th>';
                    html += '<th class="text-center">Shortage</th>';
                    html += '</tr></thead><tbody>';

                    products.forEach(function (product) {
                        html += '<tr>';
                        html += '<td><strong>' + (product.product_name || '-') + '</strong></td>';
                        html += '<td>' + (product.part_no || '-') + '</td>';
                        html += '<td>' + (product.warehouse || '-') + '</td>';
                        html += '<td class="text-center"><span class="badge bg-danger">' + parseFloat(product.current_stock || 0).toFixed(2) + '</span></td>';
                        html += '<td class="text-center">' + parseFloat(product.min_stock || 0).toFixed(2) + '</td>';
                        html += '<td class="text-center"><span class="badge bg-warning">' + parseFloat(product.shortage || 0).toFixed(2) + '</span></td>';
                        html += '</tr>';
                    });

                    html += '</tbody></table></div>';
                } else {
                    html = '<div class="p-4 text-center"><p class="mb-0 fs-5 text-success"><i class="ti ti-check-circle"></i> All products are above minimum stock level.</p></div>';
                }
                $('#minimumStockProducts').html(html);
            }

            $('#company_id').on('change', function () {
                if (loginType === 'admin') {
                    fetchDashboardStatistics();
                }
            });

            $('#refresh_dashboard').on('click', function () {
                if (loginType === 'admin') {
                    $('#company_id').val(null).trigger('change');
                }
                $('#date_range').val('');
                fetchDashboardStatistics();
            });

            // Listen for date change
            $('.my_daterangepicker').on('apply.daterangepicker', function (ev, picker) {
                fetchDashboardStatistics();
            });

            fetchDashboardStatistics();

            if (document.getElementById('map')) {
                var map = L.map('map').setView([22.2734, 70.7513], 13); // Rajkot center

                var greenIcon = new L.Icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                    attribution: '© ocean INfotech'
                }).addTo(map);

                var markers = L.markerClusterGroup();

                filter_by_daterange = $('input[name="filter_by_date_range"]').val().replace(' - ', ' to ');
                let url = new URL(`{{ route('dashboard.getLocations') }}`);
                url.searchParams.append('filter_by_daterange', filter_by_daterange);

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        console.log("Customer location data:", data);
                        let locationsRes = data.locations;
                        $(".total_customer").text(data.locCountCustomers + "/" + data.totalCustomers);
                        locationsRes.forEach(function (location) {
                            if (location.latitude && location.longitude) {
                                var lat = parseFloat(location.latitude);
                                var lng = parseFloat(location.longitude);

                                if (!isNaN(lat) && !isNaN(lng)) {
                                    // Use the green icon here:
                                    var marker = L.marker([lat, lng], {
                                        icon: greenIcon
                                    });
                                    marker.bindPopup(
                                        `<b>${location.customer_name}</b><br><b>${location.contact_person_name}</b><br><b>${location.mobile_number}</b>`
                                    );
                                    markers.addLayer(marker);
                                } else {
                                    console.warn("Invalid lat/lng for:", location);
                                }
                            }
                        });

                        map.addLayer(markers);

                        if (markers.getLayers().length > 0) {
                            map.fitBounds(markers.getBounds());
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching customer locations:", error);
                    });
            }

        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const earningChartEl = document.getElementById('earningChart');
            if (!earningChartEl) return;

            const ctx = earningChartEl.getContext('2d');
            let chart;
            let chartData = {};

            function fetchChartData() {
                fetch("{{ route('dashboard.chart-data') }}")
                    .then(res => res.json())
                    .then(data => {
                        console.log('Chart Data:', data);
                        chartData = data;
                        renderChart('quotations');
                    })
                    .catch(err => console.error('Fetch error:', err));
            }

            function renderChart(type) {
                if (!chartData.datasets) return;

                if (chart) chart.destroy();

                const datasetMap = {
                    quotations: {
                        type: 'bar',
                        label: 'Quotations',
                        data: chartData.datasets.quotations,
                        backgroundColor: '#6c757d'
                    },
                    orders: {
                        type: 'bar',
                        label: 'Orders',
                        data: chartData.datasets.order_totals,
                        backgroundColor: '#1fd5eb'
                    },
                    dispatches: {
                        type: 'bar',
                        label: 'Dispatches',
                        data: chartData.datasets.dispatches,
                        borderColor: '#66bb6a',
                        backgroundColor: '#60c8d1bf',
                        fill: true,
                        tension: 0.3
                    },
                    purchase_orders: {
                        type: 'bar',
                        label: 'Purchase Orders',
                        data: chartData.datasets.purchase_orders,
                        backgroundColor: '#cbaacb'
                    }
                };

                const ds = datasetMap[type];
                if (!ds) return;

                chart = new Chart(ctx, {
                    type: ds.type,
                    data: {
                        labels: chartData.labels,
                        datasets: [ds]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            document.querySelectorAll('#reportTabs button').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#reportTabs button').forEach(el => el.classList
                        .remove('active'));
                    btn.classList.add('active');
                    renderChart(btn.dataset.type);
                });
            });

            fetchChartData();
        });
    </script>



    @include('utils.getCompany')
@endpush