    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Job Work Issue List - Print</title>
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
                    <th colspan="20" class="text-center">
                        <h3>
                            Job Work Issue - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    @if (!$company_id)
                        <th>Company Name</th>
                    @endif
                    <th>Job Work Issue No</th>
                    <th>Job Work Issue Date</th>
                    <th>Work Type</th>
                    <th>Vendor</th>
                    <th>Grand Total</th>
                    <th>Team Person Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($jobWorkIssues as $index => $issue)

                    @php
                        $team_person_name = '';
                        if ($issue->created_type == 'admin') {
                            $team_person_name = $issue?->admin_person?->name;
                        } else {
                            $team_person_name = $issue?->team_person?->name;
                        }

                        $vendorCode = optional($issue->vendor)->vendor_code;
                        $vendorName = optional($issue->vendor)->vendor_name;

                        $vendorInfo = $vendorCode . ' - ' . $vendorName;
                    @endphp

                    <tr>
                        <td>{{ $index + 1 }}</td>
                        @if (!$company_id)
                            <td>{{ $issue->company->company_name ?? '-' }}</td>
                        @endif
                        <td>{{ $issue->job_work_issue_no ?? '-' }}</td>
                        <td>{{ $issue->job_work_issue_date ? \Carbon\Carbon::parse($issue->job_work_issue_date)->format('d-m-Y') : '-' }}
                        </td>
                        <td>{{ $issue->job_work_type->name ?? '-' }}</td>
                        <td>{{ $vendorInfo ?? '-' }}</td>
                        <td>{{ number_format($issue->grand_total, 2) }}</td>
                        <td>{{ $team_person_name ?? '-' }}</td>
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
