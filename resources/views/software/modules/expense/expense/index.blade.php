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
                                <label class="form-label">Filter by Team Person</label>
                                <select id="team_person_ids" name="team_person_ids"
                                    class="form-control select2 select_filter" data-append="search_by_company">
                                    <option value="">Filter by Team Person</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Category</label>
                                <select id="expense_category_id" name="expense_category_id"
                                    class="form-control search_by_expense_category select2 select_filter"
                                    data-append="search_by_expense_category">
                                    <option value="">Filter by Category</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Sub Category</label>
                                <select id="expense_subcategory_id" name="expense_subcategory_id"
                                    class="form-control search_by_expense_subcategory select2 select_filter"
                                    data-append="search_by_expense_subcategory">
                                    <option value="">Filter by Sub Category</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="form-label">Filter by Date</label>
                            <input type="text" id="filter_by_date" name="filter_by_date" class="form-control date_filter"
                                placeholder="Select date range" autocomplete="off" />
                        </div>

                         <div class="col-md-2 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label for="status_filter" class="form-label">Filter by Status</label>
                                <select id="status_filter" name="status" class="form-select select2">
                                    <option value="all">All</option>
                                    <option value="Pending">Pending</option>
                                    <option value="pass">Approve</option>
                                    <option value="Reject">Reject</option>
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

        <!-- Status Modal -->
        <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="statusForm">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="id" id="statusRecordId">
                            <input type="hidden" name="update_status" id="statusToUpdate">
                            <input type="hidden" id="requestAmount" name="request_amount">

                            <div class="mb-3" id="passAmountField" style="display: none;">
                                <label for="pass_amount" class="form-label">Pass Amount</label>
                                <input type="number" class="form-control" name="pass_amount" id="pass_amount"
                                    placeholder="Enter amount" min="0" step="any"
                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');">
                            </div>


                            <div class="mb-3" id="rejectionReasonField" style="display: none;">
                                <label for="reason" class="form-label">Reason</label>
                                <textarea class="form-control" name="reason" id="reason" rows="3" placeholder="Enter reason"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                    @if ($errors->any())
                        <script>
                            @foreach ($errors->all() as $error)
                                toastr.error("{{ $error }}");
                            @endforeach
                        </script>
                    @endif
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
        let filterDateRange = [];

        const filterByInquiryDate = flatpickr("#filter_by_date", {
            mode: "range",
            dateFormat: "d-m-Y",
            onChange: function(selectedDates) {
                filterDateRange = selectedDates;

                // Update filterParams globally if you want, but here we rely on ajax.data callback for fresh data
                dtable.draw(); // redraw datatable on date range change
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
                        data.filter_company = $('select[name="company_id"] option:selected').val();
                        data.filter_expense_category = $(
                            'select[name="expense_category_id"] option:selected').val();
                        data.filter_expense_subcategory = $(
                            'select[name="expense_subcategory_id"] option:selected').val();
                        data.filter_team_person = $('select[name="team_person_ids"] option:selected')
                            .val();
                        if (filterDateRange.length > 0) {
                            if (filterDateRange[0]) {
                                data.from_date = moment(filterDateRange[0]).format(
                                    'YYYY-MM-DD');
                            }
                            if (filterDateRange[1]) {
                                data.to_date = moment(filterDateRange[1]).format(
                                    'YYYY-MM-DD');
                            }
                        } else {
                            data.from_date = null;
                            data.to_date = null;
                        }
                        data.status = $('#status_filter').val();

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
                $('#filter_by_date').val('');

                filterDateRange = [];
                dtable.draw();
            });

            $(document).on('click', '.update-status', function() {
                const id = $(this).data('id');
                const status = $(this).data('update_status');
                const requestAmount = $(this).data('request_amount');

                $('#statusRecordId').val(id);
                $('#statusToUpdate').val(status);
                $('#requestAmount').val(requestAmount);

                if (status === 'pass') {
                    $('#passAmountField').show();
                    $('#rejectionReasonField').hide();
                } else if (status === 'reject') {
                    $('#rejectionReasonField').show();
                    $('#passAmountField').hide();
                } else {
                    $('#passAmountField').hide();
                    $('#rejectionReasonField').hide();
                }

                $('#statusModal').modal('show');
            });

            $('#statusForm').submit(function(e) {
                e.preventDefault();

                const passAmount = parseFloat($('#pass_amount').val());
                const requestAmount = parseFloat($('#requestAmount').val());

                if (passAmount > requestAmount) {
                    toastr.error('Pass amount cannot be greater than the request amount.');
                    return;
                }

                const formData = new FormData(this);
                const updateUrl = "{{ route($modules['route'] . '.status-update') }}";

                $.ajax({
                    url: updateUrl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#statusModal').modal('hide');
                        $('#statusForm')[0].reset();
                        toastr.success('Status updated successfully');
                        dtable.ajax.reload();
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON.message || 'Something went wrong');
                    }
                });
            });
            // excle button
            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();

                let filters = {
                    search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"]').val(),
                    filter_expense_category: $('select[name="expense_category_id"]').val(),
                    filter_expense_subcategory_id: $('select[name="expense_subcategory_id"]').val(),
                    filter_team_person_id: $('select[name="team_person_ids"]').val(),
                    from_date: filterDateRange[0] ? moment(filterDateRange[0]).format('YYYY-MM-DD') :
                        '',
                    to_date: filterDateRange[1] ? moment(filterDateRange[1]).format('YYYY-MM-DD') : ''
                };

                const queryParams = $.param(filters);
                const url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });


            // Print button
            $('#print_btn').on('click', function(e) {
                e.preventDefault();
                let filters = {
                    search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"] option:selected').val(),
                    filter_expense_category: $('select[name="expense_category_id"]').val(),
                    filter_expense_subcategory_id: $('select[name="expense_subcategory_id"]').val(),
                    filter_team_person_id: $('select[name="team_person_ids"]').val(),
                    filter_status: $('select[name="status"]').val(),
                    from_date: filterDateRange[0] ? moment(filterDateRange[0]).format('YYYY-MM-DD') :
                        '',
                    to_date: filterDateRange[1] ? moment(filterDateRange[1]).format('YYYY-MM-DD') : ''
                };

                let queryParams = $.param(filters);
                let url = "{{ route($route . '.print') }}" + "?" + queryParams;
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
    @include('utils.getTeamPerson')
    @include('utils.getExpenseCategory')
    @include('utils.getExpenseSubCategory')
    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-restore-record')
    @include('software.inlcudes.script-update-status')
@endpush
