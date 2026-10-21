<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Party Jobwork Statement</title>

    <style>
        @page {
            size: A4;
            margin: 6mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        h1 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 2px;
        }

        h2 {
            text-align: center;
            font-size: 12px;
            margin-bottom: 10px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cfd3d8;
            padding: 4px;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-start {
            text-align: left;
        }

        @media print {
            th {
                background: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <h1>SHALIBHADRA INTERNATIONAL PVT LTD</h1>
    <div style="text-align: center; font-size: 14px;">Plot No. 4536, GIDC, Phase III, Dared</div>
    <div style="text-align: center; font-size: 14px;">JAMNAGAR Phone : 02882731337</div><br>
    <div style="text-align: center; font-size: 14px;"><b>Party Jobwork Statement</b></div><br>

    @php
        $dateRange = '';
        if (request()->filled('filter_job_work_issue_date')) {
            $range = explode(' to ', request()->filter_job_work_issue_date);
            if (count($range) === 2) {
                $dateRange =
                    'From: ' .
                    \Carbon\Carbon::createFromFormat('d/m/Y', $range[0])->format('d-m-Y') .
                    ' To ' .
                    \Carbon\Carbon::createFromFormat('d/m/Y', $range[1])->format('d-m-Y');
            }
        }
    @endphp

    @if ($dateRange)
        <div style="text-align: left; font-size: 14px; margin-bottom: 15px; font-weight: bold;">
            {{ $dateRange }}
        </div>
    @endif

    <table class="table table-bordered table-sm text-center">
        <thead>
            <tr>
                <th rowspan="2">DATE</th>
                <th rowspan="2">CHALLAN</th>
                <th rowspan="2">PARTICULARS</th>
                <th colspan="2">ISSUED</th>
                <th colspan="2">RECD.</th>
                <th colspan="2">SCRAP / BL</th>
            </tr>
            <tr>
                <th>WT</th>
                <th>QTY</th>
                <th>WT</th>
                <th>QTY</th>
                <th>SCRAP</th>
                <th>BL%</th>
            </tr>
        </thead>

        <tbody>
            {{-- Issue Items --}}
            @foreach ($issueItems as $item)
                @php
                    $wt = (float) $item->product?->net_wt;
                    if ($wt == 0.0) {
                        $wt = (float) $item->product?->gross_wt;
                    }
                    $total_quantity =
                        $wt > 0 ? \App\Helpers\Helper::kgToQtyConvertForStock($item->issue_weight ?? 0, $wt) : 0;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->job_work_issue->job_work_issue_date)->format('d-m-Y') }}</td>
                    <td>{{ $item->job_work_issue->job_work_issue_no }}</td>
                    <td class="text-start">{{ $item->product?->product_name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($item->issue_weight ?? 0, 3) }}</td>
                    <td class="text-end">{{ number_format($total_quantity ?? 0, 2) }}</td> {{-- Calculated Qty --}}
                    <td class="text-end">0.000</td>
                    <td class="text-end">0</td>
                    <td class="text-end">0.000</td>
                    <td class="text-end">0%</td>
                </tr>
            @endforeach

            {{-- Receive Items --}}
            @foreach ($receiveItems as $rec)
                @php
                    $issue = $rec->job_work_receive?->job_work_issue;
                @endphp
                <tr>
                    <td>{{ $issue ? \Carbon\Carbon::parse($issue->job_work_issue_date)->format('d-m-Y') : '-' }}</td>
                    <td>{{ $issue?->job_work_issue_no ?? '-' }}</td>
                    <td class="text-start">{{ $rec->product?->product_name ?? '-' }}</td>
                    <td class="text-end">0.000</td>
                    <td class="text-end">0</td>
                    <td class="text-end">{{ number_format($rec->received_weight ?? 0, 3) }}</td>
                    <td class="text-end">{{ $rec->quantity ?? 0 }}</td>
                    <td class="text-end">{{ number_format($rec->incoming_scrap ?? 0, 3) }}</td>
                    <td class="text-end">{{ number_format($rec->bl_percentage ?? 0, 2) }}%</td>
                </tr>
            @endforeach

        </tbody>
    </table>

    <script>
        function printAndRedirect() {
            window.print();
            window.onafterprint = function () {
                setTimeout(function () {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 1);
            };
        }
    </script>

</body>

</html>