<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice List - Print</title>
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

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background-color: #f8f8f8;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <h1>Invoice List</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    @php
        $showGrandTotal =
            $modules['currentGuard'] === 'admin_software' ||
            ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']));
    @endphp

    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Invoice No</th>
                @if (!$company_id)
                <th>Company Name</th>
                @endif
                <th>Invoice Date</th>
                <th>Invoice Status</th>
                <th>Customer Name</th>
                <th>Team Person Name</th>
                @if ($showGrandTotal)
                    <th>Total Amount</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($Invoice as $index => $invoice)
                @php
                $team_person_name = '';
                if ($invoice->created_type == 'admin') {
                    $team_person_name = $invoice?->admin_person?->name;
                } else {
                    $team_person_name = $invoice?->team_person?->name;
                }

                $clientCode   = optional($invoice->customer)->client_code;
                $customerName = $invoice->customer_name ?? optional($invoice->customer)->customer_name;


                if (
                    $modules['currentGuard'] === 'admin_software' ||
                    ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))
                ) {
                    $customerInfo = $clientCode . ' - ' . $customerName;
                } else {
                    $customerInfo = $clientCode ?? '-';
                }

                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $invoice->invoice_order_no }}</td>
                    @if (!$company_id)
                    <td>{{ $invoice->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_order_date)->format('d-m-Y') }}</td>
                    <td>{{ ucfirst($invoice?->sales_status?->statusname ?? 'Pending') }}</td>
                    <td>{{ $customerInfo }}</td>
                    <td>
                       {{ $team_person_name ?? '-' }}
                    </td>
                    @if ($showGrandTotal)
                        <td>{{ number_format($invoice->grand_total, 2) }}</td>
                    @endif
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
