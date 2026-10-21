<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Expense Sub Cetegory List - Print</title>
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
                <th colspan="10" class="text-center">
                    <h3>Expense Sub Cetegory - List Printed on
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
                <th>Sub Category Name</th>
                <th>Team Person Name</th>
                <th>Expense Details</th>
                <th>Expense Type</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ExpenseSubCategory as $index => $ExpenseSubCategorys)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @if (!$company_id)
                        <td>{{ $ExpenseSubCategorys->company->company_name ?? '-' }}</td>
                    @endif
                    <td>{{ $ExpenseSubCategorys->expense_category->name ?? '-' }}</td>
                    <td>{{ $ExpenseSubCategorys->name }}</td>
                      <td>{{ $ExpenseSubCategorys->team_person->name ?? '-' }}</td>
                    <!-- Expense Details Column -->
                    <td>
                        @if ($ExpenseSubCategorys->expense_type === 'General')
                            Min: {{ $ExpenseSubCategorys->min_amount }}<br>
                            Max: {{ $ExpenseSubCategorys->max_amount }}
                        @elseif ($ExpenseSubCategorys->expense_type === 'KM')
                            Per KM Rate: {{ $ExpenseSubCategorys->per_km_rate }}
                        @elseif ($ExpenseSubCategorys->expense_type === 'Food')
                            Fix Amount: {{ $ExpenseSubCategorys->fix_amount }}<br>
                            From: {{ $ExpenseSubCategorys->from_time }}<br>
                            To: {{ $ExpenseSubCategorys->to_time }}
                        @else
                            -
                        @endif
                    </td>

                    <td>{{ ucfirst($ExpenseSubCategorys->expense_type) }}</td>
                    <td>{{ ucfirst($ExpenseSubCategorys->status) }}</td>
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
