<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Leave Type List - Print</title>
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

        .wrap-column {
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            max-width: 150px;
        }
    </style>
</head>

<body onload="printAndRedirect();">

    <table>
        <thead>
            <tr>
                <th colspan="11" class="text-center">
                    <h3>Leave Type - List Printed on
                        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Team Person Name</th>
                <th>Leave Type Name</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>HalfDay / FullDay</th>
                <th>Leave Reason</th>

                <th>Rejection Reason</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($LeaveApplication as $index => $LeaveApplications)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $LeaveApplications->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $LeaveApplications->team_person->name ?? '-' }}</td>
                    <td>{{ $LeaveApplications->leave_type->name ?? '-' }}</td>
                    <td>
                        {{ $LeaveApplications->fromdate_time ? \Carbon\Carbon::parse($LeaveApplications->fromdate_time)->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td>
                        {{ $LeaveApplications->todate_time ? \Carbon\Carbon::parse($LeaveApplications->todate_time)->format('d/m/Y H:i') : '-' }}
                    </td>
                    <td>{{ $LeaveApplications->halfday_fullday ?? '-' }}</td>
                    <td class="wrap-column">{{ $LeaveApplications->leave_reason ?? '-' }}</td>
                    <td>{{ $LeaveApplications->rejection_reason ?? '-' }}</td>
                    <td class="text-center">{{ ucfirst($LeaveApplications->status) }}</td>
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
