    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>City List - Print</title>
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
                    <th colspan="5" class="text-center">
                        <h3>City - List Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    <th>Country Name</th>
                    <th>State Name</th>
                    <th>City Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($city as $index => $citys)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $citys->country->name ?? '-' }}</td>
                        <td>{{ $citys->state->name ?? '-' }}</td>
                        <td>{{ $citys->name ?? '-' }}</td>
                        <td>{{ ucfirst($citys->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <script>
            function printAndRedirect() {
                window.print();

                window.onafterprint = function () {
                    setTimeout(function () {
                        window.location.href = "{{ route($modules['route'] . '.index') }}";
                    }, 1);
                };
            }
        </script>
    </body>

    </html>
