<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Job Work Order {{ $jobOrder->job_work_order_no ?? '-' }} - Print</title>
    <style>
        @page {
            size: A4;
            /* margin-top: 20mm; */
            /* margin-bottom: 5mm;
            margin-left: 0mm;
            margin-right: 0mm; */
            margin:10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 18px;
        }

        table {
            width: 100%;
            /* height: 200px; */
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 14px;
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
            border: 1px solid #000;
            padding: 6px;
            margin-bottom: 8px;
        }

        .logo-img {
            max-height: 60px;
            object-fit: contain;
        }

        .print-box {
            height: 1000px;
            border: 1px solid #000;
            padding: 15px;
            margin: auto;
            box-sizing: border-box;
        }
    </style>
</head>

<body onload="printAndRedirect();">

    <div class="print-box" style="width: 100%; margin: auto;">
        <h3 class="text-center fw-bold">JOB WORK ORDER</h3>

        {{-- Top Section --}}
        <table style="margin-bottom: 10px; width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tr>
                <!-- Left Column: Company Info -->
                <td style="width: 40%; vertical-align: top; word-wrap: break-word;">
                    <strong>{{ optional($jobOrder->company)->company_name ?? 'Company Name' }}</strong>
                    @php
                        $raw = $jobOrder->company->address;
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);

    $part1 = $parts[0] ?? '';
    $part2 = $parts[1] ?? '';
                    @endphp
                    {{-- {{ str_replace('&nbsp;', ' ', subject: strip_tags(optional($jobOrder->company)->address)) }}<br> --}}
                    {!! str_replace('</p>', '</p>' . PHP_EOL, $part1 ?? '-') !!}
                    {!! str_replace('</p>', '</p>' . PHP_EOL, $part2 ?? '-') !!}
                    {{-- GSTIN: {{ optional($jobOrder->company)->gst_no ?? '-' }} --}}
                </td>

                <!-- Middle Column: Order Info -->
                <td style="width: 40%; vertical-align: top; word-wrap: break-word;">
                    <strong>JW. Order No. :</strong> {{ $jobOrder->job_work_order_no ?? '-' }}<br>
                    <strong>PO Date :</strong>
                    {{ $jobOrder->job_work_order_date ? \Carbon\Carbon::parse($jobOrder->job_work_order_date)->format('d/m/Y') : '-' }}<br>
                    <strong>Delivery Date :</strong>
                    {{ optional($jobOrder->job_work_order_items->first())->delivered_date ? \Carbon\Carbon::parse($jobOrder->job_work_order_items->first()->delivered_date)->format('d/m/Y') : '-' }}<br>
                    <strong>Payment Terms :</strong> {{ $jobOrder->payment_terms ?? '-' }}
                </td>

                <!-- Right Column: Logo -->
                <td style="width: 20%; text-align: center; vertical-align: middle;">
                    @php
                        $logo = $jobOrder->company->company_logo_url ?? asset('software/img/logo.png');
                    @endphp
                    <img src="{{ $logo }}" style="max-height: 60px; max-width: 100%; object-fit: contain;">
                </td>
            </tr>
        </table>



        {{-- Vendor / Delivery Info --}}
        <table style="margin-bottom: 10px;">
            <tr>
                <td style="width: 100%;">
                    <strong>To,</strong> <strong>{{ optional($jobOrder->vendor)->vendor_name ?? '-' }}</strong><br>
                    {{ optional($jobOrder->vendor)->address ?? '-' }}<br>
                    <strong>Contact:</strong> {{ $jobOrder->phone ?? ($jobOrder->mobile_number ?? '-') }}<br>
                    <strong>GSTIN :</strong> {{ optional($jobOrder->vendor)->gst_number ?? '-' }}
                </td>
                {{-- <td style="width: 50%;">
                    <strong>Delivery To,</strong><br>
                    {{ optional($jobOrder->vendor)->shipping_address ?? '-' }}<br>
                    <strong>Contact Person:</strong> {{ optional($jobOrder->vendor)->vendor_name ?? '-' }}<br>
                    <strong>Mobile No:</strong> {{ $jobOrder->mobile_no ?? '-' }}
                </td> --}}
            </tr>
        </table>

        {{-- Items Table --}}
        <table style="margin-bottom: 10px;">
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
                        $qty_kg = $item->alter_quantity ?? 0;
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
                        <td class="text-end">{{ $item->unit ?? '-' }}</td>
                        <td class="text-end">{{ number_format($rate, 2) }}</td>
                        <td class="text-center">{{ number_format($gst, 2) }}</td>
                        <td class="text-end">{{ number_format($taxable, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end fw-bold">Total :</td>
                    <td class="text-end">{{ number_format($jobOrder->job_work_order_items->sum('alter_quantity'), 2) }}
                    </td>
                    <td class="text-end">{{ number_format($jobOrder->job_work_order_items->sum('quantity'), 2) }}</td>
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

        {{-- Remarks --}}
        <div class="border-box">
            <strong>Remarks :</strong> {{ $jobOrder->remarks ?? '-' }}
        </div>

        {{-- Terms & Signature --}}
        <table style="width:100%; margin-top:10px; border-collapse: collapse;">
            <tr>
                <td style="width:70%; padding:5px;">
                    <strong>Terms & Conditions :</strong><br>
                    {!! $jobOrder->term_and_condition ?? '-' !!}

                </td>
                <td style="width:30%; text-align:center; vertical-align:bottom; padding:5px;">
                    <div>For, {{ optional($jobOrder->company)->company_name ?? 'Company Name' }}</div>
                    @if ($jobOrder->company && $jobOrder->company->authorised_signature_url)
                        <div style="margin: 8px auto;">
                            <img src="{{ $jobOrder->company->authorised_signature_url }}" alt="Authorized Signature" style="max-height: 80px; max-width: 160px; object-fit: contain;">
                        </div>
                    @else
                        <br><br><br>
                    @endif
                    <strong>Authorized Signatory</strong>
                </td>
            </tr>
        </table>

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
