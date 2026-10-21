<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>packingSlip {{ $packingSlip->packing_slip_no ?? '-' }}
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

            @page {
                size: 100mm 75mm;
                margin: 0;
            }

            html,
            body {
                margin: 0;
                padding: 0;
            }

            .page {
                width: 100mm;
                height: 75mm;
                display: flex;
                justify-content: center;
                align-items: center;
                page-break-after: always;
            }

            /* 👇 REAL FIX */
            .label-wrapper {
                width: 96mm;
                /* ⬅️ SAFE WIDTH */
                height: 71mm;
                /* ⬅️ SAFE HEIGHT */
                border: 2px solid #000;
                box-sizing: border-box;
                padding: 3mm;
            }


            .item-name,
            .code-name {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                text-overflow: ellipsis;
                line-height: 1.2;
                min-height: calc(1.2em * 2);
                /* ✅ FIXED 2 LINES */
            }

                .label-inner {
                width: 100%;
                height: 100%;
                border: 1px solid #000;
                display: flex;
                flex-direction: column;
                font-size: 12px;
                line-height: 1.2;
            }

            .label-logo {
                text-align: center;
                padding: 4px 0 2px;
                border-bottom: 1px solid #000;
            }

            .label-logo img {
                max-height: 19mm;
                /* slightly increased for bigger label */
            }

            .label-content {
                flex: 1;
            }

            .label-data {
                margin: 4px;
            }

            .label-content p {
                margin: 2px 0;
                font-size: 16px;
                font-weight: 600;
                color: #000;
            }

            .label-footer {
                border-top: 1px solid #000;
                padding: 0px 4px;
                text-align: right;
                font-weight: bold;
                font-size: 9px;
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
    @foreach ($packingSlip->packing_slip_item as $item)
        @php
            $totalLabels = max(1, (int)($item->box_no ?? 1));
        @endphp

        @for ($i = 1; $i <= $totalLabels; $i++)
            <div class="page">
                <div class="label-wrapper">
                    <div class="label-inner">

                        <div class="label-logo">
                            <img src="{{ $sidebarMainLogo }}">
                        </div>

                        <div class="label-content">
                            <div class="label-data">
                                @php
                                    $prodName = $item->product ? ($item->product->product_name ?: $item->product->product_description) : $item->product_name;
                                    $prodPartNo = $item->product?->part_no ?? $item->product_code;
                                    $psCustomer = $packingSlip->customer ?? ($packingSlip->sales_order->customer ?? null);
                                    $custItemCode = '';
                                    if ($psCustomer && $psCustomer->customer_name) {
                                        $cItem = \App\Models\ProductCustomerItem::where('company_id', $packingSlip->company_id ?? $item->company_id)
                                            ->where('product_id', $item->product_id)
                                            ->where('customer_name', $psCustomer->customer_name)
                                            ->whereNull('deleted_at')
                                            ->first();
                                        if ($cItem) {
                                            $custItemCode = trim($cItem->customer_item_code ?? '');
                                        }
                                    }
                                @endphp
                                <p class="item-name">
                                    <strong>ITEM :</strong> {{ $prodName }}
                                </p>

                                <p class="code-name">
                                    <strong>CODE :</strong> {{ $prodPartNo }}
                                </p>

                                @if (!empty($custItemCode))
                                    <p>
                                        <strong>Cust. Item CODE :</strong>
                                        {{ $custItemCode }}
                                    </p>
                                @else
                                    <p style="visibility:hidden;">&nbsp;</p>
                                @endif

                                @php
                                    $unitDisplay = !empty($item->unit) ? (strtolower($item->unit) === 'pcs' ? 'Pcs' : ucfirst($item->unit)) : 'Pcs';
                                    $boxCount = (float)($item->box_no ?? 0);
                                    $qtyCtn = (float)($item->qty_ctn ?? 0);
                                    $totalQtyVal = (float)($item->total_qty ?? 0);
                                    $totalQtyFormatted = (int)$totalQtyVal == $totalQtyVal ? (int)$totalQtyVal : number_format($totalQtyVal, 2);
                                    if ($boxCount > 0 && $qtyCtn > 0) {
                                        $boxFormatted = (int)$boxCount == $boxCount ? (int)$boxCount : $boxCount;
                                        $qtyText = $boxFormatted . ' Box X ' . number_format($qtyCtn) . ' ' . $unitDisplay . ' = ' . $totalQtyFormatted;
                                    } else {
                                        $qtyText = number_format($totalQtyVal) . ' ' . $unitDisplay;
                                    }
                                @endphp
                                <p><strong>QTY :</strong> {{ $qtyText }}</p>

                                <p>
                                    <strong>P.S. NO :</strong> {{ $packingSlip->packing_slip_no }}
                                    &nbsp;
                                    <strong>DTD :</strong>
                                    {{ \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="label-footer">
                            MADE IN INDIA
                        </div>

                    </div>
                </div>
            </div>
        @endfor
    @endforeach
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
