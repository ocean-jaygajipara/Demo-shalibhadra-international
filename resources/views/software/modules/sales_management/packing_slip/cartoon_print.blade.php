<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Carton Label {{ $packingSlip->packing_slip_no ?? '-' }}
        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }} - Print </title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

            .label-wrapper {
                width: 96mm;
                height: 71mm;
                border: 2px solid #000;
                box-sizing: border-box;
                padding: 3mm;
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

        @media screen {
            body {
                background: #f4f6f9;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .label-wrapper {
                width: 97mm;
                height: 70mm;
                border: 2px solid #000;
                box-sizing: border-box;
                padding: 4mm;
                background: #fff;
                display: flex;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            .label-inner {
                width: 100%;
                height: 100%;
                border: 1px solid #000;
                box-sizing: border-box;
                font-size: 13px;
                line-height: 1.25;
                display: flex;
                flex-direction: column;
            }

            .label-logo {
                text-align: center;
                padding: 8px 0 3px 0;
                border-bottom: 1px solid #000;
            }

            .label-logo img {
                max-height: 16mm;
            }

            .label-content {
                flex: 1;
            }

            .label-data {
                margin: 8px;
            }

            .label-content p {
                margin: 4px 0;
                font-weight: 600;
                color: #000;
            }

            .label-footer {
                border-top: 1px solid #000;
                padding: 4px 6px;
                text-align: right;
                font-weight: bold;
                font-size: 13px;
                color: #000;
            }
        }
    </style>

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

        $invoiceNo = $packingSlip->invoice?->invoice_order_no ?? ($packingSlip->packing_slip_no ?? '-');
        $invoiceDate = $packingSlip->invoice?->invoice_order_date
            ? \Carbon\Carbon::parse($packingSlip->invoice->invoice_order_date)->format('d/m/Y')
            : ($packingSlip->packing_slip_date
                ? \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d/m/Y')
                : '-');

        $totalCartons = max(1, (int) ($packingSlip->total_no_of_carton ?? 1));
    @endphp
</head>

<body onload="printAndRedirect();">
    @php
        $items = $packingSlip->packing_slip_item;
    @endphp

    @if ($items && count($items) > 0)
        @foreach ($items as $item)
            @php
                $prodName = $item->product
                    ? ($item->product->product_name ?: $item->product->product_description)
                    : $item->product_name;
                $prodPartNo = trim($item->product?->part_no ?? '');
                $fallbackCode = trim($item->product_code ?? ($prodName ?? '--'));

                $custItemCode = '';
                if (!empty($item->customerItem?->customer_item_code)) {
                    $custItemCode = trim($item->customerItem->customer_item_code);
                } else {
                    $custName = $packingSlip->customer_name ?: ($packingSlip->customer?->customer_name ?: ($packingSlip->sales_order?->customer?->customer_name ?? ''));
                    $clientCode = $packingSlip->customer?->client_code ?? ($packingSlip->customer?->customer_code ?? '');

                    $cItemQuery = \App\Models\ProductCustomerItem::where('product_id', $item->product_id)
                        ->whereNull('deleted_at');

                    if (!empty($packingSlip->company_id)) {
                        $cItemQuery->where('company_id', $packingSlip->company_id);
                    }

                    $cItem = (clone $cItemQuery)->where(function ($q) use ($custName, $clientCode) {
                        if (!empty($custName)) {
                            $q->where('customer_name', $custName);
                        }
                        if (!empty($clientCode)) {
                            $q->orWhere('customer_code', $clientCode);
                        }
                    })->first();

                    if (!$cItem && !empty($custName)) {
                        $cItem = (clone $cItemQuery)->where('customer_name', 'like', '%' . trim($custName) . '%')->first();
                    }

                    if ($cItem && !empty($cItem->customer_item_code)) {
                        $custItemCode = trim($cItem->customer_item_code);
                    }
                }

                if (!empty($custItemCode)) {
                    $itemDisplay = $custItemCode;
                } elseif (!empty($prodPartNo)) {
                    $itemDisplay = $prodPartNo;
                } else {
                    $itemDisplay = $fallbackCode;
                }

                $unitDisplay = !empty($item->unit)
                    ? (strtolower($item->unit) === 'pcs' ? 'Pcs' : ucfirst($item->unit))
                    : 'Pcs';
                $packetCount = (float) ($item->total_packet ?? 0);
                $boxCount = (float) ($item->box_no ?? 0);
                $qtyPc = (float) ($item->qty_pc ?? 0);
                $qtyCtn = (float) ($item->qty_ctn ?? 0);
                $totalQtyVal = (float) ($item->total_qty ?? 0);

                if ($packetCount > 0 && $qtyPc > 0) {
                    $boxFormatted = (int) $packetCount == $packetCount ? (int) $packetCount : $packetCount;
                    $qtyPerFormatted =
                        (int) $qtyPc == $qtyPc ? number_format($qtyPc) : number_format($qtyPc, 2);
                    $finalTotal = $qtyCtn > 0 ? $qtyCtn : ($packetCount * $qtyPc);
                    $totalFormatted =
                        (int) $finalTotal == $finalTotal
                            ? (int) $finalTotal
                            : number_format($finalTotal, 2);
                    $qtyFormatted =
                        $boxFormatted .
                        ' Box X ' .
                        $qtyPerFormatted .
                        ' ' .
                        $unitDisplay .
                        ' = ' .
                        $totalFormatted;
                } elseif ($boxCount > 0 && $qtyPc > 0) {
                    $boxFormatted = (int) $boxCount == $boxCount ? (int) $boxCount : $boxCount;
                    $qtyPerFormatted =
                        (int) $qtyPc == $qtyPc ? number_format($qtyPc) : number_format($qtyPc, 2);
                    $totalFormatted =
                        (int) $totalQtyVal == $totalQtyVal
                            ? (int) $totalQtyVal
                            : number_format($totalQtyVal, 2);
                    $qtyFormatted =
                        $boxFormatted .
                        ' Box X ' .
                        $qtyPerFormatted .
                        ' ' .
                        $unitDisplay .
                        ' = ' .
                        $totalFormatted;
                } elseif ($qtyCtn > 0) {
                    $qtyFormatted = number_format($qtyCtn) . ' ' . $unitDisplay;
                } else {
                    $totalFormatted =
                        (int) $totalQtyVal == $totalQtyVal
                            ? (int) $totalQtyVal
                            : number_format($totalQtyVal, 2);
                    $qtyFormatted = $totalFormatted . ' ' . $unitDisplay;
                }

                $totalLabels = max(1, (int) ($item->box_no ?? 1));
                $startCarton = max(1, (int) ($item->no_of_carton ?? 1));
            @endphp

            @for ($c = 1; $c <= $totalLabels; $c++)
                @php
                    $cartonNoVal = $startCarton + ($c - 1);
                @endphp
                <div class="page">
                    <div class="label-wrapper">
                        <div class="label-inner">

                            <div class="label-logo">
                                <img src="{{ $sidebarMainLogo }}">
                            </div>

                            <div class="label-content">
                                <div class="label-data">
                                    <p><strong>ITEM :</strong> {{ $itemDisplay }}</p>
                                    <p><strong>QTY :</strong> {{ $qtyFormatted }}</p>
                                    <p><strong>INVOICE NO :</strong> {{ $invoiceNo }}</p>
                                    <p><strong>INVOICE DATE :</strong> {{ $invoiceDate }}</p>
                                    <p><strong>CARTON NO. :</strong> {{ $cartonNoVal }} / {{ $totalCartons }}</p>
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
    @else
        @for ($i = 1; $i <= $totalCartons; $i++)
            <div class="page">
                <div class="label-wrapper">
                    <div class="label-inner">

                        <div class="label-logo">
                            <img src="{{ $sidebarMainLogo }}">
                        </div>

                        <div class="label-content">
                            <div class="label-data">
                                <p><strong>ITEM :</strong> --</p>
                                <p><strong>QTY :</strong> --</p>
                                <p><strong>INVOICE NO :</strong> {{ $invoiceNo }}</p>
                                <p><strong>INVOICE DATE :</strong> {{ $invoiceDate }}</p>
                                <p><strong>CARTON NO. :</strong> {{ $i }} / {{ $totalCartons }}</p>
                            </div>
                        </div>

                        <div class="label-footer">
                            MADE IN INDIA
                        </div>

                    </div>
                </div>
            </div>
        @endfor
    @endif

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
