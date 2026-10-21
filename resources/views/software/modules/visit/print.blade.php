<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Customer Module - Print</title>
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

<body>
    <h1>Customer Module</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Company Name</th>
                <th>Team Person Name</th>
                <th>Meet Person Name</th>
                <th>Date & Time</th>
                <th>Start Remark</th>
                <th>Start Address</th>
                <th>Start Image</th>
                <th>Customer Name</th>
                <th>Stop Remark</th>
                <th>Stop Address</th>
                <th>Stop Image</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($visits as $index => $visit)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $visit->company->name ?? '-' }}</td>
                    <td>{{ $visit->team_person->name ?? '-' }}</td>
                    <td>{{ $visit->meet_person_name ?? '-' }}</td>
                    <td>{{ $visit->visit_start_date ?? '-' }}</td>
                    <td>{{ $visit->visit_start_remark ?? '-' }}</td>
                    <td>{{ $visit->visit_start_address ?? '-' }}</td>
                    <td>
                        @if($visit->start_image)
                            <img src="{{ asset('uploads/visits/' . $visit->start_image) }}" alt="Start Image" width="50">
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $visit->customer->customer_name ?? '-' }}</td>
                    <td>{{ $visit->visit_stop_remark ?? '-' }}</td>
                    <td>{{ $visit->visit_stop_address ?? '-' }}</td>
                    <td>
                        @if($visit->stop_image)
                            <img src="{{ asset('uploads/visits/' . $visit->stop_image) }}" alt="Stop Image" width="50">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>

</html>
