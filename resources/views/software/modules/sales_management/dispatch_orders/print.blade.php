<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Dispatch - Print</title>
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
        }

        th.text-center,
        td.text-center {
            text-align: center;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <h1>Dispatch</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Dispatch No</th>
                <th>Order No</th>
                <th>Dispatch Date</th>
                <th>Customer Name</th>
                <th>Team Person Name</th>
                <th>Dispatch Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dispatch as $index => $dispatch)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $dispatch->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $dispatch->dispatch_no }}</td>
                    <td>{{ $dispatch->sales_order->sales_order_no ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d-m-Y') }}</td>
                    <td>{{ optional($dispatch->customer)->customer_name . ' - ' . $dispatch->contact_person_name }}
                    </td>
                    <td>
                        @if ($dispatch->created_type == 'admin')
                        {{ $dispatch->admin_person->name ?? '-' }}
                        @else
                        {{ $dispatch->team_person->name ?? '-' }}
                        @endif
                        <td>{{ $dispatch->dispatch_items->sum('dispatch_quantity') ?? '0' }}</td>
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
