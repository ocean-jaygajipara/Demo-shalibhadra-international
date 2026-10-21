    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Job Work Issue Report List - Print</title>
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
                    <th colspan="20" class="text-center">
                        <h3>
                            Job Work Issue Report - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    <th>Challan No</th>
                    <th>Date</th>
                    <th>Party Name</th>
                    <th>Item Name</th>
                    <th class="text-end">Issued Qty</th>
                    <th class="text-end">Taxable Amt</th>
                    <th class="text-end">CGST</th>
                    <th class="text-end">SGST</th>
                    <th class="text-end">IGST</th>
                    <th class="text-end">Total GST</th>
                    <th class="text-end">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jobWorkIssueItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>

                        <td>{{ $item->job_work_issue?->job_work_issue_no ?? '-' }}</td>
                        <td>
                            {{ $item->job_work_issue?->job_work_issue_date
                                ? \Carbon\Carbon::parse($item->job_work_issue->job_work_issue_date)->format('d/m/Y')
                                : '-' }}
                        </td>
                        <td>{{ $item->job_work_issue?->vendor?->vendor_name ?? '-' }}</td>
                        <td>{{ $item->product?->product_name ?? '-' }}</td>
                        <td class="text-end">{{ number_format($item->issue_weight ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($item->taxable_amount ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($item->cgst_amount ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($item->sgst_amount ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($item->igst_amount ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($item->total_gst ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($item->grand_total ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr style="font-weight: bold; background: #f8f9fa;">
                    <td colspan="5" class="text-end">Grand Total</td>
                    <td class="text-end">{{ number_format($grandTotals['issue_weight'], 2) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['taxable_amount'], 2) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['cgst_amount'], 2) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['sgst_amount'], 2) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['igst_amount'], 2) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['total_gst'], 2) }}</td>
                    <td class="text-end">{{ number_format($grandTotals['grand_total'], 2) }}</td>
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
