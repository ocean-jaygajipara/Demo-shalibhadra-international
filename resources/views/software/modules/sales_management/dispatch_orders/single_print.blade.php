<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>dispatch {{ $dispatch->dispatch_no ?? '-' }}
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
            .dispatch_dv {
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
            const hidePrices = localStorage.getItem('dispatch_without_price') === 'true';
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
    if (isset($dispatch) && isset($dispatch->company_id) && !empty($dispatch->company_id)) {
        $company_id = $dispatch->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }
    $borderColor = optional($dispatch->company)->dispatch_view_color ?? '#266BEE';
@endphp

<body style="--bs-scrollbar-width: 0px;" onload="printAndRedirect();">

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y" style="margin: 0px;padding: 0px;">
                        <div class="row invoice-preview" id="invoice-preview-main" style="width: 1100px;">
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
                                            {{ optional($dispatch->company)->dispatch_title ?? '-' }}</h4>
                                        <div class="row">
                                            <div class="col-md-3 dispatch_dv">
                                                <h5 class="mb-6"><strong>Dispatch No :
                                                        {{ $dispatch->dispatch_no ?? '-' }}</strong></h5>
                                                <div class="mb-1 text-heading">
                                                    <span>Dispatch Date:</span>
                                                    <span
                                                        class="fw-medium">{{ $dispatch->dispatch_date ? \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d-m-Y') : '' }}</span>
                                                </div>
                                                <div class="text-heading">
                                                    <span><strong>GSTIN : </strong></span>
                                                    <span>{{ $dispatch->gst_no ?? '-' }}</span>
                                                </div>
                                                <div class="text-heading">
                                                    <span>Transport By : </span>
                                                    <span>{{ $dispatch?->transport_by?->name ?? '-' }}</span>
                                                </div>
                                                <div class="text-heading">
                                                    <span>Transporter Detail : </span>
                                                    <span>{{ $dispatch?->transport_detail?->name ?? '-' }}</span>
                                                </div>
                                            </div>
                                            <div
                                                class="col-xl-3 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-6 mb-sm-0 mb-6 invoice_dv">
                                                <h5><strong>Customer Detail:</strong></h5>
                                                <p class="mb-1"><b>Customer Name :
                                                    </b>{{ $dispatch->customer->customer_name ?? 'N/A' }}</p>
                                                <p class="mb-1"><b>Person Name :
                                                    </b>{{ $dispatch->company->person_name ?? 'N/A' }}</p>
                                                <p class="mb-1"><b>Tel/Mobile : </b>{{ $dispatch->phone ?? 'N/A' }}
                                                </p>
                                                <p class="mb-1"><b>Email :
                                                    </b>{{ $dispatch->customer->email ?? 'N/A' }}</p>
                                                <p class="mb-0"><b>Price List :
                                                    </b>{{ $dispatch->price_list?->name ?? 'N/A' }}</p>
                                                <p class="mb-0"><b>Address : </b>{{ $dispatch->address ?? 'N/A' }}
                                                </p>
                                            </div>
                                            <div class="col-xl-3 col-md-12 col-sm-7 col-12 billing_dv">
                                                <h5><strong>Billing Address:</strong></h5>
                                                <table>
                                                    <tbody>
                                                        <tr>
                                                            <td class="pe-4">{{ $dispatch->billing_address ?? '-' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-xl-3 col-md-12 col-sm-7 col-12 shipping_dv">
                                                <h5><strong>Shipping Address:</strong></h5>
                                                <table>
                                                    <tbody>
                                                        <tr>
                                                            <td class="pe-4">{{ $dispatch->shipping_address ?? '-' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table table-hover table-bordered rounded border-panel"
                                        style="border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                        <table class="table m-0">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Description</th>
                                                    <th>HSN</th>
                                                    <th>Unit</th>
                                                    <th class="price-col">Qty</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $sub_total = 0; @endphp
                                                @forelse($dispatch->dispatch_items as $item)
                                                    @php $sub_total += $item->amount; @endphp
                                                    <tr>
                                                        <td class="text-nowrap text-heading">
                                                            {{ $item->product_name ?? '-' }}</td>
                                                        <td class="text-nowrap">{{ $item->product_description ?? '-' }}
                                                        </td>
                                                        <td>{{ $item->hsn_code ?? '-' }}</td>
                                                        <td>{{ $item->unit ?? '-' }}</td>
                                                        <td class="price-col">{{ $item->dispatch_quantity ?? '-' }}
                                                        </td>

                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted">No items found
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="justify-content-start no_display">
                                        <div class="d-flex justify-content-start mb-3 px-3 col-12 col-md-12">
                                            <h6 class="mb-2 text-start"><strong>Note:</strong></h6>
                                            <p class="mb-2 text-start">
                                                {{ str_replace('&nbsp;', '', strip_tags($dispatch->remarks)) ?? '-' }}
                                            </p>
                                        </div>
                                    </div>

                                    {{-- <div class="col-md-12 mt-5 price-col">
                                <div class="row mb-sm" style="width:100%;margin:0px;">

                                    <div style="width: 53%;float: left;padding:0px 10px 0 0;">
                                        <div class="panel-body no-padding" style="margin-bottom:10px;">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered m-n invoice border-panel" style="border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                                    <tbody>
                                                    <tr style="background-color: var(--bs-gray-50);">
                                                        <td class="pe-4 align-top" style="vertical-align: top;"><strong>Bank Details:</strong></td>
                                                        <td>{!! str_replace('</p>', '</p>' . PHP_EOL, $dispatch->company->bank_details ?? '-') !!}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="pe-4"><strong>Terms and Conditions:</strong></td>
                                                        <td>{{ str_replace("&nbsp;", "", strip_tags($dispatch->term_and_condition)) ?? '-' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="pe-4 align-top" style="vertical-align: top;"><strong>Regards:</strong></td>
                                                        <td>{{ str_replace("&nbsp;", "", strip_tags($dispatch->regards)) ?? '-' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="pe-4 align-top" style="vertical-align: top;"><strong>Note:</strong></td>
                                                        <td>{{ str_replace("&nbsp;", "", strip_tags($dispatch->notes)) ?? '-' }}</td>
                                                    </tr>
                                                     <tr>
                                                        <td class="pe-4 align-top" style="vertical-align: top;"><strong>Amount (In Words) : </strong></td>
                                                        <td><strong>{{ App\Helpers\Helper::convertToRupees($dispatch->grand_total); }}</strong></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div> --}}
                                    {{-- <div class="table-responsive mt-5">
                                                <table>
                                                    <tbody>
                                                    <tr>
                                                        <td class="pe-4 align-top" style="vertical-align: top;"><strong>Note:</strong></td>
                                                        <td>{{ str_replace("&nbsp;", "", strip_tags($dispatch->notes)) ?? '-' }}</td>
                                                    </tr>
                                                     <tr>
                                                        <td class="pe-4 pt-sm-4"><strong>Amount (In Words) : </strong></td>
                                                        <td><strong>{{ App\Helpers\Helper::convertToRupees($dispatch->grand_total); }}</strong></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div> --}}
                                    {{-- </div> --}}

                                    {{-- </div>
                                    <div style="width: 47%;float: right;padding-right: 0;">
                                        <div class="panel-body no-padding">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered m-n invoice border-panel" style="color: #000;border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                                    <thead>
                                                        <tr>
                                                            <th colspan="2" class="text-center">
                                                                Order Summary (<span class="currencysymbol">₹</span>)
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Sub Total</td>
                                                            <td class="text-right"><b>₹{{ number_format($sub_total, 2) }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Cash Discount Amount <span class="per text-right">( {{ $dispatch->cash_discount_percentage }} % )</span></td>
                                                            <td class="text-right"><b>₹{{ $dispatch->cash_discount_amount }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Additional Discount Amount
                                                                <span class="per text-right">( {{ $dispatch->additional_discount_percentage }} % )</span>
                                                            </td>
                                                            <td class="text-right">
                                                                <span id="tcs" name="tcsamount"><b>₹{{ $dispatch->additional_discount_amount }}</b></span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Transport</td>
                                                            <td class="text-right"><b>₹{{ $dispatch->transport_charge }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Packing & Forwarding</td>
                                                            <td class="text-right"><b>₹{{ $dispatch->packing_forwarding_charge }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total Taxable</td>
                                                            <td class="text-right"><b>₹{{ $dispatch->total_taxable }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>GST Amount</td>
                                                            <td class="text-right"><b>₹{{ $dispatch->gst_amount }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>TCS Amount</td>
                                                            <td class="text-right"><b>₹{{ $dispatch->tcs_amount }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Rounded Off</td>
                                                            <td class="text-right"><b>₹{{ $dispatch->round_off }}</b></td>
                                                        </tr>
                                                        <tr>
                                                            <td><b>Grand Total</b></td>
                                                            <td class="text-right"><b>₹{{ $dispatch->grand_total }}</b></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr> --}}
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
