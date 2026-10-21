<?php

$headerLogo = null;

if (!empty($jobIssue->company) && !empty($jobIssue->company->order_header_logo_url)) {
    $headerLogo = public_path($jobIssue->company->order_header_logo_url);
} elseif (!empty($jobIssue->company) && !empty($jobIssue->company->company_logo_url)) {
    $headerLogo = public_path($jobIssue->company->company_logo_url);
} else {
    $headerLogo = public_path('software/img/logo.png');
}
$copies = ["Original", "Duplicate"];



?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Delivery Challan - Job Work Issue</title>
    <style>
        body {
            /* font-family: DejaVu Sans, sans-serif; */
            font-size: 10px;
        }

        @page {
            size: A4;
            margin: 8mm;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        td,
        th {
            /* padding: 4px; */
            border: 1px solid #000;
            vertical-align: top;
            word-wrap: break-word;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .no-border {
            border: none !important;
        }

        .copy-box {
            page-break-after: always;
            page-break-inside: avoid;
        }

        .copy-box:last-child {
            page-break-after: avoid;
        }

        .bottom-border {
            border-bottom: 1px solid #000;

        }

        .flex-container {
            display: table;
            width: 100%;
        }

        .flex-left {
            display: table-cell;
            width: 50%;
            border-right: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }

        .flex-right {
            display: table-cell;
            width: 50%;
            padding: 4px;
            vertical-align: top;
        }
    </style>
</head>

<body>
    <?php foreach ($copies as $copy): ?>
        <div class="copy-box">
            <!-- HEADER -->
            <table>
                <tr>
                    <td style="width:30%; text-align:center; border-right:1px solid #000; vertical-align:middle;">
                        <div style="width:100%; height:40px; text-align:center; overflow:hidden;">
                            <?php if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo)) { ?>
                                <img src="<?php echo $sidebarheaderLogo; ?>" class="main-logo"
                                    style="max-height: 35px; width: auto; margin-top: 2px;" />
                            <?php } else { ?>
                                <img src="<?php echo $sidebarMainLogo; ?>" class="main-logo"
                                    style="max-height: 35px; width: auto; margin-top: 2px;" />
                            <?php } ?>
                        </div>
                    </td>

                    <td style="width:40%; text-align:center; font-size:22px; font-weight:bold;">
                        Delivery Challan
                    </td>
                    <td style="width:30%; text-align:center; font-size:16px; font-weight:bold;">
                        <?php echo $copy; ?>
                    </td>
                </tr>
            </table>

            <!-- MOVEMENT RULE -->
            <table>
                <tr>
                    <td style="font-size:13px; line-height:1.4;">
                        Movement of inputs or partially processed goods for job-work under Rule 55 of The Central Goods And
                        Service Tax Rules, 2017 from one factory to another factory for further processing / operation.
                    </td>
                </tr>
            </table>

            <!-- MAIN CONTENT (2 COLUMNS) -->
            <table>
                <tr>
                    <!-- LEFT COLUMN -->
                    <td style="width:50%;">

                        <!-- Supplier -->
                        <p>
                            <b>Name and Address of Supplier / Manufacturer:</b><br>
                            <?php echo $jobIssue->company->company_name ?? '-'; ?><br>
                            <?php echo $jobIssue->company->address ?? '-'; ?>
                            <b>GST NO:</b> <?php echo $jobIssue->company->gst_no ?? '-'; ?>
                        </p>


                        <!-- Items -->
                        <div>
                            <table style="width:100%; border-collapse:collapse; border:none;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left; padding:4px; border-left:none; border-right:none;">
                                            Description</th>
                                        <th
                                            style="text-align:right; border-bottom:1px solid #000; padding:4px; width:120px; border-left:none; border-right:none;">
                                            Quantity (Kgs)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($jobIssue->job_work_issue_items)): ?>
                                        <?php foreach ($jobIssue->job_work_issue_items as $item): ?>
                                            <?php
                                            // Convert values
                                            $gross_weight = floatval($item->gross_weight ?? 0);
                                            $bl_percentage = floatval($item->bl_percentage ?? 0);

                                            // BL Weight
                                            $bl_weight = ($gross_weight * $bl_percentage) / 100;

                                            // Final Weight
                                            $final_weight = floatval($gross_weight) + floatval($bl_weight);
                                            ?>

                                            <tr>
                                                <td
                                                    style="padding:4px; border-bottom:1px solid #ffffff; border-left:none; border-right:none;">
                                                    <?php echo $item->product_name ?? '-'; ?>
                                                </td>
                                                <td
                                                    style="padding:4px; text-align:right; border-bottom:1px solid #ffffff; border-left:none; border-right:none;">
                                                    <?= \App\Helpers\Helper::formatWeight(number_format($final_weight, 3)) ?>

                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="2" style="border:none; padding:0;">
                                            <div style="min-height:150px;">&nbsp;</div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td
                                            style="padding:4px; font-weight:bold; border-top:none; border-left:none; border-right:none;">
                                            Vehicle No</td>
                                        <td
                                            style="padding:4px; text-align:right; font-weight:bold; border-top:none; border-left:none; border-right:none;">
                                            <b><?= !empty($jobIssue->vehicle_no) ? $jobIssue->vehicle_no : 'GJ 10 TW 4865'; ?></b>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                        <!-- Quantity -->
                        <p class="bottom-border">
                            <b>3. Quantity (Pcs / Kgs):</b>
                            <?php echo number_format($jobIssue->total_gross_weight ?? 0, 3); ?>
                        </p>

                        <!-- Tariff -->
                        <p class="bottom-border">
                            <b>4. Tariff Classification:</b>
                            <?php echo $jobIssue->tariff_code ?? '-'; ?>
                        </p>

                        <!-- Estimated Value -->
                        <div class="bottom-border">
                            <b>5. Estimated Value / Tax</b>
                            <table style="width:100%; border-collapse:collapse; margin-top:6px; text-align:center;">
                                <thead>
                                    <tr>
                                        <th style="border:1px solid #000; padding:6px;">Total Amount</th>
                                        <th style="border:1px solid #000; padding:6px;">CGST Rs.</th>
                                        <th style="border:1px solid #000; padding:6px;">SGST Rs.</th>
                                        <th style="border:1px solid #000; padding:6px;">IGST Rs.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                                            <?php echo number_format($jobIssue->sub_total ?? 0, 2); ?>
                                        </td>
                                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                                            <?php echo number_format($jobIssue->total_cgst_amount ?? 0, 2); ?>
                                        </td>
                                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                                            <?php echo number_format($jobIssue->total_sgst_amount ?? 0, 2); ?>
                                        </td>
                                        <td style="border:1px solid #000; padding:6px; text-align:right;">
                                            <?php echo number_format($jobIssue->igst_amount ?? 0, 2); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>


                        <!-- Date & Time -->
                        <p class="bottom-border">
                            <b>6. Date & Time of Issue:</b>
                            <?php echo !empty($jobIssue->job_work_issue_date) ? date('d/m/Y - h:i A', strtotime($jobIssue->job_work_issue_date)) : '-'; ?>
                        </p>


                        <!-- Nature of Processing -->
                        <p class="bottom-border" style="min-height: 90px;">
                            <b>7. Nature of processing/Manufacturing required:</b>
                            <?php echo $jobIssue->job_work_type->name ?? '-'; ?>
                        </p>


                        <!-- Factory / Place -->
                        <p class="bottom-border" style="height: 100px;">
                            <b>8. Factory/Place of processing:</b><br>
                            <?php echo $jobIssue->vendor->vendor_name ?? '-'; ?><br>
                            <?php echo $jobIssue->vendor->address ?? '-'; ?><br>
                            GSTIN: <?php echo $jobIssue->vendor->gst_no ?? '-'; ?>
                        </p>

                        <!-- Signature -->
                        <div style="margin-top:20px;">
                            For, <b><?php echo $jobIssue->company->company_name ?? '-'; ?></b><br><br>
                            Date : <?php echo date('d/m/Y'); ?><br>
                            Place : <?php echo $jobIssue->city->city ?? '-'; ?><br>
                            Authorised Signature
                        </div>

                    </td>

                    <!-- RIGHT COLUMN -->
                    <td style="width:50%; vertical-align:top;">

                        <!-- Processor Notes -->
                        <div class="flex-container">
                            <div class="flex-left">
                                <div style="font-size:11px; font-weight:bold;">
                                    To be filled by the processing unit in Original and Duplicate Challan
                                </div>
                            </div>
                            <div class="flex-right">
                                <div style="font-size:11px; font-weight:bold;">
                                    Serial Number
                                </div>
                                <div style="font-size:11px; margin-top:3px;">
                                    <?php echo $jobIssue->job_work_issue_no ?? '-'; ?>
                                </div>
                            </div>
                        </div>

                        <table style="width:100%;">

                            <tr>
                                <td colspan="3" style="border-top:1px solid #000; border-left:none; border-right:none; border-bottom:none; padding:6px;">
                                    <b>1. Details of type, qty, date & time of processing done and return of processed goods to parent factory:</b>
                                </td>
                            </tr>

                            <tr>
                                <th style="border-top:1px solid #000; border-bottom:1px solid #000; border-right:1px solid #000; border-left:none; text-align:center;">
                                    Size / Type
                                </th>
                                <th style="border:1px solid #000; text-align:center;">
                                    Quantity
                                </th>
                                <th style="border-top:1px solid #000; border-bottom:1px solid #000; border-left:1px solid #000; border-right:none; text-align:center;">
                                    Date
                                </th>
                            </tr>
                            <tr>
                                <td style="border-top:1px solid #000; border-bottom:1px solid #000; border-right:1px solid #000; border-left:none; height:400px; vertical-align:top;"></td>

                                <td style="border:1px solid #000; height:400px; vertical-align:top;"></td>

                                <td style="border-top:1px solid #000; border-bottom:1px solid #000; border-left:1px solid #000; border-right:none; height:400px; vertical-align:top;"></td>
                            </tr>

                            <!-- 2. Nature of Processing -->
                            <tr>
                                <td colspan="3"
                                    style="border-top:1px solid #000; border-bottom:1px solid #000;
               border-left:none; border-right:none;
               height:40px; vertical-align:top; padding:6px;">
                                    <b>2. Nature of Proc. / Manufacturing done</b>
                                </td>
                            </tr>


                            <!-- 3. Waste Details -->
                            <tr>
                                <td colspan="3"
                                    style="border-top:1px solid #000; border-bottom:1px solid #000;
               border-left:none; border-right:none;
               height:110px; vertical-align:top; padding:6px;">
                                    <b>3. Qty of waste material / reject returned to the factory & Burning loss/Process loss</b>
                                </td>
                            </tr>

                            <!-- 4. Processor Details -->
                            <tr>
                                <td colspan="3"
                                    style="border-top:1px solid #000; border-bottom:1px solid #000;
               border-left:none; border-right:none;
               height:110px; padding:6px;">
                                    <b>4. Name & Address of the Processor :</b><br>
                                    <?php echo $jobIssue->vendor->vendor_name ?? '-'; ?><br>
                                    <?php echo $jobIssue->vendor->address ?? '-'; ?><br>
                                    GSTIN: <?php echo $jobIssue->vendor->gst_no ?? '-'; ?>
                                </td>
                            </tr>

                            <!-- 5. Signature -->
                            <tr>
                                <td colspan="3"
                                    style="border-top:1px solid #000; border-bottom:none;
               border-left:none; border-right:none;
               height:100px; vertical-align:top; padding:6px;">
                                    <b>5. Signature of Processor :</b>
                                </td>
                            </tr>


                        </table>

        </div><!-- end copy-box -->

    <?php endforeach; ?>

</body>

</html>
