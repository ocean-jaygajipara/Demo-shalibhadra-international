    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Job Work Receive Report List - Print</title>
        <style>
            @media print {
                @page {
                    size: A4;
                    margin: 5mm;
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
                font-size: 24px;
                font-weight: bold;
                text-transform: uppercase;
                color: #2c3e50;
            }

            h2 {
                text-align: center;
                font-size: 14px;
                margin-bottom: 30px;
                color: #7f8c8d;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th {
                padding: 8px;
                border: 1px solid #ddd;
            }

            th.text-center {
                text-align: center;
            }

            td {
                padding: 8px;
                border: 1px solid #ddd;
            }
        </style>
    </head>

    <body onload="printAndRedirect();">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th colspan="11" class="text-center">
                        <h3>
                            Job Work Issue Report - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Ch. No</th>
                    <th>Party Name</th>
                    <th>Item Name</th>
                    <th>Process</th>
                    <th>Date</th>
                    <th class="text-end">Recd. Qty</th>
                    <th class="text-end">Recd. Kgs</th>
                    <th class="text-end">Incoming Scrap</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            <tbody>
                @foreach ($jobWorkIssueItems as $item)
                    <tr>
                        <td>{{ $item->job_work_receive->job_work_receive_no ?? '-' }}</td>
                        <td>{{ $item->job_work_receive?->vendor?->vendor_name  ?? '-' }}</td>
                        <td>{{ $item->product_name ?? '-' }}</td>
                        <td>{{ $item->job_work_receive?->process_received?->name ?? '-' }}</td>
                        <td>
                            {{ $item->job_work_receive?->job_work_receive_date
                                ? \Carbon\Carbon::parse($item->job_work_receive->job_work_receive_date)->format('d/m/Y')
                                : '-' }}
                        </td>
                        <td class="text-end">{{ number_format($item->quantity ?? 0, 3) }}</td>
                        <td class="text-end">{{ number_format($item->received_weight ?? 0, 3) }}</td>
                        <td class="text-end">{{ ($item->incoming_scrap > 0) ? number_format($item->incoming_scrap, 3) : 0 }}</td>
                        <td class="text-end">{{ number_format($item->rate_in_amount ?? 0, 3) }}</td>
                        <td class="text-end">{{ number_format($item->amount ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>

            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: #f8f9fa;">
                    <td colspan="5" class="text-end">Grand Total:</td>
                    <td class="text-end">{{ number_format($grandTotals['received_qty'], 3) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['received_kg'], 3) }}</td>
                    <td></td>
                    <td></td>
                    <td class="text-end"></td>
                </tr>
            </tfoot>
        </table>






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
