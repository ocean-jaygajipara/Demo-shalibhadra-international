<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Follow Up List - Print</title>
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
                <th colspan="11 " class="text-center">
                    <h3>Follow Up - List Printed on
                        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                <th>Inquiry No</th>
                <th>Followup Status</th>
                @if (!$company_id)
                    <th>Team Person Name</th>
                @endif
                <th>Customer Details</th>
                <th>Mobile No </th>
                <th>Contact Person Name</th>
                <th>Description</th>
                <th>Follow Up Date & Time</th>
                <th>Created Date & Time</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($Followup as $index => $FollowupS)
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- Serial number -->
                    <td>{{ $FollowupS->inquiry->inquiry_no ?? '-' }}</td>
                    <td>{{ ucfirst($FollowupS->marketingStatus->name) }}</td>
                    @if (!$company_id)
                        <td>{{ $FollowupS->teamPerson->name ?? '-' }}</td>
                    @endif
                    <td>
                        {{ $FollowupS->inquiry->customer_name ?? '-' }}
                    </td>
                    <td>
                        {{ $FollowupS->inquiry->mobile_no ?? '-' }}
                    </td>
                    <td>
                        {{ $FollowupS->inquiry->contact_person_name ?? '-' }}
                    </td>
                    <td>{{ $FollowupS->details ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($FollowupS->followup_date)->format('d-m-Y h:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($FollowupS->created_at)->format('d-m-Y h:i A') }}</td>
                    @if (!$company_id)
                        <td>{{ $FollowupS->company->company_name ?? '-' }}</td>
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
