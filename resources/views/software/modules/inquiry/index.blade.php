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
    $inquiryLimitReached = isset($inquiryLimitReached) ? $inquiryLimitReached : null;

    /**
     * Jiyare Permission ma all na rights hoy tiyare badho data show thase as team person.
     */
    if (isset($modules['all_data_permission']) && $modules['all_data_permission']) {
        $loginUserId = null;
    }
@endphp


@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <style>
        .duplicate-row-highlight {
            background-color: #fff3cd !important;
        }
        .duplicate-row-highlight td {
            background-color: #fff3cd !important;
        }
    </style>
@endsection

@section('content')
    {{-- <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
            'route' => $route,
            'show_add_btn' => isset($modules['add_permission'])
                ? $modules['add_permission'] && !$inquiryLimitReached
                : false,
            'show_filter_btn' => true,
            'show_assign_to_team_btn' => isset($modules['add_permission']) ? $modules['add_permission'] : false,
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
                'show_assign_to_team_btn' => isset($modules['add_permission'])
                    ? $modules['add_permission']
                    : false,
                'show_bulk_delete_btn' => isset($modules['delete_permission'])
                    ? $modules['delete_permission']
                    : false,
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
        <div class="col-md-12 mb-5" id="filter_section" style="display: none;">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Customer Name and Mobile No.</label>
                            <input type="search" class="form-control search" name="search" placeholder="search..."
                                autofocus autocomplete="off">
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
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Inquiry Status</label>
                                <select id="inquiry_status_id" name="inquiry_status_id"
                                    class="form-control search_by_inquiry_status select2 select_filter"
                                    data-append="search_by_inquiry_status">
                                    <option value="">Filter by Select Inquiry Status</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Followup Status</label>
                                <select id="followup_status_id" name="followup_status_id"
                                    class="form-control search_by_followup_status select2 select_filter"
                                    data-append="search_by_followup_status">
                                    <option value="">Filter by Select Followup Status</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">Filter by Source of Inquiry</label>
                                <select id="source_of_inquiry_id" name="source_of_inquiry_id"
                                    class="form-control search_by_source_of_inquiry select2 select_filter"
                                    data-append="search_by_source_of_inquiry">
                                    <option value="">Filter by Select Source of Inquiry</option>
                                </select>
                            </div>
                        </div>
                        @if (!$parent_type_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Created By</label>
                                    <select id="inquiry_created_by" name="inquiry_created_by"
                                        class="form-control search_by_created_by select2 select_filter"
                                        data-append="search_by_created_by"
                                        data-selectedinquirycreatedby="{{ $loginUserId ? $loginUserId : '' }}">
                                        <option value="">Filter by Select Created By</option>
                                    </select>
                                </div>
                            </div>
                        @else
                            {{-- {{ dd(!$company_id, $modules, !$parent_type_id, $loginUserId) }} --}}
                            <input type="hidden" class="form-control search_by_created_by" name="inquiry_created_by"
                                value="{{ $loginUserId }}" />
                        @endif

                        @if (!$parent_type_id)
                            <div class="col-md-3 mb-2 col-sm-12">
                                <div class="form-group">
                                    <label class="form-label">Filter by Assigned To</label>
                                    <select id="inquiry_assigned_to" name="inquiry_assigned_to"
                                        class="form-control search_by_assigned_to select2 select_filter"
                                        data-append="search_by_assigned_to"
                                        data-selectedinquiryassignedto="{{ $loginUserId ? $loginUserId : '' }}">
                                        <option value="">Filter by Select Assigned To</option>
                                    </select>
                                </div>
                            </div>
                        @else
                            {{-- {{ dd(!$company_id, $modules, !$parent_type_id, $loginUserId) }} --}}
                            <input type="hidden" class="form-control inquiry_assigned_to" name="inquiry_created_by"
                                value="{{ $loginUserId }}" />
                        @endif
                        {{-- <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Inquiry Date</label>
                            <input type="text" id="filter_by_inquiry_date" name="filter_by_inquiry_date" class="form-control date_filter" placeholder="Select inquiry date range" autocomplete="off" />
                            <input type="text" name="selected_date" id="selected_date" class="form-control table_filter"
                                value="" placeholder="Filter by date range">
                            <input type="hidden" id="selectedStartDate" name="selectedStartDate">
                            <input type="hidden" id="selectedEndDate" name="selectedEndDate">
                        </div> --}}
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Inquiry Date</label>
                            <input type="text" name="selected_date" class="form-control my_daterangepicker table_filter"
                                value="" placeholder="Filter by date range">
                        </div>
                        {{-- <div class="col-md-3 mb-2">
                            <label class="form-label">Filter by Followup Date</label>
                            <input type="text" id="followup_date_display" class="form-control search date_filter"
                                value="{{ old('followup_date') ? date('d-m-Y', strtotime(old('followup_date'))) : '' }}"
                                placeholder="DD-MM-YYYY" readonly autofocus />

                            <input type="hidden" name="followup_date" id="followup_date"
                                value="{{ old('followup_date') ? date('Y-m-d', strtotime(old('followup_date'))) : '' }}" />

                        </div> --}}
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Filter by Country</label>
                                <select id="country" name="country_id"
                                    class="form-control search_by_country select2 select_filter"
                                    data-filterByStatus="active" data-append="search_by_country">
                                    <option value="">Filter by Country</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-12 mb-2">
                            <div class="form-group">
                                <label class="form-label">Filter by State</label>
                                <select id="state" name="state_id"
                                    class="form-control search_by_state select2 select_filter"
                                    data-append="search_by_state">
                                    <option value="">Filter by State</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end mb-2">
                            <div class="flex-grow-1 me-2">
                                <label class="form-label">Filter by City</label>
                                <select id="city_id" name="city_id"
                                    class="form-control search_by_city select2 select_filter"
                                    data-append="search_by_city">
                                    <option value="">Filter by City</option>
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

    <script>
        const inputDateFormat = 'DD-MM-YYYY h:mm A';
    </script>
    @include('software._utils.inquiryFollowupModel')

    <div class="modal fade" id="assign_to_team_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Assign Inquiry to Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form name="AssignToTeamForm" id="AssignToTeamForm" method="POST">
                        @csrf
                        <input type="hidden" name="inquiry_ids" id="inquiry_ids">
                        @if (!$company_id)
                            <div class="col-mb-2 mb-2">
                                <div class="form-group">
                                    <label class="form-label">Select Company</label>
                                    <select id="form_company_id" name="company_id"
                                        class="form-control search_by_company select2"
                                        data-append="search_by_assigned_to">
                                        <option value="">Select Company</option>
                                    </select>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="company_id" id="company_id" value="{{ $company_id }}">
                        @endif
                        <div class="col-mb-2">
                            <div class="form-group">
                                <label class="form-label">Team Person</label>
                                <select id="filter_assigned_to" name="inquiry_assigned_to[]"
                                    class="form-control search_by_assigned_to select2" multiple
                                    data-append="search_by_assigned_to">
                                    <option value="">Select Team Person</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="assign_to_team_submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Duplicate Inquiries Modal -->
    <div class="modal fade" id="duplicate_inquiries_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inquiries for Customer (Mobile: <span id="dup_modal_mobile"></span>)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="duplicate_inquiries_table">
                            <thead>
                                <tr>
                                    <th>Inquiry No</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Followup Status</th>
                                    <th>Source</th>
                                    <th>Assigned To</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Send General WhatsApp / History Modal (Modern UI) -->
    <div class="modal fade" id="send_whatsapp_general_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <!-- Header with WhatsApp Gradient -->
                <div class="modal-header px-4 py-3 border-0 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0ba360 0%, #128C7E 100%);">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle me-3" style="width: 42px; height: 42px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px);">
                            <i class="fa-brands fa-whatsapp text-white fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title text-white fw-bold mb-0">WhatsApp Message History</h5>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm p-0 text-white shadow-none border-0" data-bs-dismiss="modal" aria-label="Close" style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; margin: 0 !important; position: static !important; transform: none !important;" onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <i class="fa-solid fa-xmark fs-5"></i>
                    </button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <input type="hidden" id="wa_inquiry_id" value="">
                    
                    <!-- Modern Info Banner -->
                    <div class="p-3 mb-4 rounded-3 border" style="background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%); border-color: #e2e8f0 !important;">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3 col-6">
                                <div class="text-muted small mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Inquiry No</div>
                                <span class="badge bg-white text-dark border px-2.5 py-1.5 shadow-xs fw-semibold fs-7" id="wa_inquiry_no">
                                    <i class="fa-solid fa-file-lines me-1 text-primary"></i> -
                                </span>
                            </div>
                            <div class="col-md-4 col-6">
                                <div class="text-muted small mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Customer / Contact</div>
                                <div class="fw-bold text-dark text-truncate" id="wa_customer_name" style="max-width: 200px;">-</div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="text-muted small mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">WhatsApp Number</div>
                                <div class="fw-bold text-success" id="wa_phone_number">
                                    <i class="fa-brands fa-whatsapp me-1"></i> -
                                </div>
                            </div>
                            <div class="col-md-2 col-6 text-md-end">
                                <div class="text-muted small mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Sent</div>
                                <span class="badge rounded-pill px-3 py-1.5 fw-bold shadow-xs" id="wa_sent_count_badge" style="background-color: #e6f9ed; color: #0ba360; border: 1px solid #b7eed0;">
                                    0 Sent
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- History Section -->
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <span class="fw-bold text-dark fs-6">
                            <i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i> Timeline
                        </span>
                        <small class="text-muted" id="wa_timeline_subtitle">Detailed record of every message sent</small>
                    </div>

                    <div id="wa_history_container">
                        <!-- Empty State -->
                        <div class="p-5 text-center border rounded-3 bg-light" id="wa_no_history_text" style="border-style: dashed !important; border-color: #cbd5e1 !important;">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background-color: #e6f9ed;">
                                <i class="fa-brands fa-whatsapp text-success fs-2"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">No WhatsApp Messages Sent Yet</h6>
                            <p class="text-muted small mb-0">Click the "Send WhatsApp Message" button below to initiate conversation with this lead.</p>
                        </div>

                        <!-- Modern Table -->
                        <div class="table-responsive d-none border rounded-3 shadow-xs" id="wa_history_table_wrapper" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="wa_history_table">
                                <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <tr>
                                        <th class="text-center py-2.5 text-secondary" style="width: 50px; font-size: 11.5px; font-weight: 700; text-transform: uppercase;">#</th>
                                        <th class="py-2.5 text-secondary" style="width: 180px; font-size: 11.5px; font-weight: 700; text-transform: uppercase;">Date & Time</th>
                                        <th class="py-2.5 text-secondary" style="width: 170px; font-size: 11.5px; font-weight: 700; text-transform: uppercase;">Sent By</th>
                                        <th class="py-2.5 text-secondary" style="font-size: 11.5px; font-weight: 700; text-transform: uppercase;">Details / Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer with Modern Action Buttons -->
                <div class="modal-footer px-4 py-3 d-flex justify-content-between align-items-center" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-outline-secondary px-4 rounded-pill" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Close
                    </button>
                    <button type="button" class="btn text-white px-4 py-2 rounded-pill shadow-sm" id="btn_send_general_wa" style="background: linear-gradient(135deg, #0ba360 0%, #128C7E 100%); font-weight: 600; transition: transform 0.15s ease;">
                        <i class="fa-brands fa-whatsapp me-2 fs-5"></i> Send WhatsApp Message
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('page_leavel_script')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            if ($('.assign-checkbox:checked').length > 0) {
                $('#assign-to-team-btn').removeClass('d-none');
            }

            $('#assign_to_team_modal').on('shown.bs.modal', function() {
                $('.select2', this).select2({
                    dropdownParent: $('#assign_to_team_modal')
                });
                if ($('#filter_assigned_to').attr("data-open") == 'yes') {
                    $('#AssignToTeamForm')[0].reset();
                    $('#AssignToTeamForm').find('.select2').val(null).trigger('change');
                }
            });
        });

        $(document).on('change', '.assign-checkbox', function() {
            const anyChecked = $('.assign-checkbox:checked').length > 0;

            if (anyChecked) {
                $('#assign-to-team-btn').removeClass('d-none');
            } else {
                $('#assign-to-team-btn').addClass('d-none');
            }
        });

        // $(document).on('click', '.open-assign-modal', function() {
        //     const checked = $('.assign-checkbox:checked');

        //     if (checked.length === 0) {
        //         alert('Please select at least one checkbox before assigning.');
        //         return;
        //     }

        //     const assignModal = new bootstrap.Modal(document.getElementById('assign_to_team_modal'));
        //     assignModal.show();
        //     $('#filter_assigned_to').attr("data-open", "yes");
        // });

        $(document).on('click', '.open-assign-modal', function() {
            const checked = $('.assign-checkbox:checked');

            if (checked.length === 0) {
                toastr.warning('Please select at least one inquiry.');
                return;
            }

            let companyIds = [];
            checked.each(function() {
                companyIds.push($(this).data('company-id'));
            });

            let uniqueCompanies = [...new Set(companyIds)];

            if (uniqueCompanies.length > 1) {
                toastr.error('You can only assign inquiries from the same company.');
                return;
            }


            let selectedCompanyId = uniqueCompanies[0];

            if ($("#form_company_id").length > 0) {
                $("#form_company_id").val(selectedCompanyId).trigger('change');
            } else {
                $("#company_id").val(selectedCompanyId);
            }


            let inquiryIds = checked.map(function() {
                return $(this).val();
            }).get();
            $("#inquiry_ids").val(inquiryIds.join(","));


            const assignModal = new bootstrap.Modal(document.getElementById('assign_to_team_modal'));
            assignModal.show();


            fetchTeams(selectedCompanyId);
        });




        $(document).on('click', '.open-single-assign-modal', function() {
            const assignModal = new bootstrap.Modal(document.getElementById('assign_to_team_modal'));
            assignModal.show();


            let initialCompanyId = $(this).attr("data-company_id");
            if (initialCompanyId) {
                $('#form_company_id').val(initialCompanyId).trigger('change');
            }

            $('#filter_assigned_to').attr("data-open", "no");


            let assigned_to_id = $(this).attr("data-selectedinquiryassignedto");
            let inquiry_id = $(this).attr("data-inquiry_id");
            let selectedIds = assigned_to_id ? assigned_to_id.toString().split(',') : [];
            $('#inquiry_ids').val(inquiry_id);

            let $select = $('#filter_assigned_to');
            $select.html('<option value="">Select Team Person</option>');

            if (initialCompanyId) {

                $.ajax({
                    url: '{{ env('API_URL') }}get-teams',
                    method: 'POST',
                    data: {
                        company_id: initialCompanyId
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status && response.data.length > 0) {
                            response.data.forEach(function(item) {
                                console.log("selectedIds");
                                console.log(selectedIds);
                                let selected = selectedIds.includes(item.id.toString()) ?
                                    'selected' : '';
                                $select.append('<option value="' + item.id + '" ' + selected +
                                    '>' + item.team_name + '</option>');
                            });

                            // Trigger select2 update
                            $select.trigger('change');
                        }
                    }
                });
            }
        });

        function fetchTeams(company_id) {
            if (!company_id) {
                let defaultOption = '<option value="">Select Team Person</option>';
                $('.search_by_assigned_to').html(defaultOption).trigger('change.select2');
                return;
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-teams',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    company_id: company_id
                },
                success: function(response) {
                    if (response.status && response.data && response.data.length > 0) {
                        let options = '<option value="">Select Team Person</option>';
                        $.each(response.data, function(i, item) {
                            options += '<option value="' + item.id + '">' + item.team_name +
                                '</option>';
                        });
                        $('.search_by_assigned_to').html(options).trigger('change.select2');
                    } else {
                        $('.search_by_assigned_to').html(
                            '<option value="">No team persons found</option>').trigger(
                            'change.select2');
                    }
                },
                error: function() {
                    $('.search_by_assigned_to').html(
                        '<option value="">Error fetching team persons</option>').trigger(
                        'change.select2');
                }
            });
        }

        $(document).ready(function() {

            $(document).on('change', '.search_by_company', function() {
                let company_id = $(this).val();
                console.log("initialCompanyId ", company_id);

                fetchTeams(company_id);
            });

            let initialCompanyId = $('.search_by_company').val();
            if (initialCompanyId) {
                fetchTeams(initialCompanyId);
            }
        });

        $('#assign_to_team_submit').on('click', function() {
            let selectedIds = $('.assign-checkbox:checked').map(function() {
                return $(this).val();
            }).get().join(',');

            let data_check = $('#filter_assigned_to').attr("data-open");
            if (data_check != 'no') {
                if (!selectedIds) {
                    toastr.warning('Please select at least one inquiry.');
                    return;
                }
                $('#inquiry_ids').val(selectedIds);
            }


            let formData = $('#AssignToTeamForm').serialize();

            $.ajax({
                url: '{{ route('inquiry.assign-inquiry') }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#assign_to_team_modal').modal('hide');
                        toastr.success('Inquiry assigned successfully.');
                        $('#yajra-datatables').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let response = JSON.parse(xhr.responseText);
                        let firstError = Object.values(response.errors)[0][0];
                        toastr.error('Validation Error: ' + firstError);
                    } else {
                        console.error(xhr.responseText);
                        toastr.error('Something went wrong. Please try again.');
                    }
                }
            });

        });
    </script>


    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var dtable = null;
        var currentAjaxRequest = null;

        $(function() {
            var picker = $('.my_daterangepicker').data('daterangepicker');
            if (picker) {
                var selectedStatusId = localStorage.getItem('selectedInquiryStatusId');

                if (selectedStatusId) {
                    $("#inquiry_created_by").attr("data-selectedinquirycreatedby", '');
                    setTimeout(function() {
                        $("#inquiry_created_by").val(null).attr("data-selectedinquirycreatedby", '')
                            .trigger('change.select2');
                        dtable.draw();
                    }, 1000);

                    var selected_inquiry_date = localStorage.getItem('selected_inquiry_date');
                    var selected_inquiry_date_arr = selected_inquiry_date.split('to');
                    var start = moment(selected_inquiry_date_arr[0], 'DD/MM/YYYY');
                    var end = moment(selected_inquiry_date_arr[1], 'DD/MM/YYYY');

                    picker.setStartDate(start);
                    picker.setEndDate(end);
                    picker.callback(start, end, start.format('DD/MM/YYYY') + ' to ' + end.format('DD/MM/YYYY'));

                    $('.my_daterangepicker').val(start.format('DD/MM/YYYY') + ' to ' + end.format('DD/MM/YYYY'));

                    localStorage.removeItem('selectedInquiryStatusId');
                    localStorage.removeItem('selected_inquiry_date');
                } else {
                    // Default range: 01/04/2025 to today
                    let startDate = moment('01/04/2025', 'DD/MM/YYYY');
                    let endDate = moment(); // today

                    picker.setStartDate(startDate);
                    picker.setEndDate(endDate);

                    let formattedRange = startDate.format('DD/MM/YYYY') + ' to ' + endDate.format('DD/MM/YYYY');
                    picker.callback(startDate, endDate, formattedRange);
                    $('.my_daterangepicker').val(formattedRange);
                }
            }
        });
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            dtable = $('#yajra-datatables').DataTable({
                processing: true,
                serverSide: true,
                // dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center"<"ps-3"l>i<"pe-4"p>>',
                dom: '<"table-responsive"t><"d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2"l i p>',

                order: [
                    [1, 'DESC']
                ],
                ajax: function(data, callback, settings) {

                    if (currentAjaxRequest) {
                        currentAjaxRequest.abort();
                    }

                    let company_id = $('select[name="company_id"] option:selected').val();
                    if (!company_id) {
                        company_id = $('meta[name="company_id"]').attr('value');
                    }

                    let inquiry_created_by = $('select[name="inquiry_created_by"] option:selected')
                        .val();
                    if (!inquiry_created_by) {
                        inquiry_created_by = $('input[name="inquiry_created_by"]').attr('value');
                    }
                    let inquiry_assigned_to = $('select[name="inquiry_assigned_to"] option:selected')
                        .val();
                    if (!inquiry_assigned_to) {
                        inquiry_assigned_to = $('input[name="inquiry_assigned_to"]').attr('value');
                    }
                    // console.log("L-580", inquiry_created_by, inquiry_assigned_to);

                    let postData = {
                        ...data,
                        search: $('input[name="search"]').val(),
                        filter_company: company_id,
                        filter_inquiry_status: $('select[name="inquiry_status_id"]').val(),
                        filter_followup_status: $('select[name="followup_status_id"]').val(),
                        filter_source_of_inquiry: $('select[name="source_of_inquiry_id"]').val(),
                        filter_created_by: inquiry_created_by,
                        filter_assigned_to: inquiry_assigned_to,
                        filter_inquiry_date: $('input[name="selected_date"]').val()?.replace(' - ',
                            ' to '),
                        filter_followup_date: $('input[name="followup_date"]').val(),
                        filter_country: $('select[name="country_id"]').val(),
                        filter_state: $('select[name="state_id"]').val(),
                        filter_city: $('select[name="city_id"]').val(),
                        show_lost: $('#show_lost').is(':checked') ? 1 : 0
                    };

                    currentAjaxRequest = $.ajax({
                        url: "{{ route($route . '.index') }}",
                        type: "GET",
                        data: postData,
                        beforeSend: function(request) {
                            request.setRequestHeader("X-CSRF-TOKEN", $(
                                'meta[name="csrf-token"]').attr('content'));
                        },
                        success: function(response) {
                            callback(response);
                        },
                        error: function(xhr, status) {
                            if (status !== 'abort') {
                                console.error("DataTable AJAX error:", xhr.responseText);
                            }
                        },
                        complete: function() {
                            currentAjaxRequest = null;
                        }
                    });
                },
                columnDefs: [
                    { width: "40px", targets: 0 },
                    { width: "45px", targets: 1 },
                ],
                columns: {!! isset($columns) ? json_encode($columns) : '[]' !!},
                language: {
                    searchPlaceholder: 'Search...',
                }
            });



            $('#yajra-datatables thead th:eq(0)').html(`
                <input type="checkbox" id="select-all-checkbox" class="form-check-input" title="Select All">
            `);

            $(document).on('change', '#select-all-checkbox', function() {
                const isChecked = $(this).is(':checked');
                $('#yajra-datatables tbody .assign-checkbox').prop('checked', isChecked);
                if (isChecked || $('.assign-checkbox:checked').length > 0) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            $(document).on('change', '#yajra-datatables tbody .assign-checkbox', function() {
                const totalCheckboxes = $('#yajra-datatables tbody .assign-checkbox').length;
                const checkedCheckboxes = $('#yajra-datatables tbody .assign-checkbox:checked').length;

                if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
                    $('#select-all-checkbox').prop('indeterminate', false).prop('checked', true);
                } else if (checkedCheckboxes === 0) {
                    $('#select-all-checkbox').prop('indeterminate', false).prop('checked', false);
                } else {
                    $('#select-all-checkbox').prop('indeterminate', true).prop('checked', false);
                }

                if (checkedCheckboxes > 0) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            dtable.on('draw', function() {
                const checkedCount = $('#yajra-datatables tbody .assign-checkbox:checked').length;
                const totalCount = $('#yajra-datatables tbody .assign-checkbox').length;

                if (checkedCount === 0) {
                    $('#select-all-checkbox').prop('indeterminate', false).prop('checked', false);
                } else if (checkedCount === totalCount && totalCount > 0) {
                    $('#select-all-checkbox').prop('indeterminate', false).prop('checked', true);
                } else if (checkedCount > 0) {
                    $('#select-all-checkbox').prop('indeterminate', true);
                }

                if (checkedCount > 0) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            $(document).on('change', '.assign-checkbox', function() {
                const anyChecked = $('.assign-checkbox:checked').length > 0;
                if (anyChecked) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            function updateDeleteButtonVisibility() {
                const checkedCount = $('.assign-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#delete-selected-btn').removeClass('d-none');
                } else {
                    $('#delete-selected-btn').addClass('d-none');
                }
            }


            $(document).on('change', '.assign-checkbox', function() {
                updateDeleteButtonVisibility();
                const anyChecked = $('.assign-checkbox:checked').length > 0;
                if (anyChecked) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            $(document).on('change', '#select-all-checkbox', function() {
                const isChecked = $(this).is(':checked');
                $('#yajra-datatables tbody .assign-checkbox').prop('checked', isChecked);
                updateDeleteButtonVisibility();
                if (isChecked) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            dtable.on('draw', function() {
                updateDeleteButtonVisibility();
                const checkedCount = $('.assign-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#assign-to-team-btn').removeClass('d-none');
                } else {
                    $('#assign-to-team-btn').addClass('d-none');
                }
            });

            $(document).on('click', '#delete-selected-btn', function() {
                const checked = $('.assign-checkbox:checked');

                if (checked.length === 0) {
                    toastr.warning('Please select at least one inquiry to delete.');
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete ${checked.length} selected inquiry(s). This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete them!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let inquiryIds = checked.map(function() {
                            return $(this).val();
                        }).get();

                        var _token = '{{ csrf_token() }}';

                        $.ajax({
                            url: '{{ route($route . ".bulk-delete") }}',
                            type: 'POST',
                            data: {
                                _token: _token,
                                inquiry_ids: inquiryIds,
                                company_id: $('select[name="company_id"] option:selected').val() || $('meta[name="company_id"]').attr('value') || null,
                            },
                            success: function(response) {
                                if (response.success) {
                                    toastr.success(response.message || 'Selected inquiries deleted successfully.');
                                    dtable.ajax.reload(null, false);
                                    $('#delete-selected-btn').addClass('d-none');
                                    $('#assign-to-team-btn').addClass('d-none');
                                } else {
                                    toastr.error(response.message || 'Failed to delete inquiries.');
                                }
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    msg = xhr.responseJSON.message;
                                }
                                toastr.error(msg);
                            }
                        });
                    }
                });
            });

            $(document).on('change', '.select_filter, .table_filter', function(event) {
                event.preventDefault();
                dtable.draw();
            });
            $(document).on('click', '.applyBtn', function(event) {
                event.preventDefault();
                if (dtable) {
                    dtable.draw();
                }
            });

            $(document).on('change', '.date_filter', function(event) {
                event.preventDefault();
                dtable.draw();
            });

            $('.search').keyup(function() {
                dtable.draw();
            });
            $('#show_lost').on('change', function() {
                dtable.draw();
            });

            // Reset filters
            $("#cilory_filter").click(function() {
                $('.search').val('');
                $('.select_filter').val(null).trigger('change');
                $('#filter_by_date').val('');
                let picker = $('.my_daterangepicker').data('daterangepicker');

                if (picker) {
                    let startDate = moment('01/04/2025', 'DD/MM/YYYY');
                    let endDate = moment(); // today
                    let formattedRange = startDate.format('DD/MM/YYYY') + ' to ' + endDate.format(
                        'DD/MM/YYYY');

                    picker.setStartDate(startDate);
                    picker.setEndDate(endDate);
                    $('.my_daterangepicker').val(formattedRange);
                    picker.callback(startDate, endDate, formattedRange);
                }
                // Clear flatpickr date range
                // filterByInquiryDate.clear();
                // filterInquiryDateRange = [];
                dtable.draw();
            });

            // Modal and assign to team logic here, unchanged...

            // Export Excel button
            $('#export_excel_btn').on('click', function(e) {
                e.preventDefault();
                let company_id = $('select[name="company_id"] option:selected').val();
                if (!company_id) {
                    company_id = $('meta[name="company_id"]').attr('value');
                }


                let inquiry_created_by = $('select[name="inquiry_created_by"] option:selected').val();
                if (!inquiry_created_by) {
                    inquiry_created_by = $('input[name="inquiry_created_by"]').attr('value');
                }

                let filters = {
                    search: $('input[name="search"]').val(),
                    company_id: company_id,
                    filter_inquiry_status: $('select[name="inquiry_status_id"] option:selected').val(),
                    filter_followup_status: $('select[name="followup_status_id"] option:selected')
                        .val(),
                    filter_source_of_inquiry: $('select[name="source_of_inquiry_id"] option:selected')
                        .val(),
                    filter_created_by: inquiry_created_by,
                    filter_assigned_to: $('select[name="inquiry_assigned_to"] option:selected').val(),
                    filter_inquiry_date: $('input[name="selected_date"]').val().replace(' - ', ' to '),
                };

                var queryParams = $.param(filters);
                var url = "{{ route($route . '.export.excel') }}" + "?" + queryParams;
                window.location.href = url;
            });

            // Print button
            $('#print_btn').on('click', function(e) {
                e.preventDefault();
                let filters = {
                    search: $('input[name="search"]').val(),
                    filter_company: $('select[name="company_id"] option:selected').val(),
                    filter_inquiry_status: $('select[name="inquiry_status_id"] option:selected').val(),
                    filter_followup_status: $('select[name="followup_status_id"] option:selected')
                        .val(),
                    filter_source_of_inquiry: $('select[name="source_of_inquiry_id"] option:selected')
                        .val(),
                    filter_created_by: $('select[name="inquiry_created_by"] option:selected').val(),
                    filter_assigned_to: $('select[name="inquiry_assigned_to"] option:selected').val(),
                    filter_inquiry_date: $('input[name="selected_date"]').val().replace(' - ', ' to '),

                };

                let queryParams = $.param(filters);
                let url = "{{ route($route . '.print') }}" + "?" + queryParams;
                window.open(url, '_blank');
            });

            // Other modals and event handlers remain unchanged...

        });
    </script>

    @if (!$company_id)
        @include('utils.getCompany')
    @endif
    @include('utils.getInquiryStatus')
    @include('utils.getFollowupStatus')
    @include('utils.getSourceOfInquiry')
    @include('utils.getTeams')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
    @include('utils.getAreaByCity')

    <script>
        $(document).on('click', '.duplicate-count-badge', function(e) {
            e.preventDefault();
            var mobile = $(this).attr('data-mobile');
            var companyId = $(this).attr('data-company-id');
            
            $('#dup_modal_mobile').text(mobile);
            $('#duplicate_inquiries_table tbody').html('<tr><td colspan="7" class="text-center">Loading inquiries...</td></tr>');
            
            var modal = new bootstrap.Modal(document.getElementById('duplicate_inquiries_modal'));
            modal.show();
            
            $.ajax({
                url: "{{ route('inquiry.get-duplicate-inquiries') }}",
                type: "GET",
                data: {
                    mobile_no: mobile,
                    company_id: companyId
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        response.data.forEach(function(item) {
                            html += '<tr>';
                            html += '<td>' + item.inquiry_no + '</td>';
                            html += '<td>' + item.inquiry_date + '</td>';
                            html += '<td>' + item.inquiry_status + '</td>';
                            html += '<td>' + item.followup_status + '</td>';
                            html += '<td>' + item.source + '</td>';
                            html += '<td>' + item.assigned_to + '</td>';
                            html += '<td><a href="' + item.edit_url + '" class="btn btn-sm btn-primary" target="_blank">Edit</a></td>';
                            html += '</tr>';
                        });
                        $('#duplicate_inquiries_table tbody').html(html);
                    } else {
                        $('#duplicate_inquiries_table tbody').html('<tr><td colspan="7" class="text-center text-muted">No duplicate inquiries found.</td></tr>');
                    }
                },
                error: function() {
                    $('#duplicate_inquiries_table tbody').html('<tr><td colspan="7" class="text-center text-danger">Failed to load inquiries. Please try again.</td></tr>');
                }
            });
        });

        // Open Send General WhatsApp Modal
        $(document).on('click', '.send-general-whatsapp', function(e) {
            e.preventDefault();
            var inquiryId = $(this).attr('data-id');
            $('#wa_inquiry_id').val(inquiryId);

            // Reset modal fields
            $('#wa_customer_name').text('Loading...');
            $('#wa_phone_number').text('Loading...');
            $('#wa_inquiry_no').text('Loading...');
            $('#wa_sent_count_badge').text('Loading...');
            $('#wa_no_history_text').removeClass('d-none').text('Loading history...');
            $('#wa_history_table_wrapper').addClass('d-none');
            $('#wa_history_table tbody').empty();
            $('#btn_send_general_wa').prop('disabled', true);

            var modalEl = document.getElementById('send_whatsapp_general_modal');
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            var previewUrl = "{{ route('inquiry.whatsapp-general-preview', ['id' => ':id']) }}".replace(':id', inquiryId);
            $.ajax({
                url: previewUrl,
                type: 'GET',
                success: function(res) {
                    if (res.success) {
                        $('#wa_customer_name').text(res.inquiry.customer_name);
                        $('#wa_phone_number').text(res.inquiry.phone || 'N/A');
                        $('#wa_inquiry_no').text(res.inquiry.inquiry_no);
                        $('#wa_sent_count_badge').text('Sent: ' + res.inquiry.sent_count + ' times');

                        // Render modern history rows
                        if (res.history && res.history.length > 0) {
                            var hHtml = '';
                            $.each(res.history, function(idx, item) {
                                var parts = (item.date || '').split(' ');
                                var dateStr = parts[0] || item.date;
                                var timeStr = (parts.slice(1).join(' ')) || '';
                                hHtml += '<tr>';
                                hHtml += '<td class="text-center fw-semibold text-muted" style="width: 50px;">' + (idx + 1) + '</td>';
                                hHtml += '<td><div class="d-flex align-items-center"><i class="fa-regular fa-clock text-primary me-2 fs-6"></i><div><span class="fw-bold text-dark d-block">' + dateStr + '</span><small class="text-muted">' + timeStr + '</small></div></div></td>';
                                hHtml += '<td><span class="fw-semibold text-dark">' + item.sender + '</span></td>';
                                hHtml += '<td class="text-secondary">' + (item.remarks || '-') + '</td>';
                                hHtml += '</tr>';
                            });
                            $('#wa_history_table tbody').html(hHtml);
                            $('#wa_history_table_wrapper').removeClass('d-none');
                            $('#wa_no_history_text').addClass('d-none');
                        } else {
                            $('#wa_history_table_wrapper').addClass('d-none');
                            $('#wa_no_history_text').removeClass('d-none');
                        }

                        $('#btn_send_general_wa').prop('disabled', false);
                    } else {
                        $('#wa_no_history_text').removeClass('d-none').html('<span class="text-danger">' + (res.message || 'Error loading history') + '</span>');
                    }
                },
                error: function(xhr) {
                    $('#wa_no_history_text').removeClass('d-none').html('<span class="text-danger">Failed to load history. Please try again.</span>');
                }
            });
        });

        // Handle Send WhatsApp confirmation
        $(document).on('click', '#btn_send_general_wa', function(e) {
            e.preventDefault();
            var inquiryId = $('#wa_inquiry_id').val();
            if (!inquiryId) return;

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...');

            var sendUrl = "{{ route('inquiry.send-whatsapp-general', ['id' => ':id']) }}".replace(':id', inquiryId);
            $.ajax({
                url: sendUrl,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    $btn.prop('disabled', false).html(originalText);
                    if (res.success) {
                        var modalEl = document.getElementById('send_whatsapp_general_modal');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sent!',
                                text: res.message || 'WhatsApp message sent successfully!',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            alert(res.message || 'WhatsApp message sent successfully!');
                        }

                        // Reload datatable to update badge
                        if ($.fn.DataTable.isDataTable('#datatable')) {
                            $('#datatable').DataTable().ajax.reload(null, false);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: res.message || 'Failed to send WhatsApp message.'
                            });
                        } else {
                            alert(res.message || 'Failed to send WhatsApp message.');
                        }
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalText);
                    var msg = 'Failed to send WhatsApp message.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                    } else {
                        alert(msg);
                    }
                }
            });
        });
    </script>

    @include('software.inlcudes.script-delete-record')
    @include('software.inlcudes.script-update-status')
    @include('software.inlcudes.script-restore-record')
@endpush
