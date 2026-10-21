<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - Print</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 5mm;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 5px;
            color: #333;
        }

        h3 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            vertical-align: middle;
            text-align: center;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <table>
        <thead>
            <tr>
                <th colspan="100%">
                    <h3>Payment Receipt List - Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Receipt No</th>
                <th>Date</th>
                <th>Customer Type</th>
                <th>Customer Name</th>
                <th>Receipt Type</th>
                <th>Quotation No</th>
                <th>Sales Order No</th>
                <th>Team Person IDs</th>
                <th>Payment By</th>
                <th>Cheque No</th>
                <th>UPI No</th>
                <th>Payment Reason</th>
                <th>Payment Mode</th>
                <th>Amount</th>
                <th>Remark</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($receipt_list as $index => $receipt)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $receipt->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $receipt->receipt_no ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($receipt->date)->format('d-m-Y') }}</td>
                    <td>{{ $receipt->customerType->name ?? '-' }}</td>
                    <td>{{ $receipt->customer->customer_name ?? '-' }}</td>
                    <td>{{ ucfirst($receipt->receipt_type ?? '-') }}</td>
                    <td>{{ $receipt->quotation->quotation_no ?? '-' }}</td>
                    <td>{{ $receipt->salesOrder->sales_order_no ?? '-' }}</td>
                    <td>{{ $receipt->team_person_ids ?? '-' }}</td>
                    <td>{{ $receipt->payment_by ?? '-' }}</td>
                    <td>{{ $receipt->cheque_no ?? '-' }}</td>
                    <td>{{ $receipt->upi_no ?? '-' }}</td>
                    <td>{{ $receipt->payment_reason ?? '-' }}</td>
                    <td>{{ $receipt->payment_mode ?? '-' }}</td>
                    <td>{{ $receipt->amount ?? '0.00' }}</td>
                    <td>{{ $receipt->remark ?? '-' }}</td>
                    <td>{{ ucfirst($receipt->status ?? '-') }}</td>
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
