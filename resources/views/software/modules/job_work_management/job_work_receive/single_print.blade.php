<!DOCTYPE html>
<html>

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($jobReceive) && isset($jobReceive->company_id) && !empty($jobReceive->company_id)) {
        $company_id = $jobReceive->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->company_logo_url ?? '';
            // dd($sidebarMainLogo);
        }
    }
@endphp

<head>
    <meta charset="UTF-8">
    <title>Job Work Receive {{ $jobReceive->job_work_receive_no ?? '-' }} - Print</title>
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
            /* Scrollbar fix: Ensure the body doesn't overflow */
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* Set default border for cells for structural tables only */
        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        /* Remove border from the Movement Rule table cells, keeping only the top/bottom of the whole table */
        .movement-rule td {
            border-top: none;
            border-bottom: none;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* Helper to span borders cleanly within padded divs */
        .full-width-line {
            border-top: 1px solid #000;
            margin: 8px -8px;
            /* Use -8px margin to span across the 8px padding of the parent div */
        }

        .page-break {
            page-break-before: always;
            break-before: page;
            display: block;
        }
         .copy {
            padding: 10px 5px;
            page-break-inside: avoid;
        }

        .no-space hr {
            margin: 8px 0;
        }

        .table th,
        .table td {
            padding: 4px !important;
            font-size: 12px;
        }

        h4,
        h5 {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body onload="printAndRedirect();">
    <div style="width: 100%; padding: 0;">

        <div class="card" style="border: 2px solid #333; padding: 10px;">

            @for ($i = 1; $i <= 2; $i++)
                <div class="copy">

                    <!-- Header Logos -->
                    <!-- Header Logos -->
                    <div
                        style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            width: 100%;
                            padding: 0 5px;
                        ">
                        <img src="{{ $sidebarheaderLogo }}" style="height: 45px; object-fit: contain;">

                        {{-- <img src="http://127.0.0.1:8000/software/img/logo.png"
                            style="height: 45px; object-fit: contain;"> --}}
                    </div>


                    <!-- Address -->
                    <div style="text-align: center; font-size: 13px; margin-top: 5px;">
                        <h4>Plot No. 4536, GIDC, Phase III, Dared, JAMNAGAR<br>
                            Ph : 02882731337
                        </h4>
                    </div>

                    <hr class="no-space">

                    <!-- Receipt Header -->
                    <!-- Receipt Details -->
                    <div
                        style="
                        display: flex;
                        justify-content: space-between;
                        width: 100%;
                        padding: 0 5px;
                        font-size: 13px;
                    ">

                        <!-- LEFT SIDE DETAILS -->
                        <div style="text-align: left;">
                            <div>Recpt No: {{ $jobReceive->job_work_receive_no }}</div>
                            <div>{{ $jobReceive->vendor->vendor_name }}</div>
                            <div>Job Work Issue No: {{ $jobReceive->job_work_issue->job_work_issue_no }}</div>
                        </div>

                        <!-- RIGHT SIDE DATE -->
                        <div style="text-align: right; white-space: nowrap;">
                            <div>Date: {{ \Carbon\Carbon::parse($jobReceive->job_work_receive_date)->format('d-m-Y') }}
                            </div>
                        </div>

                    </div>



                    <!-- Items Table -->
                    <div style="padding: 20px;">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 80px; text-align: center;">Sr. No</th>
                                    <th>Particulars</th>
                                    <th style="text-align: right;">Grs</th>
                                    <th style="text-align: right;">Qty</th>
                                    <th style="text-align: right;">Kg</th>
                                </tr>
                            </thead>

                           <tbody>
    <?php
        $total_qty = 0;
        $total_kg = 0;
        $total_grs = 0;

        $index = 1;
        foreach ($jobReceive->job_work_receive_items as $item) {

            // Calculate grs (same as Blade)
            $grs = 0;
            if (!empty($item->product) && !empty($item->product->grosse_per_qty)) {
                $grs = $item->quantity / $item->product->grosse_per_qty;
            }

            // Update totals
            $total_qty += $item->quantity ?? 0;
            $total_kg  += $item->received_weight ?? 0;
    ?>
        <tr>
            <td style="text-align: center; padding:4px; border:1px solid #000; font-size:12px;">
                <?= $index ?>
            </td>

            <td style="padding:4px; border:1px solid #000; font-size:12px;">
                <?= $item->particulars ?? ($item->product_name ?? '-') ?>
            </td>

            <td style="text-align:right; padding:4px; border:1px solid #000; font-size:12px;">
                <?= number_format($grs, 2) ?>
            </td>

            <td style="text-align:right; padding:4px; border:1px solid #000; font-size:12px;">
                <?= number_format($item->quantity ?? 0) ?>
            </td>

            <td style="text-align:right; padding:4px; border:1px solid #000; font-size:12px;">
                <?= number_format($item->received_weight ?? 0, 2) ?>
            </td>
        </tr>

    <?php
        $index++;
        }
    ?>

    <!-- TOTAL ROW -->
    <tr style="background-color:#f8f9fa; font-weight:bold;">
        <td colspan="3" style="padding:4px; border:1px solid #000; font-size:12px;">
            Total Weight & Qty
        </td>

        <td style="text-align:right; padding:4px; border:1px solid #000; font-size:12px;">
            <?= number_format($total_qty) ?>
        </td>

        <td style="text-align:right; padding:4px; border:1px solid #000; font-size:12px;">
            <?= number_format($total_kg, 2) ?>
        </td>
    </tr>
</tbody>


                        </table>

                        <!-- Received By -->
                        <div style="margin-top: 20px;padding: 10px;">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Received By:</strong>
                                </div>
                                <div class="col-6" style="text-align: right;">
                                    <div><strong>SHALIBHADRA INTERNATIONAL PVT LTD</strong></div>
                                    <div>Chirag</div>
                                </div>
                            </div>
                        </div>

                    </div>



                </div>

                @if ($i == 1)
                    <hr style="border: none; margin: 85px 0;">
                @endif
            @endfor

        </div>

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
