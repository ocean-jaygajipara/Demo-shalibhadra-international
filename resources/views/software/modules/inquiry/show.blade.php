<div class="card-datatable table-responsive">
    <table class="dt-responsive table table-hover">
        <thead>
            <tr>
                <th>Created by</th>
                <th>Assigned To</th>
                {{-- <th>Inquiry No</th>
                <th>Inquiry Date</th>
                <th>Inquiry Status</th> --}}
                <th>Inquiry</th>
                <th>Company Name</th>
                <th>Customer Name</th>
                <th>Followup Status</th>
                <th>Source of Inquiry</th>
                <th>Requirement Details</th>
            </tr>
        </thead>
        <tbody>
            {{-- @dd($inquiry); --}}
            @if (isset($inquiry))
                <tr>
                    <td>{{ $inquiry?->createdBy?->name ?? '-' }}</td>
<td>
    @if (!empty($inquiry?->assigned_to_names))
        {{ implode(', ', $inquiry->assigned_to_names) }}
    @else
        -
    @endif
</td>

                    {{-- <td>{{ $inquiry?->inquiry_no ?? ''  }}</td>
                    <td>{{ App\Helpers\Helper::convert_date($inquiry?->inquiry_date, 'Y-m-d', 'd-m-Y') ?? ''  }}</td>
                    <td>{{ $inquiry?->inquiry_status?->name ?? '-'  }}</td> --}}
                    <td>
                        <b>No :-</b>{{ $inquiry?->inquiry_no ?? '' }}<br />
                        <b>Date
                            :-</b>{{ App\Helpers\Helper::convert_date($inquiry?->inquiry_date, 'Y-m-d', 'd-m-Y') ?? '' }}<br />
                        <b>Status :-</b>{{ $inquiry?->inquiry_status?->name ?? '-' }}
                    </td>
                    <td>{{ $inquiry?->company?->company_name ?? '-' }}</td>
                    <td>
                        Contact Person :- {{ $inquiry?->contact_person_name ?? '' }} <br />
                        Email :- {{ $inquiry?->email ?? '' }} <br />
                        Mo No. :- {{ $inquiry?->mobile_no ?? '' }} <br />
                        W. No. :- {{ $inquiry?->whatsapp_no ?? '' }} <br />
                    </td>
                    <td>{{ $inquiry?->followup_status?->name ?? '-' }}</td>
                    <td>{{ $inquiry?->source_of_inquiry?->name ?? '-' }}</td>
                    <td>{{ $inquiry?->requirement_details ?? '-' }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
