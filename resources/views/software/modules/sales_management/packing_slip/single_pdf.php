<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>packingSlip <?php echo $packingSlip->packing_slip_no ?? '-'; ?> <?php echo now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A'); ?> - Print </title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 14px;
        }

        .container {
            padding: 0px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo img {
            max-width: 60%;
        }

        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }

        .row {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 10px;
        }

        .col {
            display: table-cell;
            vertical-align: top;
            padding: 0 5px;
        }

        .section-box {
            background: #fff;
            border: none !important;
            padding-top: 1rem;
            /* padding: 1rem; */
            margin-bottom: 1rem;
            /* box-shadow: 0 1px 3px rgba(0,0,0,0.05); */
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 8px 6px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f9f9f9;
        }

        .table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table th,
        .table td,
        .summary-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer-note {
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }

        .borderless td {
            border: none !important;
        }

        .card {
            margin: 0.5rem 0 1rem;
            background-color: #fff;
            border-radius: 2px;
            padding: 1rem;
            /* box-shadow: 0 1px 4px rgba(0,0,0,0.1); */
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .mt-2 {
            margin-top: 10px;
        }

        .mt-3 {
            margin-top: 15px;
        }
    </style>
</head>
<?php
// $sidebarMainLogo = public_path('software/img/logo.png');
// $sidebarSmallLogo = public_path('software/img/ring.png');
// $sidebarheaderLogo = '';
// if(isset($quotation) && isset($packingSlip->company_id) && !empty($packingSlip->company_id)){
//     $company_id = $packingSlip->company_id;
//     $company = App\Helpers\Helper::getCompanyDetailById($company_id);
//     if($company && $company?->id){
//         $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
//         $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
//         $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
//     }
// }
$borderColor = optional($packingSlip->company)->packing_slip_view_color ?? '#266BEE';
?>

<body style="--bs-scrollbar-width: 0px;">
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container">
                        <div class="logo">
                            <?php if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo)) { ?>
                                <div class="text-center">
                                    <img src="<?php echo $sidebarheaderLogo; ?>" class="main-logo" style="width: 60%;" />
                                </div>
                            <?php } else { ?>
                                <div class="text-center">
                                    <img src="<?php echo $sidebarMainLogo; ?>" class="main-logo" style="width: 60%;" />
                                </div>
                            <?php } ?>
                        </div>

                        <div class="title">packingSlip</div>



                        <table style="width: 100%; border-collapse: collapse; background-color: #f9f9f9;padding: 0px !important;margin-left: 0px !important;margin-right: 0px !important;border: 1px solid #ccc;">
                            <tr>
                                <!-- Quotation Info -->
                                <td style="width: 30%; vertical-align: top; padding: 6px; font-size: 13px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">packingSlip Info:</div>
                                    <p style="margin: 10px 0;">packingSlip No: <?php echo $packingSlip->packing_slip_no; ?></p>
                                    <p style="margin: 10px 0;">packingSlip Date: <?php echo $packingSlip->packing_slip_date ? \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d-m-Y') : ''; ?></p>

                                </td>

                                <!-- Customer Detail -->
                                <td style="width: 30%; vertical-align: top; padding: 6px; font-size: 13px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Customer Detail:</div>
                                    <p style="margin: 10px 0;">Name: <?php echo $packingSlip->customer->customer_name; ?></p>
                                    <p style="margin: 10px 0;">Person: <?php echo $packingSlip->company->person_name; ?></p>
                                    <p style="margin: 10px 0;">Phone: <?php echo $packingSlip->phone; ?></p>

                                    <p style="margin: 10px 0;">Address: <?php echo $packingSlip->address; ?></p>
                                </td>


                            </tr>
                        </table>


                        <div class="section-box" style="margin-top: 25px;padding: 0px !important;margin-left: 0px !important;margin-right: 0px !important;">
                            <?php
                            $totalQty = 0;
                            $totalWeight = 0;
                            ?>

                            <table style="background-color: #fff;border-radius: 2px; border: 1px solid #ccc;background-color: #f9f9f9;border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>HSN</th>
                                        <th class="without_price">Cartoon No</th>
                                        <th class="without_price">Qty</th>
                                        <th class="without_price">Weight</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($packingSlip->packing_slip_item) && is_iterable($packingSlip->packing_slip_item)): ?>
                                        <?php foreach ($packingSlip->packing_slip_item as $item): ?>
                                            <?php
                                            $qty = $item->qty ?? 0;
                                            $weight = $item->weight ?? 0;
                                            $totalQty += $qty;
                                            $totalWeight += $weight;
                                            ?>
                                            <tr>
                                                <td><?php echo $item->product->product_name ?? '-'; ?></td>
                                                <td><?php echo $item->product->hsn_code ?? '-'; ?></td>
                                                <td class="without_price"><?php echo $item->cartoon_no ?? '-'; ?></td>
                                                <td class="without_price"><?php echo $qty ?: '-'; ?></td>
                                                <td class="without_price"><?php echo $weight ? number_format($weight, 2) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td class="without_price"><strong><?php echo $totalQty; ?></strong></td>
                                            <td class="without_price"><strong><?php echo number_format($totalWeight, 2); ?></strong></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No items found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>


                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
