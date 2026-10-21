<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>PurchaseOrder - Print</title>
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
    <h1>PurchaseOrder</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                @if (!$company_id)
                <th>Company Name</th>
                @endif
                <th>PO NO</th>
                <th>PO Date</th>
                <th>PO Status</th>
                <th>Vendor Name</th>
                <th>Team Person Name</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder as $index => $purchase)
                @php

                $vendorCode = optional($purchase->vendor)->vendor_code;
                $vendorName = $purchase->vendor_name ?? optional($purchase->vendor)->vendor_name;

                if (
                    $modules['currentGuard'] === 'admin_software' ||
                    ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))
                ) {
                    $vendorInfo = $vendorCode . ' - ' . $vendorName;
                } else {
                    $vendorInfo = $vendorCode ?? '-';
                }
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                    <td>{{ $purchase->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $purchase->purchase_order_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($purchase->purchase_order_date)->format('d-m-Y') }}</td>
                    <td>{{ $purchase->sales_status->statusname ?? '-' }}</td>
                    <td>{{ $vendorInfo ?? '-' }}</td>
                     <td>
                        @if ($purchase->created_type == 'admin')
                        {{ $purchase->admin_person->name ?? '-' }}
                        @else
                        {{ $purchase->team_person->name ?? '-' }}
                        @endif
                    </td>
                    <td>{{ number_format($purchase->grand_total, 2) }}</td>
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
