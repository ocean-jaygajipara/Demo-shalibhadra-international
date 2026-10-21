    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Job Work Receive List - Print</title>
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
                            Job Work Receive - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    @if (!$company_id)
                        <th>Company Name</th>
                    @endif
                    <th>Job Work Receive NO</th>
                    <th>Job Work Receive Date</th>
                    <th>Process Receive</th>
                    <th>Vendor Name</th>
                    <th>Team Person Name</th>
                    <th>Total Pending</th>
                    <th>Current Pending</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($JobWorkReceives as $index => $receive)

                    @php
                        $team_person_name = '';
                        if ($receive->created_type == 'admin') {
                            $team_person_name = $receive?->admin_person?->name;
                        } else {
                            $team_person_name = $receive?->team_person?->name;
                        }

                        $vendorCode = optional($receive->vendor)->vendor_code;
                        $vendorName = optional($receive->vendor)->vendor_name;

                        $vendorInfo = $vendorCode . ' - ' . $vendorName;
                    @endphp


                    <tr>
                        <td>{{ $index + 1 }}</td>
                        @if (!$company_id)
                            <td>{{ $receive->company->company_name ?? '-' }}</td>
                        @endif
                        <td>{{ $receive->job_work_receive_no ?? '-' }}</td>
                        <td>
                            {{ $receive->job_work_receive_date
                                ? \Carbon\Carbon::parse($receive->job_work_receive_date)->format('d-m-Y')
                                : '-' }}
                        </td>
                        <td>{{ optional($receive->process_received)->name ?? '-' }}</td>
                        <td>{{ $vendorInfo ?? '-' }}</td>
                        <td>{{ $team_person_name ?? '-' }}</td>
                        <td>{{ $receive->master_total_pending ?? '-' }}</td>
                        <td>{{ $receive->master_total_current_pending ?? '-' }}</td>
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
