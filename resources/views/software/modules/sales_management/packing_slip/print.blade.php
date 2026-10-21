<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Packing Slip List - Print</title>
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
    <h1>Packing Slip List</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                <th>Company Name</th>
                @endif
                <th>Packing slip No</th>
                <th>Sales Order No</th>
                <th>Packing Slip Date</th>
                <th>Packing Slip Status</th>
                <th>Customer Name</th>
                <th>Packing Slip Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($packingSlip as $index => $order)

            @php
       $packing_slip_qty = 0;
        if ($order->packing_slip_item && $order->packing_slip_item->count() > 0) {
            $totalQtyPc = $order->packing_slip_item->sum('qty_pc');
            $packing_slip_qty = number_format($totalQtyPc, 2);
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
                    @if (!$company_id)
                    <td>{{ $order->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $order->packing_slip_no }}</td>
                    <td>{{ $order->sales_order_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->packing_slip_date)->format('d-m-Y') }}</td>
                    <td>{{ ucfirst($order?->sales_status?->statusname ?? 'Pending') }}</td>
                    <td>{{ $customerInfo }}</td>
                    <td>
                       {{ $packing_slip_qty ?? '-' }}
                    </td>
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
