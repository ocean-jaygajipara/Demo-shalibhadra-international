<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>INSPECTION REPORT {{ $QcInspection->job_work_issue_no ?? '-' }} - Print</title>
    <style>
  @page {
            size: A4;
            /* margin-top: 20mm;
            margin-bottom: 5mm;
            margin-left: 0mm;
            margin-right: 0mm; */
            margin:10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .report-container {
            background: #fff;
            padding: 15px 25px;
            max-width: 100%;
            margin: 0 auto;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            position: relative;
            min-height: 60px;
        }

        .header-left h2 {
            margin: 0;
            font-size: 24px;
            color: #e91e63;
            font-weight: bold;
        }

        .header-left h2 span {
            font-weight: normal;
        }

        .header-center {
            position: absolute;
            left: 60%;
            top: 0;
            transform: translateX(-50%);
            text-align: center;
            z-index: 0;
        }

        .header-center .pink-bar {
            color: #000;
            text-align: center;
            padding: 4px 20px;
            font-size: 16px;
            font-weight: bold;
            white-space: nowrap;
            margin-bottom: 3px;
        }

        .header-center .address-info {
            font-size: 13px;
            line-height: 1.4;
            font-weight: 200;
            white-space: nowrap;
        }



        hr {
            border: 1px solid #000;
            margin: 15px 0 8px 0;
        }

        .footer-line {
            text-align: right;
            font-size: 11px;
            margin-bottom: 10px;
        }

        /* Form Details */
        .form-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .form-field {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .form-field label {
            font-weight: bold;
            min-width: 80px;
        }

        .underline {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 15px;
            line-height: 15px;
        }

        /* Sr No Section */
        .sr-no-section {
            text-align: right;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .sr-no-section span {
            font-weight: bold;
            margin-right: 5px;
        }

        .sr-no-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 15px;
            min-width: 60px;
            text-align: center;
        }

        /* Table */
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
            margin-top: 10px;
        }

        td, th {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th {
            background: #fff;
            font-weight: bold;
            font-size: 10px;
        }

        .text-left { text-align: left !important; }
        .param-cell { text-align: left; font-weight: normal; }
        .remark-cell { min-width: 60px; }
        .sample-header { font-size: 9px; padding: 2px; }
    </style>
</head>

<body onload="printAndRedirect();">

    <div class="report-container" style="border: 1px solid #000;">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div
                    style="width: 20%; text-align:center; vertical-align: middle; border-left: none; border-top: none; border-bottom: none;">
                    <img src="{{ $QcInspection->company->company_logo_url ?? asset('software/img/logo.png') }}"
                        style="height:40px;">
                </div>
            </div>
            <div class="header-center" >
                <div class="pink-bar">INSPECTION REPORT</div>
                <div class="address-info" style="margin-top: 25px !important;">
                    {!! nl2br(strip_tags(str_replace('</p>', "</p>\n", optional($QcInspection->company)->address ?? '-'))) !!}
                </div>
            </div>

        </div>

        <hr style="border: 1px solid #000; margin: 20px 0 10px 0;">

        <!-- Form Details -->
        <div class="form-details">
            <div class="form-field">
                <label>Vendor Name :</label>
                <div class="underline">{{ $QcInspection?->vendor?->vendor_name ?? '' }}</div>
            </div>
            <div class="form-field">
                <label>Item Code:</label>
                <div class="underline">{{ $QcInspection?->product_code ?? '' }}</div>
            </div>
            <div class="form-field" style="grid-column: span 2;">
                <label>Item Name:</label>
                <div class="underline" style="width: 250px;">{{ $QcInspection?->product_name ?? '' }}</div>
                <label style="margin-left: 40px;">Weight:</label>
                <div class="underline" style="width: 70px;">{{ $QcInspection?->received_weight ?? '' }}</div>
                <label style="margin-left: 40px;">Ok Weight:</label>
                <div class="underline" style="width: 70px;">{{ $QcInspection?->ok_weight ?? '' }}</div>
            </div>
        </div>

        <!-- Sr. No Section -->
        <div class="sr-no-section" style="margin-top:17px;">
            <span>QC Inspection No.</span>
            <span class="sr-no-box">{{ $QcInspection->qc_inspection_no ?? '-' }}</span>
            <div style="display: inline-block; margin-left: 10px;">
                <label style="font-weight: bold;">QC Inspection Date:</label>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 100px; margin-left: 5px;">
                    {{ $QcInspection->qc_inspection_date ? \Carbon\Carbon::parse($QcInspection->qc_inspection_date)->format('d-m-Y') : '-' }}
                </span>
            </div>
        </div>

        <!-- Inspection Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">Sr.<br>No.</th>
                    <th style="width: 100px;">Dimension</th>
                    <th style="width: 70px;">Size</th>
                    <th style="width: 70px;">LTL</th>
                    <th style="width: 70px;">UTL</th>
                    <th style="width: 90px;">Instrument</th>
                    @for($i = 1; $i <= 10; $i++)
                        <th class="sample-header">Size {{ $i }}</th>
                    @endfor
                    <th class="remark-cell">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @php $serial_no = 1; @endphp
                @forelse($QcInspection->material_details ?? [] as $material)
                    <tr>
                        <td>{{ $serial_no++ }}</td>
                        <td class="param-cell">{{ $material->dimension ?? '-' }}</td>
                        <td>{{ $material->size ?? '-' }}</td>
                        <td>{{ $material->ltl ?? '-' }}</td>
                        <td>{{ $material->utl ?? '-' }}</td>
                        <td>{{ $material->instrument ?? '-' }}</td>
                        @for($i = 1; $i <= 10; $i++)
                            <td>{{ $material->{"size$i"} ?? '' }}</td>
                        @endfor
                        <td>{{ $material->remarks ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="text-center">No items found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

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
