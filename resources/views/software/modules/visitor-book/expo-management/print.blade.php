<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Product List - Print</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 5mm;
            }

            .no-print {
                display: none !important;
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
            vertical-align: middle;
            text-align: center;
        }

        img.product-img {
            max-width: 60px;
            max-height: 60px;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <table>
        <thead>
            <tr>
                <th colspan="10">
                    <h3>Expo List - Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Expo Category Name</th>
                <th>Expo Title</th>
                <th>Expo URL</th>
                <th>Location</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($expo_list as $index => $expo)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $expo->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $expo->expo_category->name ?? '-' }}</td>
                    <td>{{ $expo->name ?? '-' }}</td>
                    <td>{{ $expo->expo_url ?? '-' }}</td>
                    <td>{{ $expo->location ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($expo->start_date)->format('d-m-Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($expo->end_date)->format('d-m-Y') }}</td>
                    <td>{{ ucfirst($expo->status) }}</td>
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
