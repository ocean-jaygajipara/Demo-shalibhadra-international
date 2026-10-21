<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice PDF</title>

    <style>
        /* GLOBAL A4 OPTIMIZATION */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }

        .container {
            width: 100%;
            max-width: 780px;
            margin: 0 auto;
            padding: 0;
            border: 2px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        td,
        th {
            padding: 6px 8px;
            /* Smaller padding */
            vertical-align: top;
        }

        .bordered {
            border: 1px solid #000;
        }

        h2 {
            font-size: 20px;
            /* smaller */
            margin: 0 0 5px 0;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .signature {
            margin-top: 40px;
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #000;
        }

        /* Reduce item table font */
        .items td,
        .items th {
            font-size: 10px;
            padding: 6px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        /* Items table border optimization */
        .items,
        .items th,
        .items td {
            border: 1px solid #000 !important;
        }
    </style>
</head>

<body>

    <?php
    /* ====================== DATA INITIALIZATION ======================== */
    $document = $Invoice;
    $items = $Invoice->invoice_items ?? collect([]);

    // Global Summary Calculations
    $sub_total = 0;
    $total_qty = 0;
    $is_igst = 0;

    foreach ($items as $item) {
        $sub_total += floatval($item->amount ?? 0);
        $total_qty += floatval($item->quantity ?? 0);

        // Use the same IGST detection logic as in Print view
        if (!(floatval($item->cgst_amount) > 0 && floatval($item->sgst_amount) > 0)) {
            if (floatval($item->gst_amount) > 0) {
                $is_igst = 1;
            }
        }
    }

    /* ====================== PREPARE COMPANY ADDRESS SPLIT ======================== */
    $raw = $Invoice->company->address ?? '';
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);

    $part1 = $parts[0] ?? '';
    $part2 = $parts[1] ?? '';
    ?>

    <div class="container">

        <!-- ====================== HEADER ========================= -->
        <table>
            <tr>
                <td style="width:160px; text-align:center; border-bottom:1px solid #000;">
                    <img src="<?php echo $sidebarMainLogo; ?>" style="width:140px; height:auto;margin-top:35px;">
                </td>

                <td style="border-bottom:1px solid #000;">
                    <h4><?php echo $Invoice->company->company_name ?? '-'; ?></h4>
                    <div style="font-size:11px;">
                        <?php echo $part1; ?><br>
                        Phone: <?php echo $Invoice->company->whatsapp_number ?? '-'; ?><br>
                        Email: <?php echo $Invoice->company->email ?? '-'; ?><br>
                    </div>
                </td>

                <td style="width:260px; text-align:right; border-bottom:1px solid #000;">
                    <p class="bold" style="line-height:1.5; font-size:11px;">
                        <?php echo $part2; ?>
                    </p>
                </td>
            </tr>
        </table>


        <!-- ====================== BILLED TO ========================= -->
        <table>
            <tr>

                <!-- LEFT -->
                <td style="width:55%; border-right:1px solid #000; border-bottom:1px solid #000;">
                    <div style="padding:8px;">

                        <span class="bold section-title">Billed To:</span><br>

                        <strong><?php echo $document->customer_name ?? ($document->customer?->customer_name ?? '-'); ?></strong><br>
                        <?php echo nl2br(strip_tags($document->billing_address)); ?><br>
                        <?php if ($document->customer): ?>
                            <?php echo $document->customer->city->name ?? ''; ?>,
                            <?php echo $document->customer->state->name ?? ''; ?> -
                            <?php echo $document->customer->pincode ?? ''; ?><br>
                        <?php endif; ?>

                        <strong>GSTIN:</strong>
                        <?php echo $document->gst_no ?: ($document->customer?->gst_number ?? '-'); ?><br>

                        <?php 
                        $shipAddress = !empty($document->shipping_address) ? $document->shipping_address : $document->billing_address;
                        $shipGst = !empty($document->shipping_gst_no) ? $document->shipping_gst_no : ($document->gst_no ?? '');
                        if (!empty($shipAddress)): ?>
                            <br><span class="bold section-title">Shipped To:</span><br>
                            <?php echo nl2br(strip_tags($shipAddress)); ?><br>
                            <?php if (!empty($shipGst)): ?>
                                <strong>GSTIN:</strong> <?php echo $shipGst; ?><br>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($document->transport_detail->name)): ?>
                            <strong>Transporter:</strong> <?php echo $document->transport_detail->name; ?><br>
                        <?php endif; ?>

                        <?php if (!empty($document->transport_detail->transport_gst_id)): ?>
                            <strong>Transporter ID/GSTIN:</strong>
                            <?php echo $document->transport_detail->transport_gst_id; ?><br>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- RIGHT -->
                <td style="width:45%; border-bottom:1px solid #000;padding:0px !important; " cellspacing="0"
                    cellpadding="0">
                    <div style="text-align:center; border-bottom:1px solid #000; padding: 5px;">
                        <span class="bold">INVOICE</span>
                    </div>

                    <table cellspacing="0" cellpadding="0" style="padding: 10px;">
                        <tr>
                            <td>Number:</td>
                            <td class="right bold"><?php echo $document->invoice_order_no; ?></td>
                        </tr>
                        <tr>
                            <td>Date:</td>
                            <td class="right bold">
                                <?php echo $document->invoice_order_date ? date('d-m-Y', strtotime($document->invoice_order_date)) : '-'; ?>
                            </td>
                        </tr>
                        <?php if (!empty($document->eway_bill_no)): ?>
                            <tr>
                                <td>E-way bill no:</td>
                                <td class="right bold"><?php echo $document->eway_bill_no; ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($document->sales_order?->po_number)): ?>
                            <tr>
                                <td>PO No.:</td>
                                <td class="right bold"><?php echo $document->sales_order->po_number; ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>

                </td>
            </tr>
        </table>


        <!-- ====================== ITEMS TABLE ========================= -->
        <table class="items" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background:#eee;">
                    <th width="5%">#</th>
                    <th width="35%">Item</th>
                    <th class="center" width="10%">HSN</th>
                    <th class="center" width="8%">Unit</th>
                    <th class="center" width="8%">Qty</th>
                    <th class="center" width="10%">Rate</th>
                    <th class="right" width="12%">Amount</th>
                    <th class="center" width="12%">Total</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $counter = 1;
                foreach ($items as $item):
                    $cgst_amount = floatval($item->cgst_amount ?? 0);
                    $sgst_amount = floatval($item->sgst_amount ?? 0);
                    $gst_amount = floatval($item->gst_amount ?? 0);
                    $amount = floatval($item->amount ?? 0);

                    $cgst_per = floatval($item->cgst_in_percentage ?? 0);
                    $sgst_per = floatval($item->sgst_in_percentage ?? 0);
                    $gst_per = floatval($item->gst_in_percentage ?? 0);

                    $item_gst_per = ($cgst_amount > 0 && $sgst_amount > 0) ? ($cgst_per + $sgst_per) : $gst_per;
                    $item_total = $amount + ($amount * $item_gst_per / 100);
                    ?>
                    <tr>
                        <td class="center"><?php echo $counter++; ?></td>
                        <td>
                            <strong><?php echo $item->product_code ?? '-'; ?> :</strong>
                            <?php echo $item->product_name ?? ($item->product_description ?? '-'); ?>
                        </td>
                        <td class="center"><?php echo $item->hsn_code ?? '-'; ?></td>
                        <td class="center"><?php echo $item->unit ?? '-'; ?></td>
                        <td class="center">
                            <?php echo strtolower($item->unit) === 'kg' ? number_format($item->quantity ?? 0, 3, '.', '') : (int) ($item->quantity ?? 0); ?>
                        </td>
                        <td class="center"><?php echo number_format($item->price_in_amount ?? 0, 2); ?></td>
                        <td class="right"><?php echo number_format($amount, 2); ?></td>
                        <td class="right"><?php echo number_format($item_total, 2); ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php
                $max_items = 15;
                $total_empty_rows = max(0, $max_items - count($items));

                for ($i = 0; $i < $total_empty_rows; $i++):
                    ?>
                    <tr style="height: 30px;">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr style="background:#eee;">
                    <td colspan="4" class="right bold">Total Qty</td>
                    <td class="center bold"><?php echo $total_qty; ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>


        <!-- ====================== SUMMARY SECTION ========================= -->
        <table
            style="width:100%; border: 1px solid #000; border-top: none; border-collapse: collapse; margin-top: -1px;">
            <tr>
                <!-- LEFT -->
                <td style="width:55%; border-right:1px solid #000; padding: 0; vertical-align: top;">
                    <table style="width:100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #000;">
                                <div class="section-title">Net Amount In Words</div>
                                <p class="bold" style="margin: 0; padding-top: 5px; font-size: 11px;">
                                    <?php echo App\Helpers\Helper::convertToRupees($document->grand_total); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #000;">
                                <div class="section-title">Bank Details</div>
                                <div style="font-size: 9px; line-height: 1.4; padding-top: 5px;">
                                    <?php echo str_replace('</p>', '</p>' . PHP_EOL, $document->company?->bank_details ?? '-'); ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 10px;">
                                <div class="section-title">Terms & Conditions</div>
                                <ol style="font-size:9px; padding-left: 15px; margin: 0;">
                                    <li>Good once sold cannot be taken back.</li>
                                    <li>We are not responsible for breakage, theft or damage.</li>
                                    <li>Interest @ 24% P.A will be charged after 5 days of due date.</li>
                                    <li>Subject to Jamnagar jurisdiction.</li>
                                </ol>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- RIGHT -->
                <td style="width:45%; vertical-align:top; border-top: none; padding: 0;">

                    <table style="width:100%; font-size: 11px; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px 6px; border-bottom: 1px solid #000;">Basic Amount</td>
                            <td class="right bold" style="padding: 10px 6px; border-bottom: 1px solid #000;"><span
                                    style="font-family: DejaVu Sans;">&#8377;</span>
                                <?php echo number_format($sub_total, 2); ?></td>
                        </tr>

                        <?php if (!empty($document->packing_forwarding_charge)): ?>
                            <tr>
                                <td style="padding: 10px 6px; border-bottom: 1px solid #000;">Packing & Forwarding</td>
                                <td class="right bold" style="padding: 10px 6px; border-bottom: 1px solid #000;"><span
                                        style="font-family: DejaVu Sans;">&#8377;</span>
                                    <?php echo number_format($document->packing_forwarding_charge, 2); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($is_igst == 1): ?>
                            <tr>
                                <td style="padding: 10px 6px; border-bottom: 1px solid #000;">IGST</td>
                                <td class="right bold" style="padding: 10px 6px; border-bottom: 1px solid #000;"><span
                                        style="font-family: DejaVu Sans;">&#8377;</span>
                                    <?php echo number_format($document->gst_amount ?? 0, 2); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td style="padding: 10px 6px; border-bottom: 1px solid #000;">CGST</td>
                                <td class="right bold" style="padding: 10px 6px; border-bottom: 1px solid #000;"><span
                                        style="font-family: DejaVu Sans;">&#8377;</span>
                                    <?php echo number_format(($document->gst_amount ?? 0) / 2, 2); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 10px 6px; border-bottom: 1px solid #000;">SGST</td>
                                <td class="right bold" style="padding: 10px 6px; border-bottom: 1px solid #000;"><span
                                        style="font-family: DejaVu Sans;">&#8377;</span>
                                    <?php echo number_format(($document->gst_amount ?? 0) / 2, 2); ?></td>
                            </tr>
                        <?php endif; ?>

                        <tr style="background:#eee;">
                            <td class="bold" style="padding: 12px 6px; font-size: 13px; border-bottom: 1px solid #000;">
                                Net Amount</td>
                            <td class="right bold"
                                style="padding: 12px 6px; font-size: 14px; border-bottom: 1px solid #000;"><span
                                    style="font-family: DejaVu Sans;">&#8377;</span>
                                <?php echo number_format($document->grand_total, 2); ?></td>
                        </tr>
                    </table>

                    <div style="text-align: center; margin-top: 0; padding: 0;">
                        <div style="height: 120px; margin-bottom: 2px;">
                            <?php if (!empty($authorisedSignature)): ?>
                                <img src="<?php echo $authorisedSignature; ?>"
                                    style="max-height: 150px; width: 220px; object-fit: contain;" />
                            <?php endif; ?>
                        </div>
                        <div class="bold" style="border-top: 2px solid #000; padding: 10px 0;">Authorised Signature
                        </div>
                    </div>

                </td>
            </tr>
        </table>

    </div>

</body>

</html>