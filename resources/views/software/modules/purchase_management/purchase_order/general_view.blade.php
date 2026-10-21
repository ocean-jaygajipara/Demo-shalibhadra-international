@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($purchaseOrder) && isset($purchaseOrder->company_id) && !empty($purchaseOrder->company_id)) {
        $company_id = $purchaseOrder->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
            // dd($sidebarMainLogo);
        }
    }
    // dd($page_title);
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px;
        }

        td,
        th {
            /* border: 1px solid #000; */
            padding: 5px;
            vertical-align: top;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
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
    </style>
@endsection

@php
    $canViewPrice = false;
    $currentGuard = $modules['currentGuard'] ?? null;
    $authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
    $parent_type_id = isset($modules['parent_type_id'])
    ? (int) $modules['parent_type_id']
    : 0;

    if ($currentGuard === 'admin_software') {
        // Company Admin or Super Admin
        $canViewPrice = true;
    } elseif ($currentGuard === 'team_person' && $authLoginUserDetail) {
        // Check if team_person has parent_type = 0
        if ($parent_type_id === 0) {
            $canViewPrice = true;
        }
    }
@endphp


@section('content')
    <div style="width: 100%; padding: 10px;">

        <!-- Header -->
        {{-- <table style="width: 100%;border-collapse: collapse;">
            <tr style="height:80px;">
                <td style="width: 20%; text-align:center;vertical-align: middle;">
                    <img src="{{ asset('software/img/logo.png') }}" style="height:40px;">
                </td>

            </tr>
        </table> --}}

        <div>
            <div class="card" style="border: 2px solid #333; padding: 10px;">

                @for ($i = 1; $i <= 2; $i++)
                    <!-- ONE COPY START -->
                    <div style="padding: 10px;">

                        <div class="row" style="padding: 10px; align-items: center;">

                            <!-- LEFT LOGO -->
                            <div class="col-md-6">
                                <img src="{{ $sidebarheaderLogo }}" style="height: 120px;">
                            </div>

                            <!-- RIGHT LOGO -->
                            {{-- <div class="col-md-6" style="text-align: right;">
                                <img src="http://127.0.0.1:8000/software/img/logo.png" style="height: 92px;">
                            </div> --}}

                        </div>

                        <div style="text-align: center; font-size: 13px; margin-top: 5px;">
                            <h4> Plot No. 4536, GIDC, Phase III, Dared, JAMNAGAR<br>
                                Ph : 02882731337</h4>
                        </div>

                        <hr>

                        <!-- Header -->
                        <div class="row">
                            <div class="col-md-6" style="padding: 20px; text-align: left;">

                                <h5 style="margin: 0;"> <label>Recpt No:</label> {{ $purchaseOrder->purchase_order_no }}
                                </h5>
                                <h5 style="margin: 0;">{{ $purchaseOrder->vendor_name ?: (optional($purchaseOrder->vendor)->vendor_name ?? '-') }}</h5>


                            </div>

                            <div class="col-md-6" style="padding: 20px; text-align: right;">
                                <h5 style="margin: 0;">
                                    <label>Date:</label>
                                    {{ date('d-m-Y', strtotime($purchaseOrder->purchase_order_date)) }}
                                </h5>
                            </div>

                        </div>

                        <!-- Items Table -->
                        <div style="padding: 20px;">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 80px; text-align: center;">Sr. No</th>
                                        <th>Particulars</th>
                                        <th style="text-align: right;">Grs</th>
                                        <th style="text-align: right;">Qty</th>
                                        <th style="text-align: right;">Kg</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        $totalQty = 0;
                                        $totalKg = 0;
                                        $sr = 1;
                                    @endphp

                                    @foreach ($purchaseOrder->purchase_order_items as $item)
                                        @php

                                            $grossWtGram = $item->product->gross_wt ?? 0;
                                            $grossePerQty = $item->product->grosse_per_qty ?? 0;
                                            $unit = strtolower($item->unit ?? 'pcs');
                                            $inputValue = $item->quantity ?? 0;

                                            $grossWtKg = $grossWtGram / 1000;

                                            $qty = 0;
                                            $kg = 0;
                                            $divideValue = 0;

                                            if ($unit == 'kg') {
                                                $kg = $inputValue;
                                                if ($grossWtKg > 0) {
                                                    $qty = $kg / $grossWtKg;
                                                }
                                                if ($grossePerQty > 0) {
                                                    $divideValue = $qty / $grossePerQty;
                                                }
                                            } elseif ($unit == 'gross') {
                                                $divideValue = $inputValue;

                                                if ($grossePerQty > 0) {
                                                    $qty = $inputValue * $grossePerQty;

                                                    $qty = ceil($qty);
                                                }

                                                if ($grossWtKg > 0) {
                                                    $kg = $qty * $grossWtKg;
                                                }
                                            } else {
                                                $qty = $inputValue;
                                                if ($grossWtKg > 0) {
                                                    $kg = $qty * $grossWtKg;
                                                }
                                                if ($grossePerQty > 0) {
                                                    $divideValue = $qty / $grossePerQty;
                                                }
                                            }

                                            $totalQty += $qty;
                                            $totalKg += $kg;
                                        @endphp

                                        <tr>
                                            <td style="text-align:center;">{{ $sr++ }}</td>
                                            <td>{{ $item->product_name }}</td>

                                            <!-- Gross -->
                                            <td style="text-align:right;">{{ ($unit == 'kg' && $grossWtKg <= 0) ? '-' : number_format($divideValue, 2) }}</td>

                                            <!-- Qty -->
                                            <td style="text-align:right;">{{ ($unit == 'kg' && $grossWtKg <= 0) ? '-' : number_format($qty, 2) }}</td>

                                            <!-- Kg -->
                                            <td style="text-align:right;">{{ ($unit != 'kg' && $grossWtKg <= 0) ? '-' : number_format($kg, 3) }}</td>
                                        </tr>
                                    @endforeach


                                    <tr style="background:#f8f9fa; font-weight:bold;">
                                        <td colspan="3">Total Weight & Qty</td>
                                        <td style="text-align:right;">{{ $totalQty > 0 ? number_format($totalQty, 2) : '-' }}</td>
                                        <td style="text-align:right;">{{ $totalKg > 0 ? number_format($totalKg, 3) : '-' }}</td>
                                    </tr>

                                </tbody>

                            </table>

                            <!-- Received By -->
                            <div style="margin-top: 20px;padding: 10px;">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Received By:</strong>
                                    </div>
                                    <div class="col-6" style="text-align: right;">
                                        <div><strong>SHALIBHADRA INTERNATIONAL PVT LTD</strong></div>
                                        <div>Chirag</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                    <!-- ONE COPY END -->

                    <!-- Divider between 2 copies -->
                    @if ($i == 1)
                        <hr style="border: none; margin: 75px 0;">
                    @endif
                @endfor

            </div>

        </div>






        </td>
        </tr>
        </table>


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
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendpurchaseOrderBtn"
                href="{{ route($modules['route'] . '.sendPurchaseOrderPdf', [$purchaseOrder->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route('whatsapp.send', ['id' => $purchaseOrder->id, 'type' => 'purchase_order']) }}"
                title="Send WhatsApp" target="_blank"><i class="fab fa-whatsapp"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.purchaseOrderPrint', [$purchaseOrder->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendpurchaseOrderBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('sales_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush

{{-- @extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($purchaseOrder) && isset($purchaseOrder->company_id) && !empty($purchaseOrder->company_id)) {
        $company_id = $purchaseOrder->company_id;
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
            .quotation_dv {
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

    <div class="d-flex justify-content-end mb-3 px-3">
        <label for="withoutPriceToggle" class="d-flex align-items-center" style="font-size: 1.25rem;">
            <input type="checkbox" class="form-check-input me-2" id="withoutPriceToggle"
                style="width: 20px; height: 20px;position: unset;opacity: unset; pointer-events: unset;">
            Without Price
        </label>
    </div>
    <div class="row invoice-preview w-100" id="purchase_order_download_save">
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

                <div class="card-body px-0">

                    <div class="row">
                        <h4 class="text-center fw-bold mb-3">
                            {{ optional($purchaseOrder->company)->purchase_order_title ?? '-' }}
                        </h4>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <h5><strong>Purchase Order No : {{ $purchaseOrder->purchase_order_no ?? '-' }}</strong></h5>
                            <p><strong>Purchase Order Date:</strong>
                                {{ $purchaseOrder->purchase_order_date ? \Carbon\Carbon::parse($purchaseOrder->purchase_order_date)->format('d-m-Y') : '-' }}
                            </p>
                            <p><strong>GSTIN:</strong> {{ $purchaseOrder->gst_no ?? '-' }}</p>
                            <p><strong>Transport By:</strong> {{ $purchaseOrder->transport_by->name ?? '-' }}</p>
                            <p><strong>Transporter Detail:</strong> {{ $purchaseOrder->transport_detail->name ?? '-' }}</p>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <h5><strong>Vendor Detail:</strong></h5>
                            <p><strong>Name:</strong> {{ $purchaseOrder->vendor->vendor_name ?? 'N/A' }}</p>
                            <p><strong>Phone:</strong> {{ $purchaseOrder->phone ?? 'N/A' }}</p>
                            <p><strong>Address:</strong> {{ $purchaseOrder->address ?? 'N/A' }}</p>
                        </div>
                        @if (trim($purchaseOrder->billing_address ?? '') === trim($purchaseOrder->shipping_address ?? ''))
                            <div class="col-xl-3 col-md-6 col-12 mb-4">
                                <h5><b>Billing Address:</b></h5>
                                <p>{{ $purchaseOrder->billing_address ?? '-' }}</p>
                            </div>
                        @else
                            <div class="col-xl-3 col-md-6 col-12 mb-4">
                                <h5><b>Billing Address:</b></h5>
                                <p>{{ $purchaseOrder->billing_address ?? '-' }}</p>
                            </div>
                            <div class="col-xl-3 col-md-6 col-12 mb-4">
                                <h5><b>Shipping Address:</b></h5>
                                <p>{{ $purchaseOrder->shipping_address ?? '-' }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body table-responsive border-panel">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>HSN</th>
                                    <th>Unit</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $sub_total = 0; @endphp
                                @forelse($purchaseOrder->purchase_order_items as $item)
                                     @php
                                        $price = $item->price_in_amount ?? 0;
                                        $discountPercent = $item->discount_percentage ?? 0;
                                        $discountFlat = $item->discount_flat ?? 0;
                                        $discountAmount = $price * ($discountPercent / 100);
                                        $totalDiscount = $discountAmount + $discountFlat;
                                        $rate = $price - $totalDiscount;
                                        $quantity = $item->quantity ?? 1;
                                        $amount = $rate * $quantity;

                                        $sub_total += $amount;

                                        $quantity = strtolower($item->unit) === 'kg'
                                            ? number_format($item->quantity ?? 0, 3, '.', '')
                                            : (int) ($item->quantity ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $item->product_name ?? '-' }}</td>
                                        <td>{{ $item->product_description ?? '-' }}</td>
                                        <td>{{ $item->hsn_code ?? '-' }}</td>
                                        <td>{{ $item->unit ?? '-' }}</td>
                                        <td>{{ $quantity }}</td>
                                        <td>{{ number_format($price, 2) }}</td>
                                        <td>{{ number_format($totalDiscount, 2) }}</td>
                                        <td>{{ number_format($rate, 2) }}</td>
                                        <td>{{ number_format($amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No items found</td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-7">
                        <div class="card mb-3">
                            <div class="card-body border-panel">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Terms and Conditions:</strong></td>
                                        <td>{!! $purchaseOrder->term_condition?->description ?? '-' !!}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Regards:</strong></td>
                                        <td>{{ strip_tags($purchaseOrder->regards) ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Note:</strong></td>
                                        <td>{{ strip_tags($purchaseOrder->notes) ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount (In Words):</strong></td>
                                        <td><strong>{{ App\Helpers\Helper::convertToRupees($purchaseOrder->grand_total) }}</strong>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card mb-3">
                            <div class="card-body border-panel">
                                <table class="table">
                                    <tr>
                                        <td>Sub Total</td>
                                        <td class="text-end">₹{{ number_format($sub_total, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Cash Discount ({{ $purchaseOrder->cash_discount_percentage }}%)</td>
                                        <td class="text-end">₹{{ $purchaseOrder->cash_discount_amount }}</td>
                                    </tr>
                                    <tr>
                                        <td>Additional Discount ({{ $purchaseOrder->additional_discount_percentage }}%)
                                        </td>
                                        <td class="text-end">₹{{ $purchaseOrder->additional_discount_amount }}</td>
                                    </tr>
                                    <tr>
                                        <td>Transport</td>
                                        <td class="text-end">₹{{ $purchaseOrder->transport_charge }}</td>
                                    </tr>
                                    <tr>
                                        <td>Packing & Forwarding</td>
                                        <td class="text-end">₹{{ $purchaseOrder->packing_forwarding_charge }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Taxable</td>
                                        <td class="text-end">₹{{ $purchaseOrder->total_taxable }}</td>
                                    </tr>
                                    <tr>
                                        <td>GST Amount</td>
                                        <td class="text-end">₹{{ $purchaseOrder->gst_amount }}</td>
                                    </tr>
                                    <tr>
                                        <td>TCS Amount</td>
                                        <td class="text-end">₹{{ $purchaseOrder->tcs_amount }}</td>
                                    </tr>
                                    <tr>
                                        <td>Rounded Off</td>
                                        <td class="text-end">₹{{ $purchaseOrder->round_off }}</td>
                                    </tr>
                                    <tr>
                                        <th>Grand Total</th>
                                        <th class="text-end">₹{{ $purchaseOrder->grand_total }}</th>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
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
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendpurchaseOrderBtn"
                href="{{ route($modules['route'] . '.sendPurchaseOrderPdf', [$purchaseOrder->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.purchaseOrderPrint', [$purchaseOrder->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendpurchaseOrderBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('sales_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush --}}
