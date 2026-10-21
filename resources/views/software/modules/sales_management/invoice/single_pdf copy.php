<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo $Invoice->invoice_order_no ?? '-'; ?> <?php echo now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A'); ?> - Print </title>
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
// if(isset($quotation) && isset($Invoice->company_id) && !empty($Invoice->company_id)){
//     $company_id = $Invoice->company_id;
//     $company = App\Helpers\Helper::getCompanyDetailById($company_id);
//     if($company && $company?->id){
//         $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
//         $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
//         $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
//     }
// }
$borderColor = optional($Invoice->company)->quotation_view_color ?? '#266BEE';

$canViewPrice = false;
$currentGuard = $modules['currentGuard'] ?? null;
$authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
$parent_type_id = isset($modules['parent_type_id'])
? (int) $modules['parent_type_id']
: 0;

if ($currentGuard === 'admin_software') {
    // Company Admin or Super Admin
    $canViewPrice = true;
} elseif ($currentGuard === 'team_person' && $authLoginUserDetail) {
    // Check if team_person has parent_type = 0
    if ($parent_type_id === 0) {
        $canViewPrice = true;
    }
}
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

                        <div class="title">Invoice</div>



                        <table style="width: 100%; border-collapse: collapse; background-color: #f9f9f9; padding: 0px !important; margin-left: 0px !important; margin-right: 0px !important; border: 1px solid #ccc;">
                            <tr>

                                <!-- Invoice Info -->
                                <td style="width: 30%; vertical-align: top; padding: 6px; font-size: 13px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Invoice Info:</div>
                                    <p style="margin: 10px 0;">Invoice No: <?php echo $Invoice->invoice_order_no ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Invoice Date:
                                        <?php echo $Invoice->invoice_order_date ? \Carbon\Carbon::parse($Invoice->invoice_order_date)->format('d-m-Y') : '-'; ?>
                                    </p>
                                    <?php if ($canViewPrice){ ?>
                                    <p style="margin: 10px 0;">GSTIN: <?php echo $Invoice->gst_no ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Transport By: <?php echo $Invoice?->transport_by?->name ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Transporter Detail: <?php echo $Invoice?->transport_detail?->name ?? '-'; ?></p>
                                    <?php } ?>
                                </td>

                                <!-- Customer Detail -->
                                <td style="width: 30%; vertical-align: top; padding: 6px; font-size: 13px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Customer Detail:</div>
                                    <p class="mb-1"><b>Customer Code :
                                                    </b><?php echo $Invoice->customer?->client_code ?? 'N/A'; ?></p>
                                     <?php if ($canViewPrice){ ?>
                                    <p style="margin: 10px 0;">Name: <?php echo $Invoice->customer->customer_name ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Person: <?php echo $Invoice->company->person_name ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Phone: <?php echo $Invoice->phone ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Email: <?php echo $Invoice->customer->email ?? '-'; ?></p>
                                    <p style="margin: 10px 0;">Address: <?php echo $Invoice->address ?? '-'; ?></p>
                                    <?php } ?>
                                </td>

                                <!-- Billing Address -->
                                <td style="width: 20%; vertical-align: top; padding: 6px; font-size: 13px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Billing Address:</div>
                                    <p style="margin: 10px 0;"><?php echo $Invoice->billing_address ?? '-'; ?></p>
                                </td>

                                <!-- Shipping Address -->
                                <?php if ($canViewPrice){ ?>
                                <td style="width: 20%; vertical-align: top; padding: 6px; font-size: 13px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Shipping Address:</div>
                                    <p style="margin: 10px 0;"><?php echo $Invoice->shipping_address ?? '-'; ?></p>
                                </td>
                                <?php } ?>
                            </tr>
                        </table>



                        <div class="section-box" style="margin-top: 25px;padding: 0px !important;margin-left: 0px !important;margin-right: 0px !important;">

                            <table style="background-color: #fff;border-radius: 2px; border: 1px solid #ccc;background-color: #f9f9f9;border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                <thead>
                                    <tr>
                                        <th>Sr No.</th>
                                        <th>Item</th>
                                        <th>Description</th>
                                        <th>HSN</th>
                                        <th>Unit</th>
                                        <th class="price-col">Qty</th>
                                        <?php if ($canViewPrice){ ?>
                                            <th class="rate-col">Price</th>
                                            <th class="discount-col">Taxable Amount</th>
                                        <?php } ?>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    $sub_total = 0;
                                    $k = 1;  // <-- Sr No. start here

                                    foreach ($Invoice->invoice_items as $item) {

                                        // subtotal
                                        $sub_total += $item->amount;
                                        $quantity = strtolower($item->unit) === 'kg'
                                                        ? number_format($item->quantity ?? 0, 3, '.', '')
                                                        : (int) ($item->quantity ?? 0);
                                    ?>
                                        <tr>
                                            <td><?php echo $k++; ?></td>
                                            <td><?php echo $item->product_name; ?></td>
                                            <td><?php echo $item->product_description; ?></td>
                                            <td><?php echo $item->hsn_code; ?></td>
                                            <td><?php echo $item->unit; ?></td>
                                            <td class="price-col"><?php echo $quantity; ?></td>
                                            <?php if ($canViewPrice){ ?>
                                                <td class="rate-col"><?php echo number_format($item->price_in_amount, 2); ?></td>
                                                <td class="discount-col"><?php echo number_format($item->amount, 2); ?></td>
                                            <?php } ?>
                                        </tr>
                                    <?php } ?>
                                </tbody>

                            </table>

                        </div>


                            <div class="justify-content-start no_display">
                                <div class="d-flex justify-content-start mb-3 px-3 col-12 col-md-12">
                                    <h6 class="mb-2 text-start"><strong>Note:</strong></h6>
                                    <p class="mb-2 text-start"><?php echo str_replace("&nbsp;", "", strip_tags($Invoice->notes)) ?? '-'; ?></p>
                                </div>
                            </div>
                             <?php if ($canViewPrice){ ?>
                            <div class="row" style="border-spacing: 0px !important;">
                                <div class="col" style="padding: 0rem;">
                                    <div class="card" style="border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                        <table class="borderless">
                                            <tr>
                                                <td><strong>Bank Details:</strong></td>
                                                <td><?php echo str_replace('</p>', '</p>' . PHP_EOL, $Invoice->company->bank_details ?? '-'); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Terms and Conditions:</strong></td>
                                                <td><?php echo str_replace("&nbsp;", "", strip_tags($Invoice->term_and_condition)) ?? '-'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Regards:</strong></td>
                                                <td><?php echo str_replace("&nbsp;", "", strip_tags($Invoice->regards)) ?? '-'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Note:</strong></td>
                                                <td><?php echo str_replace("&nbsp;", "", strip_tags($Invoice->notes)) ?? '-'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Amount in Words:</strong></td>
                                                <td><?php echo App\Helpers\Helper::convertToRupees($Invoice->grand_total); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>


                                <div class="card" style="border-top: 3px solid <?php echo $borderColor; ?> !important;">
                                    <table class="summary-table">
                                        <tr style="line-height: 12px !important;">
                                            <td colspan="2" class="text-center">
                                                Order Summary (<span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>)
                                            </td>
                                        </tr>
                                        <tr style="line-height: 12px !important;">
                                            <td class="label">Sub Total:</td>
                                            <td class="text-right">
                                                <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span><?php echo number_format($sub_total, 2); ?>
                                            </td>
                                        </tr>

                                        <?php if (!empty($Invoice->transport_charge) && $Invoice->transport_charge > 0) { ?>
                                            <tr style="line-height: 12px !important;">
                                                <td class="label">Transport:</td>
                                                <td class="text-right">
                                                    <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>
                                                    <?php echo number_format($Invoice->transport_charge, 2); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <?php if (!empty($Invoice->packing_forwarding_charge) && $Invoice->packing_forwarding_charge > 0) { ?>
                                            <tr style="line-height: 12px !important;">
                                                <td class="label">Packing & Forwarding:</td>
                                                <td class="text-right">
                                                    <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>
                                                    <?php echo number_format($Invoice->packing_forwarding_charge, 2); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <tr style="line-height: 12px !important;">
                                            <td class="label">Total Taxable:</td>
                                            <td class="text-right"><span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span><?php echo number_format($Invoice->total_taxable, 2); ?></td>
                                        </tr>
                                        <?php
                                        $customerStateName = $Invoice->customer->state->name ?? null;
                                        ?>

                                        <?php if (strtolower($customerStateName) === 'gujarat'): ?>

                                            <tr style="line-height: 12px !important;">
                                                <td class="label">CGST:</td>
                                                <td class="text-right">
                                                    <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>
                                                    <?php echo number_format($Invoice->gst_amount / 2, 2); ?>
                                                </td>
                                            </tr>

                                            <tr style="line-height: 12px !important;">
                                                <td class="label">SGST:</td>
                                                <td class="text-right">
                                                    <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>
                                                    <?php echo number_format($Invoice->gst_amount / 2, 2); ?>
                                                </td>
                                            </tr>

                                        <?php else: ?>

                                            <tr style="line-height: 12px !important;">
                                                <td class="label">IGST:</td>
                                                <td class="text-right">
                                                    <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>
                                                    <?php echo number_format($Invoice->gst_amount, 2); ?>
                                                </td>
                                            </tr>

                                        <?php endif; ?>

                                        <?php if (!empty($Invoice->tcs_amount) && $Invoice->tcs_amount > 0) { ?>
                                            <tr style="line-height: 12px !important;">
                                                <td class="label">TCS Amount:</td>
                                                <td class="text-right">
                                                    <span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span>
                                                    <?php echo number_format($Invoice->tcs_amount, 2); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr style="line-height: 12px !important;">
                                            <td class="label">Rounded Off:</td>
                                            <td class="text-right"><span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span><?php echo number_format($Invoice->round_off, 2); ?></td>
                                        </tr>
                                        <tr style="line-height: 12px !important;">
                                            <td class="label"><strong>Grand Total:</strong></td>
                                            <td class="text-right"><span style="font-family: 'DejaVu Sans', sans-serif;">&#8377;</span><strong><?php echo number_format($Invoice->grand_total, 2); ?></strong></td>
                                        </tr>

                                    </table>
                                </div>

                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
