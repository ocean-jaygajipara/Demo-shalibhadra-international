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
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
            // 'show_filter_btn' => true,
            // 'show_export_btn' => true,
            // 'show_excal_btn' => true,
            // 'show_print_btn' => true,
        ])
    </div>

    <div class="row my-3">
        <div class="col-md-12 mb-5">
            <div class="card">
                <div class="card-body">
                    <div class="row">
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
                                    class="form-control select2 select_filter search_by_customer_type"
                                    data-append="search_by_customer_type">
                                    <option value="">Filter by Customer Type</option>
                                </select>
                            </div>
                        </div>



                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Customer</label>
                                <select id="customer_id" name="customer_id"
                                    class="form-control select2 select_filter search_by_customer"
                                    data-append="search_by_customer">
                                    <option value="">Select Customer</option>
                                </select>
                            </div>
                        </div>






                        <div class="col-md-2 mb-2">
                            <label class="form-label">Filter By Transaction Date</label>
                            <input type="text" name="followup_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div>
                        <input type="hidden" id="customer_id_hidden" value="">
                        <div class="col-md-2 mb-2 d-flex align-items-end gap-3">
                            <button type="button" class="btn btn-primary" id="view_ledger_btn">
                                <i class="fa fa-plus me-1"></i> View Ledger
                            </button>
                            {{-- <div class="col-md-2 mb-2 d-flex align-items-end"> --}}
                            <button type="button" class="btn btn-warning ms-2" id="print_ledger_btn">
                                <i class="fa fa-print me-1"></i> Print
                            </button>
                            {{-- </div> --}}



                        </div>
                    </div>
                </div>
            </div>
            <!-- Ledger Table -->
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Sr No.</th>
                            <th>Transaction Date</th>
                            <th>Description</th>
                            <th class="text-end">Debit (₹)</th>
                            <th class="text-end">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="ledger_body">
                        <!-- Data will be appended here -->
                    </tbody>
                    <tfoot id="ledger_footer">
                        <!-- Totals will be shown here -->
                    </tfoot>
                </table>
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

    @endsection

    @section('page_leavel_script')
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @endsection

    @push('page_scripts')
        <script type="text/javascript">
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // $(function() {

            //     var picker = $('.my_daterangepicker').data('daterangepicker');
            //     if (picker) {
            //         picker.maxDate = false;
            //         var today = moment();
            //         var financialYearStart = moment().month() < 3 ?
            //             moment().subtract(1, 'year').startOf('year').month(3).startOf('month') // April 1st of last year
            //             :
            //             moment().startOf('year').month(3).startOf('month'); // April 1st of current year
            //         picker.setStartDate(financialYearStart);
            //         picker.setEndDate(today);
            //         picker.callback(financialYearStart, today, 'Financial Year Range');
            //     }
            // });
            $(function() {
                var $pickerElement = $('.my_daterangepicker');

                // Initialize daterangepicker with the exact format PHP expects
                $pickerElement.daterangepicker({
                    autoUpdateInput: true,
                    locale: {
                        format: 'DD/MM/YYYY' // matches PHP's 'd/m/Y'
                    }
                });

                var picker = $pickerElement.data('daterangepicker');
                if (picker) {
                    picker.maxDate = false;

                    var today = moment();
                    var financialYearStart = moment().month() < 3 ?
                        moment().subtract(1, 'year').startOf('year').month(3).startOf('month') :
                        moment().startOf('year').month(3).startOf('month');

                    // Set default only if no value exists
                    if (!$pickerElement.val()) {
                        picker.setStartDate(financialYearStart);
                        picker.setEndDate(today);
                        $pickerElement.val(
                            financialYearStart.format('DD/MM/YYYY') +
                            ' to ' +
                            today.format('DD/MM/YYYY')
                        );
                    }
                }
            });

            var dtable = null;
            var followup_status = null;
            var followup_selected_status = null;

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

                        },
                    },
                    columns: {!! isset($columns) ? json_encode($columns) : [] !!},
                    language: {
                        searchPlaceholder: 'Search...',
                    }
                });
            });

            $(document).on('change', '.select_filter, .table_filter', function(event) {
                event.preventDefault();

                if (dtable) {
                    dtable.draw();
                }
            });

            $('.search').keyup(function() {
                dtable.draw();
            });

            $(document).ready(function() {

                $("#cilory_filter").click(function() {
                    $('.select_filter').val(null).trigger('change');
                    $('.search').val('');
                    $('.my_daterangepicker').val('').trigger('change');
                    dtable.draw();
                });
            });

            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();

                let queryParams = $.param({
                    search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"]').val(),

                });

                let url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });



            $('#print_btn').on('click', function(e) {
                e.preventDefault();

                let queryParams = $.param({
                    search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"]').val(),

                });

                let url = "{{ route($route . '.print') }}" + "?" + queryParams;
                window.open(url, '_blank');
            });
        </script>

        <script>
            $(document).ready(function() {
                $('#view_ledger_btn').on('click', function(e) {
                    e.preventDefault();

                    let company_id = $('#company_id').val();
                    let customer_type_id = $('#customer_type_id').val();
                    let customer_id = $('#customer_id').val();
                    let followup_date = $('input[name="followup_date"]').val().replace(' - ', ' to ');

                    let metaCompanyId = $('meta[name="company_id"]').attr('value');
                    if (!company_id && metaCompanyId) {
                        company_id = metaCompanyId;
                    }

                    if (!company_id || !customer_type_id || !customer_id || !followup_date) {
                        toastr.error("Please fill all filters before viewing the ledger.");
                        return;
                    }

                    $.ajax({
                        url: "{{ route('account-ledger.getReport') }}",
                        type: "POST",
                        dataType: 'json',
                        data: {
                            company_id,
                            customer_type_id,
                            customer_id,
                            followup_date,
                            _token: '{{ csrf_token() }}'
                        },
                        beforeSend: function() {
                            $('#ledger_body').html(
                                '<tr><td colspan="5" class="text-center">Loading...</td></tr>');
                            $('#ledger_footer').html('');
                        },
                        success: function(response) {
                            let html = '',
                                sr = 1;

                            if (response.ledger.length === 0) {
                                html =
                                    `<tr><td colspan="5" class="text-center">No transactions found.</td></tr>`;
                                $('#ledger_footer').html('');
                            } else {
                                response.ledger.forEach(item => {
                                    html += `
                            <tr>
                                <td>${sr++}</td>
                                <td>${item.date}</td>
                                <td>${item.description}</td>
                                <td class="text-end">₹${parseFloat(item.debit).toFixed(2)}</td>
                                <td class="text-end">₹${parseFloat(item.credit).toFixed(2)}</td>
                            </tr>`;
                                });

                                const footer = `
                        <tr class="table-info">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold">₹${parseFloat(response.total_debit).toFixed(2)}</td>
                            <td class="text-end fw-bold">₹${parseFloat(response.total_credit).toFixed(2)}</td>
                        </tr>
                        <tr class="table-warning">
                            <td colspan="3" class="text-end fw-bold">Closing Balance</td>
                            <td colspan="2" class="text-end fw-bold">₹${parseFloat(response.closing_balance).toFixed(2)}</td>
                        </tr>`;
                                $('#ledger_footer').html(footer);
                            }

                            $('#ledger_body').html(html);
                        },
                        error: function(xhr) {
                            alert("Something went wrong while loading ledger data.");
                            console.error(xhr.responseText);
                            $('#ledger_body').html('');
                            $('#ledger_footer').html('');
                        }
                    });
                });
            });
        </script>


        <script>
            document.getElementById("print_ledger_btn").addEventListener("click", function() {
                const company_id = document.getElementById("company_id")?.value;
                const customer_type_id = document.getElementById("customer_type_id")?.value;
                const customer_id = document.getElementById("customer_id")?.value;
                const followup_date = document.querySelector('input[name="followup_date"]')?.value;

                const params = new URLSearchParams();

                if (company_id) params.append("company_id", company_id);
                if (customer_type_id) params.append("customer_type_id", customer_type_id);
                if (customer_id) params.append("customer_id", customer_id);
                if (followup_date) params.append("followup_date", followup_date);

                const url = "{{ route('account-ledger.print') }}?" + params.toString();
                window.open(url, "_blank");
            });
        </script>




        @if (!$company_id)
            @include('utils.getCompany')
        @endif
        @if (!$parent_type_id)
            @include('utils.getTeams')
        @endif
        @include('utils.getCustomer')

        @include('utils.getCompanyByCustomerType')
        @include('utils.getFollowupStatus')
        @include('software.inlcudes.script-delete-record')
        @include('software.inlcudes.script-update-status')
        @include('software.inlcudes.script-restore-record')
    @endpush
