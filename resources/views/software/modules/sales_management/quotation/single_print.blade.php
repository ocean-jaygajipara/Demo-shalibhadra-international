<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_no ?? '-' }}
        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }} - Print </title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Optional: Boxicons or other icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>

        /* Content wrapper with padding */
        .content-wrapper {
            box-sizing: border-box;
        }

        .container-xxl {
            margin: 0px !important;
            box-sizing: border-box;
        }

        /* Invoice preview row */
        .invoice-preview {
            margin: 0 auto !important;
            box-sizing: border-box;
        }

        /* Table styles - keep minimal internal padding */
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

        /* Remove gaps from paragraphs and divs inside tables */
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

        /* Print specific settings */
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
                size: A4;
                margin: 5mm 5mm 5mm 10mm;
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

        .pdf24_ sup {
            vertical-align: baseline;
            position: relative;
            top: -0.4em;
        }

        .pdf24_ sub {
            vertical-align: baseline;
            position: relative;
            top: 0.4em;
        }

        .pdf24_ a:link {
            text-decoration: none;
        }

        .pdf24_ a:visited {
            text-decoration: none;
        }

        @media screen and (min-device-pixel-ratio:0),
        (-webkit-min-device-pixel-ratio:0),
        (min--moz-device-pixel-ratio: 0) {
            .pdf24_view {
                font-size: 10em;
                transform: scale(0.1);
                -moz-transform: scale(0.1);
                -webkit-transform: scale(0.1);
                -moz-transform-origin: top left;
                -webkit-transform-origin: top left;
            }
        }

        .pdf24_layer {}

        .pdf24_ie {
            font-size: 1pt;
        }

        .pdf24_ie body {
            font-size: 12em;
        }

        @media print {
            .pdf24_view {
                font-size: 1em;
                transform: scale(1);
            }
        }

        .pdf24_grlink {
            position: relative;
            width: 100%;
            height: 100%;
            z-index: 1000000;
        }

        .pdf24_01 {
            position: absolute;
            white-space: nowrap;
        }

        .pdf24_02 {
            font-size: 1em;
            line-height: 0.0em;
            width: 49.75em;
            height: 70.33334em;
            border-style: none;
            display: block;
            margin: 0em;
        }

        @supports(-ms-ime-align:auto) {
            .pdf24_02 {
                overflow: hidden;
            }
        }

        .pdf24_03 {
            position: relative;
        }

        .pdf24_04 {
            position: absolute;
            pointer-events: none;
            clip: rect(0.010844em, 49.77131em, 70.29166em, 0.041679em);
            width: 100%;
        }

        .pdf24_05 {
            position: relative;
            width: 49.75em;
        }

        .pdf24_06 {
            height: 7.033333em;
        }

        .pdf24_ie .pdf24_06 {
            height: 70.33334em;
        }

        @font-face {
            font-family: "ELOWCL+Poppins-Medium";
            src: url("data:application/octet-stream;base64,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") format("woff");
        }

        .pdf24_07 {
            font-size: 0.833333em;
            font-family: "ELOWCL+Poppins-Medium";
            color: #FFFFFF !important;
        }

        .pdf24_08 {
            line-height: 1.659em;
        }

        .pdf24_09 {
            letter-spacing: 0em;
        }

        .pdf24_ie .pdf24_09 {
            letter-spacing: 0px;
        }

        .pdf24_10 {
            font-size: 0.774583em;
            font-family: "ELOWCL+Poppins-Medium";
            color: #000004;
        }

        .pdf24_11 {
            font-size: 1.191667em;
            font-family: "ELOWCL+Poppins-Medium";
            color: #FFFFFF !important;
        }
          .force-white-text {
            color: #ffffff !important;
            print-color-adjust: exact !important;
            mix-blend-mode: normal !important;
        }


         @page {
            size: A4;
            margin: 5mm 5mm 5mm 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
        }

        /* Make letterhead repeat on every page */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
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
            border-top: none !important;
            background: #fff;
            font-family: Arial, sans-serif;
            padding-top: 5px !important;
            page-break-inside: avoid;
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
            height: 35px; /* ⬅ reduced */
            margin: 0;
        }

        /* Red address bar */
        .footer-red {
            position: absolute;
            top: -10px; /* ⬅ adjusted */
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
                margin: 5mm 5mm 5mm 10mm;
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
        }
        /* Watermark Styles - Reliable Centering and Visibility */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.12; /* Balanced opacity */
            z-index: 9999; /* Higher z-index to avoid background overlaps */
            pointer-events: none;
            user-select: none;
            width: 80%;
            text-align: center;
        }

        .watermark img {
            width: 100%;
            height: auto;
            max-width: 400px; /* Favicons are small, keep it reasonably sized */
            object-fit: contain;
        }

        /* Signature box – shared for screen and print */
        .signature-print-box {
            margin: 0 auto;
            width: 100%;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .signature-print-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            margin: 0 auto;
        }

        /* Make signature visible and clear on print */
        @media print {
            .signature-print-box {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                height: 110px !important;
            }
            .signature-print-box img {
                max-height: 105px !important;
            }
        }

        @media print {
            .watermark {
                display: block !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>

</head>
@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    
    // Inject the letter head manually to ensure it's available even if controller was reverted
    $quotation_letter_head = '';

    if (isset($quotation) && isset($quotation->company_id) && !empty($quotation->company_id)) {
        $company_id = $quotation->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
            $quotation_letter_head = $company?->invoice_letter_head ?? '';
        }
    }
    
    $quotation_letter_head = App\Helpers\Helper::webpToBase64($quotation_letter_head);

    $borderColor = optional($quotation->company)->quotation_view_color ?? '#266BEE';

    // Check if user can view price-related fields
    $canViewPrice = false;
    $currentGuard = $modules['currentGuard'] ?? null;
    $authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
    $parent_type_id = isset($modules['parent_type_id']) ? (int) $modules['parent_type_id'] : 0;

    if ($currentGuard === 'admin_software') {
        $canViewPrice = true;
    } elseif ($currentGuard === 'team_person' && $authLoginUserDetail) {
        if ($parent_type_id === 0) {
            $canViewPrice = true;
        }
    }

    $authDetail = $modules['authLoginUserDetail'] ?? ($authLoginUserDetail ?? (Auth::guard('admin_software')->user() ?? Auth::guard('team_person')->user()));
    $isDhruvik = false;
    if ($authDetail) {
        $userCompanyId = (int)($authDetail->company_id ?? ($company_id ?? ($modules['company_id'] ?? 0)));
        $userTeamRoleId = (int)($authDetail->team_role_id ?? 0);
        $authName = strtoupper($authDetail->name ?? '');
        $authUsername = strtoupper($authDetail->username ?? '');
        $authEmpCode = strtoupper($authDetail->employee_code ?? '');

        if (
            (Auth::guard('admin_software')->check() && !Auth::guard('team_person')->check()) ||
            ($userCompanyId == 2 && ($userTeamRoleId == 2 || (int)$authDetail->id == 2 || $authEmpCode === 'EMP-001')) ||
            str_contains($authName, 'DHRUVIK') ||
            str_contains($authUsername, 'DHRUVIK')
        ) {
            $isDhruvik = true;
        }
    }

    // Chunking logic for repeated header/footer
    $items = $quotation->quotation_items ?? collect([]);
    $rowsPerPage = 7;
    $chunks = $items->chunk($rowsPerPage);
    $totalChunks = count($chunks);

    $raw = $quotation->company->address ?? '';
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);
    $part1 = $parts[0] ?? '';
    $part2 = $parts[1] ?? '';

    $document = $quotation;
    
    // Calculate global totals for consistent footer
    $sub_total = 0;
    $total_qty = 0;
    $is_igst = 0;
    foreach($items as $item) {
        $sub_total += $item->amount ?? 0;
        $total_qty += $item->quantity ?? 0;
        if (!($item->cgst_amount > 0 && $item->sgst_amount > 0)) {
            if ($item->gst_amount > 0) {
                $is_igst = 1;
            }
        }
    }
@endphp

<body style="--bs-scrollbar-width: 0px;" onload="printAndRedirect();">

    <div class="watermark">
        <img src="{{ $sidebarSmallLogo }}" alt="Watermark">
    </div>

    <div class="container-xxl p-0">
        @foreach ($chunks as $pageIndex => $chunk)
            <div class="{{ $pageIndex < $totalChunks - 1 ? 'page-break' : '' }}" style="margin-bottom: 20px;">
                <table class="main-table" style="width: 100%; border-collapse: collapse; height: 276mm;">
                    <!-- Letterhead - Will repeat on every page -->
                    <thead class="letterhead-header">
                        <tr id="letterhead-header">
                            <td colspan="2" style="padding:0;">

                                <!-- ONE SINGLE OUTER BOX FOR FULL HEADER -->
                                <table width="100%" cellspacing="0" cellpadding="0"
                                    style="border-collapse:collapse; border:1px solid #000;">

                                    <!-- LETTERHEAD -->
                                    <tr>
                                        <td colspan="2" style="padding:0;">
                                            <img src="{{ $quotation_letter_head ?? '' }}"
                                                style="width:100%; display:block;">
                                        </td>
                                    </tr>

                                    <!-- INVOICE DETAILS -->
                                    <tr>
                                        <td colspan="2" style="padding: 0px !important;margin: 0px !important;">

                                            <table width="100%" cellspacing="0" cellpadding="0"
                                                style="border-collapse:collapse;">
                                                <tr>

                                                    <!-- LEFT : BILLED TO -->
                                                    <td width="60%"
                                                        style="vertical-align:top; padding: 0px !important;margin: 0px !important; border-right:1px solid #000;border-top:1px solid #000;">

                                                        <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                                            <tr>
                                                                <td style="width: 100px; font-weight: bold; font-size: 15px; padding: 2px 10px !important; vertical-align: top;">Billed:</td>
                                                                <td style="font-size: 14px; padding: 2px 10px !important; vertical-align: top;">
                                                                    <strong>{{ $document->customer_name ?? optional($document->customer)->customer_name ?? '-' }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="font-weight: bold; font-size: 14px; padding: 2px 10px !important; vertical-align: top;">To:</td>
                                                                <td style="font-size: 14px; padding: 2px 10px !important; vertical-align: top; line-height: 1.4;">
                                                                    {!! nl2br(strip_tags($document->billing_address)) !!}
                                                                    @if ($document->customer)
                                                                        <br>
                                                                        {{ $document->customer->city->name ?? '' }},
                                                                        {{ $document->customer->state->name ?? '' }} -
                                                                        {{ $document->customer->pincode ?? '' }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="font-weight: bold; font-size: 14px; padding: 2px 10px !important; vertical-align: top;">GSTIN:</td>
                                                                <td style="font-size: 14px; padding: 2px 10px !important; vertical-align: top;">
                                                                    {{ $document->gst_no ?? '-' }}
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        </div>

                                                        @if(!empty($document->transport_detail->name))
                                                            <div style="margin:3px 0 0 10px !important; font-size:14px;">
                                                                <strong>Transporter:</strong>
                                                                {{ $document->transport_detail->name }}
                                                            </div>
                                                        @endif

                                                        @if(!empty($document->transport_detail->transport_gst_id))
                                                            <div style="margin:3px 0 0 10px !important; font-size:14px;">
                                                                <strong>Transporter ID/GSTIN:</strong>
                                                                {{ $document->transport_detail->transport_gst_id }}
                                                            </div>
                                                        @endif

                                                    </td>

                                                    <!-- RIGHT : INVOICE INFO -->
                                                    <td width="40%" style="vertical-align:top;padding: 0px !important;margin: 0px !important;border-top:1px solid #000;">

                                                        <div style="
                                                            font-size:15px;
                                                            font-weight:bold;
                                                            text-align:center;
                                                            padding-bottom:5px;
                                                            margin-bottom:8px;
                                                            border-bottom:1px solid #000;
                                                        ">
                                                            Quotation
                                                        </div>

                                                        <table width="100%" cellspacing="0" cellpadding="0">
                                                            <tr>
                                                                <td style="padding:7px !important;">Number:</td>
                                                                <td align="right">
                                                                    <strong>{{ $document->quotation_no ?? '-' }}</strong>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td style="padding:7px !important;">Date:</td>
                                                                <td align="right">
                                                                    <strong>
                                                                        {{ $document->quotation_date
                                                                            ? \Carbon\Carbon::parse($document->quotation_date)->format('d-m-Y')
                                                                            : '-' }}
                                                                    </strong>
                                                                </td>
                                                            </tr>

                                                        </table>

                                                    </td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>

                                </table>

                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="tr-items-table">
                            <td colspan="100%" style="padding: 0; vertical-align: top; height: 100%;">
                                <!-- Items Table -->
                                <div class="items-wrapper" id="items-wrapper" style="height: 100%;">
                                    <table class="items-table" style="margin: 0 !important; height: 100% !important; border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center; width: 5%;border-top: 1px solid #000 !important; border-left: none !important;">Sr No.</th>
                                                <th style="text-align: center; width: 10%;border-top: 1px solid #000 !important;">Product Code</th>
                                                <th style="text-align: center; width: 28%;border-top: 1px solid #000 !important;">Product Description</th>
                                                <th style="text-align: center; width: 8%;border-top: 1px solid #000 !important;">HSN</th>
                                                <th style="text-align: center; width: 8%;border-top: 1px solid #000 !important;">Unit</th>
                                                <th style="text-align: center; width: 8%;border-top: 1px solid #000 !important;">Qty</th>
                                                @if ($canViewPrice)
                                                <th class="without_price" style="text-align: center; width: 10%;border-top: 1px solid #000 !important;">Price</th>
                                                <th class="without_price" style="text-align: center; width: 12%;border-top: 1px solid #000 !important; border-right: none !important;">Taxable Amount</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $counter = $pageIndex * $rowsPerPage + 1;
                                            @endphp
                                            @foreach($chunk as $item)
                                                @php
                                                    $productModel = $item->product ?? \App\Models\Product::find($item->product_id);
                                                    $prodPartNo = $productModel ? $productModel->part_no : ($item->product_code ?? '-');
                                                    $displayProdName = $productModel ? ($productModel->product_name ?: $productModel->product_description) : ($item->product_name ?? ($item->product_description ?? '-'));

                                                    $custInfo = '';
                                                    $quotationCustomer = $quotation->customer ?? null;
                                                    if ($quotationCustomer && $quotationCustomer->customer_name) {
                                                        $cItem = \App\Models\ProductCustomerItem::where('company_id', $quotation->company_id ?? $item->company_id)
                                                            ->where('product_id', $item->product_id)
                                                            ->where('customer_name', $quotationCustomer->customer_name)
                                                            ->whereNull('deleted_at')
                                                            ->first();
                                                        if ($cItem) {
                                                            $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                                            $custDesc = trim($cItem->customer_item_description ?? '');
                                                            $custParts = array_filter([$custCode, $custDesc]);
                                                            $custInfo = implode(' ', $custParts);
                                                        }
                                                    }
                                                    $displayProductCode = $prodPartNo;
                                                    if ($isDhruvik && !empty($custInfo)) {
                                                        $displayProductCode .= ' (' . $custInfo . ')';
                                                    } elseif (empty($prodPartNo) || $prodPartNo === '-') {
                                                        $displayProductCode = $item->product_code ?? '-';
                                                        if (!$isDhruvik && !empty($displayProductCode)) {
                                                            $displayProductCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayProductCode);
                                                        }
                                                    }
                                                @endphp
                                                <tr style="height: 42px;">
                                                    <td style="padding: 12px 10px; border-left: none !important; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">{{ $counter++ }}</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">{{ $displayProductCode }}</td>
                                                    <td style="padding-left: 6px !important; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; line-height: 1.5;">{{ $displayProdName }}</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">{{ $item->hsn_code ?? '-' }}</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">{{ $item->unit ?? '-' }}</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                                        {{ strtolower($item->unit) === 'kg'
                                                            ? number_format($item->quantity ?? 0, 3, '.', '')
                                                            : (int) ($item->quantity ?? 0) }}
                                                    </td>
                                                    @if ($canViewPrice)
                                                    <td class="without_price" style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">{{ $item->price_in_amount ?? '' }}</td>
                                                    <td class="without_price" style="padding: 12px 10px; border-right: none !important; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">{{ $item->amount ?? '' }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach

                                            {{-- Add empty rows if needed --}}
                                            @php $emptyRowsCount = $rowsPerPage - count($chunk); @endphp
                                            @for ($i = 0; $i < $emptyRowsCount; $i++)
                                                <tr style="{{ ($i == $emptyRowsCount - 1) ? 'height: auto;' : 'height: 42px;' }}">
                                                    <td style="padding: 12px 10px; border-left: none !important; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    <td style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    @if ($canViewPrice)
                                                    <td class="without_price" style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    <td class="without_price" style="padding: 12px 10px; border-right: none !important; border-bottom: none !important; border-top: none !important;">&nbsp;</td>
                                                    @endif
                                                </tr>
                                            @endfor
                                        </tbody>
                                        <tfoot>
                                            <!-- Total Qty Row -->
                                            <tr class="total-qty-row">
                                                <td colspan="5" style="text-align: right; border-left: none !important;">Total Qty</td>
                                                <td style="text-align: center;">{{ $total_qty ?? '' }}</td>
                                                @if ($canViewPrice)
                                                <td class="without_price" colspan="2" style="border-right: none !important;"></td>
                                                @endif
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </td>
                        </tr>

                        <tr class="final-footer-row" id="final-footer-row">
                            <td colspan="100%" style="padding:0;">

                                <table class="bottom-section no-break" style="width: 100%;margin-top: 0px !important;">
                                    <tr>
                                        <td style="width: 55%; border-right: 1px solid #000; vertical-align: top;padding:0px !important;border-top: none !important;">

                                            <div style="min-height: 70px !important; margin-bottom: 10px; padding:0px !important; border-bottom: 1px solid #000;">
                                                <p style="margin-bottom:5px !important; font-size: 17px; font-weight: bold;margin-left:10px !important;margin-top:5px !important;">Net Amount In
                                                    Words</p>
                                                <p style="margin-bottom:5px !important; font-size: 14px; font-weight: bold;margin-left:10px !important;">
                                                    {{ App\Helpers\Helper::convertToRupees($document->grand_total) }}
                                                </p>
                                            </div>


                                            <div style="margin-bottom: 10px; padding:0px !important;padding-bottom: 4px !important; border-bottom: 1px solid #000;">
                                                <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold;margin-left:10px !important;margin-top:5px !important;">Bank Details</p>
                                                <div style="margin-left:16px !important;font-size: 10px !important;">
                                                    <table style="width: 100%; ">
                                                        {!! str_replace('</p>', '</p>' . PHP_EOL, $document->company->bank_details ?? '-') !!}
                                                    </table>
                                                </div>
                                            </div>


                                            <div style="margin-left:10px !important;margin-bottom:5px !important;">
                                                <p
                                                    style="margin: 0 0 8px 0; padding:0px !important; font-size: 13px; font-weight: bold;margin-top:5px !important;">
                                                    Terms and Conditions
                                                </p>
                                                <ol style="margin: 0; padding-left: 18px; font-size: 10px; line-height: 1;">
                                                    {!! $document->term_and_condition ?? '-' !!}
                                                </ol>
                                            </div>
                                        </td>

                                        <td style="width: 45%; vertical-align: top;padding:0px !important;border-top: none !important;">

                                            <table style="width: 100%; font-size: 14px;margin-top:5px !important;margin-bottom:5px !important;">
                                                <tr>
                                                    <td style="padding: 10px 6px !important; text-align: left;border: none !important;">Basic Amount</td>
                                                    <td style="padding: 10px 6px !important; text-align: right;border: none !important;"><strong>₹
                                                            {{ $sub_total ?? '' }}</strong></td>
                                                </tr>

                                                @if(!empty($document->packing_forwarding_charge))
                                                    <tr>
                                                    <td style="padding: 10px 6px !important; text-align: left;border: none !important;">Packing & Forwarding Charge</td>
                                                    <td style="padding: 10px 6px !important; text-align: right;border: none !important;"><strong>₹
                                                    {{ $document->packing_forwarding_charge ?? '' }}</strong></td>
                                                    </tr>
                                                @endif

                                                @if(!empty($document->transport_charge))
                                                    <tr>
                                                    <td style="padding: 10px 6px !important; text-align: left;border: none !important;">Transport</td>
                                                    <td style="padding: 10px 6px !important; text-align: right;border: none !important;"><strong>₹
                                                    {{ $document->transport_charge ?? '' }}</strong></td>
                                                    </tr>
                                                @endif

                                                @if(!empty($document->freight_charge))
                                                    <tr>
                                                    <td style="padding: 10px 6px !important; text-align: left;border: none !important;">Freight</td>
                                                    <td style="padding: 10px 6px !important; text-align: right;border: none !important;"><strong>₹
                                                    {{ $document->freight_charge ?? '' }}</strong></td>
                                                    </tr>
                                                @endif


                                                @if($is_igst == 1)
                                                    <tr>
                                                        <td style="padding: 10px 6px !important; text-align: left;border: none !important;">IGST</td>
                                                        <td style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                            <strong>₹ {{ number_format($document->gst_amount, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                @else
                                                    <tr>
                                                        <td style="padding: 10px 6px !important; text-align: left;border: none !important;">CGST</td>
                                                        <td style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                            <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 10px 6px !important; text-align: left;border: none !important;">SGST</td>
                                                        <td style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                            <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                                        </td>
                                                    </tr>
                                                @endif

                                                <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;margin-top:19px !important;">
                                                    <td style="padding: 12px 6px !important; text-align: left; font-size: 15px;margin-bottom:10px !important;margin-top:10px !important;border-left: none !important;border-right: none !important;"><strong>Net
                                                            Amount</strong></td>
                                                    <td
                                                        style="padding: 12px 6px !important; text-align: right; font-size: 16px; font-weight: bold;margin-bottom:10px !important;margin-top:10px !important;border-left: none !important;border-right: none !important;">
                                                        ₹ {{ $document->grand_total }}
                                                    </td>
                                                </tr>
                                            </table>


                                            <div style="text-align:center; margin-top:10px;">
                                                <div class="signature-print-box">
                                                    @if ($document->company && $document->company->authorised_signature_url)
                                                        <img src="{{ $document->company->authorised_signature_url }}"/>
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
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const withoutPrice = localStorage.getItem('quotation_without_price') === 'true';
            if (withoutPrice) {
                var elements = document.querySelectorAll('.without_price');
                elements.forEach(function(el) {
                    el.style.display = 'none';
                });
            }
        });

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
