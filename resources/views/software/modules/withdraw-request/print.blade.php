<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Withdraw Request - Print</title>
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

        h3 {
            text-align: center;
            font-size: 16px;
            margin-bottom: 20px;
            color: #7f8c8d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th.text-center,
        td.text-center {
            text-align: center;
        }
    </style>
</head>

<body onload="printAndRedirect();">

    <h1>Withdraw Request Report</h1>
    <h3>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h3>

    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Customer Name</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Request Points</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>

            </tr>
        </thead>
        <tbody>
            @foreach ($withdraw_request as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->customer->customer_name ?? '-' }}</td>
                    @if (!$company_id)
                        <td>{{ $item->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $item->request_points }}</td>
                    <td>{{ $item->amount }}</td>
                    <td>{{ ucfirst($item->status) }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->withdraw_request_date)->format('d-m-Y') }}</td>
                </tr>
            @endforeach
        </tbody>
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
