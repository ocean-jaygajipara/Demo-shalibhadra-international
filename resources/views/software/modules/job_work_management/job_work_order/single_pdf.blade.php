<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Job Work Order {{ $jobOrder->job_work_order_no ?? '-' }} - PDF</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            /* Using PDF-friendly font size */
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 13px;
            vertical-align: top;
        }

        .no-border td {
            border: none;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .border-box {
            margin: 0 -15px 10px -15px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: none;
            border-right: none;
            padding: 6px 15px;
            box-sizing: border-box;
        }

        .logo-img {
            max-height: 60px;
            object-fit: contain;
        }

        .print-box {
            border: 1px solid #000;
            padding: 15px 15px 65px 15px;
            margin: auto;
            box-sizing: border-box;
            min-height: 950px;
            position: relative;
        }

        .items-table-wrapper {
            margin: 0 -15px 10px -15px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-left: none;
            border-right: none;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .items-table th:first-child,
        .items-table td:first-child {
            border-left: none !important;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            border-right: none !important;
        }

        .terms-wrapper {
            margin: 0 -15px;
        }

        .terms-table {
            width: 100%;
            border-collapse: collapse;
            border-left: none;
            border-right: none;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .terms-table td:first-child {
            border-left: none !important;
        }

        .terms-table td:last-child {
            border-right: none !important;
        }

        .footer-box {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #000;
            background: #fff;
            padding-top: 6px;
            page-break-inside: avoid;
        }

        .footer-white {
            padding: 4px 10px 8px 10px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            border: none;
            padding: 0 4px;
            vertical-align: middle;
            font-size: 11.5px;
            color: #000;
        }

        .footer-black {
            position: relative;
            background: #000;
            height: 30px;
            margin: 0;
        }

        .footer-red {
            position: absolute;
            top: -8px;
            left: 10px;
            right: 10px;
            background: #d32027;
            color: #fff;
            text-align: center;
            font-size: 12.5px;
            font-weight: bold;
            padding: 6px 10px;
            border-radius: 12px 12px 0 0;
        }

        /* Watermark Styles */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.10;
            z-index: 0;
            pointer-events: none;
            user-select: none;
            width: 80%;
            text-align: center;
        }

        .watermark img {
            width: 100%;
            height: auto;
            max-width: 360px;
            object-fit: contain;
        }
    </style>
</head>

<body>

    <div class="print-box">
        @php
            $company = $jobOrder->company_id ? App\Helpers\Helper::getCompanyDetailById($jobOrder->company_id) : null;
            $letterHeadRaw = $company?->invoice_letter_head ?? ($company?->order_header_logo ?? '');
            $headerImg = !empty($letterHeadRaw) ? App\Helpers\Helper::webpToBase64($letterHeadRaw) : '';
            $sidebarMainLogo = isset($sidebarMainLogo) && !empty($sidebarMainLogo) ? $sidebarMainLogo : ($company?->company_logo ? App\Helpers\Helper::webpToBase64($company->company_logo) : '');
            $authorised_signature = $company?->authorised_signature ? App\Helpers\Helper::webpToBase64($company->authorised_signature) : '';
            $watermarkLogo = $company?->company_favicon ? App\Helpers\Helper::webpToBase64($company->company_favicon) : '';

            $mailPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABBUlEQVRo3u1YQRLDIAhMdvoE//9C/pDeOo4TKwhIbNlbHEd3YcXIcSQSiUQisTHO+qOUcu1Amog+vLF7BrYX8JKkKwIjW0O7QCR5toUiRHD3BNc+pZRrlZB6n5GFIT0DniLaIHHOH8tCK0S0a3KLB7uMEtHpZalZ8lP3gGU27iwjLdtTF5mFCE3UTW5ijaWsyJv8SkizobWMqYBe1Htj7bhFEYBl5ehZ6htRrQhYkeda6s42GhHwqB69MYlQFwGS6lET7s2zEAEP8pJ5WhHwJL/iYkQ0ea2I337Ur4q+JguYXfwpIvAk8i7vgai2CnffYV/o6e1G7EJ0KCC6A5dIJBKJxF/iDdeH2rYqb0uPAAAAAElFTkSuQmCC';
            $phonePng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAA50lEQVRoge2ZSw7DIAxE0x6E+5+Li7QrS1GkpsYef1DmrbIJ9jCATXIchBBCnszL8tIY4yPPc07TGCiWgp8Tv1IlRB30LnmhQoQqoCZ5IVvEOzNYBH9na2X2hUwXtndgewHcxNVsXwfUDlS3DL9YWkIdRUD3gKVmeFkW0M0F+CmU7YJJQCcXXIl0uB+ECRCihbgHrxYBGdgrwnPHhs2M9vQ5J4jYQ1Br0UeoRgR8bWaLgBey7BoRFgzpxN2khF1ospwIvZFliEhdrxGfaEqaMuRHgrKuEtWClLbF7SqxlU7/Gwgh5GF8ATkDbXL+8ldOAAAAAElFTkSuQmCC';
            $globePng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABkUlEQVRoge1ZUa7EIAh0X/Yc3P9cXsT389wYH+AoSG22k2yyacsMWMCWpvTgwXfjtYOUiIp0LufsqulCJjncOotcEw4iKr1j2upz5zmOEHCiqCMWWzOkFZt1QOLYGohG7hHAKtdlgmFBzBbm1byhIlb+H4txFDQ/1ABGBERUrBtRzvml8Y/sxQBOWf0KyR/oDmgEu4DqsQHU1OjTIyqIXqf6wukP7wAXREvk2YW4nXhUY1AKcSQeQYw4kAbxRsW0biE5YAHa3diVHRnvrAVEu70G7kKcCFfoK1wt36w9nEIjJzx4VqAW56lQF+wvgOGvvQ618eDoF1hKISQlSkrp05mICDD5DyKqK1omdD9YKuLGaZd0s/CxEUutNLI+JP3+ONyFdg2rrI/qy+8Dlt6N8pjeByQSj81LwsoTMJtC0nNP1IbV69f/nD7chaJ3W1RPDODSoSuDJX+QUV/EWGV5KnHKXTD7cevJHEJ2/Gx0h6AX19QLTdufd9THTm5RbHTM29YVJ3wju/1Xytt/J37wwIhfZixfzgYqz/YAAAAASUVORK5CYII=';
            $pinPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAACXBIWXMAAA7EAAAOxAGVKw4bAAABN0lEQVRoge1ZyxKEIAwLO/vh/jl7EYbBB9Im6uw0d2nSVGkrEAgEAgEHkurgnHPeBEuJHo964B7pw8AkMTQBM+RrcIIIigAL+UrAKcItwEO+knCIcAlgkK9EjCLMApjkCywivmwSLVpCCsEA8FEcCmyzqbgDAGMJjbJ5Rtbz7B7oDowIsJ2QldBdCAE9RjXO/hrJ7oG9Wme/wICwhHqyFzM/7Y70IjOUy30OsD+H1uNcJbQsi+fxHvc2cwWkdtrMxf0Sk1x4Zh4ocE5kLh6Uz6jThWdHygLjUO/mQLvIjC68YytRMLkXosSnthKTLrxrsVVwxQVW9gFBM3fRBVriJIP2mQvM7AOidnrgArcJZB7W4mC9To8pG2gOXHj3/4EerQuK7APirUTngjRZMqwuSPaigUDgD/ADkvV3wWTc4+gAAAAASUVORK5CYII=';
        @endphp
        @if (!empty($watermarkLogo))
            <div class="watermark">
                <img src="{{ $watermarkLogo }}" alt="Watermark">
            </div>
        @endif
        @if (!empty($headerImg))
            <div style="margin: -15px -15px 0 -15px; border-bottom: 1px solid #000;">
                <img src="{{ $headerImg }}" style="width: 100%; display: block;" />
            </div>
        @endif

        {{-- Billed To & Job Work Order Info Section --}}
        <div style="margin: {{ !empty($headerImg) ? '0' : '-15px' }} -15px 14px -15px;">
            <table style="width: 100%; border-collapse: collapse; border: none; {{ !empty($headerImg) ? '' : 'border-top: 1px solid #000;' }} border-bottom: 1px solid #000;">
                <tr>
                    <!-- LEFT : BILLED TO -->
                    <td style="width: 58%; vertical-align: top; padding: 6px 10px; border: none; border-right: 1px solid #000; font-size: 12px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 60px; font-weight: bold; border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">Billed:</td>
                                <td style="border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">
                                    <strong>{{ optional($jobOrder->vendor)->vendor_name ?? '-' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold; border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">To:</td>
                                <td style="border: none; padding: 2px 0; vertical-align: top; line-height: 1.2; font-size: 12px;">
                                    {!! nl2br(strip_tags(optional($jobOrder->vendor)->address)) !!}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold; border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">Contact:</td>
                                <td style="border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">
                                    {{ $jobOrder->phone ?? ($jobOrder->mobile_number ?? '-') }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: bold; border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">GSTIN:</td>
                                <td style="border: none; padding: 2px 0; vertical-align: top; font-size: 12px;">
                                    {{ optional($jobOrder->vendor)->gst_number ?? '-' }}
                                </td>
                            </tr>
                        </table>
                    </td>

                    <!-- RIGHT : JOB WORK ORDER INFO BOX -->
                    <td style="width: 42%; vertical-align: top; padding: 0; border: none;">
                        <div style="font-size: 14px; font-weight: bold; text-align: center; padding: 4px 0; border-bottom: 1px solid #000; background: #fff; letter-spacing: 0.5px;">
                            Job Work Order
                        </div>
                        <div style="padding: 4px 8px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="border: none; padding: 3px 6px; font-size: 12px;">JW Order No:</td>
                                    <td style="border: none; padding: 3px 6px; text-align: right; font-weight: bold; font-size: 12px;">
                                        {{ $jobOrder->job_work_order_no ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border: none; padding: 3px 6px; font-size: 12px;">Date:</td>
                                    <td style="border: none; padding: 3px 6px; text-align: right; font-weight: bold; font-size: 12px;">
                                        {{ $jobOrder->job_work_order_date ? \Carbon\Carbon::parse($jobOrder->job_work_order_date)->format('d-m-Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border: none; padding: 3px 6px; font-size: 12px;">Delivery Date:</td>
                                    <td style="border: none; padding: 3px 6px; text-align: right; font-weight: bold; font-size: 12px;">
                                        {{ optional($jobOrder->job_work_order_items->first())->delivered_date ? \Carbon\Carbon::parse($jobOrder->job_work_order_items->first()->delivered_date)->format('d-m-Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border: none; padding: 3px 6px; font-size: 12px;">Payment Terms:</td>
                                    <td style="border: none; padding: 3px 6px; text-align: right; font-weight: bold; font-size: 12px;">
                                        {{ $jobOrder->payment_terms ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Items Table --}}
        <div class="items-table-wrapper">
            <table class="items-table">
            <thead>
                <tr class="text-center">
                    <th>Sr No</th>
                    <th>Item Description</th>
                    <th>Quantity (Pcs)</th>
                    <th>Unit</th>
                    <th>Rate</th>
                    <th>GST%</th>
                    <th>Taxable Value</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $i = 1;
                    $total_value = 0;
                @endphp
                @foreach ($jobOrder->job_work_order_items as $item)
                    @php
                        $qty_pc = $item->quantity ?? 0;
                        $rate = $item->rate_in_amount ?? 0;
                        $gst = $item->gst_percent ?? 18;
                        $taxable = $qty_pc * $rate;
                        $total_value += $taxable;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $i++ }}</td>
                        <td>{{ $item->product_name ?? '-' }}</td>
                        <td class="text-end">{{ number_format($qty_pc, 2) }}</td>
                        <td class="text-center">{{ $item->unit ?? '-' }}</td>
                        <td class="text-end">{{ number_format($rate, 2) }}</td>
                        <td class="text-center">{{ number_format($gst, 2) }}</td>
                        <td class="text-end">{{ number_format($taxable, 2) }}</td>
                    </tr>
                @endforeach
                @php
                    $itemCount = count($jobOrder->job_work_order_items);
                    $totalRows = 7;
                    $emptyCount = max(0, $totalRows - $itemCount);
                    if ($itemCount <= 1) {
                        $emptyRowHeight = 38;
                    } elseif ($itemCount == 2) {
                        $emptyRowHeight = 32;
                    } elseif ($itemCount == 3) {
                        $emptyRowHeight = 28;
                    } elseif ($itemCount == 4) {
                        $emptyRowHeight = 24;
                    } else {
                        $emptyRowHeight = 22;
                    }
                @endphp
                @for ($k = 0; $k < $emptyCount; $k++)
                    <tr>
                        <td class="text-center" style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                        <td style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                        <td style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                        <td style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                        <td style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                        <td style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                        <td style="height: {{ $emptyRowHeight }}px; border-bottom: none; border-top: none;">&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end fw-bold">Total :</td>
                    <td class="text-end">{{ number_format($jobOrder->job_work_order_items->sum('quantity'), 2) }}</td>
                    <td class="text-center">-</td>
                    <td colspan="2" class="text-end fw-bold">Total Assessable Value</td>
                    <td class="text-end fw-bold">{{ number_format($total_value, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end">CGST</td>
                    <td class="text-end">{{ number_format($total_value * 0.09, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end">SGST</td>
                    <td class="text-end">{{ number_format($total_value * 0.09, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-end fw-bold">Grand Total</td>
                    <td class="text-end fw-bold">{{ number_format($total_value * 1.18, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>

        {{-- Remarks --}}
        <div class="border-box">
            <strong>Remarks :</strong> {{ $jobOrder->remarks ?? '-' }}
        </div>

        {{-- Terms & Signature --}}
        <div class="terms-wrapper">
            <table class="terms-table">
                <tr>
                    <td style="width:70%; padding: 6px 15px; border-right: 1px solid #000;">
                        <strong>Terms & Conditions :</strong><br>
                        {!! $jobOrder->term_and_condition ?? '-' !!}
                    </td>
                    <td style="width:30%; text-align:center; vertical-align:bottom; padding: 6px 15px;">
                        <div>For, {{ optional($jobOrder->company)->company_name ?? 'Company Name' }}</div>
                        @if (!empty($authorised_signature))
                            <div style="margin: 4px auto;">
                                <img src="{{ $authorised_signature }}" style="max-height: 65px; max-width: 130px; object-fit: contain;"/>
                            </div>
                        @else
                            <br><br><br><br>
                        @endif
                        <strong>Authorized Signatory</strong>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer-box">
            <div class="footer-white">
                <table class="footer-table">
                    <tr>
                        <td style="width: 33%; text-align: left;">
                            <table style="border-collapse: collapse; display: inline-table;">
                                <tr>
                                    <td style="padding: 0 5px 0 0; border: none; vertical-align: middle;">
                                        <img src="{{ $mailPng }}" width="14" height="14" style="display: block;">
                                    </td>
                                    <td style="padding: 0; border: none; vertical-align: middle; font-size: 12px;">
                                        info@shalibhadrainternational.com
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="width: 37%; text-align: center;">
                            <table style="border-collapse: collapse; display: inline-table; margin: 0 auto;">
                                <tr>
                                    <td style="padding: 0 5px 0 0; border: none; vertical-align: middle;">
                                        <img src="{{ $phonePng }}" width="14" height="14" style="display: block;">
                                    </td>
                                    <td style="padding: 0; border: none; vertical-align: middle; font-size: 12px;">
                                        +91 73832 38373 | +91 90333 41444
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="width: 30%; text-align: right;">
                            <table style="border-collapse: collapse; display: inline-table; float: right;">
                                <tr>
                                    <td style="padding: 0 5px 0 0; border: none; vertical-align: middle;">
                                        <img src="{{ $globePng }}" width="14" height="14" style="display: block;">
                                    </td>
                                    <td style="padding: 0; border: none; vertical-align: middle; font-size: 12px;">
                                        www.shalibhadrainternational.com
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="footer-black">
                <div class="footer-red">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; text-align: center; vertical-align: middle; padding: 0; color: #ffffff; font-size: 12.5px; font-weight: bold;">
                                <img src="{{ $pinPng }}" width="12" height="12" style="vertical-align: middle; margin-right: 5px;">
                                <span style="vertical-align: middle;">Plot No.4536, GIDC Phase - 3, Dared, Jamnagar-361005, Gujarat, India.</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
