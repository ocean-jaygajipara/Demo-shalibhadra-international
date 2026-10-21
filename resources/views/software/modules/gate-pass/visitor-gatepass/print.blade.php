<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Visitor Gatepass List - Print</title>
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
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            color: #2c3e50;
        }

        h2 {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
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
            text-align: left;
        }

        th.text-center,
        td.text-center {
            text-align: center;
        }
    </style>
</head>

<body onload="printAndRedirect();">

    <h1>Visitor Gatepass List</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Visitor Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Visitor Type</th>
                <th>Purpose of Visit</th>
                <th>Meeting With</th>
                <th>Department</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($VisitorGatepass as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $item->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $item->visitor_name }}</td>
                    <td>{{ $item->contact_number }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ $item->visitorType?->name }}</td>
                    <td>{{ $item->purposeOfVisit?->name }}</td>
                    <td>{{ $item->teamPerson?->name }}</td>
                    <td>{{ $item->department?->name }}</td>
                    <td class="text-center">{{ ucfirst($item->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>


    <script>
        function printAndRedirect() {
            window.print();
            window.onafterprint = function() {
                setTimeout(() => {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 500);
            };
        }
    </script>
</body>

</html>
