<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Ledger Print - {{ $customer?->customer_name ?? 'All Customers' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 30px;
            font-size: 14px;
            background-color: #f8f9fa;
        }

        .header-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .customer-box {
            background-color: #ffffff;
            /* border-left: 5px solid #0d6efd; */
            padding: 15px 20px;
            margin-bottom: 25px;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background-color: #e9ecef;
            text-align: center;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .footer-total {
            background-color: #d1ecf1;
        }

        .footer-balance {
            background-color: #fff3cd;
        }
    </style>
</head>

<body onload="printAndRedirect();">

    <div class="header-title">
        <h3>Account Ledger Report</h3>
        <p class="text-muted">For {{ $customer?->customer_name ?? 'All Customers' }}</p>
    </div>

    @if ($customer)
        <div class="customer-box mx-auto" style="max-width: 800px;">
            <h5 class="mb-3 text-center"><strong>Customer Details</strong></h5>
            <div class="row mb-2">
                <div class="col-12">
                    <div class="d-flex">
                        <div class="w-50 ps-4">
                            <strong>Name:</strong> {{ $customer->customer_name }}
                        </div>
                        <div class="w-50 text-end" style="padding-right: 3rem;">
                            <strong>Customer Type:</strong> {{ $customer->customerType->name ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-2">
                    <div class="d-flex">
                        <div class="w-50 ps-4">
                            <strong>Phone:</strong> {{ $customer->mobile_number ?? '-' }}
                        </div>
                        <div class="w-50 text-end" style="padding-right: 3rem;">
                            <strong>Address:</strong> {{ $customer->address ?? '-' }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endif





    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Sr No.</th>
                <th>Date</th>
                <th>Description</th>
                <th class="text-end">Debit (₹)</th>
                <th class="text-end">Credit (₹)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledger as $index => $entry)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ $entry['description'] }}</td>
                    <td class="text-end">{{ number_format($entry['debit'], 2) }}</td>
                    <td class="text-end">{{ number_format($entry['credit'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No ledger entries found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="table-info fw-bold">
                <td colspan="3" class="text-end">Total</td>
                <td class="text-end">{{ number_format($total_debit, 2) }}</td>
                <td class="text-end">{{ number_format($total_credit, 2) }}</td>
            </tr>
            <tr class="table-warning fw-bold">
                <td colspan="4" class="text-end">Net Closing Balance</td>
                <td class="text-end">{{ number_format($runningBalance, 2) }}</td>
            </tr>
        </tfoot>

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
