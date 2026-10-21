@php
    $documentNoField = 'invoice_order_no';
    $documentDateField = 'invoice_order_date';
    $documentTitle = 'Invoice';
    $itemsRelation = $itemRelation ?? 'invoice_items';
    $withoutPriceKey = $withoutPriceKey ?? 'invoice_without_price';

    $items = $document->{$itemsRelation} ?? collect([]);
    $borderColor = $borderColor ?? (optional($document->company)->order_view_color ?? '#266BEE');
    $printMode = $isPrint ?? false;
    $showPrice = $canViewPrice ?? false;
    $raw = $document->company->address;
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);

    $part1 = $parts[0] ?? '';
    $part2 = $parts[1] ?? '';
@endphp
@if ($printMode)

    {{-- Print Mode Layout --}}

    <table class="main-table" id="main-table">
        <!-- Letterhead - Will repeat on every page -->

        <thead class="letterhead-header">
            <tr id="letterhead-header">
                <td colspan="2" style="padding:0;">

                    <!-- ONE SINGLE OUTER BOX FOR FULL HEADER -->
                    <table width="100%" cellspacing="0" cellpadding="0"
                        style="border-collapse:collapse; border:1px solid #000;">

                        <!-- LETTERHEAD -->
                        <tr>
                            <td colspan="2" style="padding:0;">
                                <img src="{{ $invoice_letter_head ?? '' }}" style="width:100%; display:block;">
                            </td>
                        </tr>

                        <!-- INVOICE DETAILS -->
                        <tr>
                            <td colspan="2" style="padding: 0px !important;margin: 0px !important;">

                                <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                                    <tr>

                                        <!-- LEFT : BILLED TO -->
                                        <td width="60%"
                                            style="vertical-align:top; padding: 0px !important;margin: 0px !important; border-right:1px solid #000;border-top:1px solid #000;">

                                            <div style="display: flex; align-items: flex-start;padding: 0 !important;">
                                                <strong style="font-size:15px;padding: 10px !important;">Billed
                                                    To:</strong><br>

                                                <div style="font-size:14px; line-height:1.6;padding: 10px !important;">
                                                    {{ $document->customer_name ?? (optional($document->customer)->customer_name ?? '-') }}<br>
                                                    {!! nl2br(strip_tags($document->billing_address)) !!}<br>
                                                    <strong>GSTIN:</strong> {{ $document->gst_no ?? '-' }}
                                                </div>
                                            </div>
                                            @php
                                                $shipAddress = !empty($document->shipping_address) ? $document->shipping_address : $document->billing_address;
                                                $shipGst = !empty($document->shipping_gst_no) ? $document->shipping_gst_no : ($document->gst_no ?? '');
                                            @endphp
                                            @if (!empty($shipAddress))
                                                <div style="display: flex; align-items: flex-start;padding: 0 !important; margin-top: 5px;">
                                                    <strong style="font-size:15px;padding: 5px 10px !important;">Shipped To:</strong><br>
                                                    <div style="font-size:14px; line-height:1.6;padding: 5px 10px !important;">
                                                        {!! nl2br(strip_tags($shipAddress)) !!}
                                                    </div>
                                                </div>
                                                @if (!empty($shipGst))
                                                    <div style="display: flex; align-items: flex-start;padding: 0 !important;">
                                                        <strong style="font-size:15px;padding: 5px 10px !important;">GSTIN:</strong><br>
                                                        <div style="font-size:14px; line-height:1.6;padding: 5px 10px !important;">
                                                            {{ $shipGst }}
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                            @if (!empty($document->transport_detail->name))
                                                <div style="margin:3px 0 0 10px !important; font-size:14px;">
                                                    <strong>Transporter:</strong>
                                                    {{ $document->transport_detail->name }}
                                                </div>
                                            @endif

                                            @if (!empty($document->transport_detail->transport_gst_id))
                                                <div style="margin:3px 0 0 10px !important; font-size:14px;">
                                                    <strong>Transporter ID/GSTIN:</strong>
                                                    {{ $document->transport_detail->transport_gst_id }}
                                                </div>
                                            @endif

                                        </td>

                                        <!-- RIGHT : INVOICE INFO -->
                                        <td width="40%"
                                            style="vertical-align:top;padding: 0px !important;margin: 0px !important;border-top:1px solid #000;">

                                            <div
                                                style="
                                                font-size:15px;
                                                font-weight:bold;
                                                text-align:center;
                                                padding-bottom:5px;
                                                margin-bottom:8px;
                                                border-bottom:1px solid #000;
                                            ">
                                                Invoice
                                            </div>

                                            <table width="100%" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td style="padding:7px !important;">Number:</td>
                                                    <td align="right">
                                                        <strong>{{ $document->invoice_order_no ?? '-' }}</strong>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td style="padding:7px !important;">Date:</td>
                                                    <td align="right">
                                                        <strong>
                                                            {{ $document->invoice_order_date ? \Carbon\Carbon::parse($document->invoice_order_date)->format('d-m-Y') : '-' }}
                                                        </strong>
                                                    </td>
                                                </tr>

                                                @if (!empty($document->sales_order?->po_number))
                                                    <tr>
                                                        <td>PO No:</td>
                                                        <td align="right">
                                                            <strong>{{ $document->sales_order->po_number }}</strong>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>

                                        </td>
                                    </tr>
                                </table>

                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </thead>

        <!-- Main Content -->
        <tbody>
            <tr id="tr-items-table">
                <td style="padding: 0;">
                    <!-- Invoice Details -->


                    <!-- Items Table -->
                    <div class="items-wrapper" id="items-wrapper"
                        style="min-height: 480px !important; border-left: 1px solid #000;border-right: 1px solid #000;border-bottom: 1px solid #000;margin-top:-8px !important;">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th
                                        style="text-align: left; width: 30%;border-top: 1px solid #000 !important; border-left: none !important;">
                                        Item</th>
                                    <th style="width: 10%;border-top: 1px solid #000 !important;">HSN</th>
                                    <th style="width: 8%;border-top: 1px solid #000 !important;">Unit</th>
                                    <th style="width: 8%;border-top: 1px solid #000 !important;">Qty</th>
                                    <th style="width: 10%;border-top: 1px solid #000 !important;">Rate</th>
                                    <th style="width: 12%;border-top: 1px solid #000 !important;">Amount</th>
                                    <th style="width: 8%;border-top: 1px solid #000 !important;">GST%</th>
                                    <th
                                        style="width: 14%;border-top: 1px solid #000 !important; border-right: none !important;">
                                        Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $sub_total = 0;
                                    $total_qty = 0;
                                    $counter = 1;
                                    $total_gst_amount = 0;
                                    $total_gst_percentage = 0;
                                    $total_gst_amount_new = 0;
                                    $is_igst = 0;
                                @endphp
                                @forelse($Invoice->invoice_items as $item)
                                    @php
                                        $quantity = $item->quantity ?? 1;
                                        $cgst_amount = floatval($item->cgst_amount ?? 0);
                                        $sgst_amount = floatval($item->sgst_amount ?? 0);
                                        $gst_amount = floatval($item->gst_amount ?? 0);

                                        $cgst_per = floatval($item->cgst_in_percentage ?? 0);
                                        $sgst_per = floatval($item->sgst_in_percentage ?? 0);
                                        $gst_per = floatval($item->gst_in_percentage ?? 0);

                                        // Default
                                        $row_gst_amount = 0;
                                        $row_gst_percentage = 0;

                                        // ✔ Condition: If both CGST and SGST exist, use them
                                        if ($cgst_amount > 0 && $sgst_amount > 0) {
                                            $row_gst_amount = $cgst_amount + $sgst_amount;
                                            $row_gst_percentage = $cgst_per + $sgst_per;
                                        } else {
                                            $is_igst = 1;
                                            $row_gst_amount = $gst_amount;
                                            $row_gst_percentage = $gst_per;
                                        }

                                        $amount = $item->amount ?? 0;

                                        // Add totals
                                        $total_qty += $quantity;
                                        $total_gst_amount_new += $row_gst_amount;
                                        $total_gst_percentage = $row_gst_percentage;
                                        $total_gst_amount = $amount + ($amount * $row_gst_percentage) / 100;
                                        $sub_total += $amount;
                                    @endphp
                                    <tr>
                                        <td
                                            style="padding-left: 6px !important; border-left: none !important; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; line-height: 1.5;">
                                            <strong>{{ $counter++ }}) {{ $item->product_code ?? '-' }} :</strong>
                                            {{ $item->product_name ?? ($item->product_description ?? '-') }}
                                        </td>
                                        <td
                                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                            {{ $item->hsn_code ?? '-' }}</td>
                                        <td
                                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                            {{ $item->unit ?? '-' }}</td>
                                        <td
                                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                            {{ strtolower($item->unit) === 'kg'
                                                ? number_format($item->quantity ?? 0, 3, '.', '')
                                                : (int) ($item->quantity ?? 0) }}
                                        </td>
                                        <td
                                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                            {{ $item->price_in_amount ?? '' }}</td>
                                        <td
                                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">
                                            {{ $item->amount ?? '' }}</td>
                                        <td
                                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                                            {{ number_format($total_gst_percentage, 2) }}</td>
                                        <td
                                            style="padding: 12px 10px; border-right: none !important; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">
                                            {{ number_format($total_gst_amount, 2) }} </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No invoice items found.</td>
                                    </tr>
                                @endforelse

                                <!-- Total Qty Row -->
                                <tr class="total-qty-row">
                                    <td colspan="3" style="text-align: right; border-left: none !important;">Total
                                        Qty</td>
                                    <td style="text-align: center;">{{ $total_qty ?? '' }}</td>
                                    <td colspan="4" style="border-right: none !important;"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Bottom Section -->

                </td>
            </tr>

            <tr class="final-footer-row" id="final-footer-row">
                <td colspan="100%" style="padding:0;">

                    <table class="bottom-section no-break" style="width: 100%;margin-top: -5px !important;">
                        <tr>
                            <td
                                style="width: 55%; border-right: 1px solid #000; vertical-align: top;padding:0px !important;border-top: none !important;">

                                <div
                                    style="min-height: 70px !important; margin-bottom: 10px; padding:0px !important; border-bottom: 1px solid #000;">
                                    <p
                                        style="margin-bottom:5px !important; font-size: 17px; font-weight: bold;margin-left:10px !important;margin-top:5px !important;">
                                        Net Amount In
                                        Words</p>
                                    <p
                                        style="margin-bottom:5px !important; font-size: 14px; font-weight: bold;margin-left:10px !important;">
                                        {{ App\Helpers\Helper::convertToRupees($document->grand_total) }}
                                    </p>
                                </div>


                                <div
                                    style="margin-bottom: 10px; padding:0px !important;padding-bottom: 4px !important; border-bottom: 1px solid #000;">
                                    <p
                                        style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold;margin-left:10px !important;margin-top:5px !important;">
                                        Bank Details</p>
                                    <div style="margin-left:16px !important;font-size: 10px !important;">
                                        <table style="width: 100%; ">
                                            {!! str_replace('</p>', '</p>' . PHP_EOL, $document->company->bank_details ?? '-') !!}
                                        </table>
                                    </div>
                                </div>


                                <div style="margin-left:10px !important;margin-bottom:5px !important;">
                                    <p
                                        style="margin: 0 0 8px 0; padding:0px !important; font-size: 13px; font-weight: bold;margin-top:5px !important;">
                                        Terms and Conditions
                                    </p>
                                    <ol style="margin: 0; padding-left: 18px; font-size: 10px; line-height: 1;">
                                        <li>Good once sold cannot be taken back.</li>
                                        <li>We are not responsible for any breakage, theft or damage.</li>
                                        <li>Interest 24% P.A. will be charged after 5 DAYS of payment due date.</li>
                                        <li>Subject to Jamnagar Jurisdiction.</li>
                                        <li>IF YOU REQUIRE GST BENEFIT IN UNDER NOTIFICATION NO. 41/2017 SO WE REQUIRE
                                            BL COPY WITHIN 60 DAYS. IF YOU DO NOT PROVIDE BL COPY, OUR COMPANY WILL
                                            CHARGE
                                            18% GST PLUS DELAY INTEREST 2% PER DAY.</li>
                                    </ol>
                                </div>
                            </td>

                            <td
                                style="width: 45%; vertical-align: top;padding:0px !important;border-top: none !important;">

                                <table
                                    style="width: 100%; font-size: 14px;margin-top:5px !important;margin-bottom:5px !important;">
                                    <tr>
                                        <td
                                            style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                            Basic Amount</td>
                                        <td
                                            style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                            <strong>₹
                                                {{ $sub_total ?? '' }}</strong></td>
                                    </tr>

                                    @if (!empty($document->packing_forwarding_charge))
                                        <tr>
                                            <td
                                                style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                                Packing & Forwarding Charge</td>
                                            <td
                                                style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹
                                                    {{ $document->packing_forwarding_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif

                                    @if (!empty($document->transport_charge))
                                        <tr>
                                            <td
                                                style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                                Transport</td>
                                            <td
                                                style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹
                                                    {{ $document->transport_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif

                                    @if (!empty($document->freight_charge))
                                        <tr>
                                            <td
                                                style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                                Freight</td>
                                            <td
                                                style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹
                                                    {{ $document->freight_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif


                                    @if ($is_igst == 1)
                                        <tr>
                                            <td
                                                style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                                IGST</td>
                                            <td
                                                style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($document->gst_amount, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td
                                                style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                                CGST</td>
                                            <td
                                                style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td
                                                style="padding: 10px 6px !important; text-align: left;border: none !important;">
                                                SGST</td>
                                            <td
                                                style="padding: 10px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @endif

                                    <tr
                                        style="border-top: 1px solid #000; border-bottom: 1px solid #000;margin-top:19px !important;">
                                        <td
                                            style="padding: 12px 6px !important; text-align: left; font-size: 15px;margin-bottom:10px !important;margin-top:10px !important;border-left: none !important;border-right: none !important;">
                                            <strong>Net
                                                Amount</strong></td>
                                        <td
                                            style="padding: 12px 6px !important; text-align: right; font-size: 16px; font-weight: bold;margin-bottom:10px !important;margin-top:10px !important;border-left: none !important;border-right: none !important;">
                                            ₹ {{ $document->grand_total }}
                                        </td>
                                    </tr>
                                </table>


                                <div style="text-align:center; margin-top:10px;">
                                    <div
                                        style="margin:0 auto; width:100%; height:100px; display: flex; align-items: center; justify-content: center;">
                                        @if ($document->company && $document->company->authorised_signature_url)
                                            <img style="max-width: 700px; max-height: 500px; object-fit: contain;"
                                                src="{{ $document->company->authorised_signature_url }}" />
                                        @endif
                                    </div>
                                </div>


                                <div style="border-top: 1px solid #000; padding: 5px 5px; margin-top: 10px;">
                                    <p
                                        style="font-size: 17px; font-weight: bold; text-align: center; padding-top: 10px !important;">
                                        Authorised Signature
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>


                    <div class="footer-box">

                        <div class="footer-white">
                            <span><b>✉</b> info@shalibhadrainternational.com</span>
                            <span><b>📞</b> +91 73832 38373 | +91 90333 41444</span>
                            <span><b>🌐</b> www.shalibhadrainternational.com</span>
                        </div>

                        <div class="footer-black">

                            <div class="footer-red">
                                <span class="loc">📍</span>
                                Plot No.4536, GIDC Phase - 3, Dared, Jamnagar-361005, Gujarat, India.
                            </div>
                        </div>

                    </div>

                </td>
            </tr>
        </tbody>

        <!-- Footer - Will repeat on every page -->

        {{-- <tfoot class="page-footer">
            <tr>
                <td>

                    <table class="bottom-section no-break" style="width: 100%;">
                        <tr>
                            <td style="width: 55%; border-right: 1px solid #000; vertical-align: top;padding:0px !important;border-top: none !important;">

                                <div style="margin-bottom: 10px; padding:0px !important; border-bottom: 1px solid #000;">
                                    <p style="margin-bottom:5px !important; font-size: 17px; font-weight: bold;margin-left:10px !important;margin-top:5px !important;">Net Amount In
                                        Words</p>
                                    <p style="margin-bottom:5px !important; font-size: 14px; font-weight: bold;margin-left:10px !important;">
                                        {{ App\Helpers\Helper::convertToRupees($document->grand_total) }}
                                    </p>
                                </div>


                                <div style="margin-bottom: 10px; padding:0px !important;padding-bottom: 4px !important; border-bottom: 1px solid #000;">
                                    <p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold;margin-left:10px !important;margin-top:5px !important;">Bank Details</p>
                                    <div style="margin-left:16px !important;font-size: 11px !important;">
                                        <table style="width: 100%; ">
                                            {!! str_replace('</p>', '</p>' . PHP_EOL, $document->company->bank_details ?? '-') !!}
                                        </table>
                                    </div>
                                </div>


                                <div style="margin-left:10px !important;margin-bottom:5px !important;">
                                    <p
                                        style="margin: 0 0 8px 0; padding:0px !important; font-size: 13px; font-weight: bold;margin-top:5px !important;">
                                        Terms and Conditions
                                    </p>
                                    <ol style="margin: 0; padding-left: 18px; font-size: 11px; line-height: 1;">
                                        <li>Good once sold cannot be taken back.</li>
                                        <li>We are not responsible for any breakage, theft or damage.</li>
                                        <li>Interest 24% P.A. will be charged after 5 DAYS of payment due date.</li>
                                        <li>Subject to Jamnagar Jurisdiction.</li>
                                        <li>IF YOU REQUIRE GST BENEFIT IN UNDER NOTIFICATION NO. 41/2017 SO WE REQUIRE
                                            BL COPY WITHIN 60 DAYS. IF YOU DO NOT PROVIDE BL COPY, OUR COMPANY WILL
                                            CHARGE
                                            18% GST PLUS DELAY INTEREST 2% PER DAY.</li>
                                    </ol>
                                </div>
                            </td>

                            <td style="width: 45%; vertical-align: top;padding:0px !important;border-top: none !important;">

                                <table style="width: 100%; font-size: 14px;margin-top:5px !important;margin-bottom:5px !important;">
                                    <tr>
                                        <td style="padding: 8px 6px !important; text-align: left;border: none !important;">Basic Amount</td>
                                        <td style="padding: 8px 6px !important; text-align: right;border: none !important;"><strong>₹
                                                {{ $sub_total ?? '' }}</strong></td>
                                    </tr>

                                    @if (!empty($document->packing_forwarding_charge))
                                        <tr>
                                        <td style="padding: 8px 6px !important; text-align: left;border: none !important;">Packing & Forwarding Charge</td>
                                        <td style="padding: 8px 6px !important; text-align: right;border: none !important;"><strong>₹
                                        {{ $document->packing_forwarding_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif

                                    @if (!empty($document->transport_charge))
                                        <tr>
                                        <td style="padding: 8px 6px !important; text-align: left;border: none !important;">Transport</td>
                                        <td style="padding: 8px 6px !important; text-align: right;border: none !important;"><strong>₹
                                        {{ $document->transport_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif

                                    @if (!empty($document->freight_charge))
                                        <tr>
                                        <td style="padding: 8px 6px !important; text-align: left;border: none !important;">Freight</td>
                                        <td style="padding: 8px 6px !important; text-align: right;border: none !important;"><strong>₹
                                        {{ $document->freight_charge ?? '' }}</strong></td>
                                        </tr>
                                    @endif


                                    @if ($is_igst == 1)
                                        <tr>
                                            <td style="padding: 8px 6px !important; text-align: left;border: none !important;">IGST</td>
                                            <td style="padding: 8px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($document->gst_amount, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td style="padding: 8px 6px !important; text-align: left;border: none !important;">CGST</td>
                                            <td style="padding: 8px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 6px !important; text-align: left;border: none !important;">SGST</td>
                                            <td style="padding: 8px 6px !important; text-align: right;border: none !important;">
                                                <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                            </td>
                                        </tr>
                                    @endif

                                    <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;margin-top:19px !important;">
                                        <td style="padding: 12px 6px !important; text-align: left; font-size: 15px;margin-bottom:10px !important;margin-top:10px !important;border-left: none !important;border-right: none !important;"><strong>Net
                                                Amount</strong></td>
                                        <td
                                            style="padding: 12px 6px !important; text-align: right; font-size: 16px; font-weight: bold;margin-bottom:10px !important;margin-top:10px !important;border-left: none !important;border-right: none !important;">
                                            ₹ {{ $document->grand_total }}
                                        </td>
                                    </tr>
                                </table>


                                <div style="text-align:center; margin-top:-20px; ">
                                    <div style="margin:0 auto; width:180px; height:200px;">
                                            <img style="max-width: 200px; max-height: 100px; object-fit: contain;" src="data:image/jpeg;base64,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AKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACqt3bi7jlhlRJIXG0o4yGHcEVapCM0nsB8Q+O/2b/iZ8LvjFe+IvgtHZWOnatb/wClpIBtVyzfjxweBWh4E/YBTxNet4h+LmvXHizWbr95NaliIUJ/hB4OBxX2U0W6QNtXIGNx60sabWb7xz3JzX0P9uYxRShJJ2tdLVpeZl7ON7nMeCfhvofw60aHSvD2m2+m2UQ2qsSgEj0J710UduUfdwBjkdasUV4FRyqzc6rcm+5fKkIQe1GDTqKlq5QUUUUwCiiigBhBJ4o2mn0UuVdh3GBTn0oZSRwfzFPopcqEQrE+0g4U542UhjfGBtx6nmp6SqWgxgRsYODR5QLbsAN2NSUUbiIzHg8AfUmkEPzbv4vrUtFJq6sBB9mzIzEgqeSMf1zQkDKzEtuB+6CPu/jU9FDV7agRJGyjbuJHqTzShWC44496filpWAiWIqxPc9TUgFLRSjBRAKKKKsAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACiiigAooooAKKKKACmnrRRUS2ATNOBoorOO4xaKKK3EFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFABRRRQAUUUUAFFFFAH//2Q=="/>
                                        </div>
                                    </div>


                                <div style="border-top: 1px solid #000; padding: 5px 5px; margin-top: 10px;">
                                    <p
                                        style="font-size: 17px; font-weight: bold; text-align: center; padding-top: 10px !important;">
                                        Authorised Signature
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class="footer-box">

                        <div class="footer-white">
                            <span><b>✉</b> info@shalibhadrainternational.com</span>
                            <span><b>📞</b> +91 73832 38373 | +91 90333 41444</span>
                            <span><b>🌐</b> www.shalibhadrainternational.com</span>
                        </div>

                        <div class="footer-black">

                            <div class="footer-red">
                                <span class="loc">📍</span>
                                Plot No.4536, GIDC Phase - 3, Dared, Jamnagar-361005, Gujarat, India.
                            </div>
                        </div>

                    </div>
                </td>
            </tr>
        </tfoot> --}}


    </table>
@else
    {{-- View Mode Layout --}}
    <div
        style="width: 100%; max-width: 1200px;margin: 0 auto !important; background-color: white; border: 3px solid #000;">

        <!-- Header Section -->
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td
                    style="width: 200px; padding: 20px; border-bottom: 1px solid #000; text-align: center; vertical-align: middle;">
                    <div
                        style="width: 100px; height: 100px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                        @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                            <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                                style="height:40px;width:180px;object-fit: contain;padding-left: 55px;" />
                        @else
                            <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                                style="height:40px;width:180px;object-fit: contain;padding-left: 55px;" />
                        @endif
                    </div>
                </td>
                <td style="padding: 15px 20px; border-bottom: 1px solid #000; vertical-align: top;">
                    <h2 style="margin: 0 0 12px 0; font-size: 26px; font-weight: bold;">
                        {{ $document->company->company_name ?? '-' }}</h2>
                    <p style="margin: 0; font-size: 14px; line-height: 1.6; text-align: left;">
                        {!! str_replace('</p>', '</p>' . PHP_EOL, $part1 ?? '-') !!}
                        <br />
                        Phone: {{ $document->company->whatsapp_number ?? '-' }}<br>
                        Email: {{ $document->company->email ?? '-' }}<br>
                    </p>
                </td>
                <td style="width: 350px; padding: 15px 20px; border-bottom: 1px solid #000; vertical-align: bottom;">
                    <p style="margin:0; font-size:13px; line-height:1.8; text-align:right;font-weight: bold;">

                        {!! str_replace('</p>', '</p>' . PHP_EOL, $part2 ?? '-') !!}
                    </p>

                </td>
            </tr>
        </table>

        <!-- Billed To and Invoice Section -->
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td
                    style="width: 60%; padding: 0; border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align: top;">
                    <div style="padding: 15px;">
                        <div style="display: flex;">
                            <p style="margin: 0 0 5px 0; font-size: 15px;font-weight: bold;margin-top: 4px; width: 90px;">
                                Billed To:</p>
                            <p
                                style="margin: 0; font-size: 14px; line-height: 1.8;padding-left: 20px;padding-top: -7px;">
                                {{ $document->customer_name ?? (optional($document->customer)->customer_name ?? '-') }}<br>
                                {!! nl2br(strip_tags($document->billing_address)) !!}
                                @if($document->customer)
                                    {{-- <br><b>City:</b> {{ $document->customer->city->name ?? '' }} <b>State:</b> {{ $document->customer->state->name ?? '' }} <b>Pincode:</b> {{ $document->customer->pincode ?? '' }} --}}
                                    , {{ $document->customer->city->name ?? '' }}, {{ $document->customer->state->name ?? '' }} - {{ $document->customer->pincode ?? '' }}
                                @endif
                            </p>
                        </div>
                        <div style="display: flex;">
                            <p style="margin: 0 0 5px 0; font-size: 15px;font-weight: bold;margin-top: 4px; width: 90px;">
                                GSTIN:</p>
                            <p style="margin: 0; font-size: 14px; line-height: 1.8;padding-left: 20px;padding-top: -7px;">
                                {{ $document->gst_no ?? '-' }}
                            </p>
                        </div>
                        @php
                            $shipAddress = !empty($document->shipping_address) ? $document->shipping_address : $document->billing_address;
                            $shipGst = !empty($document->shipping_gst_no) ? $document->shipping_gst_no : ($document->gst_no ?? '');
                        @endphp
                        @if (!empty($shipAddress))
                            <div style="display: flex; margin-top: 5px;">
                                <p style="margin: 0 0 5px 0; font-size: 15px;font-weight: bold;margin-top: 4px; width: 90px;">
                                    Shipped To:</p>
                                <p style="margin: 0; font-size: 14px; line-height: 1.8;padding-left: 20px;padding-top: -7px;">
                                    {!! nl2br(strip_tags($shipAddress)) !!}
                                </p>
                            </div>
                            @if (!empty($shipGst))
                                <div style="display: flex;">
                                    <p style="margin: 0 0 5px 0; font-size: 15px;font-weight: bold;margin-top: 4px; width: 90px;">
                                        GSTIN:</p>
                                    <p style="margin: 0; font-size: 14px; line-height: 1.8;padding-left: 20px;padding-top: -7px;">
                                        {{ $shipGst }}
                                    </p>
                                </div>
                            @endif
                        @endif
                        <p style="margin: 0 0 1px 0; font-size: 15px;">&nbsp;&nbsp;</p>
                        @if (!empty($document->transport_detail->name))
                            <p style="margin: 0 0 -11px 0; font-size: 15px;"><strong
                                    style="font-weight: bold;">Transporter:</strong> &nbsp;&nbsp;
                                {{ $document->transport_detail->name ?? '-' }}</p>
                        @endif
                        <p style="margin: 0 0 3px 0; font-size: 15px;">&nbsp;&nbsp;</p>
                        @if (!empty($document->transport_detail->transport_gst_id))
                            <p style="margin: 0 0 -11px 0; font-size: 15px;"><strong
                                    style="font-weight: bold;">Transporter ID/GSTIN:</strong> &nbsp;&nbsp;
                                {{ $document->transport_detail->transport_gst_id ?? '-' }}</p>
                        @endif
                    </div>
                </td>
                <td style="width: 40%; padding: 0; border-bottom: 1px solid #000; vertical-align: top;">
                    <div style="padding: 12px 15px; border-bottom: 1px solid #000; text-align: center;">
                        <strong style="font-size: 15px;font-weight: bold;">Invoice</strong>
                    </div>
                    <table style="width: 100%; font-size: 15px; margin: 0;">
                        <tr>
                            <td style="padding: 10px 15px;">Number:</td>
                            <td style="padding: 10px 15px; text-align: right;font-weight: bold;">
                                {{ $document->invoice_order_no ?? '-' }}</td>
                        </tr>
                        @if (!empty($document->eway_bill_no))
                            <tr>
                                <td style="padding: 10px 15px;">E-way bill no:</td>
                                <td style="padding: 10px 15px; text-align: right;font-weight: bold;">
                                    {{ $document->eway_bill_no }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="padding: 10px 15px;">Date:</td>
                            <td style="padding: 10px 15px; text-align: right;font-weight: bold;">
                                {{ $document->invoice_order_date ? \Carbon\Carbon::parse($document->invoice_order_date)->format('d-m-Y') : '-' }}
                            </td>
                        </tr>
                        @if (!empty($document->sales_order?->po_number))
                            <tr>
                                <td style="padding: 10px 15px;">PO NO.:</td>
                                <td style="padding: 10px 15px; text-align: right; font-weight: bold;">
                                    {{ $document->sales_order->po_number }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>


        <!-- Items Table -->
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f0f0f0;">
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: left;">
                        Item</th>
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: center; width: 100px;">
                        HSN</th>
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: center; width: 100px;">
                        Unit</th>
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: center; width: 100px;">
                        Qty</th>
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: center; width: 80px;">
                        Rate</th>
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: right; width: 100px;">
                        Amount</th>
                    <th
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: center; width: 70px;">
                        GST%</th>
                    <th
                        style="padding: 12px 10px; border-bottom: 1px solid #000; font-size: 14px; font-weight: bold; text-align: right; width: 100px;">
                        Total</th>
                </tr>
            </thead>
            <tbody>

                @php
                    $sub_total = 0;
                    $total_qty = 0;
                    $counter = 1;
                    $total_gst_amount = 0;
                    $total_gst_percentage = 0;
                    $is_igst = 0;

                @endphp
                @forelse($Invoice->invoice_items as $item)
                    @php
                        $quantity = $item->quantity ?? 1;
                        $cgst_amount = floatval($item->cgst_amount ?? 0);
                        $sgst_amount = floatval($item->sgst_amount ?? 0);
                        $gst_amount = floatval($item->gst_amount ?? 0);

                        $cgst_per = floatval($item->cgst_in_percentage ?? 0);
                        $sgst_per = floatval($item->sgst_in_percentage ?? 0);
                        $gst_per = floatval($item->gst_in_percentage ?? 0);

                        // Default
                        $row_gst_amount = 0;
                        $row_gst_percentage = 0;

                        // ✔ Condition: If both CGST and SGST exist, use them
                        if ($cgst_amount > 0 && $sgst_amount > 0) {
                            $row_gst_amount = $cgst_amount + $sgst_amount;
                            $row_gst_percentage = $cgst_per + $sgst_per;
                        } else {
                            $is_igst = 1;
                            $row_gst_amount = $gst_amount;
                            $row_gst_percentage = $gst_per;
                        }

                        $amount = $item->amount ?? 0;

                        // Add totals
                        $total_qty += $quantity;
                        //$total_gst_amount = $row_gst_amount;
                        $total_gst_percentage = $row_gst_percentage;
                        $total_gst_amount = $amount + ($amount * $row_gst_percentage) / 100;
                        $sub_total += $amount;
                    @endphp
                    <tr>
                        <td
                            style="padding: 12px 10px; padding-left:5px !important; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; line-height: 1.5;">
                            <strong>{{ $counter++ }}) {{ $item->product_code ?? '-' }} :</strong>
                            {{ $item->product_name ?? ($item->product_description ?? '-') }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                            {{ $item->hsn_code ?? '-' }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                            {{ $item->unit ?? '-' }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                            {{ strtolower($item->unit) === 'kg'
                                ? number_format($item->quantity ?? 0, 3, '.', '')
                                : (int) ($item->quantity ?? 0) }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                            {{ $item->price_in_amount ?? '' }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">
                            {{ $item->amount ?? '' }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 13px; text-align: center;">
                            {{ number_format($total_gst_percentage, 2) }}
                        </td>

                        <td
                            style="padding: 12px 10px; border-bottom: 1px solid #000; font-size: 13px; text-align: right;">
                            {{ number_format($total_gst_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No invoice items found.</td>
                    </tr>
                @endforelse

                <!-- Empty rows for spacing -->
                <tr style="height: 250px;">
                    <td colspan="8"
                        style="border-right: 1px solid #000;border-bottom: 1px solid #000;border-right: none;"></td>
                </tr>
                <tr>
                    <td colspan="3"
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; text-align: right; font-weight: bold;">
                        Total Qty</td>
                    <td
                        style="padding: 12px 10px; border-right: 1px solid #000; border-bottom: 1px solid #000; font-size: 14px; text-align: center; font-weight: bold;">
                        {{ $total_qty ?? '' }}</td>
                    <td colspan="5" style="border-bottom: 1px solid #000;"></td>
                </tr>
            </tbody>
        </table>

        <!-- Bottom Section -->


        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 55%; border-right: 1px solid #000; vertical-align: top;">
                    <!-- Net Amount in Words -->
                    <div style="margin-bottom: 10px; padding: 5px 10px;border-bottom: 1px solid #000;">
                        <p style="margin: 0 0 5px 0; font-size: 14px; font-weight: bold;">Net Amount In
                            Words</p>
                        <p style="margin: 0 0 3px 0; font-size: 13px; font-weight: bold;">
                            {{ App\Helpers\Helper::convertToRupees($document->grand_total) }}</p>
                    </div>

                    <!-- Bank Details -->
                    <div style="margin-bottom: 10px; padding: 0px 10px;border-bottom: 1px solid #000;">
                        <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: bold;">Bank Details</p>
                        <table style="width: 100%; font-size: 10px; line-height: 1.7;">
                            {!! str_replace('</p>', '</p>' . PHP_EOL, $document->company->bank_details ?? '-') !!}
                        </table>
                    </div>

                    <!-- Terms and Conditions -->
                    <div>
                        <p style="margin: 0 0 8px 0; padding: 0px 10px; font-size: 12px; font-weight: bold;">
                            Terms and Conditions</p>
                        <ol style="margin: 0; padding-left: 18px; font-size: 10px; line-height: 1.9;">
                            <li>Good once sold cannot be taken back.</li>
                            <li>We are not responsible for any breakage, theft or damage.</li>
                            <li>Interest 24% P.A. will be charged after 5 DAYS of payment due date.</li>
                            <li>Subject to Jamnagar Jurisdiction.</li>
                            <li>IF YOU REQUIRE GST BENEFIT IN UNDER NOTIFICATION NO. 41/2017 SO WE REQUIRE
                                BL COPY WITH IN 60 DAYS. IF YOU NOT PROVIDE ME BL COPY OUR COMPANY CHARGED
                                18% GST PLUS DELAY INTEREST 2% DAY TO DAY.</li>
                        </ol>
                    </div>
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <!-- Amount Summary -->
                    <table style="width: 100%; font-size: 14px;height:160px;">
                        <tr>
                            <td style="padding: 5px 12px; text-align: left;">Basic Amount</td>
                            <td style="padding: 5px 12px; text-align: right;"><strong>₹
                                    {{ $sub_total ?? '' }}</strong></td>
                        </tr>
                        @if (!empty($document->packing_forwarding_charge))
                            <tr>
                                <td style="padding: 5px 12px; text-align: left;">Packing & Forwarding Charge</td>
                                <td style="padding: 5px 12px; text-align: right;"><strong>₹
                                        {{ $document->packing_forwarding_charge ?? '' }}</strong></td>
                            </tr>
                        @endif
                        @if (!empty($document->transport_charge))
                            <tr>
                                <td style="padding: 5px 12px; text-align: left;">Transport</td>
                                <td style="padding: 5px 12px; text-align: right;"><strong>₹
                                        {{ $document->transport_charge ?? '' }}</strong></td>
                            </tr>
                        @endif
                        @if (!empty($document->freight_charge))
                            <tr>
                                <td style="padding: 5px 12px; text-align: left;">Freight</td>
                                <td style="padding: 5px 12px; text-align: right;"><strong>₹
                                        {{ $document->freight_charge ?? '' }}</strong></td>
                            </tr>
                        @endif
                        @if ($is_igst == 1)
                            <tr>
                                <td style="padding: 5px 12px; text-align: left;">IGST</td>
                                <td style="padding: 5px 12px; text-align: right;">
                                    <strong>₹ {{ number_format($document->gst_amount, 2) }}</strong>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td style="padding: 5px 12px; text-align: left;">CGST</td>
                                <td style="padding: 5px 12px; text-align: right;">
                                    <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 12px; text-align: left;">SGST</td>
                                <td style="padding: 5px 12px; text-align: right;">
                                    <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr style="border-top: 1px solid #000;border-bottom: 1px solid #000; padding: 5px 10px;">
                            <td style="padding: 5px 12px; text-align: left; font-size: 15px;"><strong>Net
                                    Amount</strong></td>
                            <td style="padding: 5px 12px; text-align: right; font-size: 16px;font-weight: bold;">
                                ₹ {{ $document->grand_total }}</td>
                        </tr>
                    </table>


                    <!-- Company Name and Signature -->
                    <div style="text-align:center; margin-top:10px;">
                        <div style="margin:0 auto; width:100%; height:120px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                            @if ($document->company && $document->company->authorised_signature_url)
                                <img style="max-width:100%; max-height:100%; object-fit:contain;"
                                    src="{{ $document->company->authorised_signature_url }}" />
                            @endif
                        </div>
                    </div>

                    <div style="border-top: 1px solid #000;padding: 5px 5px;bottom: 20px;">
                        <p
                            style="/* padding: 5px 7px; */font-size: 14px;font-weight: bold;text-align: center;margin-top: 10px;">
                            Authorised Signature</p>
                    </div>
                </td>
            </tr>
        </table>

    </div>

@endif
