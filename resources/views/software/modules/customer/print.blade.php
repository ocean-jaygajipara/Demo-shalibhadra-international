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

<body onload="printAndRedirect();">

    <h1>Customer Module</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Customer Name</th>
                <th>Contact Person Name</th>
                <th>Mobile Number</th>
                <th>Whatsapp Number</th>
                <th>Email</th>
                <th>Client Code</th>
                <th>Country</th>
                <th>State</th>
                <th>City</th>
                <th>Area</th>
                <th width='10%' class="no-sort text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($Customer as $index => $customerS)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $customerS->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $customerS->customer_name ?? '-' }}</td>
                    <td> {{ $customerS->contact_person_name ?? '-' }}<br></td>
                    <td> {{ $customerS->mobile_number ?? '-' }}<br></td>
                    <td> {{ $customerS->whatsapp_number ?? '-' }}<br></td>
                    <td>{{ $customerS->email ?? '-' }}</td>
                    <td>{{ $customerS->client_code ?? '-' }}</td>
                    <td>{{ $customerS->country->name ?? '-' }}</td>
                    <td>{{ $customerS->state->name ?? '-' }}</td>
                    <td>{{ $customerS->city->name ?? '-' }}</td>
                    <td>{{ $customerS->area->area_name ?? '-' }}</td>
                    <td>{{ $customerS->status }}</td>

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
