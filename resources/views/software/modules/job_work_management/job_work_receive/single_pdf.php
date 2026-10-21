<?php
// --- PREPARE VARIABLES ---
// $headerLogo = null;

// if (!empty($jobReceive->company) && !empty($jobReceive->company->order_header_logo_url)) {
//     $headerLogo = public_path($jobReceive->company->order_header_logo_url);
// } elseif (!empty($jobReceive->company) && !empty($jobReceive->company->company_logo_url)) {
//     $headerLogo = public_path($jobReceive->company->company_logo_url);
// } else {
//     $headerLogo = public_path('software/img/logo.png');
// }
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Job Work Receive</title>
</head>

<body style="font-family: DejaVu Sans, sans-serif; font-size: 11px; margin: 0; padding: 10px;">

    <div style="width: 100%; padding: 0;">
        <div style="border: 2px solid #333; padding: 10px;">

            <?php for ($i = 1; $i <= 2; $i++) { ?>
                <div style="padding: 10px 5px;">

                    <!-- Header Logo -->
                    <div style="display: table; width: 100%; padding: 0 5px;">
                        <div style="display: table-cell; text-align: left;">


                            <img src="<?php echo $sidebarHeaderLogo; ?>" style="height: 45px; object-fit: contain; display: block;">
                        </div>
                    </div>

                    <!-- Address -->
                    <div style="text-align: center; font-size: 13px; margin-top: 5px;">
                        <h4 style="margin: 0; padding: 0; font-weight: bold;">
                            Plot No. 4536, GIDC, Phase III, Dared, JAMNAGAR<br>
                            Ph : 02882731337
                        </h4>
                    </div>

                    <hr style="border: none; border-top: 1px solid #000; margin: 8px 0;">

                    <!-- Receipt Details -->
         <table style="width:100%; font-size:13px; margin-bottom:5px;">
    <tr>
        <td style="text-align:left; vertical-align:top;">

            Recpt No: <?= $jobReceive->job_work_receive_no ?><br>
            <?= $jobReceive->vendor->vendor_name ?><br>
            Job Work Issue No: <?= $jobReceive->job_work_issue->job_work_issue_no ?>

        </td>

        <td style="text-align:right; vertical-align:top; white-space:nowrap;">
            Date: <?= date('d-m-Y', strtotime($jobReceive->job_work_receive_date)) ?>
        </td>
    </tr>
</table>


                    <!-- Items Table -->
                    <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f8f9fa;">
                                <th style="width: 60px; text-align: center; padding: 4px; border: 1px solid #000; font-size: 12px;">Sr</th>
                                <th style="text-align: left; padding: 4px; border: 1px solid #000; font-size: 12px;">Particulars</th>
                                <th style="text-align: right; padding: 4px; border: 1px solid #000; font-size: 12px;">Grs</th>
                                <th style="text-align: right; padding: 4px; border: 1px solid #000; font-size: 12px;">Qty</th>
                                <th style="text-align: right; padding: 4px; border: 1px solid #000; font-size: 12px;">Kg</th>
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
                    <div style="margin-top: 10px;">
                        <table style="width: 100%; border-collapse: collapse; border: none;">
                            <tr>
                                <td style="width: 50%; border: none; padding: 0; font-size: 12px;">
                                    <strong>Received By:</strong>
                                </td>
                                <td style="width: 50%; border: none; padding: 0; text-align: right; font-size: 12px;">
                                    <strong>SHALIBHADRA INTERNATIONAL PVT LTD</strong><br>
                                    Chirag
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>

                <?php if ($i == 1) { ?>
                    <hr style="border: none; margin: 85px 0;">
                <?php } ?>
            <?php } ?>

        </div>
    </div>

</body>

</html>
