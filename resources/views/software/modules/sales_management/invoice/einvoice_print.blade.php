<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>EInvoice {{ $Invoice->invoice_order_no ?? '-' }} - Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .content-wrapper {
            box-sizing: border-box;
        }

        .container-xxl {
            margin: 0px !important;
            box-sizing: border-box;
        }

        .invoice-preview {
            margin: 0 auto !important;
            box-sizing: border-box;
        }

        table {
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            margin: 0 !important;
            width: 100%;
        }

        td,
        th {
            padding: 2px 4px !important;
            margin: 0 !important;
            line-height: 1.2 !important;
        }

        table p,
        table div {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Keep padding for main sections */
        .invoice-header,
        .invoice-body,
        .invoice-footer {
            margin: 0 !important;
            padding: 5px !important;
        }

        /* Line height */
        * {
            line-height: 1.2 !important;
            box-sizing: border-box;
        }

        /* Table rows */
        tr {
            margin: 0 !important;
            padding: 0 !important;
        }


        @media print {
            * {
                box-sizing: border-box;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body,
            html {
                margin: 0 !important;
                padding: 0 !important;
                width: 100%;
                height: 100%;
            }

            @page {
                /* padding: 5mm; */
                size: A4;
                margin: 10mm;
            }
        }

        .invoice-container>* {
            margin: 0 !important;
        }

        /* Prevent overflow */
        .layout-wrapper,
        .layout-container,
        .layout-page {
            overflow: visible !important;
        }

        .force-white-text {
            color: #ffffff !important;
            print-color-adjust: exact !important;
            mix-blend-mode: normal !important;
        }


        @page {
            size: A4;
            margin: 6mm 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.4;
        }

        table.main-table {
            width: 100%;
            border-collapse: collapse;
            height: 98vh; /* Explicit table full height */
        }

        table.main-table thead.letterhead-header {
            display: table-header-group;
        }

        table.main-table tbody {
            display: table-row-group;
        }

        /* Letterhead styles */
        .letterhead-row {
            page-break-inside: avoid;
        }

        .letterhead-content {
            padding: 15px 20px;
            color: white;
        }

        .letterhead-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-circle {
            width: 100%;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            flex-shrink: 0;
        }


        .force-white-text {
            color: white !important;
        }

        /* Invoice details section */
        .invoice-details-section {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
        }

        .invoice-details-section td {
            border: 1px solid #000;
            padding: 0;
            vertical-align: top;
        }

        .billed-to-cell {
            width: 60%;
            padding: 12px 15px;
        }

        .invoice-info-cell {
            width: 40%;
            padding: 0;
        }

        .invoice-header-title {
            background: #f0f0f0;
            text-align: center;
            padding: 10px;
            font-weight: bold;
            font-size: 15px;
            border-bottom: 1px solid #000;
        }

        .invoice-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-info-table td {
            padding: 8px 12px;
            font-size: 13px;
            border: none;
        }

        .invoice-info-table td:first-child {
            text-align: left;
        }

        .invoice-info-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .transporter-info {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 13px;
        }

        .items-table thead {
            background: #f0f0f0;
        }

        .items-table th {
            font-weight: bold;
            text-align: center;
        }

        .items-table tbody tr:nth-child(odd) {
            background: #fafafa;
        }

        .total-qty-row {
            background: #f0f0f0 !important;
            font-weight: bold;
        }

        /* Bottom section */
        .bottom-section {
            border: 1px solid #000;
            border-top: none;
            width: 100%;
            border-collapse: collapse;
        }

        .bottom-section td {
            border: 1px solid #000;
            padding: 15px;
            vertical-align: top;
        }

        .left-bottom-cell {
            width: 55%;
        }

        .right-bottom-cell {
            width: 45%;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .divider {
            border-bottom: 1px solid #ddd;
            margin: 12px 0;
            padding-bottom: 12px;
        }

        .bank-details-table {
            width: 100%;
            font-size: 12px;
            line-height: 1.6;
        }

        .terms-list {
            margin: 0;
            padding-left: 18px;
            font-size: 12px;
            line-height: 1.5;
        }

        .terms-list li {
            margin-bottom: 4px;
        }

        .amount-summary-table {
            width: 100%;
            font-size: 15px;
        }

        .amount-summary-table td {
            padding: 6px 0;
            border: none;
        }

        .amount-summary-table td:first-child {
            text-align: left;
        }

        .amount-summary-table td:last-child {
            text-align: right;
        }

        .net-amount-row {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 16px;
        }

        .net-amount-row td {
            padding: 10px 0 !important;
        }

        .signature-box {
            text-align: center;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #000;
            font-weight: bold;
            font-size: 15px;
        }

        /* Footer */
        .page-footer td {
            padding: 0;
        }

        .footer-box {
            border: 1.5px solid #000;
            border-top: 1px solid #000 !important;
            background: #fff;
            font-family: Arial, sans-serif;
            padding-top: 5px !important;
            page-break-inside: avoid;
        }

        /* Signature box – shared for screen and print */
        .signature-print-box {
            margin: 0 auto;
            width: 100%;
            height: 100px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            overflow: hidden;
        }

        .signature-print-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Slightly reduce signature height on print to prevent overlap */
        @media print {
            .signature-print-box img {
                max-height: 70px !important;
            }
        }

        /* White section */
        .footer-white {
            font-size: 13px;
            text-align: center;
            padding: 2px 8px 12px 8px !important;
        }

        /* Black bar – reduced height */
        .footer-black {
            position: relative;
            background: #000;
            height: 35px;
            /* ⬅ reduced */
            margin: 0;
        }

        /* Red address bar */
        .footer-red {
            position: absolute;
            top: -10px;
            /* ⬅ adjusted */
            left: 10px;
            right: 10px;
            background: #d32027;
            color: #fff;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 10px !important;
            border-radius: 12px 12px 0 0;
        }

        .loc {
            margin-right: 6px;
        }

        /* Footer wrapper */
        .page-footer-wrapper {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Force footer only at end */
        .final-footer-row {
            page-break-before: auto;
        }

        /* Print specific styles */
        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }

            thead.letterhead-header {
                display: table-header-group;
            }

            .page-break {
                page-break-before: always;
            }

            .no-break {
                page-break-inside: avoid;
            }

            /* Keep footer visually at bottom of each printed page */
            .footer-box {
                /* position: fixed; */
                /* bottom: 5mm;
                left: 10mm;
                right: 10mm;
                width: auto; */
            }
        }
    </style>
</head>

<body>

    @php

        $sidebarMainLogo = asset('software/img/logo.png');
        $sidebarSmallLogo = asset('software/img/ring.png');
        $sidebarheaderLogo = '';
        if (isset($Invoice) && isset($Invoice->company_id) && !empty($Invoice->company_id)) {
            $company_id = $Invoice->company_id;
            $company = App\Helpers\Helper::getCompanyDetailById($company_id);
            if ($company && $company?->id) {
                $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
                $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
                $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
            }
        }

        $items = $Invoice->invoice_items ?? collect([]);
        $raw = $Invoice->company->address;
        $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
        $parts = explode('<br><br>', $clean, 2);

        $part1 = $parts[0] ?? '';
        $part2 = $parts[1] ?? '';
        
        $chunks = $items->chunk(6);
        if ($chunks->count() == 0) {
            $chunks = collect([collect([])]);
        }
        $global_sub_total = 0;
        $global_total_qty = 0;
        $global_is_igst = 0;
        foreach($items as $item) {
            $global_total_qty += ($item->quantity ?? 1);
            $global_sub_total += ($item->amount ?? 0);
            $cgst = floatval($item->cgst_amount ?? 0);
            $sgst = floatval($item->sgst_amount ?? 0);
            if ($cgst <= 0 || $sgst <= 0) {
                $global_is_igst = 1;
            }
        }
        $counter = 1;
    @endphp

    @if ($Invoice->irn_status === 'CNL' || $Invoice->e_invoice_status === 'CANCELLED')
        <!-- Diagonal CANCELLED Watermark (2 directions for better visibility) -->
        <div
            style="
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 120px;
        font-weight: bold;
        color: rgba(255, 0, 0, 0.25);
        white-space: nowrap;
        pointer-events: none;
        z-index: 9999;
        text-align: center;
        width: 100%;
    ">
            CANCELLED
        </div>
    @endif
    @foreach($chunks as $chunkIndex => $chunk)
    <table class="main-table" id="main-table-{{ $chunkIndex }}" style="page-break-after: {{ $loop->last ? 'auto' : 'always' }};">
        <!-- Letterhead - Will repeat on every page -->

        <thead>
            <tr id="letterhead-header">
                <td colspan="2" style="padding:0;">

                    <!-- ONE SINGLE OUTER BOX FOR FULL HEADER -->
                    <table width="100%" cellspacing="0" cellpadding="0"
                        style="border-collapse:collapse; border:1px solid #000;">

                        <!-- LETTERHEAD -->
                        <tr>
                            <td colspan="2" style="padding:0;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td
                                            style="width: 200px; padding: 10px; text-align: center; vertical-align: middle;">
                                            <div
                                                style="width: 100px; height: 100px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                                                @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                                                    <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                                                        style="height:40px;width:180px;object-fit: contain;padding-left: 55px;" />
                                                @else
                                                    <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                                                        style="height:40px;width:180px;object-fit: contain;padding-left: 55px;" />
                                                @endif
                                            </div>
                                        </td>
                                        <td style="width: 430px; padding: 10px 15px; vertical-align: top;">
                                            <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: bold;">
                                                {{ $Invoice->company->company_name ?? '-' }}</h2>
                                            <p style="margin: 0; font-size: 14px; line-height: 1.6; text-align: left;">
                                                {!! str_replace('</p>', '</p>' . PHP_EOL, $part1 ?? '-') !!}
                                                <br />
                                                Phone: {{ $Invoice->company->whatsapp_number ?? '-' }}<br>
                                                Email: {{ $Invoice->company->email ?? '-' }}<br>
                                            </p>
                                        </td>
                                        <td style="width: 350px; padding: 15px 20px; vertical-align: bottom;">
                                            <p
                                                style="margin:0; font-size:13px; line-height:1.8; text-align:right;font-weight: bold;">

                                                {!! str_replace('</p>', '</p>' . PHP_EOL, $part2 ?? '-') !!}
                                            </p>

                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>

                        <!-- INVOICE DETAILS -->

                    </table>

                </td>
            </tr>
        </thead>

        <!-- Main Content -->
        <tbody>

            <tr>
                <td colspan="2" style="padding: 4px !important;">

                    <table width="100%" cellspacing="0" cellpadding="0"
                        style="border-collapse:collapse;margin-top:-8px !important;">
                        <tr style="border-left:1px solid #000;border-right:1px solid #000;">

                            <!-- LEFT : BILLED TO -->
                            <td width="70%"
                                style="vertical-align:top; padding: 0px !important;margin: 0px !important; border-right:1px solid #000;">

                                <div style="display: flex; align-items: flex-start;padding: 0 !important;">
                                    <strong style="font-size:15px;padding: 4px 10px !important;">Billed To:</strong><br>

                                    <div style="font-size:14px; line-height:1.4;padding: 4px 10px !important;">
                                        <strong>{{ $Invoice->customer_name ?? (optional($Invoice->customer)->customer_name ?? '-') }}</strong><br>
                                        {!! nl2br(strip_tags($Invoice->billing_address)) !!}
                                        @if ($Invoice->customer)
                                            <br>{{ $Invoice->customer->city->name ?? '' }},
                                            {{ $Invoice->customer->state->name ?? '' }} -
                                            {{ $Invoice->customer->pincode ?? '' }}
                                        @endif
                                    </div>
                                </div>
                                <div style="display: flex; align-items: flex-start;padding: 0 !important;">
                                    <strong style="font-size:15px;padding: 4px 10px !important;">GSTIN:</strong><br>

                                    <div style="font-size:14px; line-height:1.4;padding: 4px 10px !important;">
                                        {{ $Invoice->gst_no ?? '-' }}
                                    </div>
                                </div>

                            </td>

                            <!-- RIGHT : INVOICE INFO -->
                            <td width="30%"
                                style="vertical-align:top;padding: 0px !important;margin: 0px !important;">

                                <div
                                    style="font-size:15px;font-weight:bold;text-align:center;padding-bottom:5px;margin-bottom:8px;border-bottom:1px solid #000;">
                                    Invoice
                                </div>

                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="padding:4px 7px !important;">Number:</td>
                                        <td align="right">
                                            <strong>{{ $Invoice->invoice_order_no ?? '-' }}</strong>
                                        </td>
                                    </tr>

                                    @if (!empty($Invoice->eway_bill_no))
                                        <tr>
                                            <td style="padding:4px 7px !important;">Eway Bill No:</td>
                                            <td align="right">
                                                <strong>{{ $Invoice->eway_bill_no ?? '-' }}</strong>
                                            </td>
                                        </tr>
                                    @endif

                                    @if (!empty($Invoice->company->lut_number))
                                        <tr>
                                            <td style="padding:4px 7px !important;">LUT NO:</td>
                                            <td align="right">
                                                <strong>{{ $Invoice->company->lut_number ?? '-' }}</strong>
                                            </td>
                                        </tr>
                                    @endif

                                    @if (!empty($Invoice->invoice_order_date))
                                        <tr>
                                            <td style="padding:4px 7px !important;">Date:</td>
                                            <td align="right">
                                                <strong>
                                                    {{ $Invoice->invoice_order_date ? \Carbon\Carbon::parse($Invoice->invoice_order_date)->format('d-m-Y') : '-' }}
                                                </strong>
                                            </td>
                                        </tr>
                                    @endif

                                    @if (!empty($Invoice->sales_order?->po_number))
                                        <tr>
                                            <td>PO No:</td>
                                            <td align="right">
                                                <strong>{{ $Invoice->sales_order->po_number }}</strong>
                                            </td>
                                        </tr>
                                    @endif
                                </table>

                            </td>
                        </tr>


                        <tr style="border-left:1px solid #000;border-right:1px solid #000;">
                            <td width="70%"
                                style="border-top:1px solid #000; border-right:1px solid #000; padding:4px 8px; font-size:13px;">

                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <!-- LABEL ROW -->
                                    <tr style="font-weight:bold;">
                                        <td width="35%">E-Invoice IRN</td>
                                        <td width="25%">Ack.No.</td>
                                        <td width="25%">Ack. Date</td>
                                        <td width="15%" rowspan="2"
                                            style="text-align:right; vertical-align:middle;">
                                            @if (!empty($qrCodeDataUri))
                                                <img src="{{ $qrCodeDataUri }}" style="width:90px; height:90px;">
                                            @endif
                                        </td>
                                    </tr>

                                    <!-- VALUE ROW -->
                                    <tr>
                                        <td
                                            style="word-break: break-all;overflow-wrap: break-word;white-space: normal;">
                                            {{ $Invoice->irn ?? '-' }}</td>
                                        <td>{{ $Invoice->ack_no ?? '-' }}</td>
                                        <td>
                                            {{ $Invoice->ack_date ? \Carbon\Carbon::parse($Invoice->ack_date)->format('d M Y H:i') : '-' }}
                                        </td>
                                    </tr>
                                </table>

                            </td>

                            <!-- EMPTY RIGHT CELL TO KEEP INVOICE LAYOUT -->
                            <td width="30%" style="border-top:1px solid #000;">
                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td><strong>Transporter : </strong></td>
                                        <td>{{ $Invoice->transport_detail->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Transporter Id / GSTIN : </strong></td>
                                        <td>{{ $Invoice->transport_detail->transport_gst_id ?? '-' }}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>


                        {{-- <tr style="border-left:1px solid #000;border-right:1px solid #000;">
                            <td width="70%"
                                style="border-top:1px solid #000; border-right:1px solid #000; padding:8px; font-size:13px;">

                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="20%"><strong>Transporter</strong></td>
                                        <td width="30%"><strong>Transporter Id / GSTIN</strong></td>
                                    </tr>

                                    <tr>
                                        <td width="20%">{{ $Invoice->transport_detail->name ?? '-' }}</td>
                                        <td width="30%">
                                            {{ $Invoice->transport_detail->transport_gst_id ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr></tr>
                                </table>

                            </td>
                            <td width="30%" style="border-top:1px solid #000;"></td>
                        </tr> --}}

                    </table>

                </td>
            </tr>

            <tr id="tr-items-table" style="height: 100%;">
                <td style="padding: 0; vertical-align: top; height: 100%;">
                    <!-- Invoice Details -->


                    <!-- Items Table -->
                    <div class="items-wrapper" id="items-wrapper"
                        style="border-bottom: 1px solid #000;margin-top:-8px !important; height: 100%;">
                        <table class="items-table" style="height: 100%;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; width: 30%;border-top: 1px solid #000 !important;">Item
                                    </th>
                                    <th style="width: 10%;border-top: 1px solid #000 !important;">HSN</th>
                                    <th style="width: 8%;border-top: 1px solid #000 !important;">Unit</th>
                                    <th style="width: 8%;border-top: 1px solid #000 !important;">Qty</th>
                                    <th style="width: 10%;border-top: 1px solid #000 !important;">Rate</th>
                                    <th style="width: 12%;border-top: 1px solid #000 !important;">Amount</th>
                                    <th style="width: 8%;border-top: 1px solid #000 !important;">GST%</th>
                                    <th style="width: 14%;border-top: 1px solid #000 !important;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($chunk as $item)
                                    @php
                                    $quantity = $item->quantity ?? 1;
                                    $cgst_amount = floatval($item->cgst_amount ?? 0);
                                    $sgst_amount = floatval($item->sgst_amount ?? 0);
                                    $gst_amount = floatval($item->gst_amount ?? 0);

                                    $cgst_per = floatval($item->cgst_in_percentage ?? 0);
                                    $sgst_per = floatval($item->sgst_in_percentage ?? 0);
                                    $gst_per = floatval($item->gst_in_percentage ?? 0);

                                    // Default
                                    $row_gst_percentage = 0;

                                    if ($cgst_amount > 0 && $sgst_amount > 0) {
                                        $row_gst_percentage = $cgst_per + $sgst_per;
                                    } else {
                                        $row_gst_percentage = $gst_per;
                                    }

                                    $amount = $item->amount ?? 0;
                                    
                                    // Row Totals
                                    $total_gst_percentage = $row_gst_percentage;
                                    $total_gst_amount = $amount + ($amount * $row_gst_percentage) / 100;
                                @endphp
                                <tr>
                                    <td
                                        style="padding: 6px !important; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; line-height: 1.4;">
                                        <strong>{{ $counter++ }}) {{ $item->product_code ?? '-' }} :</strong>
                                        {{ $item->product_name ?? ($item->product_description ?? '-') }}
                                    </td>
                                    <td
                                        style="padding: 6px 4px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                        {{ $item->hsn_code ?? '-' }}</td>
                                    <td
                                        style="padding: 6px 4px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                        {{ $item->unit ?? '-' }}</td>
                                    <td
                                        style="padding: 6px 4px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                        {{ strtolower($item->unit) === 'kg'
                                            ? number_format($item->quantity ?? 0, 3, '.', '')
                                            : (int) ($item->quantity ?? 0) }}
                                    </td>
                                    <td
                                        style="padding: 6px 4px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                        {{ $item->price_in_amount ?? '' }}</td>
                                    <td
                                        style="padding: 6px 4px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">
                                        {{ $item->amount ?? '' }}</td>
                                    <td
                                        style="padding: 6px 4px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                        {{ number_format($total_gst_percentage, 2) }}</td>
                                    <td
                                        style="padding: 6px 4px; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">
                                        {{ number_format($total_gst_amount, 2) }} </td>
                                </tr>
                                    
                                @empty
                                    @if($chunkIndex == 0)
                                    <tr>
                                        <td colspan="8" class="text-center">No invoice items found.</td>
                                    </tr>
                                    @endif
                                @endforelse

                                @php
                                    $fillerRows = 6 - $chunk->count();
                                @endphp
                                
                                @for($i = 0; $i < $fillerRows; $i++)
                                <tr class="empty-filler-row">
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important; border-right: 1px solid #000 !important;">&nbsp;</td>
                                    <td style="border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                </tr>
                                @endfor



                                {{-- <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>

                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr>
                                    <tr>
                                        <td>5</td>
                                        <td><div class="item-text">BRASS FASTENERS</div><div class="item-code">412662</div></td>
                                        <td>74153390</td>
                                        <td>16545 Pcs</td>
                                        <td>5.40</td>
                                        <td>89,343.00</td>
                                        <td>89.34<br>(0.1%)</td>
                                        <td>89,432.34</td>
                                    </tr> --}}

                                <!-- Total Qty Row -->
                                
                                
                            </tbody>
                            <tfoot>
                                <tr class="total-qty-row">
                                    <td colspan="3" style="text-align: right;">Total Qty</td>
                                    <td style="text-align: center;">{{ $global_total_qty ?? '' }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <!-- Bottom Section -->

                </td>
            </tr>
        </tbody>

        <tfoot style="display: table-footer-group;">
            <tr class="final-footer-row" id="final-footer-row">
                <td colspan="2" style="padding:0;">

                    <table class="bottom-section no-break" style="width: 100%;margin-top: -5px !important;">
                        <tr>
                            <td
                                style="width: 55%; border-right: 1px solid #000; vertical-align: top;padding:0px !important;border-top: none !important;">

                                <div
                                    style="margin-bottom: 4px; padding:0px !important; border-bottom: 1px solid #000;">
                                    <p
                                        style="margin-bottom:2px !important; font-size: 15px; font-weight: bold;margin-left:10px !important;margin-top:2px !important;">
                                        Net Amount In Words</p>
                                    <p
                                        style="margin-bottom:2px !important; font-size: 12px; font-weight: bold;margin-left:10px !important;">
                                        {{ App\Helpers\Helper::convertToRupees($Invoice->grand_total) }}
                                    </p>
                                </div>


                                <div
                                    style="margin-bottom: 4px; padding:0px !important;padding-bottom: 2px !important; border-bottom: 1px solid #000;">
                                    <p
                                        style="margin: 0 0 4px 0; font-size: 15px; font-weight: bold;margin-left:10px !important;margin-top:2px !important;">
                                        Bank Details</p>
                                    <div style="margin-left:16px !important;font-size: 12px !important;">
                                        <table style="width: 100%; ">
                                            {!! str_replace('</p>', '</p>' . PHP_EOL, $Invoice->company->bank_details ?? '-') !!}
                                        </table>
                                    </div>
                                </div>


                                <div style="margin-left:10px !important;margin-bottom:2px !important;">
                                    <p
                                        style="margin: 0 0 4px 0; padding:0px !important; font-size: 15px; font-weight: bold;margin-top:2px !important;">
                                        Terms and Conditions
                                    </p>
                                    <ol style="margin: 0; padding-left: 18px; font-size: 10px; line-height: 1.1;">
                                        <li>Good once sold cannot be taken back.</li>
                                        <li>We are not responsible for any breakage, theft or damage.</li>
                                        <li>Interest 24% P.A. will be charged after 5 DAYS of payment due date.</li>
                                        <li>Subject to Jamnagar Jurisdiction.</li>
                                        <li>IF YOU REQUIRE GST BENEFIT IN UNDER NOTIFICATION NO. 41/2017 SO WE REQUIRE
                                            BL COPY WITHIN 60 DAYS. IF YOU DO NOT PROVIDE BL COPY, OUR COMPANY WILL
                                            CHARGE
                                            18% GST PLUS DELAY INTEREST 2% PER DAY.</li>
                                    </ol>
                                </div>
                            </td>

                            <td
                                style="width: 45%; vertical-align: top;padding:0px !important;border-top: none !important;">

                                <table
                                    style="width: 100%; font-size: 14px;margin-top:5px !important;margin-bottom:5px !important;">
                                    <tr>
                                        <td
                                            style="padding: 6px !important; text-align: left;border: none !important;">
                                            Basic Amount</td>
                                        <td
                                            style="padding: 6px !important; text-align: right;border: none !important;">
                                            <strong>₹
                                                {{ $global_sub_total ?? '' }}</strong></td>
                                    </tr>

                                    @if (!empty($Invoice->packing_forwarding_charge))
                                        <tr>
                                            <td
                                                style="padding: 6px !important; text-align: left;border: none !important;">
                                                Packing & Forwarding Charge</td>
                                            <td
                                                style="padding: 6px !important; text-align: right;border: none !important;">
                                                <strong>₹
                                                    {{ $Invoice->packing_forwarding_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif


                                    @if ($global_is_igst == 1)
                                        <tr>
                                            <td
                                                style="padding: 6px !important; text-align: left;border: none !important;">
                                                IGST</td>
                                            <td
                                                style="padding: 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($Invoice->gst_amount, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td
                                                style="padding: 6px !important; text-align: left;border: none !important;">
                                                CGST</td>
                                            <td
                                                style="padding: 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($Invoice->gst_amount / 2, 2) }}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding: 6px !important; text-align: left;border: none !important;">
                                                SGST</td>
                                            <td
                                                style="padding: 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($Invoice->gst_amount / 2, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @endif

                                    <tr
                                        style="border-top: 1px solid #000; border-bottom: 1px solid #000;margin-top:10px !important;">
                                        <td
                                            style="padding: 6px 4px !important; text-align: left; font-size: 14px;margin-bottom:4px !important;margin-top:4px !important;border-left: none !important;border-right: none !important;">
                                            <strong>Net
                                                Amount</strong></td>
                                        <td
                                            style="padding: 6px 4px !important; text-align: right; font-size: 15px; font-weight: bold;margin-bottom:4px !important;margin-top:4px !important;border-left: none !important;border-right: none !important;">
                                            ₹ {{ $Invoice->grand_total }}
                                        </td>
                                    </tr>
                                </table>


                                <div style="text-align:center; margin-top:-20px;">
                                    <div class="signature-print-box">
                                        @if ($Invoice->company && $Invoice->company->authorised_signature_url)
                                            <img src="{{ $Invoice->company->authorised_signature_url }}" />
                                        @endif
                                    </div>
                                </div>


                                <div style="border-top: 1px solid #000; padding: 5px 5px; margin-top: 10px;">
                                    <p
                                        style="font-size: 17px; font-weight: bold; text-align: center; padding-top: 10px !important;">
                                        Authorised Signature
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>


                    <div class="footer-box">

                        <div class="footer-white">
                            <span><b>✉</b> info@shalibhadrainternational.com</span>
                            <span><b>📞</b> +91 73832 38373 | +91 90333 41444</span>
                            <span><b>🌐</b> www.shalibhadrainternational.com</span>
                        </div>

                        <div class="footer-black">

                            <div class="footer-red">
                                <span class="loc">📍</span>
                                Plot No.4536, GIDC Phase - 3, Dared, Jamnagar-361005, Gujarat, India.
                            </div>
                        </div>

                    </div>

                </td>
            </tr>
        </tfoot>

    </table>
    @endforeach
    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                setTimeout(() => window.close(), 100);
            };
        };
    </script>
</body>

</html>
