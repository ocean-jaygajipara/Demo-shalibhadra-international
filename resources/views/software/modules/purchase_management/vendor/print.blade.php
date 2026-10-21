<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Vendor List - Print</title>
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
                <th colspan="13" class="text-center">
                    <h3>Vendor - List Printed on
                        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th>Vendor Name</th>
                <th>Gst Number</th>
                <th>Mobile Number</th>
                <th>Whatsapp Number</th>
                <th>Email Id</th>
                <th>Vendor Code</th>
                <th>Country</th>
                <th>State</th>
                <th>City</th>
                <th>Area</th>
                {{-- <th>Team Person Name</th> --}}
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vendors as $index => $vendor)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $vendor->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ ($vendor->vendor_name ?? '-') }}</td>
                    <td>{{ ($vendor->gst_number ?? '-') }}</td>
                    <td>{{ ($vendor->mobile_number ?? '-') }}</td>
                    <td>{{ ($vendor->whatsapp_number ?? '-') }}</td>
                    <td>{{ ($vendor->email_id ?? '-') }}</td>
                    <td>{{ $vendor->vendor_code ?? '-' }}</td>
                    <td>{{ optional($vendor->country)->name ?? '-' }}</td>
                    <td>{{ optional($vendor->state)->name ?? '-' }}</td>
                    <td>{{ optional($vendor->city)->name ?? '-' }}</td>
                    <td>{{ optional($vendor->area)->area_name ?? '-' }}</td>
                    {{-- <td>
                        @if ($vendor->created_type == 'admin')
                        {{ $vendor->admin_person->name ?? '-' }}
                        @else
                        {{ $vendor->team_person->name ?? '-' }}
                        @endif
                    </td> --}}
                    <td class="text-center">{{ ucfirst($vendor->status) }}</td>
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
