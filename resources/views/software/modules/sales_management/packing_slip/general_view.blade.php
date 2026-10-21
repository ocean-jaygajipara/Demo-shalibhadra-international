@extends('software.layout.app')
@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($packingSlip) && isset($packingSlip->company_id) && !empty($packingSlip->company_id)) {
        $company_id = $packingSlip->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/materialize/materialize.min.css') }}">
@endsection

@section('content')

    <style>
        .per {
            float: right;
        }

        .invoice-preview .invoice-preview-header,
        .invoice-edit .invoice-preview-header,
        .invoice-add .invoice-preview-header {
            background-color: var(--bs-gray-50);
        }

        .rounded {
            border-radius: var(--bs-border-radius) !important;
        }

        .card-body {
            flex: 1 1 auto;
            padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
            color: var(--bs-card-color);
        }

        .main-logo {
            margin-left: -6px;
        }

        .border-panel {
            border-top: 3px solid #266BEE !important;
            border-radius: 3px !important;
        }

        .text-right {
            text-align: right !important;
        }

        .fab-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
        }

        .fab-actions {
            pointer-events: none;
        }

        .fab-action {
            transform: scale(0);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .fab-container:hover .fab-action {
            transform: scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        .btn-md {
            padding: 0.65rem 1.25rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.3rem;
            width: 56px;
            height: 56px;
            color: #fff !important;
        }

        @media print {
            .company_dv {
                width: 100% !important;
            }

            .invoice_dv,
            .billing_dv,
            .shipping_dv,
            .packingSlip_dv {
                width: 25% !important;
            }

            .per {
                float: right;
            }

            .invoice-preview .invoice-preview-header,
            .invoice-edit .invoice-preview-header,
            .invoice-add .invoice-preview-header {
                background-color: #ced7e0dc !important;
            }

            .rounded {
                border-radius: var(--bs-border-radius) !important;
            }

            .card-body {
                flex: 1 1 auto;
                padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
                color: var(--bs-card-color);
            }

            .main-logo {
                margin-left: -6px;
            }

            .border-panel {
                border-top: 3px solid #266BEE !important;
                border-radius: 3px !important;
            }

            .text-right {
                text-align: right !important;
            }
        }
    </style>

    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
        ])
    </div>



    <div class="row invoice-preview w-100" id="sales_order_download_save">
        <div class="col-12 mb-4">
            <div class="card invoice-preview-card p-4">
                <div class="card-body invoice-preview-header rounded"
                    style="height: 205px; display: flex; justify-content: center; align-items: center;">
                    @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                        <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                            style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                    @else
                        <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                            style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                    @endif
                </div>


                <div class="container"> <!-- Optional: keeps content inside page margins -->
                    <div class="row justify-content-center"> <!-- centers all columns -->
                        <h4 style="text-align: center;font-weight: 700;margin-bottom: 22px;">
                            {{ optional($packingSlip->company)->packing_slip_title ?? '-' }}</h4>
                        {{-- @php
                            dd($packingSlip);
                        @endphp --}}

                        <!-- Packing Slip -->
                        <div class="col-md-6 col-12 mb-4">
                            <h5 class="mb-2"><strong>Packing Slip:</strong></h5>

                            <div class="mb-1 d-flex">
                                <strong class="me-2">Slip No:</strong>
                                <span>{{ $packingSlip->packing_slip_no ?? '-' }}</span>
                            </div>

                            <div class="mb-1 d-flex">
                                <strong class="me-2">Slip Date:</strong>
                                <span>
                                    {{ $packingSlip->packing_slip_date ? \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d-m-Y') : '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Customer Detail -->
                        <div class="col-xl-6 col-md-8 col-12 mb-4"> <!-- Slightly wider -->
                            <h5 class="mb-2 ms-5"><strong>Customer Detail:</strong></h5>

                            <div class="row">
                                <div class="col-md-6 mb-1 d-flex">
                                    <strong class="me-2">Name:</strong>
                                    <span>{{ $packingSlip->customer->customer_name ?? 'N/A' }}</span>
                                </div>
                                <div class="col-md-6 mb-1 d-flex">
                                    <strong class="me-2">Person:</strong>
                                    <span>{{ $packingSlip->company->person_name ?? 'N/A' }}</span>
                                </div>
                                <div class="col-md-6 mb-1 d-flex">
                                    <strong class="me-2">Phone:</strong>
                                    <span>{{ $packingSlip->phone ?? 'N/A' }}</span>
                                </div>
                                <div class="col-md-6 mb-1 d-flex">
                                    <strong class="me-2">Address:</strong>
                                    <span>{{ $packingSlip->address ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


                @php
                    $borderColor = optional($packingSlip->company)->packing_slip_view_color ?? '#266BEE';
                @endphp

                <div class="card mt-3">
                    <div class="card-body table-responsive border-panel"
                        style="border-top: 3px solid {{ $borderColor }} !important;">
                        <table class="table table-hover">
                            @php
                                $totalQty = 0;
                                $totalWeight = 0;
                            @endphp

                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>HSN</th>
                                    <th class="without_price">Cartoon No</th>
                                    <th class="without_price">Qty</th>
                                    <th class="without_price">Weight</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!empty($packingSlip->packing_slip_item) && is_iterable($packingSlip->packing_slip_item))
                                    @foreach ($packingSlip->packing_slip_item as $item)
                                        @php
                                            $totalQty += $item->qty ?? 0;
                                            $totalWeight += $item->weight ?? 0;
                                        @endphp
                                        <tr>
                                            <td>{{ $item->product->product_name ?? '-' }}</td>
                                            <td>{{ $item->product->hsn_code ?? '-' }}</td>
                                            <td class="without_price">{{ $item->cartoon_no ?? '-' }}</td>
                                            <td class="without_price">{{ $item->qty ?? '-' }}</td>
                                            <td class="without_price">
                                                {{ isset($item->weight) ? number_format($item->weight, 2) : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    {{-- Totals Row --}}
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="without_price"><strong>{{ $totalQty }}</strong></td>
                                        <td class="without_price"><strong>{{ number_format($totalWeight, 2) }}</strong>
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No items found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                    </div>
                </div>

                <div class="with_price justify-content-start">
                    <div class="d-flex justify-content-start mb-3 px-3 col-12 col-md-12">
                        <h6 class="mb-2 text-start"><strong>Note:</strong></h6>
                        <p class="mb-2 text-start">{{ str_replace('&nbsp;', '', strip_tags($packingSlip->notes)) ?? '-' }}
                        </p>
                    </div>
                </div>

                <!-- Bank Details and Order Summary -->
                {{-- <div class="row mt-4 without_price">
                    <!-- Bank Details -->
                    <div class="col-12 col-md-7 order-1">
                        <div class="card mb-3">
                            <div class="card-body border-panel"
                                style="border-top: 3px solid {{ $borderColor }} !important;">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr style="background-color: var(--bs-gray-50);">
                                            <td class="pe-4 align-top"><strong>Bank Details:</strong></td>
                                            <td>{!! str_replace('</p>', '</p>' . PHP_EOL, $packingSlip->company->bank_details ?? '-') !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="pe-4"><strong>Terms and Conditions:</strong></td>
                                            <td>{!! str_replace('</p>', '</p>' . PHP_EOL, $packingSlip->term_and_condition ?? '-') !!}</td>
                                        </tr>
                                        <tr>
                                            <td class="pe-4 align-top"><strong>Regards:</strong></td>
                                            <td>{{ str_replace('&nbsp;', '', strip_tags($packingSlip->regards)) ?? '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="table table-borderless mt-3">
                                    <tbody>
                                        <tr>
                                            <td class="pe-4 align-top"><strong>Note:</strong></td>
                                            <td>{{ str_replace('&nbsp;', '', strip_tags($packingSlip->notes)) ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="pe-4 pt-sm-4"><strong>Amount (In Words):</strong></td>
                                            <td><strong>{{ App\Helpers\Helper::convertToRupees($packingSlip->grand_total) }}</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div> --}}

                <!-- Order Summary -->
                {{-- <div class="col-12 col-md-5 order-2">
                        <div class="card mb-3">
                            <div class="card-body border-panel"
                                style="border-top: 3px solid {{ $borderColor }} !important;">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th colspan="2" class="text-center">Order Summary (₹)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Sub Total</td>
                                            <td class="text-end"><b>₹{{ number_format($sub_total, 2) }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>Cash Discount ({{ $packingSlip->cash_discount_percentage }}%)</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->cash_discount_amount }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>Additional Discount ({{ $packingSlip->additional_discount_percentage }}%)
                                            </td>
                                            <td class="text-end"><b>₹{{ $packingSlip->additional_discount_amount }}</b>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Transport</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->transport_charge }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>Packing & Forwarding</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->packing_forwarding_charge }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>Total Taxable</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->total_taxable }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>GST Amount</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->gst_amount }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>TCS Amount</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->tcs_amount }}</b></td>
                                        </tr>
                                        <tr>
                                            <td>Rounded Off</td>
                                            <td class="text-end"><b>₹{{ $packingSlip->round_off }}</b></td>
                                        </tr>
                                        <tr>
                                            <td><b>Grand Total</b></td>
                                            <td class="text-end"><b>₹{{ $packingSlip->grand_total }}</b></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div> --}}
                {{-- </div> --}}
            </div>

        </div>
    </div>
    </div>


@endsection

@section('page_leavel_script')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        // OR using jQuery
        $(document).ready(function() {
            $(document).on('click', '.fab-toggle', function() {
                fabToggle();
            });

            function fabToggle() {
                const fabMenu = document.getElementById("fabMenu");
                fabMenu.classList.toggle("d-none");
            }
        });
        $(document).ready(function() {
            const $toggle = $('#withoutPriceToggle');
            const hideableColumns = ['without_price'];

            // Load saved state
            const withoutPrice = localStorage.getItem('sales_without_price') === 'true';
            $toggle.prop('checked', withoutPrice);
            toggleColumns(withoutPrice);
            toggleWithPriceSection(withoutPrice); // <-- Added

            // On toggle
            $toggle.on('change', function() {
                const isChecked = $(this).is(':checked');
                localStorage.setItem('sales_without_price', isChecked);
                toggleColumns(isChecked);
                toggleWithPriceSection(isChecked); // <-- Added
            });

            function toggleColumns(hide) {
                hideableColumns.forEach(function(cls) {
                    $('.' + cls).css('display', hide ? 'none' : '');
                });
            }

            // New function to toggle .with_price section
            function toggleWithPriceSection(show) {
                if (show) {
                    $('.with_price').show();
                } else {
                    $('.with_price').hide();
                }
            }
        });
    </script>

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendpackingSlipBtn"
                href="{{ route($modules['route'] . '.sendpackingSlipPdf', [$packingSlip->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.packingSlipPrint', [$packingSlip->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendpackingSlipBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('sales_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush
