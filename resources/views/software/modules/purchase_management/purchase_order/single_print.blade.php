<!DOCTYPE html>
<html>

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
@endphp

<head>
    <meta charset="UTF-8">
    <title>Purchase Order No {{ $purchaseOrder->purchase_order_no ?? '-' }} - Print</title>
    <style>
        @page {
            size: A4;
            /* margin-top: 20mm;
            margin-bottom: 5mm;
            margin-left: 0mm;
            margin-right: 0mm; */
            margin:10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            /* Scrollbar fix: Ensure the body doesn't overflow */
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* Set default border for cells for structural tables only */
        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        /* Remove border from the Movement Rule table cells, keeping only the top/bottom of the whole table */
        .movement-rule td {
            border-top: none;
            border-bottom: none;
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

        /* Helper to span borders cleanly within padded divs */
        .full-width-line {
            border-top: 1px solid #000;
            margin: 8px -8px;
            /* Use -8px margin to span across the 8px padding of the parent div */
        }

        .page-break {
            page-break-before: always;
            break-before: page;
            display: block;
        }

         .copy {
            padding: 10px 5px;
            page-break-inside: avoid;
        }

        .no-space hr {
            margin: 8px 0;
        }

        .table th,
        .table td {
            padding: 4px !important;
            font-size: 12px;
        }

        h4,
        h5 {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body onload="printAndRedirect();">


    <div style="width: 100%; padding: 0;">

        <div class="card" style="border: 2px solid #333; padding: 10px;">

            @for ($i = 1; $i <= 2; $i++)
                <div class="copy">

                    <!-- Header Logos -->
                    <!-- Header Logos -->
                    <div
                        style="
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    padding: 0 5px;
">
                        <img src="{{ $sidebarheaderLogo }}" style="height: 45px; object-fit: contain;">

                        {{-- <img src="http://127.0.0.1:8000/software/img/logo.png"
                            style="height: 45px; object-fit: contain;"> --}}
                    </div>


                    <!-- Address -->
                    <div style="text-align: center; font-size: 13px; margin-top: 5px;">
                        <h4>Plot No. 4536, GIDC, Phase III, Dared, JAMNAGAR<br>
                            Ph : 02882731337
                        </h4>
                    </div>

                    <hr class="no-space">

                    <!-- Receipt Header -->
                    <!-- Receipt Details -->
                    <div
                        style="
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    width: 100%;
    margin-top: 10px;
">

                        <!-- LEFT SIDE -->
                        <div style="font-size: 14px; line-height: 18px;">

                            <h5 style="margin: 0;"> <label>Recpt No:</label> {{ $purchaseOrder->purchase_order_no }}
                            </h5>
                            <h5 style="margin: 0;">{{ $purchaseOrder->vendor_name ?: (optional($purchaseOrder->vendor)->vendor_name ?? '-') }}</h5>
                        </div>

                        <!-- RIGHT SIDE -->
                        <div style="text-align: right; font-size: 14px; line-height: 18px;">
                            <strong>Date:</strong>
                            {{ date('d-m-Y', strtotime($purchaseOrder->purchase_order_date)) }}<br><br>

                        </div>

                    </div>

                    <!-- Items Table -->
                    <table class="table table-bordered" style="width:100%; margin-top:10px;">
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align:center;">Sr</th>
                                <th>Particulars</th>
                                <th style="text-align:right;">Grs</th>
                                <th style="text-align:right;">Qty</th>
                                <th style="text-align:right;">Kg</th>
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
                    <div style="margin-top: 10px;">
                        <div class="row" style="margin:0;">
                            <div class="col-6"><strong>Received By:</strong></div>
                            <div class="col-6" style="text-align:right;">
                                <strong>SHALIBHADRA INTERNATIONAL PVT LTD</strong><br>
                                Chirag
                            </div>
                        </div>
                    </div>

                </div>

                @if ($i == 1)
                    <hr style="border: none; margin: 85px 0;">
                @endif
            @endfor

        </div>

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
