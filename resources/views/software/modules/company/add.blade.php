@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    // dd($modules);
@endphp
@section('title', $page_title)


@section('content')
    <div class="px-1">
    <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                ['title' => (isset($edit) && $edit?->id) ? "Edit ".$page_title : "Create ".$page_title , 'url' => ''],
            ],
        ])
        <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}">
            <i class="menu-icon ti ti-chevrons-left"></i> Back
        </a>
    </div>
</div>
    <div class="card my-3 mb-4">
        {{-- <h5 class="card-header"></h5> --}}
        <div class="card-body">
            <form action="{{ route($route . '.store') }}" method="POST" enctype="multipart/form-data" id="companyAddForm">
                @csrf
                <input type="hidden" name="otp" value="" id="otp">
                <div class="row">
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> GST No </label>
                            <div class="input-group">
                                <input id="gst_no" type="text"
                                    class="form-control @error('gst_no') is-invalid @enderror" name="gst_no"
                                    value="{{ old('gst_no') }}" autocomplete="gst_no" placeholder="Enter GST No"
                                    id="gst_no" data-name="gst_no">
                                {{-- <button class="btn btn-outline-primary waves-effect" type="button" id="get_gst_details">Get Details</button> --}}
                            </div>
                            @error('gst_no')
                                <span class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> LUT Number </label>
                            <div class="input-group">
                                <input id="lut_number" type="text"
                                    class="form-control @error('lut_number') is-invalid @enderror" name="lut_number"
                                    value="{{ old('lut_number') }}" autocomplete="lut_number" placeholder="Enter LUT Number"
                                    id="lut_number" data-name="lut_number">
                            </div>
                            @error('lut_number')
                                <span class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> Company Name <span class="text-danger">*</span> </label>
                            <input id="company_name" type="text"
                                class="form-control @error('company_name') is-invalid @enderror required"
                                name="company_name" value="{{ old('company_name') }}" autocomplete="company_name"
                                placeholder="Enter company name" data-name="company_name">

                            @error('company_name')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> Person Name <span class="text-danger">*</span></label>
                            <input id="person_name" type="text"
                                class="form-control @error('person_name') is-invalid @enderror required" name="person_name"
                                value="{{ old('person_name') }}" autocomplete="person_name" placeholder="Enter person name"
                                data-name="person_name">

                            @error('person_name')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label"> WhatsApp Number (Use At Login) <span
                                    class="text-danger">*</span></label>
                            <input id="whatsapp_number" type="text"
                                class="length10 form-control @error('whatsapp_number') is-invalid @enderror required"
                                name="whatsapp_number" value="{{ old('whatsapp_number') }}" autocomplete="whatsapp_number"
                                placeholder="Enter whatsapp number" data-name="whatsapp_number"
                                onkeypress="return isNumber(event)">

                            @error('whatsapp_number')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Email Id <span class="text-danger">*</span></label>
                            <input id="email" type="text"
                                class="form-control @error('email') is-invalid @enderror required" name="email"
                                value="{{ old('email') }}" autocomplete="email" placeholder="Enter email id"
                                data-name="email">

                            @error('email')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Password <span class="text-danger">*</span></label>

                            <div class="input-group input-group-merge">
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror required" name="password"
                                    value="{{ old('password') }}" placeholder="Enter password" aria-describedby="password"
                                    data-name="password">
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            @error('password')
                                <span class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    {{-- Country --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Country <span class="text-danger">*</span></label>
                            <select name="country_id" id="country_id" data-name="country"
                                class="form-control @error('country_id') is-invalid @enderror search_by_country select2 required"
                                data-append="search_by_country" data-selectedCountryId="{{ old('country_id') }}"
                                data-selectedStateId="{{ old('state_id') }}" autofocus>
                                <option value="" disabled
                                    {{ old('country_id', $edit->country_id ?? '') ? '' : 'selected' }}>
                                    Select Country
                                </option>
                            </select>
                            @error('country_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- State --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select State <span class="text-danger">*</span></label>
                            <select name="state_id" id="state_id" data-name="state"
                                class="form-control @error('state_id') is-invalid @enderror search_by_state select2 required"
                                data-append="search_by_state" data-selectedStateId="{{ old('state_id') }}">

                                <option value="" disabled
                                    {{ old('state_id', $edit->state_id ?? '') ? '' : 'selected' }}>
                                    Select State
                                </option>
                            </select>
                            @error('state_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>


                    {{-- City --}}
                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select City</label>
                            <select id="city_id" name="city_id" data-name="city"
                                class="form-control @error('city_id') is-invalid @enderror search_by_city select2 required"
                                data-append="search_by_city" data-selectedCityId="{{ old('city_id') }}">
                                <option value="" disabled
                                    {{ old('city_id', $edit->city_id ?? '') ? '' : 'selected' }}>
                                    Select City
                                </option>
                            </select>
                            @error('city_id')
                                <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-12 mb-2">
                        <div class="form-group">
                            <label class="form-label">Select Plan <span class="text-danger">*</span></label>
                            <select class="form-control select2 w-100 @error('plan') is-invalid @enderror required"
                                name="plan_id" data-name="plan" id="plan_id">
                                <option value="">Select Plan</option>
                                @foreach ($plans as $row_plan)
                                    <option value="{{ $row_plan->id }}">{{ $row_plan->name }}</option>
                                @endforeach
                            </select>

                            @error('plan_id')
                                <span class="invalid-feedback d-block">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12 mb-2">
                        <div class="mb-3 bg-light p-3 rounded">
                            <h6>Password must contain:</h6>
                            <ul class="small mb-0">
                                <li id="minimumlength">Minimum <strong>8 characters</strong></li>
                                <li id="leastlowercase">At least <strong>one lowercase</strong> letter (a-z)</li>
                                <li id="leastuppercase">At least <strong>one uppercase</strong> letter (A-Z)</li>
                                <li id="onenumber">At least <strong>one number</strong> (0-9)</li>
                            </ul>
                        </div>
                    </div>


                    <div class="divider">
                        <hr />
                    </div>
                    <div class="col-md-12 text-center">
                        <button type="button" class="btn btn-success mt-1 mb-1" id="submit_btn">
                            {{ isset($edit) ? 'Update' : 'Submit' }}
                        </button>
                        <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- OTP Modal -->
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold" id="otpModalLabel">Enter OTP - <span id="otpNumberDisplay"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center">
                    <p class="mb-3">We've sent a 4-digit OTP to your registered number.</p>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <input type="text" class="form-control text-center modal_opt_numbers" maxlength="1"
                            style="width: 50px; font-size: 1.5rem;" oninput="moveToNext(this, 'otp2')" id="otp1">
                        <input type="text" class="form-control text-center modal_opt_numbers" maxlength="1"
                            style="width: 50px; font-size: 1.5rem;" oninput="moveToNext(this, 'otp3')" id="otp2">
                        <input type="text" class="form-control text-center modal_opt_numbers" maxlength="1"
                            style="width: 50px; font-size: 1.5rem;" oninput="moveToNext(this, 'otp4')" id="otp3">
                        <input type="text" class="form-control text-center modal_opt_numbers" maxlength="1"
                            style="width: 50px; font-size: 1.5rem;" id="otp4">
                    </div>

                    <button class="btn btn-primary w-100" onclick="submitOTP()">Verify OTP</button>

                    <div class="mt-3">
                        <p class="small">Didn't receive the code? <a href="javascript:void(0)" class="text-primary"
                                onclick="resendOTP()">Resend OTP</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('page_scripts')
    @include('utils.getCountry')
    @include('utils.getStateByCountry')
    @include('utils.getCityByState')
@endpush

@push('page_scripts')
    <script type="text/javascript">
        function isNumber(evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        function IsEmail(email) {
            const regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if (!regex.test(email)) {
                return false;
            } else {
                return true;
            }
        }

        function isValidGSTIN(gstin) {
            if (!gstin) return false;
            const regex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
            return regex.test(gstin.toUpperCase());
        }


        function validatePassword() {
            const password = document.getElementById("password").value;
            let isValid = true;

            if (password.length < 8) {
                $("#minimumlength").css("color", "red");
                isValid = false;
            } else {
                $("#minimumlength").css("color", "green");
            }

            if (!/[a-z]/.test(password)) {
                $("#leastlowercase").css("color", "red");
                isValid = false;
            } else {
                $("#leastlowercase").css("color", "green");
            }

            if (!/[A-Z]/.test(password)) {
                $("#leastuppercase").css("color", "red");
                isValid = false;
            } else {
                $("#leastuppercase").css("color", "green");
            }

            if (!/[0-9]/.test(password)) {
                $("#onenumber").css("color", "red");
                isValid = false;
            } else {
                $("#onenumber").css("color", "green");
            }

            return isValid;
        }


        function generateOTP() {
            return Math.floor(1000 + Math.random() * 9000); // Generates a 4-digit number
        }

        function openOTPModal() {
            const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
            otpModal.show();
        }

        function moveToNext(current, nextFieldId) {
            if (current.value.length === 1) {
                document.getElementById(nextFieldId).focus();
            }
        }

        function resendOTP() {
            // Implement OTP resend logic here
            var otp = generateOTP();

            $("#otp").val(otp);
            $("#otpNumberDisplay").text(otp);

            toastr.success('OTP resent successfully', 'Success');

        }

        function submitOTP() {
            const otp1 = document.getElementById('otp1').value;
            const otp2 = document.getElementById('otp2').value;
            const otp3 = document.getElementById('otp3').value;
            const otp4 = document.getElementById('otp4').value;

            const otp = otp1 + otp2 + otp3 + otp4;

            if (otp.length === 4) {

                var otpNumberHidden = $("#otp").val();

                if (otpNumberHidden === otp) {

                    var formData = new FormData($('#companyAddForm')[0]);

                    $.ajax({
                        url: "{{ route('company.store') }}",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            console.log(response);
                            toastr.success(response.message, 'Success');
                            setTimeout(function() {
                                window.location.href = "{{ route($route . '.index') }}";
                            }, 1000);
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let res = xhr.responseJSON;
                                if (res.errors && Object.keys(res.errors).length > 0) {
                                    $.each(res.errors, function(field, messages) {
                                        toastr.error(messages[0], 'Error');
                                    });

                                } else {
                                    // No specific errors, show general message
                                    toastr.error(res.message, 'Error');
                                }
                            } else {
                                // Handle other errors
                                toastr.error("Something went wrong.", 'Error');
                            }
                        }
                    });

                } else {
                    toastr.error('Invalid OTP', 'Error');

                }
            } else {
                toastr.error('Please enter all 4 digits of OTP.', "Error");

            }
        }

        $(document).ready(function() {

            $(".length10").attr("maxlength", "10");
            $(".length10").attr("minlength", "10");



            $(document).on('keyup', '#password', function(e) {
                validatePassword();
            });

            $(document).on('click', '#get_gst_details', function(e) {
                e.preventDefault();
                var gst = $('#gst_no').val();
                if (gst === '') {
                    toastr.error('Please enter GST No.');
                    return;
                }

                if (!isValidGSTIN(gst)) {

                    toastr.error('Please enter valid GST No.');
                    return;
                }
                toastr.error('Sorry, we couldn’t find the company details. Please add them manually.');

                return;
                $.ajax({
                    url: `/get-gst-details/${gst}`,
                    type: 'GET',
                    success: function(data) {
                        $('#company_name').val(data.company_name);
                        $('#person_name').val(data.person_name);
                        $('#whatsapp_number').val(data.whatsapp_number);
                        $('#email').val(data.email);
                        $('#password').val(data.password);
                        $('#country_id').val(data.country_id);
                        $('#state_id').val(data.state_id);
                        $('#city_id').val(data.city_id);
                    },
                    error: function(xhr, status, error) {
                        let response = JSON.parse(xhr
                        .responseText); // Convert JSON string to an object
                        toastr.error(response.message);

                    }
                });
            });

            $(document).on('click', '#submit_btn', function(e) {
                let isValid = true;
                e.preventDefault();
                $("#companyAddForm").find(".required").each(function() {

                    if (!$(this).val()) {
                        $(this).addClass('is-invalid');
                        isValid = false;
                        var error_message = $(this).attr("data-name").replace(/_/g, " ");
                        toastr.error(error_message + " is required");

                    } else {
                        $(this).removeClass("is-invalid");
                    }
                });

                var gst_no = $('#gst_no').val();

                if (gst_no != '' && gst_no != null) {
                    if (!isValidGSTIN(gst_no)) {
                        $('#gst_no').addClass('is-invalid');
                        toastr.error('Please enter valid GST No.');
                        isValid = false;
                    } else {
                        $("#gst_no").removeClass("is-invalid");
                    }
                }

                var whatsapp_number = $('#whatsapp_number').val();
                if (whatsapp_number.length != 10) {
                    $('#whatsapp_number').addClass('is-invalid');
                    toastr.error('Please enter a valid WhatsApp number.');
                    isValid = false;
                } else {
                    $("#whatsapp_number").removeClass("is-invalid");
                }

                var password = $("#password").val();
                if (validatePassword(password) === false) {
                    $('#password').addClass('is-invalid');
                    toastr.error('Please enter a valid password');
                    isValid = false;
                } else {
                    $("#whatsapp_number").removeClass("is-invalid");
                }

                if (isValid) {
                    var company_name = $("#company_name").val();
                    var gst_no = $("#gst_no").val();

                    $.ajax({
                        url: "{{ route('company.check-company-exists') }}",
                        type: "POST",
                        data: {
                            company_name: company_name,
                            gst_no: gst_no,
                            _token: '{{ csrf_token() }}'
                        },
                        datatype: 'json',
                        success: function(response) {
                            if (response.status != true) {
                                toastr.error(response.message);
                            } else {
                                $(".modal_opt_numbers").val();
                                var otp = generateOTP();
                                $("#otp").val(otp);
                                $("#otpNumberDisplay").text(otp);

                                openOTPModal();
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error(xhr.responseJSON.message);
                        }
                    });

                } else {
                    //toastr.error('Please fill all required fields.');
                }
            });

        });
    </script>
@endpush
