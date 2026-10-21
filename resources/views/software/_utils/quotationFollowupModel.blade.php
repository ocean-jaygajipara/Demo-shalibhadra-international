@php
    $now = \Carbon\Carbon::now()->setTimezone('Asia/Kolkata')->format('Y-m-d\TH:i');

@endphp

@push('push_style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- CSS CDN -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.min.css" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/select2/select2.css') }}" />
@endpush

<div class="modal fade" id="listFollowupModalQuotation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="exampleModalLabel4">Followup List</h5>
                <div>
                    <button type="button" class="btn btn-primary me-2" id="addFollowupBtnQuotation">
                        Add Followup
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <input type="hidden" id="company_id" name="company_id" value="">
            <input type="hidden" id="quotation_id" name="quotation_id" value="">
            <input type="hidden" id="company_name" name="company_name" value="">
            <div class="modal-body">
                <div class="showFollowupQuotationDetail" style="d-none">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <b>Quotation :- </b>
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <b>Company Name :- </b>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <b> Customer Name :- </b>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <b> Mobile :- </b>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <b> WhatsAPP :- </b>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <b> Email :- </b>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-datatable text-nowrap mt-3">
                <div class="card-datatable table-responsive">
                    <table id="all-followup-list-quotation" class="dt-responsive table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th class="w-20 text-wrap">Response</th>
                                <th>Date Details</th>
                                <th>Sales Person Name</th>
                                <th class="w-20 text-wrap">Through - Description</th>
                                <th>Followup Status</th>
                                <!-- <th>Followup Reason</th> -->
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="responseQuotationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <form name="FollowupResponseFormQuotation" id="FollowupResponseFormQuotation" method="POST">
                @csrf
                <input type="hidden" name="followup_id" id="followup_id">
                <input type="hidden" name="company_id" id="company_id">
                <input type="hidden" name="quotation_id" id="quotation_id">
                <input type="hidden" name="company_name" id="company_name">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Add Followup Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="showQuotationDetailInResponseModal mb-4" style="display: none;"></div>

                    <div class="row">
                        <div class="col-12 mb-2">
                            <label for="response" class="form-label">Response <span class="text-danger">*</span></label>
                            <textarea id="response" name="response" class="form-control required @error('response') is-invalid @enderror"
                                placeholder="Response"></textarea>
                            @error('response')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-12 mb-2">
                            <label for="followup_action" class="form-label">Follow-up Action <span
                                    class="text-danger">*</span></label>
                            <select class="form-select required select2 @error('followup_action') is-invalid @enderror"
                                name="followup_action" id="followup_action_quotation" required>
                                <option value="">Follow-up Action</option>
                                <option value="Next Follow-up">Next Follow-up</option>
                                <option value="End Follow-up">End Follow-up</option>
                            </select>
                            @error('followup_action')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="col-12 mb-2 hide_action">
                            <label for="status_id" class="form-label">Follow Up Status <span
                                    class="text-danger">*</span></label>
                            <select name="status_id" id="status_id"
                                class="form-select select2 responseModal status_id @error('status_id') is-invalid @enderror search_by_followup_status"
                                data-append="search_by_followup_status"
                                data-selectedFollowupStatusId="{{ isset($edit) && $edit?->status_id ? $edit?->status_id : '' }}">
                                <option value="">Select Followup Status</option>
                            </select>
                            @error('status_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="add_followup_responseQuotation"
                        class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="addQuotationFollowupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <form name="addFollowupFormQuotation" id="addFollowupFormQuotation" method="POST">
                @csrf
                <input type="hidden" name="quotation_id" id="quotation_id">
                <input type="hidden" name="company_id" id="company_id">
                <input type="hidden" name="company_name" id="company_name">
                <input type="hidden" name="load_table" id="no">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Add Follow Up for Quotation <span
                            id="quotation_no"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="showQuotationDetail mb-4" style="display:none;"></div>
                    <div class="row">
                        @if (!$company_id)
                            <div class="col-12 col-md-6 mb-2">
                                <label for="company_name" class="form-label">Company Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" id="company_name" name="company_name"
                                    class="form-control required" placeholder="Company Name" disabled />
                            </div>
                        @else
                            <input type="hidden" name="company_id" id="company_id" value="{{ $company_id }}">
                        @endif

                        <div class="col-12 col-md-6 mb-2">
                            <label for="date_time" class="form-label">Follow Up Date and Time <span
                                    class="text-danger">*</span></label>
                            <input id="date_time" name="date_time" class="form-control datetimepicker required"
                                placeholder="Follow Up Date and Time" value="{{ $now }}"
                                min="{{ $now }}" />
                        </div>



                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label" for="reason_id">Follow Up Reason <span
                                    class="text-danger">*</span></label>
                            <select id="reason_id_quotation" name="reason_id" class="form-select select2 required"
                                placeholder="Followup reason">
                                <option value="">Followup reason</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label" for="through">Follow Up Through <span
                                    class="text-danger">*</span></label>
                            <select id="through_quotation" name="through" class="form-select required"
                                placeholder="Follow Up Through">
                                <option value="">Follow Up Through</option>
                                <option value="Call">Call</option>
                                <option value="SMS">SMS</option>
                                <option value="Email">Email</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="form-label" for="details">Follow Up Details <span
                                    class="text-danger">*</span></label>
                            <textarea rows="2" id="details" name="details" class="form-control required"
                                placeholder="Follow Up Details"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-12 mb-2">
                            <label class="form-label" for="status_id">Follow Up Status <span
                                    class="text-danger">*</span></label>
                            <select id="status_id" name="status_id" class="form-select select2 required status_id">
                                <option value="">Select Followup Status</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="addFollowupFormQuotationSubmit" class="btn btn-primary">Add
                        Followup</button>
                </div>
            </form>
        </div>
    </div>
</div>


@push('page_scripts')
    <!-- datetimepicker jQuery CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-datetimepicker/2.5.20/jquery.datetimepicker.full.min.js">
        < script src = "{{ asset('software/vendor/libs/select2/select2.js') }}" >
    </script>


    </script>
    <script type="text/javascript">
        // const inputDateFormat = 'DD-MM-YYYY h:mm A'; // MATCH your datetimepicker format
        $(".datetimepicker").each(function() {
            $(this).datetimepicker({
                format: 'd-m-Y h:i A', // 12-hour format with AM/PM
                formatTime: 'h:i A', // explicitly control time format
                closeOnDateSelect: true,
                step: 10, // or 30 minutes if you want
                minDate: 0, // today,
                validateOnBlur: false
            });
        });

        $(document).on('change', '.datetimepicker', function() {

            const selected = moment($(this).val(), inputDateFormat);
            const minDate = moment($(this).attr('min'), inputDateFormat);
            const now = moment().subtract(30, 'minutes').format(inputDateFormat);

            if (!selected.isValid()) {
                toastr.error('Invalid date format.');
                return;
            }

            if (selected.isBefore(minDate)) {
                toastr.error('Selected datetime must be in the future: ' + minDate.format(inputDateFormat));
                // console.log("L-278", $(this).val(), $(this).attr('min'), minDate, selected.isAfter(minDate), selected.isBefore(minDate));
                $(this).val(''); // Optional: clear the invalid value
                $(this).attr('min', now);
            } else {
                console.log('Valid datetime selected:', selected.format(inputDateFormat));
                $(this).val(selected.format(inputDateFormat));
                // $(this).attr("value", selected.format(inputDateFormat));
            }
        });

        $(document).on('blur', '.datetimepicker', function() {
            const selected = moment($(this).val(), inputDateFormat, true);
            $(this).val(selected.format(inputDateFormat)); // Correct output format
            // $(this).attr("value", selected.format(inputDateFormat));
        });

        // let followupStatus = null;


        $(document).on('click', '.list-followup-quotation', function() {
            var id = $(this).data('id');
            var quotation_id = $(this).data('quotation_id');
            var company_id = $(this).data('company_id');
            var company_name = $(this).data('company_name');
            if (company_id && quotation_id) {
                loadFollowupListQuotation(company_id, quotation_id, company_name);
            }
        });

        $(document).on('click', '.followup-list-quotation', function() {
            var quotation_id = $(this).data('quotation_id');
            var company_id = $(this).data('company_id');
            var company_name = $(this).data('company_name');
            $('#listFollowupModalQuotation #company_id').val(company_id);
            $('#listFollowupModalQuotation #quotation_id').val(quotation_id);
            $('#listFollowupModalQuotation #company_name').val(company_name);

            $('#all-followup-list-quotation tbody').empty();

            loadFollowupListQuotation(company_id, quotation_id, company_name);
        });

        function loadFollowupListQuotation(company_id, quotation_id, company_name = null) {
            $('#listFollowupModalQuotation #company_id').val(company_id);
            $('#listFollowupModalQuotation #quotation_id').val(quotation_id);
            $('#listFollowupModalQuotation #company_name').val(company_name);

            getFollowupStatus(company_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('get-followup-quotation') }}",
                data: {
                    company_id: company_id,
                    quotation_id: quotation_id,
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {


                    if (response.status == true) {

                        let followupQuotationDetail = null;
                        if (response?.extraData?.quotation) {
                            // console.log('addsad');



                            followupQuotationDetail = response?.extraData?.quotation;

                            // console.log(followupQuotationDetail);
                            window.followupQuotationDetail = followupQuotationDetail;

                            let followupQuotationHtml = null;


                            if (followupQuotationDetail?.id) {

                                followupQuotationHtml = '<div class="row">';
                                if (followupQuotationDetail?.quotation_no) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Quotation :- </b>' +
                                        followupQuotationDetail?.quotation_no + '</div>';

                                }
                                if (followupQuotationDetail?.quotation_no) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Quotation Date :- </b>' + moment(
                                            followupQuotationDetail?.quotation_date).format(
                                            'DD-MM-YYYY') + '</div>';
                                }
                                if (followupQuotationDetail?.company?.company_name) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                                        followupQuotationDetail?.company?.company_name + '</div>';
                                }
                                if (followupQuotationDetail?.customer_name) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                                        followupQuotationDetail?.customer_name + '</div>';
                                }
                                if (followupQuotationDetail?.contact_person_name) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                                        followupQuotationDetail?.contact_person_name + '</div>';
                                }
                                if (followupQuotationDetail?.mobile_no) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                                        followupQuotationDetail?.mobile_no + '">' +
                                        followupQuotationDetail?.mobile_no + '</a></div>';
                                }
                                if (followupQuotationDetail?.email) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Email :- </b> <a href="mail:' +
                                        followupQuotationDetail?.email + '" target="_blank">' +
                                        followupQuotationDetail?.email + '</a> </div>';
                                }
                                if (followupQuotationDetail?.whatsapp_no) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                                        followupQuotationDetail?.whatsapp_no + '?text=" target="_blank">' +
                                        followupQuotationDetail?.whatsapp_no + '</a> </div>';
                                }
                                if (followupQuotationDetail?.quotation_status?.name) {
                                    followupQuotationHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Quotation Status :- </b>' +
                                        followupQuotationDetail.quotation_status.name +
                                        '</div>';
                                }

                                followupQuotationHtml += '</div>';

                            }
                            $(".showFollowupQuotationDetail").empty().html(followupQuotationHtml).show();

                            followupQuotationDetail = encodeURIComponent(JSON.stringify(
                                followupQuotationDetail));
                            $("#addFollowupBtnQuotation").attr('data-quotation', followupQuotationDetail);
                        }
                        let rows = '';
                        $.each(response.data, function(key, value) {
                            rows += `<tr>`;
                            rows += '<td>' + (key + 1) + '</td>';
                            let trFollowupStatus = null;
                            if (value.response_details == null || value.response_details == '') {
                                rows +=
                                    '<td class="w-20 text-wrap"><button type="button" data-company_name="' +
                                    value
                                    .company_name + '" data-quotation_id="' + value.quotation_id +
                                    '" data-id="' + value.id + '" data-company_id="' + value
                                    .company_id + '" data-status_id="' + value.status_id +
                                    '" class="showResponseQuotationModal btn btn-primary btn-sm"';
                                if (followupQuotationDetail) {
                                    rows += "data-quotation='" + followupQuotationDetail +
                                        "'";
                                }
                                rows += '>Response</button></td>';
                                trFollowupStatus =
                                    '<button type="button" class="btn btn-sm w-100" style="color: #000; background-color: ' +
                                    value?.followup_status_color + '">' + value?.followup_status ??
                                    '-' + '</button>'
                            } else {
                                rows += '<td class="w-20 text-wrap">' + (value.response_details ?? '') +
                                    '</td>';
                                let btn =
                                    '<button type="button" class="btn btn-sm w-100 dropdown-toggle waves-effect waves-light" style="background-color: ' +
                                    value?.followup_status_color +
                                    '; color: #000;" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    value?.followup_status + '</button>';

                                let dropdown = '<ul class="dropdown-menu">';
                                if (followupStatus.length > 0) {
                                    $.each(followupStatus, function(followupStatus_key,
                                        followupStatus_value) {
                                        // dropdown += '<li><a href="javascript:void(0)" class="dropdown-item waves-effect update-status" data-url="' + route($modules['route'] + '.status-update') + '" data-id="' +htmlspecialchars($row["id"]) + '" data-update_status="' + htmlspecialchars($fstatus->id) + '">'+ htmlspecialchars($fstatus->name) .'</a> </li>';
                                        let followupStatusUpdateRoute =
                                            @json(route('followup.status-update'));
                                        dropdown +=
                                            '<li><a href="javascript:void(0)" class="dropdown-item waves-effect update-status" data-url="' +
                                            followupStatusUpdateRoute + '" data-id="' + value
                                            ?.id + '" data-update_status="' +
                                            followupStatus_value?.id +
                                            '" modelReload="#listFollowupModalQuotation">' +
                                            followupStatus_value?.followup_status +
                                            '</a> </li>';
                                    });
                                }
                                dropdown += '</ul>';

                                trFollowupStatus = '<div class="dropdown">' + btn + dropdown + '</div>';
                            }
                            rows += '<td>' + (moment(value.date_time).format(inputDateFormat) ?? '') +
                                '</td>';
                            rows += '<td class="w-20 text-wrap">' + (value.created_by_name ?? '') +
                                '</td>';
                            rows += '<td class="w-20 text-wrap">' + (value.through ?? '') +
                                `: <strong>` + (value.details ??
                                    '') + '</strong></td>';
                            // rows += '<td class="text-wrap text-center">' + (value.followup_status ?? '') + '</td>';
                            rows += '<td class="text-wrap text-center">' + trFollowupStatus + '</td>';

                            // rows += '<td>' + (value.reason ?? '') + '</td>';
                            rows += `</tr>`;
                        });

                        // Destroy previous DataTable if already initialized
                        if ($.fn.DataTable.isDataTable('#all-followup-list-quotation')) {
                            $('#all-followup-list-quotation').DataTable().clear().destroy();
                        }
                        $('#all-followup-list-quotation tbody').html(rows);
                        // Initialize DataTable
                        $('#all-followup-list-quotation').DataTable({
                            responsive: true,
                            autoWidth: false,
                            searching: false,
                            lengthChange: false,
                            pageLength: 10
                        });

                        $('#listFollowupModalQuotation').modal('show');
                    } else {
                        console.log(response.message);
                    }
                }
            });
        }

        $(document).on('click', '.showResponseQuotationModal', function() {

            var id = $(this).data('id');
            var company_id = $(this).data('company_id');
            var status_id = $(this).data('status_id');
            var quotation_id = $(this).data('quotation_id');
            var company_name = $(this).data('company_name');
            let quotationDetail = (window?.followupQuotationDetail) ? window.followupQuotationDetail : $(this).attr(
                'data-quotation');


            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-followup-status',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]')
                        .attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Followup Status</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                if (status_id == item.id) {
                                    options += "<option value='" + item.id +
                                        "' selected >" + item.followup_status +
                                        "</option>";
                                } else {
                                    options += "<option value='" + item.id +
                                        "'>" +
                                        item.followup_status + "</option>";
                                }
                            });
                        }
                        followup_status = options;

                        $("#responseQuotationModal #status_id").empty();
                        $("#responseQuotationModal #status_id").append(options);
                        $('#responseQuotationModal #company_id').val(company_id);
                        $('#responseQuotationModal #followup_id').val(id);
                        $('#responseQuotationModal #quotation_id').val(quotation_id);
                        $('#responseQuotationModal #company_name').val(company_name);
                        $('#responseQuotationModal #status_id').val(status_id);
                        $('#responseQuotationModal #status_id').trigger('change');

                        if (typeof quotationDetail == 'object') {
                            quotationDetail = JSON.stringify(quotationDetail);
                        }

                        $("#add_followup_responseQuotation").attr('data-quotation', quotationDetail);
                        quotationDetail = JSON.parse(decodeURIComponent(quotationDetail));
                        // console.log();

                        let showQuotationDetailHtml = null;

                        if (quotationDetail?.id) {
                            showQuotationDetailHtml = '<div class="row">';
                            if (quotationDetail?.quotation_no) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Quotation :- </b>' +
                                    quotationDetail?.quotation_no + '</div>';
                            }
                            if (quotationDetail?.quotation_no) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Quotation Date :- </b>' +
                                    moment(
                                        quotationDetail?.quotation_date).format(
                                        'DD-MM-YYYY') + '</div>';
                            }
                            if (quotationDetail?.company?.company_name) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                                    quotationDetail?.company?.company_name + '</div>';
                            }
                            if (quotationDetail?.customer_name) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                                    quotationDetail?.customer_name + '</div>';
                            }
                            if (quotationDetail?.contact_person_name) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                                    quotationDetail?.contact_person_name + '</div>';
                            }
                            if (quotationDetail?.mobile_no) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                                    quotationDetail?.mobile_no + '">' +
                                    quotationDetail?.mobile_no + '</a></div>';
                            }
                            if (quotationDetail?.email) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Email :- </b> <a href="mail:' +
                                    quotationDetail?.email + '" target="_blank">' +
                                    quotationDetail?.email + '</a> </div>';
                            }
                            if (quotationDetail?.whatsapp_no) {
                                showQuotationDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                                    quotationDetail
                                    ?.whatsapp_no + '?text=" target="_blank">' +
                                    quotationDetail?.whatsapp_no + '</a> </div>';
                            }
                            showQuotationDetailHtml += '</div>';
                            $(".showQuotationDetailInResponseModal").empty().html(
                                    showQuotationDetailHtml)
                                .show();

                        }

                        $('#responseQuotationModal').modal('show');
                        $('#listFollowupModalQuotation').modal('hide');


                    }
                }
            });
        });

        $('.hide_action').hide();

        $('#followup_action_quotation').on('change', function() {
            const selectedValue = $(this).val();

            if (selectedValue === 'Next Follow-up' || selectedValue === 'End Follow-up') {
                $('.hide_action').show();
            } else {
                $('.hide_action').hide();
            }
        });

        $(document).on('click', '#add_followup_responseQuotation', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('.error_laravel').remove();

            $('#FollowupResponseFormQuotation .required').each(function() {
                var value = $(this).val();
                if (value === '') {
                    $(this).addClass('is-invalid');
                    isValid = false;
                    toastr.error($(this).attr("placeholder") + " is required");
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (isValid == true) {
                $('#add_followup_responseQuotation').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#FollowupResponseFormQuotation')[0]);
                let followup_action = $('#followup_action_quotation').find('option:selected').val();

                $('#add_followup_responseQuotation').prop('disabled', true);
                $('#add_followup_responseQuotation').attr('disabled', 'disabled');
                $('#add_followup_responseQuotation').css({
                    'background': '#514ff1',
                    'opacity': '0.65',
                    'cursor': 'none'
                });

                $.ajax({
                    url: "{{ route('followup.store_response_quotation') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#add_followup_responseQuotation').attr('disabled', false).text('Submit');
                        $('#add_followup_responseQuotation').css({
                            'background': '',
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                        if (response.status == true) {

                            followup_selected_status = $('#FollowupResponseFormQuotation #status_id')
                                .val();
                            var followup_id = $('#FollowupResponseFormQuotation #followup_id').val();
                            var company_id = $('#FollowupResponseFormQuotation #company_id').val();
                            var quotation_id = $('#FollowupResponseFormQuotation #quotation_id').val();
                            var company_name = $('#FollowupResponseFormQuotation #company_name').val();

                            $('#FollowupResponseFormQuotation')[0].reset();
                            toastr.success(response?.message);
                            $('#responseQuotationModal').modal('hide');

                            if (followup_action === "Next Follow-up") {

                                let quotationDetail = $('#add_followup_responseQuotation').attr(
                                    'data-quotation');

                                    // alert(followup_action);
                                    if (followup_action != 'End Follow-up') {
                                    addFollowupFormQuotation(company_id, quotation_id, company_name,
                                        quotationDetail);
                                    return;
                                }


                            }
                            // loadFollowupList(company_id, quotation_id, company_name);
                            dtable.draw();
                            return;
                        } else {
                            toastr.error(response?.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#submit').attr('disabled', false).text('Submit');
                        $('.text-danger').remove();
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    $('#' + field).after(
                                        '<div class="text-danger error_laravel">' +
                                        errors[field].join('<br>') + '</div>');
                                }
                            }
                        } else {
                            toastr.error(xhr.responseJSON.message || 'An unexpected error occurred.');
                        }
                    }
                });
            }
        });

        $(document).on('click', '.add-followup', function() {
            const company_id = $(this).data('company_id');
            const company_name = $(this).data('company_name');
            const quotation_id = $(this).data('id');
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#addFollowupFormQuotation #company_id').val(company_id);
            $('#addFollowupFormQuotation #company_name').val(company_name);
            $('#addFollowupFormQuotation #quotation_id').val(quotation_id);

            let quotationDetail = $('.add-followup').attr('data-quotation');

            addFollowupFormQuotation(company_id, quotation_id, company_name, quotationDetail);
        });

        $(document).on('click', '#addFollowupBtnQuotation', function() {
            let company_id = $('#listFollowupModalQuotation #company_id').val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }
            let quotation_id = $('#listFollowupModalQuotation #quotation_id').val();
            let company_name = $('#listFollowupModalQuotation #company_name').val();
            let quotationDetail = $('#addFollowupBtnQuotation').attr('data-quotation');

            addFollowupFormQuotation(company_id, quotation_id, company_name, quotationDetail);
        });

        function addFollowupFormQuotation(company_id, quotation_id, company_name, quotationDetail) {
            $('#addFollowupFormQuotation #company_id').val(company_id);
            $('#addFollowupFormQuotation #quotation_id').val(quotation_id);
            $('#addFollowupFormQuotation #company_name').val(company_name);
            $('#addFollowupFormQuotation #load_table').val('yes');


            if (quotationDetail) {
                quotationDetail = JSON.parse(decodeURIComponent(quotationDetail));
                // console.log(quotationDetail);

                let addFollowupQuotationHtml = null;

                if (quotation_id) {
                    addFollowupQuotationHtml = '<div class="row">';
                    if (quotationDetail?.quotation_no) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Quotation :- </b>' +
                            quotationDetail?.quotation_no + '</div>';
                    }
                    if (quotationDetail?.quotation_no) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Quotation Date :- </b>' + moment(
                            quotationDetail?.quotation_date).format(
                            'DD-MM-YYYY') + '</div>';
                    }
                    if (quotationDetail?.company_name) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                            quotationDetail?.company_name + '</div>';

                    }

                    if (quotationDetail?.customer_name) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                            quotationDetail?.customer_name + '</div>';
                    }
                    if (quotationDetail?.contact_person_name) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                            quotationDetail?.contact_person_name + '</div>';
                    }
                    if (quotationDetail?.mobile_no) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                            quotationDetail?.mobile_no + '">' +
                            quotationDetail?.mobile_no + '</a></div>';
                    }
                    if (quotationDetail?.email) {
                        addFollowupQuotationHtml += '<div class="col-md-6 col-sm-12"> <b>Email :- </b> <a href="mail:' +
                            quotationDetail?.email + '" target="_blank">' +
                            quotationDetail?.email + '</a> </div>';
                    }
                    if (quotationDetail?.whatsapp_no) {
                        addFollowupQuotationHtml +=
                            '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                            quotationDetail
                            ?.whatsapp_no + '?text=" target="_blank">' +
                            quotationDetail?.whatsapp_no + '</a> </div>';
                    }
                    addFollowupQuotationHtml += '</div>';
                    $(".showQuotationDetail").empty().html(addFollowupQuotationHtml).show();

                }
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-followup-status',
                beforeSend: function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Followup Status</option>";
                        let defaultSelected = false;

                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let selected = "";

                                if (typeof status_id !== "undefined" && status_id == item.id) {
                                    selected = "selected";
                                    defaultSelected = true;
                                }

                                if (!defaultSelected && item.is_default === "yes") {
                                    selected = "selected";
                                    defaultSelected = true;
                                }

                                options += "<option value='" + item.id + "' " + selected + ">" + item
                                    .followup_status + "</option>";
                            });

                            $('#addFollowupFormQuotation #status_id').empty();
                            $('#addFollowupFormQuotation #status_id').append(options);
                            $('#addFollowupFormQuotation #status_id').trigger('change');
                        }
                    }
                }
            });

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-followup-reason',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr(
                        'content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options = "<option value=''>Select Followup Reason</option>";
                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                options += "<option value='" + item.id + "'>" + item.followup_reason +
                                    "</option>";
                            });
                        }

                        $('#addFollowupFormQuotation #reason_id_quotation').empty();
                        $('#addFollowupFormQuotation #reason_id_quotation').append(options);
                        $('.modal').modal('hide');

                        // console.log("781", moment(new Date()).format(inputDateFormat));

                        // $('#addQuotationFollowupModal').find('input[id="date_time"]').val(moment().format(inputDateFormat));
                        $('#addQuotationFollowupModal').find('input[id="date_time"]').attr("value", moment()
                            .format(
                                inputDateFormat));
                        $('#addQuotationFollowupModal').find('input[id="date_time"]').attr("min", moment()
                            .subtract(30,
                                'minutes').format(inputDateFormat));
                        $('#addQuotationFollowupModal').modal('show');
                    }
                }
            });
        }


        $(document).on('click', '#addFollowupFormQuotationSubmit', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('#addFollowupFormQuotation .required').each(function() {
                var value = $(this).val();
                if (value === '') {
                    $(this).addClass('is-invalid');
                    isValid = false;
                    toastr.error($(this).attr("placeholder") + " is required");
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (isValid) {
                $('#addFollowupFormQuotationSubmit').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#addFollowupFormQuotation')[0]);
                // alert('sfsdf');
                // console.log("formData 845", formData);
                // formData.set('date_time', moment(formData?.date_time).format("YYYY-MM-DD HH:MM"));
                // console.log("formData 847", formData);
                // return true;

                $.ajax({
                    url: "{{ route('quotation-store') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {

                        $('#addFollowupFormQuotationSubmit').attr('disabled', false).text('Submit');

                        if (response.status == true) {
                            let load_table = $('#addFollowupFormQuotation #load_table').val();
                            toastr.success(response.message);
                            $('#addQuotationFollowupModal').modal('hide');

                            if (load_table == 'no') {
                                dtable.ajax.reload();
                            } else {
                                let company_id = $('#addFollowupFormQuotation #company_id').val();
                                let quotation_id = $('#addFollowupFormQuotation #quotation_id').val();
                                let company_name = $('#addFollowupFormQuotation #company_name').val();

                                $('#addFollowupFormQuotation #load_table').val('no');
                                $('#listFollowupModalQuotation #company_id').val(company_id);
                                $('#listFollowupModalQuotation #quotation_id').val(quotation_id);
                                $('#listFollowupModalQuotation #company_name').val(company_name);
                                $('#all-followup-list-quotation tbody').empty();
                                $('#addFollowupFormQuotation')[0].reset();
                                // loadFollowupList(company_id, quotation_id, company_name);
                                dtable.draw();
                            }
                        } else {
                            toastr.success(response?.message ?? 'Something wan/\'t wrong');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#addFollowupFormQuotationSubmit').attr('disabled', false).text('Submit');
                        $('.text-danger').remove();
                        if (xhr.status === false) {
                            let errors = xhr.responseJSON.errors;
                            for (let field in errors) {
                                if (errors.hasOwnProperty(field)) {
                                    $('#' + field).after(
                                        '<div class="text-danger error_laravel">' +
                                        errors[field].join('<br>') + '</div>');
                                }
                            }
                        } else {
                            toastr.error(xhr.responseJSON.message ||
                                'An unexpected error occurred.');
                        }
                    }
                });
            }
        });

        async function getFollowupStatus(company_id = null) {
            if (!company_id) {
                company_id = $(".search_by_company").val();
            }

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-followup-status',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]')
                        .attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        followupStatus = response?.data;
                    }
                }
            });

        }
        // Prevent  modal closing by clicking outside
        $(document).ready(function() {
            $('#responseQuotationModal').modal({
                backdrop: 'static',
                keyboard: true
            });
            $('#listFollowupModalQuotation').modal({
                backdrop: 'static',
                keyboard: true,
                show: false
            });
            $('#addQuotationFollowupModal').modal({
                backdrop: 'static',
                keyboard: true,
                show: false
            });
        });

        // When modal is shown
        $('#addQuotationFollowupModal').on('shown.bs.modal', function() {
            $('#reason_id_quotation').select2({
                dropdownParent: $('#addQuotationFollowupModal')
            });
            $('#through_quotation').select2({
                dropdownParent: $('#addQuotationFollowupModal')
            });
            $('.status_id').select2({
                dropdownParent: $('#addQuotationFollowupModal')
            });
            $('#quotation_status').select2({
                dropdownParent: $('#addQuotationFollowupModal')
            });
        });
        $('#responseQuotationModal').on('shown.bs.modal', function() {
            $('#followup_action_quotation').select2({
                dropdownParent: $('#responseQuotationModal')
            });
            $('.responseModalstatus_id').select2({
                dropdownParent: $('#responseQuotationModal')
            });

        });
    </script>
@endpush
