<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $purchaseOrder->purchase_order_no ?? '-' }} - PDF</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 8mm 6mm 8mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .slip-box {
            border: 1.5px solid #222;
            padding: 10px 12px;
            box-sizing: border-box;
        }

        .header-table td {
            border: none;
            padding: 2px 0;
        }

        .meta-table td {
            border: none;
            padding: 3px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .items-table {
            margin-top: 8px;
            border: 1px solid #000;
        }

        .items-table th {
            border: 1px solid #000;
            background-color: #f2f2f2;
            padding: 4px;
            font-size: 10.5px;
            font-weight: bold;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10.5px;
            vertical-align: middle;
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

        .bold {
            font-weight: bold;
        }

        .footer-table {
            margin-top: 10px;
        }

        .footer-table td {
            border: none;
            font-size: 10.5px;
            vertical-align: top;
        }

        .cut-divider {
            border: none;
            border-top: 1px dashed #888;
            margin: 18px 0;
            position: relative;
            text-align: center;
        }
    </style>
</head>

<body>

    @php
        $logo = !empty($sidebarHeaderLogo) ? $sidebarHeaderLogo : (!empty($sidebarheaderLogo) ? $sidebarheaderLogo : $sidebarMainLogo);
        $vendorName = $purchaseOrder->vendor_name ?: ($purchaseOrder->vendor?->vendor_name ?? '-');
        $poNo = $purchaseOrder->purchase_order_no ?? '-';
        $poDate = !empty($purchaseOrder->purchase_order_date) ? date('d-m-Y', strtotime($purchaseOrder->purchase_order_date)) : '-';
        $companyName = $purchaseOrder->company->company_name ?? 'SHALIBHADRA INTERNATIONAL PVT LTD';
    @endphp

    @for ($copy = 1; $copy <= 2; $copy++)
        <div class="slip-box">

            <!-- Header -->
            <table class="header-table">
                <tr>
                    <td style="width: 50%; vertical-align: middle; text-align: left;">
                        @if (!empty($logo))
                            <img src="{{ $logo }}" style="max-height: 48px; max-width: 260px;">
                        @else
                            <h3 style="margin: 0; font-size: 16px; font-weight: bold;">{{ $companyName }}</h3>
                        @endif
                    </td>
                    <td style="width: 50%; vertical-align: middle; text-align: right; font-size: 10px;">
                        <span style="border: 1px solid #666; padding: 2px 6px; font-weight: bold; font-size: 9px; text-transform: uppercase;">
                            {{ $copy == 1 ? 'Original Copy' : 'Duplicate Copy' }}
                        </span>
                    </td>
                </tr>
            </table>

            <div style="text-align: center; font-size: 10px; margin: 3px 0 6px 0; border-bottom: 1px solid #ccc; padding-bottom: 4px;">
                <strong>Plot No. 4536, GIDC, Phase III, Dared, JAMNAGAR - 361005 (Ph : 02882731337)</strong>
            </div>

            <!-- Receipt Meta -->
            <table class="meta-table">
                <tr>
                    <td style="width: 60%; text-align: left;">
                        <div><strong>Recpt No:</strong> {{ $poNo }}</div>
                        <div style="font-weight: bold; font-size: 11.5px; margin-top: 2px;">{{ $vendorName }}</div>
                    </td>
                    <td style="width: 40%; text-align: right;">
                        <div><strong>Date:</strong> {{ $poDate }}</div>
                    </td>
                </tr>
            </table>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 32px;" class="text-center">Sr</th>
                        <th class="text-left">Particulars</th>
                        <th style="width: 55px;" class="text-right">Grs</th>
                        <th style="width: 65px;" class="text-right">Qty</th>
                        <th style="width: 65px;" class="text-right">Kg</th>
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
                            $grossWtGram = (float)($item->product->gross_wt ?? 0);
                            $grossePerQty = (float)($item->product->grosse_per_qty ?? 0);
                            $unit = strtolower($item->unit ?? 'pcs');
                            $inputValue = (float)($item->quantity ?? 0);
                            $grossWtKg = $grossWtGram > 0 ? ($grossWtGram / 1000) : 0;

                            $qty = 0;
                            $kg = 0;
                            $divideValue = 0;
                            $hasGrossWt = $grossWtKg > 0;

                            if ($unit == 'kg') {
                                $kg = $inputValue;
                                if ($hasGrossWt) {
                                    $qty = $kg / $grossWtKg;
                                    if ($grossePerQty > 0) {
                                        $divideValue = $qty / $grossePerQty;
                                    }
                                }
                            } elseif ($unit == 'gross') {
                                $divideValue = $inputValue;
                                if ($grossePerQty > 0) {
                                    $qty = ceil($inputValue * $grossePerQty);
                                }
                                if ($hasGrossWt) {
                                    $kg = $qty * $grossWtKg;
                                }
                            } else {
                                // pcs
                                $qty = $inputValue;
                                if ($hasGrossWt) {
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
                            <td class="text-center">{{ $sr++ }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td class="text-right">{{ ($unit == 'kg' && !$hasGrossWt) ? '-' : number_format($divideValue, 2) }}</td>
                            <td class="text-right">{{ ($unit == 'kg' && !$hasGrossWt) ? '-' : number_format($qty, 2) }}</td>
                            <td class="text-right">{{ ($unit != 'kg' && !$hasGrossWt) ? '-' : number_format($kg, 3) }}</td>
                        </tr>
                    @endforeach

                    <tr style="background-color: #f7f7f7; font-weight: bold;">
                        <td colspan="3" class="text-left">Total Weight & Qty</td>
                        <td class="text-right">{{ $totalQty > 0 ? number_format($totalQty, 2) : '-' }}</td>
                        <td class="text-right">{{ $totalKg > 0 ? number_format($totalKg, 3) : '-' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Signatures / Footer -->
            <table class="footer-table">
                <tr>
                    <td style="width: 50%; text-align: left; padding-top: 15px;">
                        <strong>Received By:</strong>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div style="font-weight: bold;">{{ $companyName }}</div>
                        <div style="margin-top: 20px; font-size: 10px;">Chirag</div>
                    </td>
                </tr>
            </table>

        </div>

        @if ($copy == 1)
            <div class="cut-divider"></div>
        @endif
    @endfor

</body>

</html>
