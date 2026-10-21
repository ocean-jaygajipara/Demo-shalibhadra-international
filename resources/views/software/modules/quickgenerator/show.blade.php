@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
@endphp

@section('title', $page_title)
<style>
    .modal-content {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        max-width: 350px;
        margin: auto;
    }

    .qr-box {
        display: flex;
        justify-content: center;
        align-items: center;
        border: 2px solid #e74c3c;
        /* Red border */
        padding: 10px;
        border-radius: 10px;
        background: #fff;
        width: 230px;
        margin: 0 auto;
    }

    #qrCodeText {
        font-size: 16px;
        color: #333;
        text-align: center;
    }
</style>
@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => isset($edit) && $edit?->id ? 'Edit ' . $page_title : 'Create ' . $page_title,
                    'url' => '',
                ],
            ],
        ])
        <a class="btn btn-primary waves-effect waves-light text-white" href="{{ url()->previous() }}">
            <i class="menu-icon ti ti-chevrons-left"></i> Back
        </a>
    </div>

    <div class="card my-3 mb-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-5 col-sm-12">
                                <p><b>Company Name :</b> {{ $show?->company->company_name ?? '' }}</p>
                                <p><b>Label :</b> {{ $show?->label ?? '' }}</p>
                            </div>
                            <div class="col-md-5 col-sm-12">
                                <p><b>Quantity :</b> {{ $show?->quantity ?? '' }}</p>
                                <p><b>Collect Points :</b> {{ $show?->collect_points ?? '' }}</p>
                            </div>
                            <div class="col-md-2 col-sm-12 d-flex justify-content-end align-items-center">
                                <button class="btn btn-success" id="print-all-btn">
                                    <span class="btn-loader spinner-border spinner-border-sm ms-2" role="status" style="display: none;"></span>
                                    <span class="btn-text">Print All</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-0" />
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center">Sr. No.</th>
                                        <th class="">Redemption Code</th>
                                        <th class="">Collect Point</th>
                                        <th class="">Scanned By</th>
                                        <th class="">Scanned Date</th>
                                        <th class="text-center">Status</th>
                                        <th class="">Print</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($show?->redemption_items))
                                        @foreach ($show?->redemption_items as $index => $item)
                                            <tr>
                                                <td class="text-center">{{ $index + 1 }}</td>
                                                <td class="">{{ $item?->redemption_code_label ?? '-' }}</td>
                                                <td class="">{{ $item?->collect_points ?? '-' }}</td>
                                                <td class="">
                                                    @if ($item?->scanned_user_id)
                                                        {!! 'Name : ' . $item?->scanned_user?->customer_name . '<br />' ?? '' !!}
                                                        {!! 'Email : ' . $item?->scanned_user?->email ?? '' !!}
                                                    @else
                                                        {{ '-' }}
                                                    @endif
                                                </td>
                                                <td class="">
                                                    {{ $item?->scanned_date ? \Carbon\Carbon::parse($item->scanned_date)->format('d/m/Y h:i A') : '-' }}
                                                </td>
                                                <td class="w-5 text-center">
                                                    @if (!$item?->scanned_user_id)
                                                        @php
                                                            $status = strtolower($item->status);
                                                            $btnClass =
                                                                $status === 'active'
                                                                    ? 'btn-success'
                                                                    : ($status === 'inactive'
                                                                        ? 'btn-danger'
                                                                        : 'btn-info');
                                                        @endphp

                                                        <div class="dropdown">
                                                            <button type="button"
                                                                class="btn btn-sm w-100 dropdown-toggle waves-effect waves-light {{ $btnClass }}"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                {{ ucfirst($status) }}
                                                            </button>
                                                            <ul class="dropdown-menu" style="">
                                                                @if ($status !== 'active')
                                                                    <li>
                                                                        <a href="javascript:void(0)"
                                                                            class="dropdown-item status-dropdown text-success"
                                                                            data-id="{{ $item->id }}"
                                                                            data-update_status="active">
                                                                            Active
                                                                        </a>
                                                                    </li>
                                                                @endif
                                                                @if ($status !== 'inactive')
                                                                    <li>
                                                                        <a href="javascript:void(0)"
                                                                            class="dropdown-item status-dropdown text-danger"
                                                                            data-id="{{ $item->id }}"
                                                                            data-update_status="inactive">
                                                                            Inactive
                                                                        </a>
                                                                    </li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    @else
                                                        <span>-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (in_array($item?->status, ['active', 'Active']) && $item?->scanned_user_id == null && $item?->scanned_date == null)
                                                        <button class="btn btn-primary print-btn"
                                                            data-code="{{ $item?->redemption_code_label }}"
                                                            data-points="{{ $item?->collect_points }}">Print</button>
                                                        <a href="javascript:void(0)" data-id="{{ $item->id }}"
                                                            data-did="{{ route('quick-qr-generator-view.redemption_items_destroy', $item->id) }}"
                                                            class="btn btn-danger btn-icon deletebutton mx-1"
                                                            title="Delete">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                        <button class="btn btn-warning scan-qr-btn"
                                                            data-code="{{ $item?->redemption_code_label }}"
                                                            data-url="{{ route('geast.user.scanner', ['company_slug' => $company_slug, 'redemption_code_label' => $item->redemption_code_label]) }}"
                                                            data-points="{{ $item?->collect_points }}">Scan QR</button>
                                                    @else
                                                        <span>-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="print-section" style="display: none;"></div>
    <div id="qrCodeView" style="display: none;margin-top:20px"></div>
    <!-- QR Code Modal -->
    <div class="modal fade" id="scanQrModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="qrCodeContainer" class="qr-box mb-3"></div>
                    <p id="qrCodeText" class="fw-bold"></p>
                    <p id="qrCodeUrl" class="{{ env('APP_URL') != 'http://127.0.0.1:8000/' ? 'd-none' : '' }}"></p>
                    {{-- <button class="btn btn-success w-100 mt-3" data-bs-dismiss="modal">Scanned</button> --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Scanned Details Modal -->
    <div class="modal-body text-center">
        <div id="qr-reader" style="width:100%;"></div>
        <p class="mt-2" id="qrCodeText"></p>
    </div>

@endsection

@section('page_leavel_script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>

@endsection

@push('page_scripts')
    @include('utils.getCompany')
    @include('software.inlcudes.script-update-status')

    <script>
        let qrOptions = {
            width: 200,
            height: 200
        };
    </script>

    <script>
        const routeTemplate =
            "{{ route('geast.user.scanner', ['company_slug' => $company_slug, 'redemption_code_label' => '__CODE__']) }}";

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            let show = @json($show);

            // Update status AJAX
            $('.status-dropdown').click(function() {
                const status = $(this).data('update_status');
                const itemId = $(this).data('id');

                $.ajax({
                    url: "{{ route('quick-qr-generator-item-status') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: itemId,
                        status: status
                    },
                    success: function(response) {
                        if (response.status === true || response.status === "true") {
                            toastr.success("Status updated successfully!");
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            let error = response.message ?? "Failed to update status.";
                            if (response.message === "Validation Error." && response.data) {
                                error = Object.values(response.data).join("<br>");
                            }
                            toastr.error(error);
                        }
                    },
                    error: function() {
                        toastr.error("Something went wrong.");
                    }
                });
            });

            // Print all QR codes
            $('#print-all-btn').click(function() {
                var $btn = $(this);
                $btn.prop('disabled', true);
                $btn.find('.btn-text').text('Printing...');
                $btn.find('.btn-loader').show();


                $("#qrCodeView").html("");
                if (show?.redemption_items?.length > 0) {

                    $.each(show.redemption_items, function(index, item) {

                        if (["Active", "active"].includes(item.status) && item?.scanned_date ==
                            null) {
                            let qrText = item.redemption_code_label;
                            // Replace the placeholder with actual code (encoded)
                            let qrLink = routeTemplate.replace('__CODE__', encodeURIComponent(
                                qrText));

                            console.log("l-278", index, item.status, item, qrText, qrLink);

                            let qrContainer = $(
                                '<div style="display: inline-block; text-align: center; margin: 10px;"></div>'
                            );
                            let qrDiv = $('<div></div>');
                            qrContainer.append(qrDiv);
                            qrContainer.append(
                                `<div style="margin-top: 10px;">Code: <b>${qrText}</b></div>`);
                            $("#qrCodeView").append(qrContainer);

                            new QRCode(qrDiv[0], {
                                ...qrOptions,
                                text: qrLink
                            });
                        }
                    });

                    setTimeout(() => {
                        printQRCode();
                        $btn.find('.btn-text').text('Print All');
                        $btn.find('.btn-loader').hide();
                        $btn.prop('disabled', false);
                    }, 300);
                } else {
                    alert("No active redemption items found!");
                }
            });

            // Print individual QR code
            $('.print-btn').click(function() {
                var $btn = $(this);
                $btn.prop('disabled', true);
                $btn.find('.btn-text').text('Printing...');
                $btn.find('.btn-loader').show();


                const code = $(this).data('code');
                const qrText = code;
                $("#qrCodeView").html("");
                // Replace the placeholder with actual code (encoded)
                let qrLink = routeTemplate.replace('__CODE__', encodeURIComponent(qrText));


                let qrContainer = $('<div style="text-align: left;"></div>');
                let qrDiv = $('<div></div>');
                qrContainer.append(qrDiv);
                qrContainer.append(`<div style="font-size: 14px; margin-top: 10px;">Code: ${qrText}</div>`);
                // qrContainer.append(`<div style="font-size: 14px; margin-top: 10px;">Qr Link: ${qrLink}</div>`);
                $("#qrCodeView").append(qrContainer);

                // console.log("L-310", qrLink, qrDiv);
                new QRCode(qrDiv[0], {
                    ...qrOptions,
                    // text: qrText
                    text: qrLink
                });
                // console.log("L-310", qrLink, qrText);


                setTimeout(() => printQRCode(), 300);
            });

             function printQRCode() {
                const qrElement = document.getElementById('qrCodeView'); // Corrected ID

                if (!qrElement) {
                    alert('QR code not found.');
                    return;
                }

                qrElement.style.display = 'block';
                var printContent = $('#qrCodeView').html();
                var $originalContent = $('body').html();
                $('body').html(printContent);
                window.print();
                $('body').html($originalContent);
                location.reload();
            }
        });
    </script>
    <Script>
        $(document).on('click', '.scan-qr-btn', function() {
            let code = $(this).data('code');
            let url = $(this).data('url');

            // Clear old QR
            $('#qrCodeContainer').html('');

            // Generate new QR with URL
            new QRCode(document.getElementById("qrCodeContainer"), {
                ...qrOptions,
                text: url,
            });

            // Set text and button link
            $('#qrCodeText').text('Code : ' + code);
            $('#qrCodeUrl').text('Url : ' + url);
            $('#openFormLink').attr('href', url);

            // Show modal
            $('#scanQrModal').modal('show');
        });
    </Script>

    <script>
        let html5QrCode;
        let qrCodeData = '';

        $('#scanQrModal').on('shown.bs.modal', function() {
            html5QrCode = new Html5Qrcode("qr-reader");

            html5QrCode.start({
                    facingMode: "environment"
                }, // rear camera
                {
                    fps: 10,
                    qrbox: 250
                },
                (decodedText) => {
                    qrCodeData = decodedText;
                    $('#qrCodeText').text("Scanned: " + decodedText);
                    // Stop scanning after first success
                    html5QrCode.stop();
                },
                (errorMessage) => {
                    console.log("Scanning error:", errorMessage);
                }
            ).catch(err => {
                console.log("Camera start error:", err);
            });
        });

        $('#scanQrModal').on('hidden.bs.modal', function() {
            if (html5QrCode) {
                html5QrCode.stop();
            }
        });
    </script>

    <script>
        $(document).on('click', '.deletebutton', function() {
            let button = $(this);
            let id = button.data('id');
            let url = button.data('did');

            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel!",
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-danger waves-effect waves-light',
                    cancelButton: 'btn btn-primary waves-effect waves-light'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'DELETE'
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message || 'Deleted successfully.',
                                icon: 'success',
                                customClass: {
                                    confirmButton: 'btn btn-primary waves-effect waves-light'
                                }
                            }).then(() => {
                                location.reload(); // or remove row dynamically
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Something went wrong. Try again.',
                                icon: 'error',
                                customClass: {
                                    confirmButton: 'btn btn-primary waves-effect waves-light'
                                }
                            });
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: 'Cancelled',
                        text: 'Your data is safe :)',
                        icon: 'info',
                        customClass: {
                            confirmButton: 'btn btn-primary waves-effect waves-light'
                        }
                    });
                }
            });
        });
    </script>
@endpush
