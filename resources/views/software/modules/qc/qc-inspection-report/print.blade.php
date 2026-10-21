    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>QC Inspection Report List - Print</title>
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
                            QC Inspection Report - List Printed on
                            {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                        </h3>
                    </th>
                </tr>
                <tr>
                    <th>Sr No</th>
                    @if (!$company_id)
                        <th>Company Name</th>
                    @endif
                    <th>QC Inspection No</th>
                    <th>QC Inspection Date</th>
                    <th>Product Name</th>
                    <th>Vendor Name</th>
                    <th>Received Weight</th>
                    <th>Ok Weight</th>
                    <th>Ok Qty</th>
                    <th>Team Person Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($QcInspections as $index => $inspections)

                    @php
                        $team_person_name = '';
                        if ($inspections->created_type == 'admin') {
                            $team_person_name = $inspections?->admin_person?->name;
                        } else {
                            $team_person_name = $inspections?->team_person?->name;
                        }

                        $code = $inspections->product_code ? "({$inspections->product_code})" : '';
                        $product_name = ($code ?? '-') . ' ' . $inspections->product_name;

                        $vendorCode = optional($inspections->vendor)->vendor_code;
                        $vendorName = optional($inspections->vendor)->vendor_name;

                        $vendorInfo = $vendorCode . ' - ' . $vendorName;
                    @endphp

                    <tr>
                        <td>{{ $index + 1 }}</td>
                        @if (!$company_id)
                            <td>{{ $inspections->company->company_name ?? '-' }}</td>
                        @endif
                        <td>{{ $inspections->qc_inspection_no ?? '-' }}</td>
                        <td>{{ $inspections->qc_inspection_date ? \Carbon\Carbon::parse($inspections->qc_inspection_date)->format('d-m-Y') : '-' }}
                        <td>{{ $product_name ?? '-' }}
                        </td>
                        <td>{{ $vendorInfo ?? '-' }}</td>
                        <td>{{ $inspections->received_weight ?? '-' }}</td>
                        <td>{{ $inspections->ok_weight ?? '-' }}</td>
                        <td>{{ $inspections->ok_qty ?? '-' }}</td>
                        <td>{{ $team_person_name ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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
