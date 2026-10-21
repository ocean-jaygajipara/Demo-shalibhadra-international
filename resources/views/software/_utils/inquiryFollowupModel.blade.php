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

<div class="modal fade" id="listFollowupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="exampleModalLabel4">Followup List</h5>
                <div>
                    @if (isset($modules['followup_add']) && $modules['followup_add'])
                        <button type="button" class="btn btn-primary me-2" id="addFollowupBtnInquiry">
                            Add Followup
                        </button>
                    @endif

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <input type="hidden" id="company_id" name="company_id" value="">
            <input type="hidden" id="inquiry_id" name="inquiry_id" value="">
            <input type="hidden" id="company_name" name="company_name" value="">
            <div class="modal-body">
                <div class="showFollowupInquiryDetail" style="d-none">
                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <b>Inquiry :- </b>
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
                    <table id="all-followup-list" class="dt-responsive table table-hover">
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

<div class="modal fade" id="responseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <form name="FollowupResponseForm" id="FollowupResponseForm" method="POST">
                @csrf
                <input type="hidden" name="followup_id" id="followup_id">
                <input type="hidden" name="company_id" id="company_id">
                <input type="hidden" name="inquiry_id" id="inquiry_id">
                <input type="hidden" name="company_name" id="company_name">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Add Followup Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="showInquiryDetailInResponseModal mb-4" style="display: none;"></div>

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
                                name="followup_action" id="followup_action" required>
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
                    <button type="button" id="add_followup_response" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="addFollowupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <form name="addFollowupForm" id="addFollowupForm" method="POST">
                @csrf
                <input type="hidden" name="inquiry_id" id="inquiry_id">
                <input type="hidden" name="company_id" id="company_id">
                <input type="hidden" name="company_name" id="company_name">
                <input type="hidden" name="load_table" id="no">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Add Follow Up for Inquiry <span
                            id="inquiry_no"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="showInquiryDetail mb-4" style="display:none;"></div>
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
                            <select id="reason_id" name="reason_id" class="form-select required">
                                <option value="">Followup reason</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label" for="through">Follow Up Through <span
                                    class="text-danger">*</span></label>
                            <select id="through" name="through" class="form-select required">
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
                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label" for="status_id">Follow Up Status <span
                                    class="text-danger">*</span></label>
                            <select id="status_id" name="status_id" class="form-select select2 required status_id">
                                <option value="">Select Followup Status</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label" for="inquiry_status">Inquiry Status <span
                                    class="text-danger">*</span></label>
                            <select id="inquiry_status" name="inquiry_status" class="form-select select2 required">
                                <option value="">Select Inquiry Status</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="submit" class="btn btn-primary">Add Followup</button>
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
    {{--
<script>
    let lastFollowupStatusId = null; // Store last used status_id

    // Handle submit of Add Followup
    $('#submit').on('click', function () {
        // You should validate and submit the form via AJAX here.
        // After successful add, hide current modal and show the response modal
        $('#addFollowupModal').modal('hide');

        // Optional: You can pre-fill data in response modal here if needed
        $('#responseModal').modal('show');
    });

    // Handle submit of Followup Response
    $('#add_followup_response').on('click', function () {
        const selectedStatusId = $('#responseModal #status_id').val();
        if (selectedStatusId) {
            lastFollowupStatusId = selectedStatusId;
        }

        // After successful response, close the modal
        $('#responseModal').modal('hide');
    });

    // When Add Followup modal opens again
    $('#addFollowupModal').on('show.bs.modal', function () {
        const followupSelect = $('#addFollowupModal #status_id');

        if (lastFollowupStatusId) {
            followupSelect.val(lastFollowupStatusId).trigger('change');
        } else {
            followupSelect.val('').trigger('change'); // default
        }
    });
</script> --}}
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

        let followupStatus = null;


        $(document).on('click', '.list-followup', function() {
            var id = $(this).data('id');
            var inquiry_id = $(this).data('inquiry_id');
            var company_id = $(this).data('company_id');
            var company_name = $(this).data('company_name');
            if (company_id && inquiry_id) {
                loadFollowupList(company_id, inquiry_id, company_name);
            }
        });

        $(document).on('click', '.followup-list', function() {
            var inquiry_id = $(this).data('inquiry_id');
            var company_id = $(this).data('company_id');
            var company_name = $(this).data('company_name');
            $('#listFollowupModal #company_id').val(company_id);
            $('#listFollowupModal #inquiry_id').val(inquiry_id);
            $('#listFollowupModal #company_name').val(company_name);

            $('#all-followup-list tbody').empty();

            loadFollowupList(company_id, inquiry_id, company_name);
        });

        function loadFollowupList(company_id, inquiry_id, company_name = null) {
            $('#listFollowupModal #company_id').val(company_id);
            $('#listFollowupModal #inquiry_id').val(inquiry_id);
            $('#listFollowupModal #company_name').val(company_name);

            getFollowupStatus(company_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('get-followup') }}",
                data: {
                    company_id: company_id,
                    inquiry_id: inquiry_id,
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {
                    if (response.status == true) {
                        let followupInquiryDetail = null;
                        if (response?.extraData?.inquiry) {
                            followupInquiryDetail = response?.extraData?.inquiry;
                            window.followupInquiryDetail = followupInquiryDetail;

                            let followupInquiryHtml = null;

                            if (followupInquiryDetail?.id) {
                                followupInquiryHtml = '<div class="row">';
                                if (followupInquiryDetail?.inquiry_no) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Inquiry :- </b>' +
                                        followupInquiryDetail?.inquiry_no + '</div>';
                                }
                                if (followupInquiryDetail?.inquiry_no) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Inquiry Date :- </b>' + moment(
                                            followupInquiryDetail?.inquiry_date).format(
                                            'DD-MM-YYYY') + '</div>';
                                }
                                if (followupInquiryDetail?.company?.company_name) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                                        followupInquiryDetail?.company?.company_name + '</div>';
                                }
                                if (followupInquiryDetail?.customer_name) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                                        followupInquiryDetail?.customer_name + '</div>';
                                }
                                if (followupInquiryDetail?.contact_person_name) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                                        followupInquiryDetail?.contact_person_name + '</div>';
                                }
                                if (followupInquiryDetail?.mobile_no) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                                        followupInquiryDetail?.mobile_no + '">' +
                                        followupInquiryDetail?.mobile_no + '</a></div>';
                                }
                                if (followupInquiryDetail?.email) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Email :- </b> ' +
                                        '<a href="https://mail.google.com/mail/?view=cm&fs=1&to=' +
                                        encodeURIComponent(followupInquiryDetail.email) + '" target="_blank">' +
                                        followupInquiryDetail.email + '</a> </div>';
                                }

                                if (followupInquiryDetail?.whatsapp_no) {
                                    followupInquiryHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                                        followupInquiryDetail?.whatsapp_no + '?text=" target="_blank">' +
                                        followupInquiryDetail?.whatsapp_no + '</a> </div>';
                                }

                                if (followupInquiryDetail?.inquiry_status?.name) {

                                    let inquiry_status_color = followupInquiryDetail.inquiry_status?.status_color;
                                    let inquiry_status_color_2 = followupInquiryDetail.inquiry_status?.status_color_2 ??
                                    inquiry_status_color;
                                    let inquiry_text_color = followupInquiryDetail.inquiry_status?.text_color ?? '#000';

                                    followupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Inquiry Status :- </b><span class="badge bg-glow" style="border: 0px; background: linear-gradient(45deg, ' +
                                    inquiry_status_color + ', ' +
                                    inquiry_status_color_2 + ');color: ' +
                                    inquiry_text_color + ';">'+followupInquiryDetail.inquiry_status.name +'</span></div>';
                                }

                                followupInquiryHtml += '</div>';

                            }
                            $(".showFollowupInquiryDetail").empty().html(followupInquiryHtml).show();

                            followupInquiryDetail = encodeURIComponent(JSON.stringify(followupInquiryDetail));
                            $("#addFollowupBtnInquiry").attr('data-inquiry', followupInquiryDetail);
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
                                    .company_name + '" data-inquiry_id="' + value.inquiry_id +
                                    '" data-id="' + value.id + '" data-company_id="' + value
                                    .company_id + '" data-status_id="' + value.status_id +
                                    '" class="showresponsemodal btn btn-primary btn-sm"';
                                if (followupInquiryDetail) {
                                    rows += "data-inquiry='" + followupInquiryDetail +
                                        "'";
                                }
                                rows += '>Response</button></td>';
                                let followup_status_color = value?.followup_status_color;
                                let followup_status_color_2 = value?.followup_status_color_2 ??
                                    followup_status_color;
                                let followup_text_color = value?.followup_text_color ?? '#000';
                                trFollowupStatus =
                                    '<button type="button" class="btn btn-sm w-100" style="background: linear-gradient(45deg, ' +
                                    followup_status_color + ', ' +
                                    followup_status_color_2 + ');color: ' +
                                    followup_text_color + ';">' + value?.followup_status ??
                                    '-' + '</button>'
                            } else {
                                rows += '<td class="w-20 text-wrap">' + (value.response_details ?? '') +
                                    '</td>';
                                /*
                                    let btn =
                                        '<button type="button" class="btn btn-sm w-100 dropdown-toggle waves-effect waves-light" style="background-color: ' +
                                        value?.followup_status_color +
                                        '; color: #000;" data-bs-toggle="dropdown" aria-expanded="false">' +
                                        value?.followup_status + '</button>';*/
                                let followup_status_color = value?.followup_status_color;
                                let followup_status_color_2 = value?.followup_status_color_2 ??
                                    followup_status_color;
                                let followup_text_color = value?.followup_text_color ?? '#000';

                                let btn =
                                    '<button type="button" class="btn btn-sm w-100 dropdown-toggle waves-effect waves-light" style="background: linear-gradient(45deg, ' +
                                    followup_status_color + ', ' +
                                    followup_status_color_2 + '); color: ' + followup_text_color +
                                    ';" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    value?.followup_status + '</button>';

                                let dropdown = '<ul class="dropdown-menu">';
                                if (followupStatus.length > 0) {
                                    $.each(followupStatus, function(followupStatus_key,
                                        followupStatus_value) {

                                        followup_status_color = followupStatus_value
                                            ?.status_color;
                                        followup_status_color_2 = followupStatus_value
                                            ?.status_color_2 ?? followup_status_color;
                                        followup_text_color = followupStatus_value
                                            ?.text_color ?? '#000';

                                        // dropdown += '<li><a href="javascript:void(0)" class="dropdown-item waves-effect update-status" data-url="' + route($modules['route'] + '.status-update') + '" data-id="' +htmlspecialchars($row["id"]) + '" data-update_status="' + htmlspecialchars($fstatus->id) + '">'+ htmlspecialchars($fstatus->name) .'</a> </li>';
                                        let followupStatusUpdateRoute =
                                            @json(route('followup.status-update'));
                                        // style="background: linear-gradient(45deg, ' + followup_status_color + ', ' + followup_status_color_2 + ');"
                                        // style=" color: ' + followup_text_color + ';"

                                        dropdown +=
                                            '<li><a href="javascript:void(0)" class="dropdown-item waves-effect update-status" data-url="' +
                                            followupStatusUpdateRoute + '" data-id="' + value
                                            ?.id + '" data-update_status="' +
                                            followupStatus_value?.id +
                                            '" modelReload="#listFollowupModal">' +
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
                        if ($.fn.DataTable.isDataTable('#all-followup-list')) {
                            $('#all-followup-list').DataTable().clear().destroy();
                        }
                        $('#all-followup-list tbody').html(rows);
                        // Initialize DataTable
                        $('#all-followup-list').DataTable({
                            responsive: true,
                            autoWidth: false,
                            searching: false,
                            lengthChange: false,
                            pageLength: 10
                        });

                        $('#listFollowupModal').modal('show');
                    } else {
                        console.log(response.message);
                    }
                }
            });
        }

        $(document).on('click', '.showresponsemodal', function() {

            var id = $(this).data('id');
            var company_id = $(this).data('company_id');
            var status_id = $(this).data('status_id');
            var inquiry_id = $(this).data('inquiry_id');
            var company_name = $(this).data('company_name');
            let inquiryDetail = (window?.followupInquiryDetail) ? window.followupInquiryDetail : $(this).attr(
                'data-inquiry');

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

                        $("#responseModal #status_id").empty();
                        $("#responseModal #status_id").append(options);
                        $('#responseModal #company_id').val(company_id);
                        $('#responseModal #followup_id').val(id);
                        $('#responseModal #inquiry_id').val(inquiry_id);
                        $('#responseModal #company_name').val(company_name);
                        $('#responseModal #status_id').val(status_id);
                        $('#responseModal #status_id').trigger('change');

                        if (typeof inquiryDetail == 'object') {
                            inquiryDetail = JSON.stringify(inquiryDetail);
                        }

                        $("#add_followup_response").attr('data-inquiry', inquiryDetail);
                        inquiryDetail = JSON.parse(decodeURIComponent(inquiryDetail));
                        let showInquiryDetailHtml = null;

                        if (inquiryDetail?.id) {
                            showInquiryDetailHtml = '<div class="row">';
                            if (inquiryDetail?.inquiry_no) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Inquiry :- </b>' +
                                    inquiryDetail?.inquiry_no + '</div>';
                            }
                            if (inquiryDetail?.inquiry_no) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Inquiry Date :- </b>' + moment(
                                        inquiryDetail?.inquiry_date).format(
                                        'DD-MM-YYYY') + '</div>';
                            }
                            if (inquiryDetail?.company?.company_name) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                                    inquiryDetail?.company?.company_name + '</div>';
                            }
                            if (inquiryDetail?.customer_name) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                                    inquiryDetail?.customer_name + '</div>';
                            }
                            if (inquiryDetail?.contact_person_name) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                                    inquiryDetail?.contact_person_name + '</div>';
                            }
                            if (inquiryDetail?.mobile_no) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                                    inquiryDetail?.mobile_no + '">' +
                                    inquiryDetail?.mobile_no + '</a></div>';
                            }
                            if (inquiryDetail?.email) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Email :- </b> ' +
                                    '<a href="https://mail.google.com/mail/?view=cm&fs=1&to=' +
                                    encodeURIComponent(inquiryDetail.email) + '" target="_blank">' +
                                    inquiryDetail.email + '</a> </div>';
                            }

                            if (inquiryDetail?.whatsapp_no) {
                                showInquiryDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                                    inquiryDetail
                                    ?.whatsapp_no + '?text=" target="_blank">' +
                                    inquiryDetail?.whatsapp_no + '</a> </div>';
                            }
                            showInquiryDetailHtml += '</div>';
                            $(".showInquiryDetailInResponseModal").empty().html(showInquiryDetailHtml)
                                .show();

                        }

                        $('#responseModal').modal('show');
                        $('#listFollowupModal').modal('hide');


                    }
                }
            });
        });

        $('.hide_action').hide();

        $('#followup_action').on('change', function() {
            const selectedValue = $(this).val();

            if (selectedValue === 'Next Follow-up' || selectedValue === 'End Follow-up') {
                $('.hide_action').show();
            } else {
                $('.hide_action').hide();
            }
        });

        $(document).on('click', '#add_followup_response', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('.error_laravel').remove();

            $('#FollowupResponseForm .required').each(function() {
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
                $('#add_followup_response').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#FollowupResponseForm')[0]);
                let followup_action = $('#followup_action').find('option:selected').val();

                $('#add_followup_response').prop('disabled', true);
                $('#add_followup_response').attr('disabled', 'disabled');
                $('#add_followup_response').css({
                    'background': '#514ff1',
                    'opacity': '0.65',
                    'cursor': 'none'
                });

                $.ajax({
                    url: "{{ route('followup.store_response') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#add_followup_response').attr('disabled', false).text('Submit');
                        $('#add_followup_response').css({
                            'background': '',
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                        if (response.status == true) {
                            followup_selected_status = $('#FollowupResponseForm #status_id')
                                .val();
                            var followup_id = $('#FollowupResponseForm #followup_id').val();
                            var company_id = $('#FollowupResponseForm #company_id').val();
                            var inquiry_id = $('#FollowupResponseForm #inquiry_id').val();
                            var company_name = $('#FollowupResponseForm #company_name').val();

                            $('#FollowupResponseForm')[0].reset();
                            toastr.success(response?.message);
                            $('#responseModal').modal('hide');

                            if (followup_action === "Next Follow-up") {

                                let inquiryDetail = $('#add_followup_response').attr('data-inquiry');
                                addFollowup(company_id, inquiry_id, company_name, inquiryDetail);
                                return;
                            }
                            loadFollowupList(company_id, inquiry_id, company_name);
                            return;
                            dtable.draw();
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
            const inquiry_id = $(this).data('id');
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#addFollowupForm #company_id').val(company_id);
            $('#addFollowupForm #company_name').val(company_name);
            $('#addFollowupForm #inquiry_id').val(inquiry_id);

            let inquiryDetail = $(this).attr('data-inquiry');

            addFollowup(company_id, inquiry_id, company_name, inquiryDetail);
        });

        $(document).on('click', '#addFollowupBtnInquiry', function() {
            let company_id = $('#listFollowupModal #company_id').val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }
            let inquiry_id = $('#listFollowupModal #inquiry_id').val();
            let company_name = $('#listFollowupModal #company_name').val();
            let inquiryDetail = $(this).attr('data-inquiry');

            addFollowup(company_id, inquiry_id, company_name, inquiryDetail);
        });

        function addFollowup(company_id, inquiry_id, company_name, inquiryDetail) {
            $('#addFollowupForm #company_id').val(company_id);
            $('#addFollowupForm #inquiry_id').val(inquiry_id);
            $('#addFollowupForm #company_name').val(company_name);
            $('#addFollowupForm #load_table').val('yes');


            let currentInquiryStatus = null;
            let currentFollowupStatusId = null;
            if (inquiryDetail) {
                inquiryDetail = JSON.parse(decodeURIComponent(inquiryDetail));

                let addFollowupInquiryHtml = null;
                currentInquiryStatus = inquiryDetail?.inquiry_status ?? null;
                currentFollowupStatusId = inquiryDetail?.followup_status_id ?? null;
                if (inquiryDetail?.id) {
                    addFollowupInquiryHtml = '<div class="row">';
                    if (inquiryDetail?.inquiry_no) {
                        addFollowupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Inquiry :- </b>' +
                            inquiryDetail?.inquiry_no + '</div>';
                    }
                    if (inquiryDetail?.inquiry_no) {
                        addFollowupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Inquiry Date :- </b>' + moment(
                            inquiryDetail?.inquiry_date).format(
                            'DD-MM-YYYY') + '</div>';
                    }
                    if (inquiryDetail?.company?.company_name) {
                        addFollowupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                            inquiryDetail?.company?.company_name + '</div>';
                    }
                    if (inquiryDetail?.customer_name) {
                        addFollowupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                            inquiryDetail?.customer_name + '</div>';
                    }
                    if (inquiryDetail?.contact_person_name) {
                        addFollowupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                            inquiryDetail?.contact_person_name + '</div>';
                    }
                    if (inquiryDetail?.mobile_no) {
                        addFollowupInquiryHtml += '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                            inquiryDetail?.mobile_no + '">' +
                            inquiryDetail?.mobile_no + '</a></div>';
                    }
                    if (inquiryDetail?.email) {
                        addFollowupInquiryHtml +=
                            '<div class="col-md-6 col-sm-12"> <b>Email :- </b>' +
                            '<a href="https://mail.google.com/mail/?view=cm&fs=1&to=' +
                            encodeURIComponent(inquiryDetail.email) + '" target="_blank">' +
                            inquiryDetail.email + '</a></div>';
                    }

                    if (inquiryDetail?.whatsapp_no) {
                        addFollowupInquiryHtml +=
                            '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                            inquiryDetail
                            ?.whatsapp_no + '?text=" target="_blank">' +
                            inquiryDetail?.whatsapp_no + '</a> </div>';
                    }
                    addFollowupInquiryHtml += '</div>';
                    $(".showInquiryDetail").empty().html(addFollowupInquiryHtml).show();

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


                                if (currentFollowupStatusId && currentFollowupStatusId == item?.id) {
                                    selected = "selected";
                                    defaultSelected = true;
                                }else if (!currentFollowupStatusId && !defaultSelected && item.is_default === "yes") {
                                    selected = "selected";
                                    defaultSelected = true;
                                }

                                options += "<option value='" + item.id + "' " + selected + ">" + item
                                    .followup_status + "</option>";
                            });

                            $('#addFollowupForm #status_id').empty();
                            $('#addFollowupForm #status_id').append(options);
                            $('#addFollowupForm #status_id').trigger('change');
                        }
                    }
                }
            });

            $.ajax({
                type: 'POST',
                url: '{{ env('API_URL') }}get-inquiry-status',
                'beforeSend': function(request) {
                    request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                data: {
                    company_id: company_id,
                },
                success: function(response) {
                    if (response.status) {
                        let options =
                            "<option value=''>Select Inquiry Status</option>";
                        let defaultSelected = false;

                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                let selected = "";

                                // If a value is passed and matches the item ID
                                if (typeof inquiry_status !== "undefined" &&
                                    inquiry_status == item.id) {
                                    selected = "selected";
                                    defaultSelected = true;
                                }

                                // If no match yet and this item is default
                                if (currentInquiryStatus && currentInquiryStatus?.id && currentInquiryStatus?.id == item?.id) {
                                    selected = "selected";
                                    defaultSelected = true;
                                }else if (!currentInquiryStatus == null && !defaultSelected && item.is_default ===
                                    "yes") {
                                    selected = "selected";
                                    defaultSelected = true;
                                }

                                options +=
                                    `<option value="${item.id}" ${selected}>${item.inquiry_status}</option>`;
                            });

                            $('#addFollowupForm #inquiry_status').empty().append(
                                options).trigger('change');
                        }

                        inquiry_status = options;
                        $('#addFollowupForm #inquiry_status').empty();
                        $('#addFollowupForm #inquiry_status').append(options);
                        $('#addFollowupForm #inquiry_status').trigger('change');
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

                        $('#addFollowupForm #reason_id').empty();
                        $('#addFollowupForm #reason_id').append(options);
                        $('.modal').modal('hide');

                        // console.log("781", moment(new Date()).format(inputDateFormat));

                        // $('#addFollowupModal').find('input[id="date_time"]').val(moment().format(inputDateFormat));
                        $('#addFollowupModal').find('input[id="date_time"]').attr("value", moment().format(
                            inputDateFormat));
                        $('#addFollowupModal').find('input[id="date_time"]').attr("min", moment().subtract(30,
                            'minutes').format(inputDateFormat));
                        $('#addFollowupModal').modal('show');
                    }
                }
            });
        }


        $(document).on('click', '#submit', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('#addFollowupForm .required').each(function() {
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
                $('#submit').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#addFollowupForm')[0]);

                // console.log("formData 845", formData);
                // formData.set('date_time', moment(formData?.date_time).format("YYYY-MM-DD HH:MM"));
                // console.log("formData 847", formData);
                // return true;

                $.ajax({
                    url: "{{ route('followup.store') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {

                        $('#submit').attr('disabled', false).text('Submit');

                        if (response.status == true) {
                            let load_table = $('#addFollowupForm #load_table').val();
                            toastr.success(response.message);
                            $('#addFollowupModal').modal('hide');

                            if (load_table == 'no') {
                                dtable.ajax.reload();
                            } else {
                                let company_id = $('#addFollowupForm #company_id').val();
                                let inquiry_id = $('#addFollowupForm #inquiry_id').val();
                                let company_name = $('#addFollowupForm #company_name').val();

                                $('#addFollowupForm #load_table').val('no');
                                $('#listFollowupModal #company_id').val(company_id);
                                $('#listFollowupModal #inquiry_id').val(inquiry_id);
                                $('#listFollowupModal #company_name').val(company_name);
                                $('#all-followup-list tbody').empty();
                                $('#addFollowupForm')[0].reset();
                                loadFollowupList(company_id, inquiry_id, company_name);
                                dtable.draw();
                            }
                        } else {
                            toastr.success(response?.message ?? 'Something wan/\'t wrong');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#submit').attr('disabled', false).text('Submit');
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
            $('#responseModal').modal({
                backdrop: 'static',
                keyboard: true
            });
            $('#listFollowupModal').modal({
                backdrop: 'static',
                keyboard: true,
                show: false
            });
            $('#addFollowupModal').modal({
                backdrop: 'static',
                keyboard: true,
                show: false
            });
        });

        // When modal is shown
        $('#addFollowupModal').on('shown.bs.modal', function() {
            $('#reason_id').select2({
                dropdownParent: $('#addFollowupModal')
            });
            $('#through').select2({
                dropdownParent: $('#addFollowupModal')
            });
            $('.status_id').select2({
                dropdownParent: $('#addFollowupModal')
            });
            $('#inquiry_status').select2({
                dropdownParent: $('#addFollowupModal')
            });
        });
        $('#responseModal').on('shown.bs.modal', function() {
            $('#followup_action').select2({
                dropdownParent: $('#responseModal')
            });
            $('.responseModalstatus_id').select2({
                dropdownParent: $('#responseModal')
            });

        });
    </script>
@endpush
