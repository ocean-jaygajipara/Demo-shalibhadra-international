<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Inquiry List - Print</title>
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
                <th colspan="13 " class="text-center">
                    <h3>Inquiry - List Printed on
                        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }}
                    </h3>
                </th>
            </tr>
            <tr>
                <th>Sr No</th>
                <th>Inquiry No</th>
                <th>Inquiry Date</th>
                <th>Inquiry Status</th>
                <th>Customer Name</th>
                <th>Contact Person Name</th>
                <th>Followup Status</th>
                <th>Mobile No.</th>
                <th>Source of Inquiry</th>
                <th>Requirement Details</th>
                <th>Created by</th>
                <th>Assigned to</th>
                 @if (!$company_id)
                    <th>Company Name</th>
                @endif
            </tr>
        </thead>
        {{-- <tbody>
            @foreach ($Inquiry as $index => $InquiryS)
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- Serial number -->
                    <td>{{ $InquiryS->inquiry_no ?? '-' }}</td>
                    <td>{{ ucfirst($InquiryS->status) }}</td>
                    <td>{{ $InquiryS->team_person_name ?? '-' }}</td>
                    <td>
                        {{ $InquiryS->customer_name ?? '-' }}<br>
                        {{ $InquiryS->customer_mobile ?? '' }}
                    </td>
                    <td>{{ $InquiryS->description ?? '-' }}</td>
                    <td>{{ $InquiryS->description ?? '-' }}</td>
                    <td>{{ $InquiryS->description ?? '-' }}</td>

                    <td>{{ $InquiryS->response ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($InquiryS->Inquiry_date)->format('d-m-Y h:i A') }}</td>
                    <td>{{ \Carbon\Carbon::parse($InquiryS->created_at)->format('d-m-Y h:i A') }}</td>
                    <td>{{ $InquiryS->company->company_name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody> --}}
        <tbody>
            @foreach ($Inquiry as $index => $InquiryS)

                @php
                    $assignedNames = $InquiryS->assigned_to_name;

                    $assignedToText = is_array($assignedNames)
                        ? implode(', ', $assignedNames)
                        : ($InquiryS->assigned_to_name ?? '-');
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- Sr No -->
                    <td>{{ $InquiryS->inquiry_no ?? '-' }}</td>
                    <td>{{ $InquiryS->inquiry_date ? \Carbon\Carbon::parse($InquiryS->inquiry_date)->format('d-m-Y') : '-' }}</td>
                    <td>{{ ucfirst(optional($InquiryS->inquiry_status)->name ?? ($InquiryS->status ?? '-')) }}</td>
                    <td>{{ $InquiryS->customer_name ?? '-' }}</td>
                    <td>{{ $InquiryS->contact_person_name ?? '-' }}</td>
                    <td>{{ optional($InquiryS->followup_status)->name ?? '-' }}</td>
                    <td>{{ $InquiryS->customer_mobile ?? ($InquiryS->mobile_no ?? '-') }}</td>
                    <td>{{ optional($InquiryS->source_of_inquiry)->name ?? '-' }}</td>
                    <td>{{ $InquiryS->requirement_details ?? ($InquiryS->description ?? '-') }}</td>
                    <td>{{ $InquiryS->created_by_name ?? '-' }}</td>
                    <td>{{ $assignedToText ?? '-' }}</td>
                     @if (!$company_id)
                        <td>{{ $InquiryS->company->company_name ?? '-' }}</td>
                    @endif
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
