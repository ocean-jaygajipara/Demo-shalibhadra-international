<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Sales Order List - Print</title>
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
    <h1>Sales Order List</h1>
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
                <th>Sales Order No</th>
                @if (!$company_id)
                <th>Company Name</th>
                @endif
                <th>Quotation No</th>
                <th>Sales Order Date</th>
                <th>Sales Order Status</th>
                <th><b>Customer Name</b></th>
                <th>Team Person Name</th>
                @if ($showGrandTotal)
                    <th>Total Amount</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($SalesOrder as $index => $order)
                @php
                $team_person_name = '';
                if ($order->created_type == 'admin') {
                    $team_person_name = $order?->admin_person?->name;
                } else {
                    $team_person_name = $order?->team_person?->name;
                }

                $clientCode   = optional($order->customer)->client_code;
                $customerName = $order->customer_name ?? optional($order->customer)->customer_name;


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
                    <td>{{ $order->sales_order_no }}</td>
                    @if (!$company_id)
                    <td>{{ $order->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $order->quotation->quotation_no ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->sales_order_date)->format('d-m-Y') }}</td>
                    <td>{{ ucfirst($order?->sales_status?->statusname ?? 'Pending') }}</td>
                    <td>{{ $customerInfo }}</td>
                    <td>
                       {{ $team_person_name ?? '-' }}
                    </td>
                    @if ($showGrandTotal)
                        <td>{{ number_format($order->grand_total, 2) }}</td>
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
