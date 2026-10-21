<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>packingSlip {{ $packingSlip->sales_order_no ?? '-' }}
        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }} - Print </title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Optional: Boxicons or other icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
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
                border-radius: 3px !important;
            }

            .text-right {
                text-align: right !important;
            }

            .without_price .price-col,
            .without_price .rate-col,
            .without_price .qty-col,
            .without_price .amount-col,
            .without_price .discount-col {
                display: none !important;
            }

            .with_price .no_display {
                display: none !important;
            }
        }
    </style>
    <script>
        (function() {
            const hidePrices = localStorage.getItem('sales_without_price') === 'true';
            if (hidePrices) {
                document.write('<body class="without_price">');
            } else {
                document.write('<body class="with_price">');
            }
        })();
    </script>
</head>
@php
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
    $borderColor = optional($packingSlip->company)->packing_slip_view_color ?? '#266BEE';
@endphp

<body style="--bs-scrollbar-width: 0px;" onload="printAndRedirect();">

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y" style="margin: 0px;padding: 0px;">
                        <div class="row invoice-preview" style="width: 1100px;">
                            <!-- Invoice -->
                            <div class="col-xl-12 col-md-12 col-12 mb-md-0 mb-6">
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
                                    <hr>
                                    <div class="card-body px-0">
                                        <h4 style="text-align: center;font-weight: 700;margin-bottom: 22px;">
                                            {{ optional($packingSlip->company)->packing_slip_title ?? '-' }}</h4>
                                        <div class="row">
                                            <div class="col-md-3 quotation_dv">
                                                <h5 class="mb-6"><strong>Packing Slip No :
                                                        {{ $packingSlip->packing_slip_no ?? '-' }}</strong></h5>
                                                <div class="mb-1 text-heading">
                                                    <span>Packing Slip Date:</span>
                                                    <span
                                                        class="fw-medium">{{ $packingSlip->packing_slip_date ? \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d-m-Y') : '' }}</span>
                                                </div>

                                            </div>
                                            <div
                                                class="col-xl-3 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-6 mb-sm-0 mb-6 invoice_dv">
                                                <h5><strong>Customer Detail:</strong></h5>
                                                <p class="mb-1"><b>Customer Name :
                                                    </b>{{ $packingSlip->customer->customer_name ?? 'N/A' }}</p>
                                                <p class="mb-1"><b>Person Name :
                                                    </b>{{ $packingSlip->company->person_name ?? 'N/A' }}</p>
                                                <p class="mb-1"><b>Tel/Mobile : </b>{{ $packingSlip->phone ?? 'N/A' }}
                                                </p>

                                                <p class="mb-0"><b>Address : </b>{{ $packingSlip->address ?? 'N/A' }}
                                                </p>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="table table-hover table-bordered rounded border-panel"
                                        style="border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                        <table class="table m-0">
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
                                                            <td class="without_price">{{ $item->cartoon_no ?? '-' }}
                                                            </td>
                                                            <td class="without_price">{{ $item->qty ?? '-' }}</td>
                                                            <td class="without_price">
                                                                {{ isset($item->weight) ? number_format($item->weight, 2) : '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    {{-- Totals Row --}}
                                                    <tr>
                                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                        <td class="without_price"><strong>{{ $totalQty }}</strong>
                                                        </td>
                                                        <td class="without_price">
                                                            <strong>{{ number_format($totalWeight, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">No items found
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>





                                </div>
                            </div>
                            <!-- /Invoice -->

                        </div>
                    </div>
                </div> <!-- layout-page -->
            </div> <!-- layout-container -->
        </div> <!-- layout-wrapper -->
    </div>


    <script>
        function printAndRedirect() {
            window.print();

            window.onafterprint = function() {
                setTimeout(function() {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 1);
            };
        }
    </script>
</body>

</html>
