    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Job Work Order List - Print</title>
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
        <table>
            <thead>
                <tr>
                    <th colspan="20" class="text-center">
                        <h3>
                            Job Work Order - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    @if (!$company_id)
                        <th>Company Name</th>
                    @endif
                    <th>Job Work Order No</th>
                    <th>Job Work Order Date</th>
                    <th>Work Type</th>
                    <th>Vendor</th>
                    <th>Team Person Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jobWorkOrders as $index => $order)

                    @php
                        $team_person_name = '';
                        if ($order->created_type == 'admin') {
                            $team_person_name = $order?->admin_person?->name;
                        } else {
                            $team_person_name = $order?->team_person?->name;
                        }

                        $vendorCode = optional($order->vendor)->vendor_code;
                        $vendorName = optional($order->vendor)->vendor_name;

                        $vendorInfo = $vendorCode . ' - ' . $vendorName;
                    @endphp

                    <tr>
                        <td>{{ $index + 1 }}</td>
                        @if (!$company_id)
                            <td>{{ $order->company->company_name ?? '-' }}</td>
                        @endif
                        <td>{{ $order->job_work_order_no ?? '-' }}</td>
                        <td>{{ $order->job_work_order_date ? \Carbon\Carbon::parse($order->job_work_order_date)->format('d-m-Y') : '-' }}
                        <td>
                            @php
                                $map = [
                                    'job_work' => 'Job Work',
                                    'settle_po' => 'Settle Po',
                                ];
                            @endphp
                            {{ $map[$order->work_type] ?? '-' }}
                        </td>
                        <td>{{ $vendorInfo ?? '-' }}</td>
                        <td>{{ $team_person_name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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
