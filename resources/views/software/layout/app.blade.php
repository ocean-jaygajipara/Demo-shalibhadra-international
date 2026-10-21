<!doctype html>

<html lang="en" class="light-style layout-wide customizer-hide layout-navbar-fixed layout-menu-fixed " dir="ltr"
    data-theme="theme-default" data-bs-theme="light" data-assets-path="{{ asset('/') }}"
    data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title> @yield('title') | {{ env('APP_NAME', 'Ocean Infotech') }}</title>

    <meta name="description" content="" />
    <meta name="currentGuard" value="{{ (isset($currentGuard) && !empty($currentGuard)) ? $currentGuard : '' }}" />
    <meta name="parent_type_id"
        value="{{ (isset($parent_type_id) && !empty($parent_type_id)) ? $parent_type_id : '' }}" />

    {{-- When Companny id foudn in admin time set using yield --}}
    @if (trim($__env->yieldContent('company_id')))
        <meta name="company_id" value="{{ trim($__env->yieldContent('company_id')) ?? '' }}" />
    @else
        <meta name="company_id" value="{{ $authenticateUserDetails?->company_id ?? '' }}" />
    @endif

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('software/img/ring.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
        rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('software/vendor/fonts/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/fonts/tabler-icons.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/fonts/flag-icons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('software/vendor/css/rtl/core.css') }}"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('software/vendor/css/rtl/theme-default.css') }}"
        class="template-customizer-theme-css" />
    {{--
    <link rel="stylesheet" href="{{ asset('software/css/demo.css') }}" /> --}}

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/sweetalert2/sweetalert2.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />

    <link rel="stylesheet" href="{{ asset('software/vendor/libs/node-waves/node-waves.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/typeahead-js/typeahead.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/apex-charts/apex-charts.css') }}" />



    <link rel="stylesheet" href="{{ asset('software/vendor/libs/select2/select2.css') }}" />

    <link rel="stylesheet"
        href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />

    <!-- Daterangepicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">


    <!-- Page Leavel -->
    @yield('page_leavel_style')

    @stack('push_style')

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="{{ asset('software/vendor/js/helpers.js') }}"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    {{--
    <script src="{{ asset('software/vendor/js/template-customizer.js') }}"></script> --}}
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="{{ asset('software/js/config.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('software/custom/custom.css') }}" />
</head>

<body style="--bs-scrollbar-width: 0px;">
    @if (!Auth::guard('admin_software')?->check() && !Auth::guard('team_person')?->check() && !Auth::guard('customer')?->check())
        @yield('content')
    @else
        <!-- Layout wrapper -->
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                @include('software.inlcudes.sidebar')

                <!-- Layout container -->
                <div class="layout-page">
                    <!-- Navbar -->
                    @include('software.inlcudes.navbar')
                    <!-- / Navbar -->

                    <!-- Content wrapper -->
                    <div class="content-wrapper">
                        <!-- Content -->
                        <div class="container-fuild flex-grow-1 container-p-y container-p-x">
                            @yield('content')
                        </div>
                    </div>
                </div>
                <!-- / Layout page -->

                <!-- Overlay -->
                <div class="layout-overlay layout-menu-toggle"></div>
                <!-- Full Screen Loader -->
                <!-- Global Loader -->
                <div id="global-loader"
                    style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;background:rgba(255,255,255,0.7);text-align:center;">
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);">
                        <div class="dot-loader">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>



                <!-- Loader Wrapper -->
                {{-- <div id="loader" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                                background: rgba(255, 255, 255, 0.7); z-index: 9999; display: flex;
                                align-items: center; justify-content: center;">

                    <!-- Loader Content -->
                    <div style="display: flex; flex-direction: column; align-items: center; gap: 1rem;">

                        <!-- 3 Bouncing Dots -->
                        <div class="dots-loader">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                </div> --}}

                <!-- Loader Styles -->


                <!-- Drag Target Area To SlideIn Menu On Small Screens -->
                <div class="drag-target"></div>
            </div>
        </div>
    @endif

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->

    <!----- punchIn / punch out code start -->
    <div id="navigator_location_info" data-navigator-location-info=""></div>
    <div id="navigator_browser_info" data-navigator-browser-info=""></div>

    <div class="buy-now">
        @if (isset($punchType) && !empty($punchType) && ($punchType == 'in' || $punchType == 'out') && $mainAttendanceAddBtn)
            @php $punchText = $punchType == 'in' ? 'Punch In' : 'Punch Out'; @endphp
            <a class="btn btn-primary btn-buy-now-primary waves-effect waves-light btn-lg"
                data-punch-type="{{ $punchType }}" data-punch-text="{{ $punchText }}" id="punch_in_out_btn"
                href="javascript:;">
                <span class="d-none d-lg-inline punch_txt_dv">{{ $punchText }}</span>
            </a>
        @endif
    </div>
    <!----- punchIn / punch out code end -->

    <!---plan expired modal display before 10 days ago start-->
    @if (
            session('show_plan_expiry_modal') &&
            isset($is_plan_expired) &&
            $is_plan_expired == true &&
            isset($mainPlanExpiryDate) &&
            !empty($mainPlanExpiryDate)
        )
        <!-- Trigger Button -->
        <script>
            window.onload = function () {
                $('#planExpiryModal').modal('show');
            };
        </script>

        <!-- Modal -->
        <div class="modal fade" id="planExpiryModal" tabindex="-1" aria-labelledby="planExpiryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalCenterTitle" style="color: white;">Plan Expiry Alert</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Your current plan is expiring soon!</strong></p>
                        <p><strong>Plan Name: {{ $mainPlanName ?? '' }}</strong></p>
                        <p><strong>Expiry Date:
                                {{ \Carbon\Carbon::parse($mainPlanExpiryDate)->format('d-m-Y') }}</strong></p>
                        <p>Please renew your plan to continue using all features without interruption.</p>
                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>

        @php
            session()->forget('show_plan_expiry_modal');
        @endphp
    @endif
    <!---plan expired modal display before 10 days ago end-->

    <script src="{{ asset('software/vendor/libs/jquery/jquery.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <!-- <script src="https://code.jquery.com/jquery-3.5.1.js"></script> -->
    <script src="{{ asset('software/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('software/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/node-waves/node-waves.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/hammer/hammer.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/i18n/i18n.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/typeahead-js/typeahead.js') }}"></script>
    <script src="{{ asset('software/vendor/js/menu.js') }}"></script>

    <script src="{{ asset('software/vendor/libs/select2/select2.js') }}"></script>
    <script>
        if ($.fn.select2) {
            $.fn.select2.defaults.set("selectOnClose", true);
            $.fn.select2.defaults.set("minimumResultsForSearch", 0); // Always show search box
        }
    </script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    {{--
    <script src="{{ asset('software/vendor/libs/@form-validation/popular.js') }}"></script> --}}
    {{--
    <script src="{{ asset('software/vendor/libs/@form-validation/bootstrap5.js') }}"></script> --}}
    {{--
    <script src="{{ asset('software/vendor/libs/@form-validation/auto-focus.js') }}"></script> --}}
    <script src="{{ asset('software/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="{{ asset('software/js/forms-selects.js') }} "></script>
    <script src="{{ asset('software/vendor/libs/apex-charts/apexcharts.js') }} "></script>
    <script src="{{ asset('software/vendor/libs/chartjs/chartjs.js') }} "></script>

    <script src="{{ asset('software/js/dashboards-crm.js') }} "></script>
    <script src="{{ asset('software/js/charts-chartjs.js') }} "></script>

    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>

    <!-- Daterangepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    {{--
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script> --}}

    <script src="https://cdn.ckeditor.com/ckeditor5/35.3.2/super-build/ckeditor.js"></script>

    <script>

        let punchinout_url = "{{ route('permissions.punchinout') }}";
        let dashboard_url = "{{ route('software.dashboard') }}";
    </script>

    @yield('page_leavel_script')

    <!-- Main JS -->
    <script src="{{ asset('software/js/main.js') }}"></script>

    <script src="{{ asset('software/custom/custom.js') }}"></script>
    <!-- Page JS -->
    <script>
        $(function () {
            if ($('.my_daterangepicker').length > 0) {
                // var start = moment(); // Today
                var start = moment().startOf('month'); // Today
                var end = moment().endOf('month'); // Today
                var endOfYear = new Date();

                var future_date = $('.my_daterangepicker').attr("data-future-date");
                if (future_date == "yes") {
                    var endOfYear = moment().endOf('year');
                }

                $('.my_daterangepicker').daterangepicker({
                    startDate: start,
                    endDate: end,
                    maxDate: endOfYear,
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
                }, function (start, end, label) {
                    $('.my_daterangepicker').val(start.format('DD/MM/YYYY') + ' to ' + end.format(
                        'DD/MM/YYYY'));

                    // Optional: do something based on label
                    // console.log("Selected range: " + label, start.format('DD/MM/YYYY'), end.format(
                    //     'DD/MM/YYYY'), $(this), $('.my_daterangepicker'));
                });

                // Set default value in input
                // $('.my_daterangepicker').val(start.format('DD/MM/YYYY') + ' to ' + end.format('DD/MM/YYYY'));
            } else {
                // console.log('❌ my_daterangepicker does NOT exist.');
            }

        });

        /** Date Picker */
        if ($('.datepicker').length > 0) {

            $(".datepicker").datepicker({
                dateFormat: 'dd/mm/yy', // Set format to 06/06/2025
                defaultDate: new Date(), // Pre-select today's date
                /*
                showButtonPanel: true,
                beforeShow: function(input) {
                    setTimeout(function() {
                        var buttonPane = $(input)
                            .datepicker("widget")
                            .find(".ui-datepicker-buttonpane");

                            var btn = $(
                                '<button type="button" class="ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all">Clear</button>'
                            );
                            btn.unbind("click").bind("click", function() {
                                $.datepicker._clearDate(input);
                            });

                            btn.appendTo(buttonPane);
                    }, 1);
                }
                    */
            });

            // If input is empty, set default date to today
            $(".datepicker").each(function () {
                if (!$(this).val()) {
                    $(this).datepicker("setDate", new Date());
                }
            });

            // Convert value from yyyy-mm-dd to dd/mm/yyyy for datepicker inputs
            $(".datepicker").each(function () {
                const val = $(this).val();
                if (val.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    const [year, month, day] = val.split("-");
                    $(this).val(`${day}/${month}/${year}`);
                }
            });
        }


    </script>
    <script>
        // $(document).ready(function() {
        //     if ($('#yajra-datatables').length > 0) {


        //         $(document).on('preXhr.dt', function(e, settings, data) {
        //             $('#loader').show();
        //         });

        //         $(document).on('xhr.dt', function(e, settings, json, xhr) {
        //             $('#loader').hide();
        //         });

        //     } else {
        //         $('#loader').hide();
        //     }
        // });
    </script>
    <script>
        $(document).ajaxStart(function () {
            $("#global-loader").show();
        });

        // Hide loader after all AJAX calls complete
        $(document).ajaxStop(function () {
            $("#global-loader").hide();
        });

        // Global AJAX error handler for session expiration
        var sessionExpiredHandled = false;
        $(document).ajaxError(function (event, xhr, settings) {
            // Check if session expired (401 status)
            if (xhr.status === 401 && !sessionExpiredHandled) {
                sessionExpiredHandled = true;
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.session_expired || response.redirect) {
                        // Show message and redirect to login
                        var message = response.message || 'Session expired. Please login again.';
                        Swal.fire({
                            title: "Session Expired",
                            text: message,
                            icon: "warning",
                            confirmButtonText: "OK",
                            customClass: {
                                confirmButton: 'btn btn-primary waves-effect waves-light',
                            },
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(function () {
                            window.location.href = response.redirect || "{{ route('software.login') }}";
                        });
                        return false;
                    }
                } catch (e) {
                    // If response is not JSON, still redirect
                    Swal.fire({
                        title: "Session Expired",
                        text: 'Session expired. Please login again.',
                        icon: "warning",
                        confirmButtonText: "OK",
                        customClass: {
                            confirmButton: 'btn btn-primary waves-effect waves-light',
                        },
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(function () {
                        window.location.href = "{{ route('software.login') }}";
                    });
                    return false;
                }
            }
        });
    </script>

    @stack('page_scripts')
    {{-- Filtte Button --}}
    <script>
        $(document).ready(function () {
            $('#filter_section').hide();
            $("#show_filter").click(function () {

                if ($('#filter_section').is(':hidden')) {
                    $('#filter_section').show();
                } else {
                    $('#filter_section').hide();
                }
            });
        });
    </script>
    @include('utils.apiLoadingHelper')
    @include('utils.toastr')
    @include('utils.updatePunchInOut')
    <script>
        function loadNotifications() {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.classList.toggle('hidden');

            $.ajax({
                url: "{{ route('notification.get_notification') }}",
                type: "POST",
                datatype: 'json',
                global: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status == true) {
                        const list = document.getElementById('notif-list');
                        list.innerHTML = '';

                        var resData = response.data;
                        if (resData && resData.length > 0) {
                            resData.forEach(notif => {

                                const hrefUrl = notif.module_url && notif.module_url !== '' ? notif
                                    .module_url : '#';
                                list.innerHTML += `<a href="${hrefUrl}" target="_blank" class="list-group-item list-group-item-action d-flex gap-3 align-items-start border-0">
                                <div class="bg-light rounded-circle p-2">
                                    <i class="tf-icons ${notif.menu_icon}" style="margin-right: 0.3rem;font-size: 1.375rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1 fw-semibold text-dark">${notif.title}</h6>
                                        <small class="text-muted">${notif.created_at}</small>
                                    </div>
                                    <div class="text-muted small">
                                        ${notif.body}
                                    </div>
                                </div>
                            </a>`;
                            });
                            document.getElementById('notif-count').innerText = resData.length;
                        } else {
                            list.innerHTML += '<div class="text-center text-muted py-3">No notifications</div>';
                        }

                    }
                }
            });
        }

        $(document).on('click', '#notif-mark-read', function (event) {
            event.stopPropagation();
            $.ajax({
                url: "{{ route('notification.clear') }}",
                type: "POST",
                datatype: 'json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status == true) {
                        const list = document.getElementById('notif-list');
                        list.innerHTML =
                            '<div class="text-center text-muted py-3">No notifications</div>';

                        $("#notif-count").html('');
                    }
                }
            });
        });

        $(document).ready(function () {
            loadNotifications();
            setInterval(loadNotifications, 120000); // 2 minutes
        });
        document.getElementById('notif-bell').addEventListener('click', loadNotifications);
    </script>
    <script>
        const userIsLoggedIn = {{ auth()->check() ? 'true' : 'false' }};
        // if(userIsLoggedIn == true && ($('meta[name="currentGuard"]').attr('value') == 'team_person') && ($('meta[name="parent_type_id"]').attr('value') != '')){
        //     var locationData = $('#navigator_location_info').attr("data-navigator-location-info");
        //     var parsedLocation = locationData ? JSON.parse(locationData) : {};

        //     if (!parsedLocation.latitude || !parsedLocation.longitude) {
        //         $('body').css({
        //             'opacity': '0.5',
        //             'pointer-events': 'none',
        //         });
        //     }
        // }
    </script>
    <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js"></script>

    <script>
        // const firebaseConfig = @json($firebaseConfig);
        // firebase.initializeApp(firebaseConfig);

        // const messaging = firebase.messaging();

        // function startFCM() {
        //     navigator.serviceWorker.register("/firebase-messaging-sw.js")
        //         .then((registration) => {
        //             messaging.useServiceWorker(registration);
        //             return messaging.requestPermission();
        //         })
        //         .then(() => {
        //             return messaging.getToken({
        //                 vapidKey: firebaseConfig.vapidKey
        //             });
        //         })
        //         .then((token) => {
        //             const storedToken = localStorage.getItem('fcm_token');

        //             if (storedToken !== token) {
        //                 return fetch('{{ route('store.token') }}', {
        //                     method: 'POST',
        //                     headers: {
        //                         'Content-Type': 'application/json',
        //                         'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //                     },
        //                     body: JSON.stringify({ token: token })
        //                 })
        //                 .then(() => {
        //                     localStorage.setItem('fcm_token', token);
        //                 });
        //             }
        //         })
        //         .catch((err) => {
        //             console.error('❌ FCM Error:', err);
        //         });
        // }

        // // Start only once after login
        // if (!localStorage.getItem('fcm_token')) {
        //     if (userIsLoggedIn == true) {
        //         startFCM();
        //     }
        // }

        // $(document).on('click', '.logout-btn', function (event) {
        //     event.preventDefault();
        //     localStorage.removeItem('fcm_token');
        //     $('#logout-form').submit();
        // });

        // messaging.onMessage(payload => {
        //     console.log('Foreground payload:', payload);

        //     new Notification(payload.notification.title, {
        //         body: payload.notification.body,
        //         icon: '/icon.png'
        //     });
        // });


        // messaging.onMessage((payload) => {
        // console.log('📩 Foreground message received:', payload);

        // const notificationTitle = payload.notification?.title || 'New Notification';
        // const notificationOptions = {
        //     body: payload.notification?.body || '',
        //     icon: payload.notification?.icon || '/firebase-logo.png',
        //     badge: '/firebase-logo.png',
        //     requireInteraction: true,
        //     tag: 'notification-' + (payload.data?.notification_id || Date.now()),
        //     data: payload.data || {},
        //     actions: [
        //         {
        //             action: 'completed',
        //             title: '✓ Completed',
        //             icon: '/icons/check.png'
        //         },
        //         {
        //             action: 'decline',
        //             title: '✗ Ignore',
        //             icon: '/icons/close.png'
        //         }
        //     ]
        // };

        // if ('serviceWorker' in navigator && Notification.permission === 'granted') {
        //         navigator.serviceWorker.ready.then(function(registration) {
        //             registration.showNotification(notificationTitle, notificationOptions)
        //                 .then(() => {
        //                     console.log('✅ Notification with buttons shown');
        //                 })
        //                 .catch((err) => {
        //                     console.error('❌ Failed to show notification:', err);
        //                 });
        //         });
        //     } else {
        //         console.warn('⚠️ Service worker not ready or permission not granted');
        //     }
        // });



        $('#show_add_btn').on('keydown', function (e) {
            if (e.key === "Enter") {
                window.location.href = $(this).attr('href');
            }
        });

        // Improved Global Field Navigation (Tab & Enter)
        $(document).ready(function () {

            $(document).on('keydown', 'input, select, textarea, .select2-selection', function (e) {
                if (e.which === 9 || e.which === 13) {
                    // If Select2 is open, let it handle the selection via selectOnClose
                    let $select = $(this).closest('.select2-container').prev('select.select2');
                    if ($select.length && $select.data('select2') && $select.data('select2').isOpen()) {
                        if (e.which === 13) return; // Let Enter select and close
                        $select.select2('close');
                    }

                    e.preventDefault();
                    let form = $(this).closest('form');
                    let focusable = form.find('input, select, textarea, button, .select2-selection')
                        .filter(':visible:not([readonly]):not([tabindex="-1"])');

                    let index = focusable.index(this);
                    if (e.shiftKey && e.which === 9) {
                        // Move Backward
                        if (index > 0) {
                            focusable.eq(index - 1).focus();
                        }
                    } else {
                        // Move Forward
                        if (index > -1 && index + 1 < focusable.length) {
                            focusable.eq(index + 1).focus();
                        }
                    }
                }
            });

            // Special handling for Select2 search field (when open)
            $(document).on('keydown', '.select2-search__field', function (e) {
                if (e.which === 9) { // Tab
                    let $select = $(this).closest('.select2-container').prev('select.select2');
                    if ($select.length) {
                        $select.select2('close');
                        let form = $select.closest('form');
                        let focusable = form.find('input, select, textarea, button, .select2-selection')
                            .filter(':visible:not([readonly]):not([tabindex="-1"])');
                        let $selection = $select.next('.select2-container').find('.select2-selection');
                        let index = focusable.index($selection);

                        if (e.shiftKey) {
                            // Move Backward
                            if (index > 0) {
                                setTimeout(() => focusable.eq(index - 1).focus(), 50);
                            }
                        } else {
                            // Move Forward
                            if (index > -1 && index + 1 < focusable.length) {
                                setTimeout(() => focusable.eq(index + 1).focus(), 50);
                            }
                        }
                    }
                }
            });

            function setInitialFocus() {
                $('input, select, textarea, button, .select2-selection')
                    .filter(':visible:not([readonly]):not([tabindex="-1"])')
                    .first()
                    .focus();
            }

            setInitialFocus();
            $(window).on('load', setInitialFocus);
            setTimeout(setInitialFocus, 1700);
        });

        // $('.select2').on('select2:ready select2:open select2:close', function() {
        //     setInitialFocus();
        // });

        // Removed select2:open and select2:close — they caused the page to scroll to top
        // on every product/dropdown selection by focusing the first visible input.
        // All readonly inputs & textareas
        $('input[readonly], textarea[readonly]').attr('tabindex', -1);

        // All hidden inputs
        $('input[type="hidden"]').attr('tabindex', -1);

        // Disabled select boxes (only if disabled)
        $('select:disabled').attr('tabindex', -1);


        // Load jQuery and Toastr if not already loaded
        startRepeatingNotification();

        function showNotification(notification) {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                timeOut: 0,              // Auto hide after 5 seconds
                extendedTimeOut: 0,      // +1 sec if hovered
                preventDuplicates: false,   // Allow multiple if needed
                positionClass: "toast-top-right",
                toastClass: 'toast-custom-width',
                iconClass: 'toast-info'
            };

            // Dynamic message based on notification data


            if (notification.module_name == 'Sales Orders' && notification.sales_order_id) {
                toastr.info(
                    `<strong>Sales Order #${notification.sales_order_no || 'Unknown'} has been created by ${notification.name} Please review.</strong><br><br>
                    <button class="btn-completed" data-id="${notification.id}" data-sales_order_id="${notification.sales_order_id}" data-module_name="${notification.module_name}" style="background:#00b300;color:white;border:none;padding:10px 20px;margin:5px;border-radius:5px;cursor:pointer;font-weight:bold;">Completed</button>
                    <button class="btn-ignore" data-id="${notification.id}" style="background:#ff3333;color:white;border:none;padding:10px 20px;margin:5px;border-radius:5px;cursor:pointer;font-weight:bold;">Ignore</button>`,
                    notification.title || "New Sales Order Created"
                );
            } else if (notification.module_name == 'Packing Slip' && notification.packing_slip_id) {
                toastr.info(
                    `<strong>Packing Slip #${notification.packing_slip_no || 'Unknown'} has been created by ${notification.name} Please review.</strong><br><br>
                    <button class="btn-completed" data-id="${notification.id}" data-packing_slip_id="${notification.packing_slip_id}"  data-module_name="${notification.module_name}" style="background:#00b300;color:white;border:none;padding:10px 20px;margin:5px;border-radius:5px;cursor:pointer;font-weight:bold;">Completed</button>
                    <button class="btn-ignore" data-id="${notification.id}" style="background:#ff3333;color:white;border:none;padding:10px 20px;margin:5px;border-radius:5px;cursor:pointer;font-weight:bold;">Ignore</button>`,
                    notification.title || "New Packing Slip Created"
                );
            }


            // Attach button clicks dynamically
            setTimeout(() => {
                document.querySelectorAll('.btn-completed').forEach(btn => {
                    btn.onclick = function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        const id = this.getAttribute('data-id');
                        const packing_slip_id = this.getAttribute('data-packing_slip_id');
                        const sales_order_id = this.getAttribute('data-sales_order_id');
                        const module_name = this.getAttribute('data-module_name');

                        // console.log($(this).closest('.toast-info').remove();)
                        // First: Immediately remove the current reminder toaster
                        $(this).closest('.toast-info').remove();

                        if (module_name == 'Sales Orders' && sales_order_id) {
                            markAsCompleted(id, sales_order_id, module_name);
                        } else if (module_name == 'Packing Slip' && packing_slip_id) {
                            markAsCompleted(id, packing_slip_id, module_name);
                        }

                    };
                });

                document.querySelectorAll('.btn-ignore').forEach(btn => {
                    btn.onclick = function (e) {
                        e.stopPropagation();
                        // Remove the current reminder toast
                        $(this).closest('.toast-info').remove();
                    };
                });
            }, 300);
        }

        function startRepeatingNotification() {
            console.log("Repeating notification started — will show every 10 minutes");
            fetchPendingPackingNotifications();
            fetchPendingSalesOrderNotifications();
            setInterval(fetchPendingPackingNotifications, 600000);
            setInterval(fetchPendingSalesOrderNotifications, 600000);
        }

        function fetchPendingPackingNotifications() {
            $.ajax({
                url: "{{ route('packing_slip.pending-packing-notifications') }}",
                type: "POST",
                datatype: 'json',
                global: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status == true) {
                        response.data.forEach(notif => {
                            if (notif.module_name === 'Packing Slip') {
                                showNotification(notif);
                            }
                        });
                        // if (response.data.length > 1) {
                        //     toastr.info(`You have ${response.data.length} pending packing slips.`);
                        // }
                    } else {
                        console.log("No pending packing slips.");
                    }
                },
                error: function (err) {
                    console.error("Error fetching notifications:", err);
                }
            });
        }

        function fetchPendingSalesOrderNotifications() {
            $.ajax({
                url: "{{ route('sales-order.pending-sales-order-notifications') }}",
                type: "POST",
                datatype: 'json',
                global: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.status == true) {
                        response.data.forEach(notif => {
                            if (notif.module_name === 'Sales Orders') {
                                showNotification(notif);
                            }
                        });
                        // if (response.data.length > 1) {
                        //     toastr.info(`You have ${response.data.length} pending orders.`);
                        // }
                    } else {
                        console.log("No pending orders.");
                    }
                },
                error: function (err) {
                    console.error("Error fetching notifications:", err);
                }
            });
        }

        function markAsCompleted(id, module_id, module_name) {
            let customUrl = '';
            let postData = {
                id: id,
                module_name: module_name,
            };

            if (module_name == 'Sales Orders') {
                customUrl = "{{ route('sales-order.mark-as-read-sales-order-notifications') }}";
                postData.sales_order_id = module_id;
            } else if (module_name == 'Packing Slip') {
                customUrl = "{{ route('packing_slip.mark-as-read-notifications') }}";
                postData.packing_slip_id = module_id;
            }

            $.ajax({
                url: customUrl,
                type: "POST",
                datatype: 'json',
                global: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: postData,
                success: function (response) {
                    toastr.success("Marked as Completed!", "", { timeOut: 1000 });
                    console.log("Completed:", response);
                },
                error: function (err) {
                    toastr.error("Error marking as completed", "", { timeOut: 1000 });
                    console.error(err);
                }
            });
        }
    </script>
</body>

</html>