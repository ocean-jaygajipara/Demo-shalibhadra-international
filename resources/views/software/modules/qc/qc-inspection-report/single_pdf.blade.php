@php
    // --- PREPARE VARIABLES ---
    $headerLogo = null;

    if (!empty($QcInspection->company) && !empty($QcInspection->company->order_header_logo_url)) {
        $headerLogo = public_path($QcInspection->company->order_header_logo_url);
    } elseif (!empty($QcInspection->company) && !empty($QcInspection->company->company_logo_url)) {
        $headerLogo = public_path($QcInspection->company->company_logo_url);
    } else {
        $headerLogo = public_path('software/img/logo.png');
    }
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>INSPECTION REPORT</title>
    <style>

        @page {
            size: A4;
            margin: 8mm 6mm 8mm 6mm;
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
<body>

<div class="report-container">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div style="width:100%; height:40px; display:flex; justify-content:center; align-items:center; overflow:hidden;">
                <?php if(isset($sidebarheaderLogo) && !empty($sidebarheaderLogo)) { ?>
                    <img src="<?php echo $sidebarheaderLogo;?>" class="main-logo" style="width: 30%;"/>
                <?php } else { ?>
                    <img src="<?php echo $sidebarMainLogo;?>" class="main-logo" style="width: 30%;"/>
                <?php } ?>
            </div>
        </div>
        <div class="header-center">
            <div class="pink-bar">INSPECTION REPORT</div>
            <div class="address-info">
             {!! nl2br(e(preg_replace(
                ['/^\s*<p[^>]*>/i', '/<\/p\s*>/i', '/<p[^>]*>/i'],
                ['', "\n", ''],
                strip_tags($QcInspection->company->address ?? '', '<p>')
            ))) !!}

            </div>
        </div>
        <div class="header-right"></div>
    </div>

    <hr>

    <!-- Form Details -->
    <table style="width: 100%; font-size: 12px; border-collapse: collapse; margin-bottom: 10px; border: none;">
        <tr style="border: none;">
            <td style="width: 45%; padding: 2px; border: none;">
                <strong>Vendor Name :</strong>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 200px; padding-bottom: 2px;">
                    {{ $QcInspection->vendor->vendor_name ?? '' }}
                </span>
            </td>
            <td style="width: 55%; padding: 2px; border: none;">
                <strong>Item Code :</strong>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 200px; padding-bottom: 2px;">
                    {{ $QcInspection->product_code ?? '' }}
                </span>
            </td>
        </tr>

        <tr style="border: none;">
            <td style="width: 55%; padding: 2px; border: none;">
                <strong>Item Name :</strong>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 250px; padding-bottom: 2px;">
                    {{ $QcInspection->product_name ?? '' }}
                </span>
            </td>
            <td style="width: 40%; padding: 2px; border: none;">
                <strong>Weight :</strong>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 70px; padding-bottom: 2px;">
                    {{ $QcInspection->received_weight ?? '' }}
                </span>
                <strong style="margin-left: 20px;">Ok Weight :</strong>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 70px; padding-bottom: 2px;">
                    {{ $QcInspection->ok_weight ?? '' }}
                </span>
            </td>
        </tr>
    </table>


    <!-- Sr. No Section -->
    <div class="sr-no-section" style="margin-top:17px;">
        <span>QC Inspection No.</span>
        <span class="sr-no-box">{{ $QcInspection->qc_inspection_no ?? '-' }}</span>
        <div style="display: inline-block; margin-left: 10px;">
            <label style="font-weight: bold;">QC Inspection Date:</label>
            <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 100px; margin-left: 5px;">
                {{ !empty($QcInspection->qc_inspection_date) ? date('d-m-Y', strtotime($QcInspection->qc_inspection_date)) : '-' }}
            </span>
        </div>
    </div>

    <!-- Inspection Table -->
    <div style="width: 100%; overflow: hidden;">
    <table>
        <thead>
            <tr>
                <th>Sr.<br>No.</th>
                <th>Dimension</th>
                <th>Size</th>
                <th>LTL</th>
                <th>UTL</th>
                <th>Instrument</th>
                @for ($i = 1; $i <= 10; $i++)
                    <th class="sample-header">Size {{ $i }}</th>
                @endfor
                <th class="remark-cell">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($QcInspection->material_details as $index => $material)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $material->dimension ?? '-' }}</td>
                    <td>{{ $material->size ?? '-' }}</td>
                    <td>{{ $material->ltl ?? '-' }}</td>
                    <td>{{ $material->utl ?? '-' }}</td>
                    <td>{{ $material->instrument ?? '-' }}</td>
                    @for($i = 1; $i <= 10; $i++)
                        @php $size = "size{$i}"; @endphp
                        <td>{{ $material->$size ?? '' }}</td>
                    @endfor
                    <td>{{ $material->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="16">No items found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>

</body>
</html>
