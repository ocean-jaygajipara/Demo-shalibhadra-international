<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quotation - Print</title>
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
    <h1>Quotation</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    @php
        $showGrandTotal =
            $modules['currentGuard'] === 'admin_software' ||
            ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']));
    @endphp

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Quotation No</th>
                @if (!$company_id)
                <th>Company Name</th>
                @endif
                <th>Quotation Date</th>
                <th>Quotation Status</th>
                <th>Customer Name</th>
                <th>Team Person Name</th>
                @if ($showGrandTotal)
                    <th>Total Amount</th>
                @endif
                <th width='10%' class="no-sort text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotations as $index => $quotation)
                @php
                $reviseOrRepeat = '';
                if (!empty($quotation->quotation_revise)) {
                    $reviseOrRepeat .= ' (Revise: ' . $quotation->parent_quotation_id . ')';
                }
                if (!empty($quotation->quotation_repeat)) {
                    $reviseOrRepeat .= ' (Repeat: ' . $quotation->parent_quotation_id . ')';
                }

                $team_person_name = '';
                if ($quotation->created_type == 'admin') {
                    $team_person_name = $quotation?->admin_person?->name;
                } else {
                    $team_person_name = $quotation?->team_person?->name;
                }

                $clientCode   = optional($quotation->customer)->client_code;
                $customerName = $quotation->customer_name ?? optional($quotation->customer)->customer_name;


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
                    <td>{{ $quotation->quotation_no. $reviseOrRepeat }}</td>
                    @if (!$company_id)
                    <td>{{ $quotation->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ \Carbon\Carbon::parse($quotation->quotation_date)->format('d-m-Y') }}</td>
                    <td>{{ $quotation->sales_status->statusname ?? '-' }}</td>
                    <td>{{ $customerInfo }}</td>
                    <td>
                        {{ $team_person_name ?? '-' }}
                    </td>
                     @if ($showGrandTotal)
                        <td>{{ number_format($quotation->grand_total, 2) }}</td>
                    @endif
                    <td class="text-center">{{ ucfirst($quotation->status) }}</td>
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
