<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Expense Module - Print</title>
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
    <h1>Expense Module</h1>
    <h2>Printed on {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Expense Sub Category Name</th>
                <th>Expense Category Name</th>
                <th>Team Person Name</th>
                <th>Expense Date</th>
                <th>Requested Amount</th>
                <th>Passed Amount</th>
                   @if (!$company_id)
                    <th>Company Name</th>
                @endif
                <th width='10%' class="no-sort text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($expenses as $index => $expense)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $expense->expense_sub_category->name ?? '-' }}</td>
                    <td>{{ $expense->expense_category->name ?? '-' }}</td>
                    <td>
                        {{ $expense->team_person->name ?? '-' }}<br>
                    
                    </td>
                    <td>{{ $expense->date ?? '-' }}</td>
                    <td>{{ $expense->req_amount ?? '-' }}</td>
                    <td>{{ $expense->pass_amount ?? '-' }}</td>
                    @if (!$company_id)
                        <td>{{ $expense->company->company_name ?? '-' }}</td>
                    @endif
                    <td class="text-center">{{ $expense->status ?? '-' }}</td>
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
