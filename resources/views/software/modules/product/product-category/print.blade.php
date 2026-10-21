<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Product Cetegory List - Print</title>
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

<body>
    <table>
        <thead>
            <tr>
                <th colspan="4" class="text-center">
                    <h3>Product Cetegory - List Printed on
                        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Category Name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productcat as $index => $productcats)
                <tr>
                    <td>{{ $index + 1 }}</td>    @if (!$company_id)
                    <td>{{ $productcats->company->company_name ?? '-' }}</td>    @endif
                    <td>{{ $productcats->name }}</td>
                    <td>{{ ucfirst($productcats->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>
