<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Job Work Pending Report - Print</title>
    <style>
        @media print {
            @page { size: A4; margin: 5mm; }
        }

        @media print {
            th {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact; /* Chrome/Edge */
                print-color-adjust: exact; /* Firefox */
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 5px;
            color: #333;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        h2 {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .sub-table th {
            background: #e5e7eb;
            font-size: 11px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: #eee;
            border-radius: 4px;
            font-size: 10px;
            color: #777;
        }

        .pending {
            font-weight: bold;
            text-align: right;
            background: #fafafa;
        }
    </style>
</head>

<body onload="printAndRedirect();">

<h1>Job Work Pending Report</h1>
<h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

@foreach ($grouped as $issueId => $items)
    @php
        $challan = $items->first()->job_work_issue;
        $vendor = $challan?->vendor?->vendor_name ?? '-';
        $issueDate = $challan?->job_work_issue_date
            ? Carbon\Carbon::parse($challan->job_work_issue_date)->format('d-m-Y')
            : '-';

        $jobWorkIssueIds = $challan->id;
        $receiveItems = App\Models\JobWorkReceiveItems::with('job_work_receive')
            ->whereHas('job_work_receive', function ($q) use ($jobWorkIssueIds) {
                $q->where('job_work_issue_id', $jobWorkIssueIds);
            })
            ->get();
        $master_total_pending = $challan?->master_total_pending ?? '-';
    @endphp

    <table>
        <thead>
            <tr style="background:#f3f4f6; font-weight:bold;">
                <th style="width:10%;">Challan</th>
                <th style="width:15%;">Date</th>
                <th style="width:30%;">Party Name</th>
                <th style="width:25%;">Item Name</th>
                <th style="width:20%;" class="text-end">Weight</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $itm)

                @php
                    $issueWeight = (float) ($itm->issue_weight ?? 0);
                    $blPercentage = (float) ($itm->bl_percentage ?? 0);

                    $finalWeight = $issueWeight + ($issueWeight * $blPercentage / 100);
                @endphp
                <tr>
                    <td>{{ $challan->job_work_issue_no ?? '-' }}</td>
                    <td>{{ $issueDate }}</td>
                    <td>{{ $vendor }}</td>
                    <td>{{ $itm->product?->product_name ?? '-' }}</td>
                    <td class="text-end">{{ number_format($finalWeight ?? 0, 3) }}</td>
                </tr>
            @endforeach

            <tr>
                <td colspan="5">
                        <table class="sub-table">
                            <thead>
                                <tr style="background:#f3f4f6; font-weight:bold;">
                                    <th>Chl No.</th>
                                    <th>Date</th>
                                    <th>Item Name</th>
                                    <th>KG</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Wire</th>
                                    <th class="text-end">Scrap</th>
                                    <th class="text-end">BL</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                        @if ($receiveItems->count())

                            @php
                                $sumKg = $sumQty = $sumScrap = $sumBL = $sumTotal = 0;
                            @endphp

                            <tbody>
                                @foreach ($receiveItems as $rec)
                                    @php
                                        $receiveDate = $rec->job_work_receive?->job_work_receive_date
                                            ? \Carbon\Carbon::parse($rec->job_work_receive->job_work_receive_date)->format('d-m-Y')
                                            : '-';

                                        $kg    = (float) ($rec->received_weight ?? 0);
                                        $qty   = (float) ($rec->quantity ?? 0);
                                        $scrap = (float) ($rec->incoming_scrap ?? 0);
                                        $bl    = (float) ($rec->bl_percentage ?? 0);
                                        $total = (float) ($rec->received_weight ?? 0);

                                        $sumKg += $kg;
                                        $sumQty += $qty;
                                        $sumScrap += $scrap;
                                        $sumBL += $bl;
                                        $sumTotal += $total;
                                    @endphp
                                    <tr>
                                        <td>{{ $challan->job_work_issue_no ?? '-' }}</td>
                                        <td>{{ $receiveDate }}</td>
                                        <td>{{ $rec->product_name ?? '-' }} {{ $rec->product_code ?? '' }}</td>
                                        <td>{{ $rec->received_weight ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($rec->quantity ?? 0, 3) }}</td>
                                        <td class="text-end"></td>
                                        <td class="text-end">{{ number_format($rec->incoming_scrap ?? 0, 3) }}</td>
                                        <td class="text-end">{{ number_format($rec->bl_percentage ?? 0, 3) }}</td>
                                        <td class="text-end">{{ number_format($rec->received_weight ?? 0, 3) }}</td>
                                    </tr>
                                @endforeach


                                <tr style="background:#e5e7eb;font-weight:bold;">
                                    <td colspan="3" class="text-end">TOTAL</td>
                                    <td class="text-end">{{ number_format($sumKg,3) }}</td>
                                    <td class="text-end">{{ number_format($sumQty,3) }}</td>
                                    <td class="text-end">-</td>
                                    <td class="text-end">{{ number_format($sumScrap,3) }}</td>
                                    <td class="text-end">{{ number_format($sumBL,3) }}</td>
                                    <td class="text-end">{{ number_format($sumTotal,3) }}</td>
                                </tr>
                            </tbody>
                        @endif
                        </table>
                </td>
            </tr>

            <tr>
                <td colspan="5" class="pending">Challan Pending Wt.: {{ number_format($master_total_pending, 3) }}</td>
            </tr>
        </tbody>
    </table>

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
