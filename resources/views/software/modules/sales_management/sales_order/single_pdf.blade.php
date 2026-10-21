<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SalesOrder {{ $salesOrder->sales_order_no ?? '-' }} - PDF</title>

    <style>
        /* PDF Specific Resets */
        @page {
            size: A4;
            margin: 5mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            background: #fff;
            line-height: 1.4;
        }

        /* The Main Page Border as in Image */
        .print-page {
            width: 100%;
            height: auto;
            border: 1.5px solid #000 !important;
            box-sizing: border-box;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td, th {
            padding: 4px 6px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .bordered {
            border: 1px solid #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        
        .bg-gray {
            background-color: #d3d3d3 !important;
        }

        /* HEADER SECTION */
        .header-table td {
            vertical-align: middle;
            border-bottom: 1px solid #000;
        }

        /* INFO GRID SECTION */
        .info-grid td {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            padding: 5px;
            font-size: 10px;
        }
        .info-grid td:last-child {
            border-right: none;
        }

        .section-banner {
            background-color: #d3d3d3;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            padding: 6px;
            border-bottom: 1px solid #000;
        }

        /* ITEMS TABLE SECTION */
        .items-table {
            border-bottom: 1px solid #000;
        }
        .items-table th {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            background-color: #d3d3d3;
            text-align: center;
            font-size: 10px;
            padding: 4px;
            vertical-align: middle;
        }
        .items-table td {
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
            font-size: 10px;
            padding: 4px;
            vertical-align: middle;
        }
        .items-table td:last-child, .items-table th:last-child {
            border-right: none;
        }

        /* FOOTER SECTION */
        .footer-table {
            page-break-inside: avoid;
        }

        .main_logo {
            max-height: 80px;
            max-width: 250px;
        }
    </style>
</head>

@php
    $document = $salesOrder;
    $documentNo = $document->sales_order_no ?? '-';
    $documentDate = $document->sales_order_date ? \Carbon\Carbon::parse($document->sales_order_date)->format('d-m-Y') : '-';
    $items = $document->sales_order_items ?? [];
    $showPrice = isset($isWithoutPrice) ? !$isWithoutPrice : true;
    
    // Address splitting for Header layout
    $raw = $document->company->address ?? '';
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);
    $part1 = $parts[0] ?? '';
    $part1_lines = array_filter(explode('<br>', $part1));
    $part2 = $parts[1] ?? '';
    
    $company = $document->company;
@endphp

<body>
    <div class="print-page">
        <!-- ====================== HEADER ========================= -->
        <table class="header-table" style="width: 100%;">
            <tr>
                <!-- Logo -->
                <td style="width: 29%; text-align: left; padding-left: 5px; vertical-align: middle;">
                    <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                                    style="max-height: 85px; max-width: 190px;" />
                </td>
                
                <!-- Company Name & Main Address -->
                <td style="width: 45%; text-align: left; padding-left: 10px; vertical-align: middle;">
                    <h3 style="margin: 0; font-size: 13px; font-weight: bold; text-align: left;">{{ $company->company_name ?? '-' }}</h3>
                    <div style="font-size: 11px; margin-top: 5px; text-align: left;">
                        @foreach($part1_lines as $line)
                            {!! strip_tags($line) !!}<br>
                        @endforeach
                        Phone: {{ $company->whatsapp_number ?? '-' }}<br>
                        Email: {{ $company->email ?? '-' }}
                    </div>
                </td>
                
                <!-- Statutory IDs -->
                <td style="width: 35%; text-align: right; font-size: 10px; font-weight: bold; white-space: nowrap; padding-right: 10px; vertical-align: middle;">
                     {!! str_replace('</p>', '</p>' . PHP_EOL, $part2 ?? '-') !!}
                </td>
            </tr>
        </table>

        <!-- ====================== SALES ORDER TITLE ========================= -->
        <div class="section-banner">Sales Order</div>

        <!-- ====================== INFO GRID ========================= -->
        <table class="info-grid" style="width: 100%;">
            <tr>
                <!-- Customer Detail -->
                <td style="width: 34%;">
                    <div class="bold" style="margin-bottom: 5px;">Customer Detail</div>
                    <span class="bold">Customer Code :</span> {{ $document->customer?->client_code ?? '-' }}<br>
                    <span class="bold">Customer Name :</span> {!! str_replace('(OPC)', '<br>(OPC)', $document->customer?->customer_name ?? '-') !!}<br>
                    <span class="bold">Phone :</span> {{ $document->phone ?: ($document->customer?->mobile_number ?? '-') }}<br>
                    <span class="bold">Email :</span> {{ $document->customer?->email ?? '-' }}<br>
                    <span class="bold">City :</span> {{ $document->customer?->city?->name ?? '-' }}<br>
                    <span class="bold">State :</span> {{ $document->customer?->state?->name ?? '-' }}<br>
                    <span class="bold">Pincode :</span> {{ $document->customer?->pincode ?? '-' }}
                </td>
                
                <!-- Billing Address -->
                <td style="width: 30%;">
                    <div class="bold" style="margin-bottom: 5px;">Billing Address:</div>
                    {!! nl2br(trim(strip_tags($document->billing_address ?: ($document->customer?->billing_address ?: ($document->customer?->address ?: '-'))))) !!}<br>
                    City: {{ $document->customer?->city?->name ?? '' }} State: {{ $document->customer?->state?->name ?? '' }}, Pincode: {{ $document->customer?->pincode ?? '' }}
                    @php
                        $soShipAddress = $document->shipping_address ?: ($document->billing_address ?: ($document->customer?->shipping_address ?: ($document->customer?->address ?: '')));
                        $soShipGst = $document->shipping_gst_no ?: ($document->gst_no ?: ($document->customer?->gst_number ?? ''));
                    @endphp
                    @if(!empty($soShipAddress))
                        <div class="bold" style="margin-top: 6px; margin-bottom: 2px;">Shipping Address:</div>
                        {!! nl2br(trim(strip_tags($soShipAddress))) !!}
                        @if(!empty($soShipGst))
                            <br><span class="bold">GSTIN:</span> {{ $soShipGst }}
                        @endif
                    @endif
                </td>
                
                <!-- Order Info -->
                <td style="width: 36%;">
                    <span class="bold">Order No:</span> {{ $documentNo }}<br>
                    <span class="bold">Order Date:</span> {{ $documentDate }}<br>
                    <span class="bold">GSTIN:</span> {{ $document->gst_no ?: ($document->customer?->gst_number ?? '-') }}
                </td>
            </tr>
        </table>

        <!-- ====================== ITEMS TABLE ========================= -->
        <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">Sr No.</th>
                        <th style="width: 12%;">Product Code</th>
                        <th style="text-align: center; width: 34%;">Product Description</th>
                        <th style="width: 9%;">HSN</th>
                        <th style="width: 8%;">Unit</th>
                        <th style="width: 8%;">Qty</th>
                        @if($showPrice)
                            <th style="width: 10%;">Price</th>
                            <th style="width: 14%;">Taxable<br>Amount</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $sub_total = 0; 
                        $counter = 1;
                    @endphp
                    @foreach($items as $item)
                        @php
                            $taxable_amt = ($item->price_in_amount ?? 0) * ($item->quantity ?? 1);
                            $sub_total += $taxable_amt;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $counter++ }}</td>
                            <td class="text-center">{{ $item->product_code ?? '-' }}</td>
                            <td class="text-center">{{ $item->product_name ?? ($item->product_description ?? '-') }}</td>
                            <td class="text-center" style="white-space: nowrap;">{{ $item->hsn_code ?? '-' }}</td>
                            <td class="text-center">{{ $item->unit ?? '-' }}</td>
                            <td class="text-center">{{ (int)$item->quantity }}</td>
                            @if($showPrice)
                                <td class="text-center">{{ number_format($item->price_in_amount ?? 0, 2) }}</td>
                                <td class="text-center">{{ number_format($taxable_amt, 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                    
                    {{-- Empty Rows to fill space like in Image --}}
                    @php
                        $emptyRowsCount = max(0, 12 - count($items));
                    @endphp
                    @for($i = 0; $i < $emptyRowsCount; $i++)
                        <tr style="height: 18px;">
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            @if($showPrice)
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            @endif
                        </tr>
                    @endfor
                    
                    {{-- Sub Total Row --}}
                    @if($showPrice)
                        <tr class="bg-gray">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center bold" style="white-space: nowrap;">Sub Total</td>
                            <td class="text-center bold" style="white-space: nowrap;">₹ {{ number_format($sub_total, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

        @php
            // Determine if IGST or CGST/SGST should be shown based on state comparison
            $is_igst = ($company->state_id != ($document->customer?->state_id ?? 0));
            $has_transport = (!empty($document->transport_charge) && $document->transport_charge > 0);
        @endphp

        <!-- ====================== FOOTER SUMMARY ========================= -->
        <table class="footer-table" style="width: 100%; border-collapse: collapse;">
            @if($showPrice)
                <tr>
                    <!-- Bank & Terms Column -->
                    <td style="width: 63%; vertical-align: top; padding: 0; border: 1px solid #000;">
                        <div style="padding: 5px 6px 2px 6px;">
                            <div class="bold" style="font-size: 9px;">Bank Details:</div>
                            <div style="font-size: 7.5px; line-height: 1.3;">
                                {!! str_replace(['</p>', '<p>'], ['', ''], $company->bank_details ?? '-') !!}
                            </div>
                        </div>
                        <div style="padding: 2px 6px;">
                            <div class="bold" style="font-size: 9px;">Terms & Condition:</div>
                            <div style="font-size: 7.5px; line-height: 1.2;">
                                {!! $document->term_and_condition ?: '1. Goods once sold cannot be taken back.<br>2. We are not responsible for any breakage, theft or damage.' !!}
                            </div>
                        </div>
                        <div style="padding: 2px 6px 5px 6px; font-size: 8.5px;">
                            <span class="bold">Grand Total In Words:</span> {{ App\Helpers\Helper::convertToRupees($document->grand_total ?? $sub_total) }}
                        </div>
                    </td>
                    
                    <!-- Totals Grid Column (Nested Table) -->
                    <td style="width: 37%; vertical-align: top; padding: 0; border: 1px solid #000; border-left: none;">
                        <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                            <tr>
                                <td class="text-right bold" style="width: 62.2%; padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; border-right: 1px solid #000;">Total Taxable Amount</td>
                                <td class="text-right bold" style="width: 37.8%; padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; white-space: nowrap;">₹ {{ number_format($sub_total, 2) }}</td>
                            </tr>

                            @if($has_transport)
                                <tr>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; border-right: 1px solid #000;">Transport</td>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; white-space: nowrap;">₹ {{ number_format($document->transport_charge, 2) }}</td>
                                </tr>
                            @endif

                            @if($is_igst)
                                <tr>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; border-right: 1px solid #000;">IGST</td>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; white-space: nowrap;">₹ {{ number_format($document->gst_amount ?? 0, 2) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; border-right: 1px solid #000;">CGST</td>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; white-space: nowrap;">₹ {{ number_format(($document->gst_amount ?? 0) / 2, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; border-right: 1px solid #000;">SGST</td>
                                    <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; white-space: nowrap;">₹ {{ number_format(($document->gst_amount ?? 0) / 2, 2) }}</td>
                                </tr>
                            @endif

                            <tr>
                                <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; border-right: 1px solid #000;">Round off</td>
                                <td class="text-right bold" style="padding: 11.5px; font-size: 9.2px; border-bottom: 1px solid #000; white-space: nowrap;">{{ $document->round_off ?: '0.00' }}</td>
                            </tr>
                            <tr>
                                <td class="text-right bold" style="padding: 13px; font-size: 10.5px; border-right: 1px solid #000;">Grand Total</td>
                                <td class="text-right bold" style="padding: 13px; font-size: 10.5px; white-space: nowrap;">₹ {{ number_format($document->grand_total, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            @else
                <tr>
                    <!-- Bank & Terms Only (No Price) -->
                    <td style="width: 100%; vertical-align: top; padding: 0; border: 1px solid #000;">
                        <div style="border-bottom: 1px solid #000; padding: 8px;">
                            <div class="bold">Bank Details:</div>
                            <div style="font-size: 9px; line-height: 1.3;">
                                {!! str_replace(['</p>', '<p>'], ['', ''], $company->bank_details ?? '-') !!}
                            </div>
                        </div>
                        <div style="padding: 8px;">
                            <div class="bold">Terms & Condition:</div>
                            <div style="font-size: 8px; line-height: 1.2;">
                                {!! $document->term_and_condition ?: '1. Goods once sold cannot be taken back.<br>2. We are not responsible for any breakage, theft or damage.' !!}
                            </div>
                        </div>
                    </td>
                </tr>
            @endif
        </table>
    </div>
</body>

</html>
