<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>User List - Print</title>
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
                <th colspan="15" class="text-center">
                    <h3>User - List Printed on
                        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                <th>Name</th>
                <th>User Name</th>
                <th>Type</th>
                <th>Email</th>
                <th>Phone</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($user as $index => $person)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $person->name ?? '-' }}</td>
                    <td>{{ $person->username ?? '-' }}</td>
                    <td>{{ $person->type == 'admin_user' ? 'Admin User' : 'Office User' }}</td>
                    <td>{{ $person->email ?? '-' }}</td>
                    <td>{{ $person->phone ?? '-' }}</td>
                    <td class="text-center">{{ ucfirst($person->status) }}</td>
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
