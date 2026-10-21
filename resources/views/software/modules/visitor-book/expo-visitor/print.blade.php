@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;
@endphp
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
  <table>
        <thead>
            <tr>
                <th colspan="10">
                    <h3>Expo Visitor List - Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h3>
                </th>
            </tr>


            <tr>
                <th>Sr No</th>
                @if (!$company_id)
                    <th>Company By</th>
                @endif
                <th>Expo Name</th>
                <th>Company Name</th>
                <th>Person Name</th>
                <th>Mobile No</th>
                <th>Email</th>
                <th>Website</th>
                <th>Requirement</th>
            </tr>
        </thead>
        <tbody>

             @foreach ($visitors as $index => $visitor)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $visitor->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $visitor->expo->name ?? '-' }}</td>
                    <td>{{ $visitor->company_name ?? '-' }}</td>
                    <td>{{ $visitor->person_name ?? '-' }}</td>
                    <td>{{ $visitor->mobile_no ?? '-' }}</td>
                    <td>{{ $visitor->email ?? '-' }}</td>
                    <td>{{ $visitor->website ?? '-' }}</td>
                    <td>{{ $visitor->requirement ?? '-' }}</td>
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
