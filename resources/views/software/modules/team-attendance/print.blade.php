<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Team Attendance List - Print</title>
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


    <table>
        <thead>
            <tr>
                    <th colspan="10" class="text-center">
                        <h3>Team Attendance - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Mobile No</th>
                <th>Email</th>
                <th>Punch In time</th>
                <th>Punch In Address</th>
                <th>Punch Out time</th>
                <th>Punch Out Address</th>
                <th>Total Working Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teamattandace as $index => $Teamattandace)
                <tr>
                    <td>{{ $index + 1 }}</td>

                    <td> {{ $Teamattandace->team_person->name ?? '-' }}</td>
                    <td> {{ $Teamattandace->team_person->mobile_no ?? '-' }}</td>
                    <td>{{ $Teamattandace->team_person->email ?? '-' }}</td>
                    <td>{{ $Teamattandace->punch_in_time ? \Carbon\Carbon::parse($Teamattandace->punch_in_time)->format('d-m-Y H:i') : '-' }}</td>
                    <td>{{ $Teamattandace->punch_in_address ?? '-' }}</td>
                    <td>{{ $Teamattandace->punch_out_time ? \Carbon\Carbon::parse($Teamattandace->punch_out_time)->format('d-m-Y H:i') : '-' }}</td>
                    <td>{{ $Teamattandace->punch_out_address ?? '-' }}</td>
                    <td>{{ $Teamattandace->working_time ?? '-' }}</td>
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
