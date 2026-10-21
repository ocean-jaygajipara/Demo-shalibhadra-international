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

<div class="modal fade" id="listFollowupModalCustomer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h5 class="modal-title" id="exampleModalLabel4">Followup List</h5>
                <div>
                    <button type="button" class="btn btn-primary me-2" id="addFollowupBtnCustomer">
                        Add Followup
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <input type="hidden" id="company_id" name="company_id" value="">
            <input type="hidden" id="customer_id" name="customer_id" value="">
            <input type="hidden" id="company_name" name="company_name" value="">
            <div class="modal-body">
                <div class="showFollowupCustomerDetail" style="d-none">
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
                    <table id="all-followup-list-customer" class="dt-responsive table table-hover">
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

<div class="modal fade" id="responseCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <form name="FollowupResponseFormCustomer" id="FollowupResponseFormCustomer" method="POST">
                @csrf
                <input type="hidden" name="followup_id" id="followup_id">
                <input type="hidden" name="company_id" id="company_id">
                <input type="hidden" name="customer_id" id="customer_id">
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
                                name="followup_action" id="followup_action_customer" required>
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
                    <button type="button" id="add_followup_responseCustomer"
                        class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="addCustomerFollowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <form name="addFollowupFormCustomer" id="addFollowupFormCustomer" method="POST">
                @csrf
                <input type="hidden" name="customer_id" id="customer_id">
                <input type="hidden" name="company_id" id="company_id">
                <input type="hidden" name="company_name" id="company_name">
                <input type="hidden" name="load_table" id="no">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Add Follow Up for Customer <span
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
                            <select id="reason_id_customer" name="reason_id" class="form-select select2 required"
                                placeholder="Followup reason">
                                <option value="">Followup reason</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 mb-2">
                            <label class="form-label" for="through">Follow Up Through <span
                                    class="text-danger">*</span></label>
                            <select id="through_customer" name="through" class="form-select required"
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
                    <button type="button" id="addFollowupFormCustomerSubmit" class="btn btn-primary">Add
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


        $(document).on('click', '.list-followup-customer', function() {
            var id = $(this).data('id');
            var customer_id = $(this).data('customer_id');
            var company_id = $(this).data('company_id');
            var company_name = $(this).data('company_name');
            if (company_id && customer_id) {
                loadFollowupListCustmer(company_id, customer_id, company_name);
            }
        });

        $(document).on('click', '.followup-list-customer', function() {
            var customer_id = $(this).data('customer_id');
            var company_id = $(this).data('company_id');
            var company_name = $(this).data('company_name');
            $('#listFollowupModalCustomer #company_id').val(company_id);
            $('#listFollowupModalCustomer #customer_id').val(customer_id);
            $('#listFollowupModalCustomer #company_name').val(company_name);

            $('#all-followup-list-customer tbody').empty();

            loadFollowupListCustmer(company_id, customer_id, company_name);
        });

        function loadFollowupListCustmer(company_id, customer_id, company_name = null) {
            $('#listFollowupModalCustomer #company_id').val(company_id);
            $('#listFollowupModalCustomer #customer_id').val(customer_id);
            $('#listFollowupModalCustomer #company_name').val(company_name);

            getFollowupStatus(company_id);

            $.ajax({
                type: 'POST',
                url: "{{ route('get-followup-customer') }}",
                data: {
                    company_id: company_id,
                    customer_id: customer_id,
                    _token: '{{ csrf_token() }}',
                },
                success: function(response) {


                    if (response.status == true) {

                        let followupCustomerDetail = null;
                        if (response?.extraData?.customers) {



                            followupCustomerDetail = response?.extraData?.customers;
                            console.log(followupCustomerDetail);

                            console.log(followupCustomerDetail);
                            window.followupCustomerDetail = followupCustomerDetail;

                            let followupCustomerHtml = null;


                            if (followupCustomerDetail?.id) {

                                followupCustomerHtml = '<div class="row">';
                                if (followupCustomerDetail?.client_code) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Client Code :- </b>' +
                                        followupCustomerDetail?.client_code + '</div>';

                                }

                                if (followupCustomerDetail?.company?.company_name) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                                        followupCustomerDetail?.company?.company_name + '</div>';
                                }
                                if (followupCustomerDetail?.customer_name) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                                        followupCustomerDetail?.customer_name + '</div>';
                                }
                                if (followupCustomerDetail?.contact_person_name) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                                        followupCustomerDetail?.contact_person_name + '</div>';
                                }
                                if (followupCustomerDetail?.mobile_number) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                                        followupCustomerDetail?.mobile_number + '">' +
                                        followupCustomerDetail?.mobile_number + '</a></div>';
                                }
                                if (followupCustomerDetail?.email) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Email :- </b> <a href="mail:' +
                                        followupCustomerDetail?.email + '" target="_blank">' +
                                        followupCustomerDetail?.email + '</a> </div>';
                                }
                                if (followupCustomerDetail?.whatsapp_no) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                                        followupCustomerDetail?.whatsapp_no + '?text=" target="_blank">' +
                                        followupCustomerDetail?.whatsapp_no + '</a> </div>';
                                }
                                if (followupCustomerDetail?.customers_status?.name) {
                                    followupCustomerHtml +=
                                        '<div class="col-md-6 col-sm-12"> <b>Quotation Status :- </b>' +
                                        followupCustomerDetail.customers_status.name +
                                        '</div>';
                                }

                                followupCustomerHtml += '</div>';

                            }
                            $(".showFollowupCustomerDetail").empty().html(followupCustomerHtml).show();

                            followupCustomerDetail = encodeURIComponent(JSON.stringify(
                                followupCustomerDetail));
                            $("#addFollowupBtnCustomer").attr('data-customer', followupCustomerDetail);
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
                                    .company_name + '" data-customer_id="' + value.customer_id +
                                    '" data-id="' + value.id + '" data-company_id="' + value
                                    .company_id + '" data-status_id="' + value.status_id +
                                    '" class="showResponseCustomerModal btn btn-primary btn-sm"';
                                if (followupCustomerDetail) {
                                    rows += "data-customer='" + followupCustomerDetail +
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
                                            '" modelReload="#listFollowupModalCustomer">' +
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
                        if ($.fn.DataTable.isDataTable('#all-followup-list-customer')) {
                            $('#all-followup-list-customer').DataTable().clear().destroy();
                        }
                        $('#all-followup-list-customer tbody').html(rows);
                        // Initialize DataTable
                        $('#all-followup-list-customer').DataTable({
                            responsive: true,
                            autoWidth: false,
                            searching: false,
                            lengthChange: false,
                            pageLength: 10
                        });

                        $('#listFollowupModalCustomer').modal('show');
                    } else {
                        console.log(response.message);
                    }
                }
            });
        }

        $(document).on('click', '.showResponseCustomerModal', function() {

            var id = $(this).data('id');
            var company_id = $(this).data('company_id');
            var status_id = $(this).data('status_id');
            var customer_id = $(this).data('customer_id');
            var company_name = $(this).data('company_name');
            let customerDetail = (window?.followupCustomerDetail) ? window.followupCustomerDetail : $(this).attr(
                'data-customer');



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

                        $("#responseCustomerModal #status_id").empty();
                        $("#responseCustomerModal #status_id").append(options);
                        $('#responseCustomerModal #company_id').val(company_id);
                        $('#responseCustomerModal #followup_id').val(id);
                        $('#responseCustomerModal #customer_id').val(customer_id);
                        $('#responseCustomerModal #company_name').val(company_name);
                        $('#responseCustomerModal #status_id').val(status_id);
                        $('#responseCustomerModal #status_id').trigger('change');

                        if (typeof customerDetail == 'object') {
                            customerDetail = JSON.stringify(customerDetail);
                        }
                        console.log(customerDetail);

                        $("#add_followup_responseCustomer").attr('data-customer', customerDetail);
                        customerDetail = JSON.parse(decodeURIComponent(customerDetail));

                        let showCustomerDetailHtml = null;

                        if (customerDetail?.id) {
                            showCustomerDetailHtml = '<div class="row">';
                            if (customerDetail?.customer_no) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Quotation :- </b>' +
                                    customerDetail?.customer_no + '</div>';
                            }
                            if (customerDetail?.customer_no) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Quotation Date :- </b>' +
                                    moment(
                                        customerDetail?.customer_date).format(
                                        'DD-MM-YYYY') + '</div>';
                            }
                            if (customerDetail?.company?.company_name) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                                    customerDetail?.company?.company_name + '</div>';
                            }
                            if (customerDetail?.customer_name) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                                    customerDetail?.customer_name + '</div>';
                            }
                            if (customerDetail?.contact_person_name) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                                    customerDetail?.contact_person_name + '</div>';
                            }
                            if (customerDetail?.mobile_no) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                                    customerDetail?.mobile_no + '">' +
                                    customerDetail?.mobile_no + '</a></div>';
                            }
                            if (customerDetail?.email) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Email :- </b> <a href="mail:' +
                                    customerDetail?.email + '" target="_blank">' +
                                    customerDetail?.email + '</a> </div>';
                            }
                            if (customerDetail?.whatsapp_no) {
                                showCustomerDetailHtml +=
                                    '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                                    customerDetail
                                    ?.whatsapp_no + '?text=" target="_blank">' +
                                    customerDetail?.whatsapp_no + '</a> </div>';
                            }
                            showCustomerDetailHtml += '</div>';
                            $(".showQuotationDetailInResponseModal").empty().html(
                                    showCustomerDetailHtml)
                                .show();

                        }

                        $('#responseCustomerModal').modal('show');
                        $('#listFollowupModalCustomer').modal('hide');


                    }
                }
            });
        });

        $('.hide_action').hide();

        $('#followup_action_customer').on('change', function() {
            const selectedValue = $(this).val();

            if (selectedValue === 'Next Follow-up' || selectedValue === 'End Follow-up') {
                $('.hide_action').show();
            } else {
                $('.hide_action').hide();
            }
        });

        $(document).on('click', '#add_followup_responseCustomer', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('.error_laravel').remove();

            $('#FollowupResponseFormCustomer .required').each(function() {
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
                $('#add_followup_responseCustomer').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#FollowupResponseFormCustomer')[0]);
                let followup_action = $('#followup_action_customer').find('option:selected').val();

                $('#add_followup_responseCustomer').prop('disabled', true);
                $('#add_followup_responseCustomer').attr('disabled', 'disabled');
                $('#add_followup_responseCustomer').css({
                    'background': '#514ff1',
                    'opacity': '0.65',
                    'cursor': 'none'
                });

                $.ajax({
                    url: "{{ route('followup.store_response_customer') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#add_followup_responseCustomer').attr('disabled', false).text('Submit');
                        $('#add_followup_responseCustomer').css({
                            'background': '',
                            'opacity': '1',
                            'cursor': 'pointer'
                        });
                        if (response.status == true) {

                            followup_selected_status = $('#FollowupResponseFormCustomer #status_id')
                                .val();
                            var followup_id = $('#FollowupResponseFormCustomer #followup_id').val();
                            var company_id = $('#FollowupResponseFormCustomer #company_id').val();
                            var customer_id = $('#FollowupResponseFormCustomer #customer_id').val();
                            var company_name = $('#FollowupResponseFormCustomer #company_name').val();

                            $('#FollowupResponseFormCustomer')[0].reset();
                            toastr.success(response?.message);
                            $('#responseCustomerModal').modal('hide');

                            if (followup_action === "Next Follow-up") {

                                let customerDetail = $('#add_followup_responseCustomer').attr(
                                    'data-customer');

                                    // alert(followup_action);
                                    if (followup_action != 'End Follow-up') {
                                    addFollowupFormCustomer(company_id, customer_id, company_name,
                                        customerDetail);
                                    return;
                                }


                            }
                            // loadFollowupList(company_id, customer_id, company_name);
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
            const customer_id = $(this).data('id');
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $('#addFollowupFormCustomer #company_id').val(company_id);
            $('#addFollowupFormCustomer #company_name').val(company_name);
            $('#addFollowupFormCustomer #customer_id').val(customer_id);

            let customerDetail = $('.add-followup').attr('data-customer');

            addFollowupFormCustomer(company_id, customer_id, company_name, customerDetail);
        });

        $(document).on('click', '#addFollowupBtnCustomer', function() {
            let company_id = $('#listFollowupModalCustomer #company_id').val();
            if (!company_id && $('meta[name="company_id"]').attr('value')) {
                company_id = $('meta[name="company_id"]').attr('value');
            }
            let customer_id = $('#listFollowupModalCustomer #customer_id').val();
            let company_name = $('#listFollowupModalCustomer #company_name').val();
            let customerDetail = $('#addFollowupBtnCustomer').attr('data-customer');

            addFollowupFormCustomer(company_id, customer_id, company_name, customerDetail);
        });

        function addFollowupFormCustomer(company_id, customer_id, company_name, customerDetail) {
            $('#addFollowupFormCustomer #company_id').val(company_id);
            $('#addFollowupFormCustomer #customer_id').val(customer_id);
            $('#addFollowupFormCustomer #company_name').val(company_name);
            $('#addFollowupFormCustomer #load_table').val('yes');


            if (customerDetail) {
                customerDetail = JSON.parse(decodeURIComponent(customerDetail));
                // console.log(customerDetail);

                let addFollowupCustomerHtml = null;

                if (customer_id) {
                    addFollowupCustomerHtml = '<div class="row">';
                    if (customerDetail?.client_code) {
                        addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Client Code :- </b>' +
                            customerDetail?.client_code + '</div>';
                    }
                    // if (customerDetail?.client_code) {
                    //     addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Client Code :- </b>' + moment(
                    //         customerDetail?.client_code).format(
                    //         'DD-MM-YYYY') + '</div>';
                    // }
                    if (customerDetail?.company_name) {
                        addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Company Name :- </b>' +
                            customerDetail?.company_name + '</div>';

                    }

                    if (customerDetail?.customer_name) {
                        addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Customer Name :- </b>' +
                            customerDetail?.customer_name + '</div>';
                    }
                    if (customerDetail?.contact_person_name) {
                        addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Contact Person Name :- </b> ' +
                            customerDetail?.contact_person_name + '</div>';
                    }
                    if (customerDetail?.mobile_no) {
                        addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Mobile No :- </b> <a href="tel:' +
                            customerDetail?.mobile_no + '">' +
                            customerDetail?.mobile_no + '</a></div>';
                    }
                    if (customerDetail?.email) {
                        addFollowupCustomerHtml += '<div class="col-md-6 col-sm-12"> <b>Email :- </b> <a href="mail:' +
                            customerDetail?.email + '" target="_blank">' +
                            customerDetail?.email + '</a> </div>';
                    }
                    if (customerDetail?.whatsapp_no) {
                        addFollowupCustomerHtml +=
                            '<div class="col-md-6 col-sm-12"> <b>Whatsapp No :- </b> <a href="https://wa.me/' +
                            customerDetail
                            ?.whatsapp_no + '?text=" target="_blank">' +
                            customerDetail?.whatsapp_no + '</a> </div>';
                    }
                    addFollowupCustomerHtml += '</div>';
                    $(".showQuotationDetail").empty().html(addFollowupCustomerHtml).show();

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

                            $('#addFollowupFormCustomer #status_id').empty();
                            $('#addFollowupFormCustomer #status_id').append(options);
                            $('#addFollowupFormCustomer #status_id').trigger('change');
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

                        $('#addFollowupFormCustomer #reason_id_customer').empty();
                        $('#addFollowupFormCustomer #reason_id_customer').append(options);
                        $('.modal').modal('hide');

                        // console.log("781", moment(new Date()).format(inputDateFormat));

                        // $('#addCustomerFollowModal').find('input[id="date_time"]').val(moment().format(inputDateFormat));
                        $('#addCustomerFollowModal').find('input[id="date_time"]').attr("value", moment()
                            .format(
                                inputDateFormat));
                        $('#addCustomerFollowModal').find('input[id="date_time"]').attr("min", moment()
                            .subtract(30,
                                'minutes').format(inputDateFormat));
                        $('#addCustomerFollowModal').modal('show');
                    }
                }
            });
        }


        $(document).on('click', '#addFollowupFormCustomerSubmit', function() {
            var isValid = true;
            $('.is-invalid').removeClass('is-invalid');
            $('#addFollowupFormCustomer .required').each(function() {
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
                $('#addFollowupFormCustomerSubmit').attr('disabled', true).text('Submitting...');
                var formData = new FormData($('#addFollowupFormCustomer')[0]);
                // alert('sfsdf');
                // console.log("formData 845", formData);
                // formData.set('date_time', moment(formData?.date_time).format("YYYY-MM-DD HH:MM"));
                // console.log("formData 847", formData);
                // return true;

                $.ajax({
                    url: "{{ route('customer-store') }}",
                    type: "POST",
                    data: formData,
                    datatype: 'json',
                    processData: false,
                    contentType: false,
                    success: function(response) {

                        $('#addFollowupFormCustomerSubmit').attr('disabled', false).text('Submit');

                        if (response.status == true) {
                            let load_table = $('#addFollowupFormCustomer #load_table').val();
                            toastr.success(response.message);
                            $('#addCustomerFollowModal').modal('hide');

                            if (load_table == 'no') {
                                dtable.ajax.reload();
                            } else {
                                let company_id = $('#addFollowupFormCustomer #company_id').val();
                                let customer_id = $('#addFollowupFormCustomer #customer_id').val();
                                let company_name = $('#addFollowupFormCustomer #company_name').val();

                                $('#addFollowupFormCustomer #load_table').val('no');
                                $('#listFollowupModalCustomer #company_id').val(company_id);
                                $('#listFollowupModalCustomer #customer_id').val(customer_id);
                                $('#listFollowupModalCustomer #company_name').val(company_name);
                                $('#all-followup-list-customer tbody').empty();
                                $('#addFollowupFormCustomer')[0].reset();
                                // loadFollowupList(company_id, customer_id, company_name);
                                dtable.draw();
                            }
                        } else {
                            toastr.success(response?.message ?? 'Something wan/\'t wrong');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#addFollowupFormCustomerSubmit').attr('disabled', false).text('Submit');
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
            $('#responseCustomerModal').modal({
                backdrop: 'static',
                keyboard: true
            });
            $('#listFollowupModalCustomer').modal({
                backdrop: 'static',
                keyboard: true,
                show: false
            });
            $('#addCustomerFollowModal').modal({
                backdrop: 'static',
                keyboard: true,
                show: false
            });
        });

        // When modal is shown
        $('#addCustomerFollowModal').on('shown.bs.modal', function() {
            $('#reason_id_customer').select2({
                dropdownParent: $('#addCustomerFollowModal')
            });
            $('#through_customer').select2({
                dropdownParent: $('#addCustomerFollowModal')
            });
            $('.status_id').select2({
                dropdownParent: $('#addCustomerFollowModal')
            });
            $('#quotation_status').select2({
                dropdownParent: $('#addCustomerFollowModal')
            });
        });
        $('#responseCustomerModal').on('shown.bs.modal', function() {
            $('#followup_action_customer').select2({
                dropdownParent: $('#responseCustomerModal')
            });
            $('.responseModalstatus_id').select2({
                dropdownParent: $('#responseCustomerModal')
            });

        });
    </script>
@endpush
