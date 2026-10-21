<div class="card mt-3">
    <div class="card-body">
        <h5>{{ $customer->company_name ?? '-' }}</h5>
        <p>{{ $customer->mobile_no ?? '-' }}</p>

        <strong>Receivables: {{ $customer->contact_person ?? '-' }}</strong><br>
        <small>All Bills As On <strong>{{ $asOnDate ?? '-' }}</strong></small>

        <div class="table-responsive mt-3">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Phone number</th>
                        <th>Customer Type</th>
                        <th class="text-end">Closing Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $customer->company_name ?? '-' }}</td>
                        <td>{{ $customer->mobile_no ?? '-' }}</td>
                        <td>{{ optional($customer->customerType)->name ?? '-' }}</td>
                        <td class="text-end">{{ number_format($closingBalance ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
