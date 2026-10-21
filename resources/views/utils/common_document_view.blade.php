@php
    // Determine document fields based on type
    if ($documentType === 'sales_order') {
        $documentNoField = 'sales_order_no';
        $documentDateField = 'sales_order_date';
        $documentTitle = 'Sales Order';
        $itemsRelation = $itemRelation ?? 'sales_order_items';
        $withoutPriceKey = $withoutPriceKey ?? 'sales_without_price';
    } else {
        $documentNoField = 'invoice_order_no';
        $documentDateField = 'invoice_order_date';
        $documentTitle = 'Invoice';
        $itemsRelation = $itemRelation ?? 'invoice_items';
        $withoutPriceKey = $withoutPriceKey ?? 'invoice_without_price';
    }

    $items = $document->{$itemsRelation} ?? collect([]);
    $borderColor = $borderColor ?? (optional($document->company)->order_view_color ?? '#266BEE');
    $printMode = $isPrint ?? false;
    $showPrice = $canViewPrice ?? false;

    $authDetail = $modules['authLoginUserDetail'] ?? (Auth::guard('admin_software')->user() ?? Auth::guard('team_person')->user());
    $isDhruvik = false;
    if ($authDetail) {
        $userCompanyId = (int)($authDetail->company_id ?? ($modules['company_id'] ?? 0));
        $userTeamRoleId = (int)($authDetail->team_role_id ?? 0);
        $userParentTypeId = (int)($authDetail->parent_type_id ?? 0);
        $authName = strtoupper($authDetail->name ?? '');
        $authUsername = strtoupper($authDetail->username ?? '');
        $authEmpCode = strtoupper($authDetail->employee_code ?? '');

        // 1. Shalibhadra First Team Person / Head (team_role_id = 2 OR team_person id = 2 OR EMP-001)
        // 2. OR Name / Username contains DHRUVIK
        // 3. OR Super Admin (admin_software)
        if (
            (Auth::guard('admin_software')->check() && !Auth::guard('team_person')->check()) ||
            ($userCompanyId == 2 && ($userTeamRoleId == 2 || (int)$authDetail->id == 2 || $authEmpCode === 'EMP-001')) ||
            str_contains($authName, 'DHRUVIK') ||
            str_contains($authUsername, 'DHRUVIK')
        ) {
            $isDhruvik = true;
        }
    }

    $raw = $document->company->address;
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);

    $part1 = $parts[0] ?? '';
    $part2 = $parts[1] ?? '';
@endphp

{{-- View Mode Layout --}}
@if ($printMode)
    <div class="print-page">

        <div class="card invoice-preview-card">
            {{-- <div class="card-body invoice-preview-header rounded"
                style="height: 205px; display: flex; justify-content: center; align-items: center;">
                @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                    style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                @else
                <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                    style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                @endif
            </div> --}}
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 300px; padding: 20px; text-align: center; vertical-align: middle;">
                        <div
                            style="width: 100px; height: 100px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                                <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                                    style="height:60px;width:260px;object-fit: contain;padding-left: 20px;" />
                            @else
                                <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                                    style="height:60px;width:260px;object-fit: contain;padding-left: 20px;" />
                            @endif
                        </div>
                    </td>
                    <td style="padding: 15px 20px; vertical-align: top;">
                        <h2 style="margin: 0 0 12px 0; font-size: 22px; font-weight: bold;">
                            {{ $document->company->company_name ?? '-' }}
                        </h2>
                        <p style="margin: 0; font-size: 16px; line-height: 1.6; text-align: left;">
                            {!! str_replace('</p>', '</p>' . PHP_EOL, $part1 ?? '-') !!}
                            <br />
                            Phone: {{ $document->company->whatsapp_number ?? '-' }}<br>
                            Email: {{ $document->company->email ?? '-' }}<br>
                        </p>
                    </td>
                    <td style="width: 320px; padding: 15px 20px; vertical-align: bottom;">
                        <p style="margin:0; font-size:16px; line-height:1.5; text-align:right;font-weight: bold;">

                            {!! str_replace('</p>', '</p>' . PHP_EOL, $part2 ?? '-') !!}
                        </p>

                    </td>
                </tr>
            </table>
            <div class="card-body p-0">

                {{-- Main Invoice Table Structure --}}
                {{-- Main Invoice Table Structure --}}
                <table class="invoice-table" style="width: 100%; border-collapse: collapse;">

                    {{-- Header Section with Title --}}
                    <thead>
                        <tr>
                            <td colspan="9"
                                style="background-color: #d3d3d3; text-align: center; padding: 10px; font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; font-size: 25px !important;">
                                {{ $documentTitle }}
                            </td>
                        </tr>
                    </thead>

                    {{-- From/To Header Section --}}
                    <tbody>
                        <tr>
                            <td colspan="3"
                                style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 14px; vertical-align: top; width: 40%; font-size: 16px; line-height: 1.6;">
                                <strong style="display: block; margin-bottom: 10px; font-size: 16px">Customer
                                    Detail</strong>
                                <strong style="display: block; font-size: 16px"><b>Customer Code :
                                    </b>{!! $document->customer?->client_code ?? 'N/A' !!}</strong>
                                @if ($showPrice)
                                    <div style="margin-top: 6px;"><b>Customer Name :
                                        </b>{{ $document->customer_name ?? (optional($document->customer)->customer_name ?? '-') }}
                                    </div>

                                    @if ($document->phone)
                                        <div style="margin-top: 10px;"><b>Phone : </b>{{ $document->phone ?? 'N/A' }}
                                        </div>
                                    @endif
                                    @if ($document?->customer?->email)
                                        <div style="margin-top: 10px;"><b>Email :
                                            </b>{{ $document->customer->email ?? 'N/A' }}</div>
                                    @endif
                                    @if ($document?->customer?->city)
                                        <div style="margin-top: 10px;"><b>City :
                                            </b>{{ $document->customer->city->name ?? '-' }}</div>
                                    @endif
                                    @if ($document?->customer?->state)
                                        <div style="margin-top: 10px;"><b>State :
                                            </b>{{ $document->customer->state->name ?? '-' }}</div>
                                    @endif
                                    @if ($document?->customer?->pincode)
                                        <div style="margin-top: 10px;"><b>Pincode :
                                            </b>{{ $document->customer->pincode ?? '-' }}</div>
                                    @endif
                                @endif
                            </td>

                            <td colspan="3"
                                style="border-top: 1px solid #000; border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 14px; vertical-align: top; width: 33%; font-size: 16px; line-height: 1.6;">
                                    <div style="margin-top: 6px;">
                                        <strong>Billing Address:</strong><br>
                                        {{ $document->billing_address ?? '-' }}
                                        @if($document->customer)
                                            <br>City: {{ $document->customer->city->name ?? '' }} State:
                                            {{ $document->customer->state->name ?? '' }}, Pincode:
                                            {{ $document->customer->pincode ?? '' }}
                                        @endif
                                    </div>
                                    @php
                                        $soShipAddress = $document->shipping_address ?: $document->billing_address;
                                        $soShipGst = $document->shipping_gst_no ?: ($document->gst_no ?: ($document->customer?->gst_number ?? ''));
                                    @endphp
                                    @if(!empty($soShipAddress))
                                        <div style="margin-top: 10px;">
                                            <strong>Shipping Address:</strong><br>
                                            {{ $soShipAddress }}
                                            @if($document->customer && empty($document->shipping_customer_id))
                                                <br>City: {{ $document->customer->city->name ?? '' }} State:
                                                {{ $document->customer->state->name ?? '' }}, Pincode:
                                                {{ $document->customer->pincode ?? '' }}
                                            @endif
                                            @if(!empty($soShipGst))
                                                <br><strong>GSTIN:</strong> {{ $soShipGst }}
                                            @endif
                                        </div>
                                    @endif
                            </td>

                            <td colspan="3"
                                style="border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 14px; vertical-align: top; width: 33%; font-size: 16px; line-height: 1.6;">
                                <div style="margin-bottom: 10px;">
                                    <strong>Order No:</strong> {{ $document->{$documentNoField} ?? '-' }}
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <strong>Order Date:</strong>
                                    {{ $document->{$documentDateField} ? \Carbon\Carbon::parse($document->{$documentDateField})->format('d-m-Y') : '-' }}
                                </div>

                                @if ($showPrice)
                                    <div style="margin-bottom: 10px;">
                                        <strong>GSTIN:</strong> {{ $document->gst_no ?? '-' }}
                                    </div>
                                    @if ($document?->transport_by && $document?->transport_by?->name)
                                        <div style="margin-bottom: 10px;">
                                            <strong>Transport By:</strong> {{ $document->transport_by->name ?? '-' }}
                                        </div>
                                    @endif
                                    @if ($document?->transport_detail?->name)
                                        <div style="margin-bottom: 10px;">
                                            <strong>Transporter Detail:</strong>
                                            {{ $document->transport_detail->name ?? '-' }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>


                <table class="invoice-table" style="width: 100%; border-collapse: collapse; ">
                    <tr style="background-color: #d3d3d3;">
                        <td
                            style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px;width: 5%;">
                            <strong>Sr No.</strong>
                        </td>
                        <td
                            style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px;width: 10%;">
                            <strong>Product Code</strong>
                        </td>
                        <td
                            style=" border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px;width: 28%;">
                            <strong>Product Description</strong>
                        </td>
                        <td
                            style=" border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px;width: 7%;">
                            <strong>HSN</strong>
                        </td>
                        <td
                            style=" border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px;width: 7%;">
                            <strong>Unit</strong>
                        </td>
                        <td
                            style=" border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px;width: 7%;">
                            <strong>Qty</strong>
                        </td>
                        @if ($showPrice)
                            <td class="without_price"
                                style=" border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px; width: 8%;">
                                <strong>Price</strong>
                            </td>
                            <td class="without_price"
                                style=" border-bottom: 1px solid #000; padding: 10px; text-align: center; font-size: 16px; width: 10%;">
                                <strong>Taxable Amount</strong>
                            </td>
                        @endif
                    </tr>
                    <tbody>
                        {{-- Items Table Header --}}
                        @php
                            $minRows = count($items) > 5 ? count($items) : 13; // Minimum rows you want
                            $emptyRows = max(0, $minRows - count($items));
                        @endphp
                        @php
                            $sub_total = 0;
                            $counter = 1;
                            $total_gst_percentage = 0;
                            $total_gst_amount_new = 0;
                            $row_gst_amount = 0;
                            $row_gst_percentage = 0;
                            $is_igst = 0;
                        @endphp
                        @forelse($items as $item)
                            @php
                                $price = $item->price_in_amount ?? 0;
                                $discountPercent = $item->discount_percentage ?? 0;
                                $discountFlat = $item->discount_flat ?? 0;
                                $discountAmount = $price * ($discountPercent / 100);
                                $totalDiscount = $discountAmount + $discountFlat;
                                $rate = $price - $totalDiscount;
                                $quantity = $item->quantity ?? 1;
                                $amount = $rate * $quantity;
                                $sub_total += $amount;
                                $displayQuantity =
                                    strtolower($item->unit) === 'kg'
                                    ? number_format($item->quantity ?? 0, 3, '.', '')
                                    : (int) ($item->quantity ?? 0);

                                $cgst_amount = floatval($item->cgst_amount ?? 0);
                                $sgst_amount = floatval($item->sgst_amount ?? 0);
                                $gst_amount = floatval($item->gst_amount ?? 0);

                                $cgst_per = floatval($item->cgst_in_percentage ?? 0);
                                $sgst_per = floatval($item->sgst_in_percentage ?? 0);
                                $gst_per = floatval($item->gst_in_percentage ?? 0);

                                if ($cgst_amount > 0 && $sgst_amount > 0) {
                                    $row_gst_amount = $cgst_amount + $sgst_amount;
                                    $row_gst_percentage = $cgst_per + $sgst_per;
                                } else {
                                    $is_igst = 1;
                                    $row_gst_amount = $gst_amount;
                                    $row_gst_percentage = $gst_per;
                                }
                            @endphp
                            <tr>
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;">
                                    {{ $counter++ }}
                                </td>
                                @php
                                    $isCombo = (int) ($item->is_combo ?? 0) === 1;
                                    if ($isCombo) {
                                        $combo = \App\Models\ProductCombo::find($item->product_id);
                                        $displayPartNo = $combo ? $combo->part_no : ($item->product_code ?? '-');
                                        $displayProdName = $combo ? $combo->combo_name : ($item->product_name ?? ($item->product_description ?? '-'));
                                    } else {
                                        $productModel = $item->product ?? \App\Models\Product::find($item->product_id);
                                        $displayPartNo = $productModel ? $productModel->part_no : ($item->product_code ?? '-');
                                        $displayProdName = $productModel ? ($productModel->product_name ?: $productModel->product_description) : ($item->product_name ?? ($item->product_description ?? '-'));
                                    }

                                    $custInfo = '';
                                    $docCustomer = $document->customer ?? ($item->customer ?? null);
                                    if ($docCustomer && $docCustomer->customer_name) {
                                        $cItem = \App\Models\ProductCustomerItem::where('company_id', $document->company_id ?? $item->company_id)
                                            ->where('product_id', $item->product_id)
                                            ->where('customer_name', $docCustomer->customer_name)
                                            ->whereNull('deleted_at')
                                            ->first();
                                        if ($cItem) {
                                            $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                            $custDesc = trim($cItem->customer_item_description ?? '');
                                            $custParts = array_filter([$custCode, $custDesc]);
                                            $custInfo = implode(' ', $custParts);
                                        }
                                    }

                                    $displayCode = $displayPartNo;
                                    if ($isDhruvik && !empty($custInfo)) {
                                        $displayCode .= ' (' . $custInfo . ')';
                                    } elseif (empty($displayPartNo) || $displayPartNo === '-') {
                                        $displayCode = $item->product_code ?? '-';
                                        if (!$isDhruvik && !empty($displayCode)) {
                                            $displayCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayCode);
                                        }
                                    }
                                @endphp
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;">
                                    {{ $displayCode }}
                                </td>
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;">
                                    {{ $displayProdName }}
                                </td>
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;">
                                    {{ $item->hsn_code ?? '-' }}
                                </td>
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;">
                                    {{ $item->unit ?? '-' }}
                                </td>
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;">
                                    {{ $displayQuantity }}
                                </td>
                                @if ($showPrice)
                                    <td class="without_price"
                                        style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; text-align: right; font-size: 16px;text-align: center;">
                                        {{ number_format($price, 2) }}
                                    </td>
                                    <td class="without_price"
                                        style="border-bottom: 1px solid #000; padding: 6px; text-align: right; font-size: 16px;text-align: center;">
                                        {{ number_format($amount, 2) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td style="border-bottom: 1px solid #000; padding: 6px; text-align: center; font-size: 16px;"
                                    colspan="{{ $showPrice ? '9' : '6' }}">No items found</td>
                            </tr>
                        @endforelse

                        @for ($i = 0; $i < $emptyRows; $i++)
                            <tr>
                                <td
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px; height: 20px;">
                                    &nbsp;</td>
                                <td style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px;">
                                    &nbsp;</td>
                                <td style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px;">
                                    &nbsp;</td>
                                <td style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px;">
                                    &nbsp;</td>
                                <td style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px;">
                                    &nbsp;</td>
                                <td style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px;">
                                    &nbsp;</td>
                                @if ($showPrice)
                                    <td class="without_price"
                                        style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px;">
                                        &nbsp;</td>
                                    <td class="without_price" style="border-bottom: 1px solid #000; padding: 6px;">
                                        &nbsp;</td>
                                @endif
                            </tr>
                        @endfor

                        @php
                            $footerRowCount = 1; // Total Taxable Amount row (always present)

                            // Add conditional rows
                            if (!empty($document->packing_forwarding_charge) && $document->packing_forwarding_charge > 0) {
                                $footerRowCount++;
                            }
                            if (!empty($document->transport_charge) && $document->transport_charge > 0) {
                                $footerRowCount++;
                            }
                            if (!empty($document->freight_charge) && $document->freight_charge > 0) {
                                $footerRowCount++;
                            }

                            // GST rows
                            if ($is_igst == 1) {
                                $footerRowCount++; // IGST row
                            } else {
                                $footerRowCount += 2; // CGST + SGST rows
                            }

                            $footerRowCount++; // Round off row
                            $footerRowCount++; // Grand Total row
                        @endphp

                        {{-- Total Row --}}
                        <tr style="background-color: #d3d3d3;">
                            <td colspan="3"
                                style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px">
                                <strong></strong>
                            </td>
                            <td
                                style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px">
                            </td>
                            <td
                                style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: center; font-size: 16px">
                            </td>

                            @if ($showPrice)
                                <td class="without_price"
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px;">
                                    &nbsp;</td>
                                <td class="without_price"
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; text-align: right; font-size: 16px">
                                    <strong>Sub Total</strong>
                                </td>
                                <td class="without_price"
                                    style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px">
                                    <strong>₹ {{ number_format($sub_total, 2) }}</strong>
                                </td>
                            @endif
                        </tr>
                    </tbody>
                </table>

                <table class="invoice-table" style="width: 100%; border-collapse: collapse; ">
                    <tbody>
                        {{-- Footer Section --}}
                        @if ($showPrice)
                            <tr class="without_price">
                                <td colspan="5" rowspan="{{ $footerRowCount }}"
                                    style="border-right: 1px solid #000; padding: 8px; vertical-align: top; font-size: 12px; line-height: 1.4;">


                                    <div style="margin-bottom: 14px;">
                                        <strong>Bank Details:</strong><br>
                                        {!! str_replace('</p>', '</p>' . PHP_EOL, $document->company->bank_details ?? '-') !!}
                                    </div>

                                    <div style="margin-bottom: 14px;">
                                        <strong>Terms & Condition:</strong><br>
                                        {!! $document->term_and_condition ?? '-' !!}
                                    </div>

                                    @if ($document->notes)
                                        <div style="margin-bottom: 14px;">
                                            <strong>Note:</strong><br>
                                            {!! $document->notes ?? '-' !!}
                                        </div>
                                    @endif

                                    <div>
                                        <strong>Grand Total In Words:</strong>
                                        {{ App\Helpers\Helper::convertToRupees($document->grand_total ?? $sub_total) }}
                                    </div>
                                </td>
                                <td colspan="2"
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>Total Taxable Amount</strong>
                                </td>
                                <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹ {{ number_format($sub_total, 2) }}</strong>
                                </td>
                            </tr>
                            @if (!empty($document->packing_forwarding_charge) && $document->packing_forwarding_charge > 0)
                                <tr>
                                    <td colspan="2"
                                        style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; font-size: 16px;text-align: right;">
                                        <strong>Packing & Forwarding</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                        <strong>₹
                                            {{ number_format($document->packing_forwarding_charge ?? 0, 2) }}</strong>
                                    </td>
                                </tr>
                            @endif
                            @if (!empty($document->transport_charge) && $document->transport_charge > 0)
                                <tr>
                                    <td colspan="2"
                                        style="border-bottom: 1px solid #000;border-right: 1px solid #000;  padding: 10px; font-size: 16px;text-align: right;">
                                        <strong>Transport</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #000;  padding: 10px; text-align: right; font-size: 16px;">
                                        <strong>₹ {{ number_format($document->transport_charge ?? 0, 2) }}</strong>
                                    </td>
                                </tr>
                            @endif
                            @if (!empty($document->freight_charge) && $document->freight_charge > 0)
                                <tr>
                                    <td colspan="2"
                                        style="border-bottom: 1px solid #000;border-right: 1px solid #000;   padding: 10px; font-size: 16px;text-align: right;">
                                        <strong>Freight</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                        <strong>₹ {{ number_format($document->freight_charge ?? 0, 2) }}</strong>
                                    </td>
                                </tr>
                            @endif
                            @if ($is_igst == 1)
                                <tr class="without_price">
                                    <td colspan="2"
                                        style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; font-size: 16px;text-align: right;">
                                        <strong>IGST</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                        <strong>₹ {{ number_format($document->gst_amount ?? 0, 2) }}</strong>
                                    </td>
                                </tr>
                            @else
                                <tr class="without_price">
                                    <td colspan="2"
                                        style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; font-size: 16px;text-align: right;">
                                        <strong>CGST</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                        <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                    </td>
                                </tr>

                                <tr class="without_price">
                                    <td colspan="2"
                                        style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; font-size: 16px;text-align: right;">
                                        <strong>SGST</strong>
                                    </td>
                                    <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                        <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                    </td>
                                </tr>
                            @endif
                            <tr class="without_price">
                                <td colspan="2"
                                    style="border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>Round off</strong>
                                </td>
                                <td style="border-bottom: 1px solid #000; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>{{ $document->round_off ?? '₹0' }}</strong>
                                </td>
                            </tr>
                            <tr class="without_price">
                                <td colspan="2"
                                    style="border-right: 1px solid #000; padding: 10px;font-size: 16px;text-align: right;">
                                    <strong>Grand Total</strong>
                                </td>
                                <td style=" padding: 10px; text-align: right; font-size: 16px"><strong>₹
                                        {{ $document->grand_total }}</strong></td>
                            </tr>
                            <tr class="without_price">

                            </tr>
                        @endif
                    </tbody>
                </table>

            </div>
        </div>

    </div>
@else
    <div class="card invoice-preview-card">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td
                    style="width: 300px; padding: 20px; border-bottom: 1px solid #000; text-align: center; vertical-align: middle;">
                    <div
                        style="width: 100px; height: 100px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                        @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                            <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                                style="height:60px;width:260px;object-fit: contain;padding-left: 20px;" />
                        @else
                            <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                                style="height:60px;width:260px;object-fit: contain;padding-left: 20px;" />
                        @endif
                    </div>
                </td>
                <td style="padding: 15px 20px; border-bottom: 1px solid #000; vertical-align: top;">
                    <h2 style="margin: 0 0 12px 0; font-size: 26px; font-weight: bold;">
                        {{ $document->company->company_name ?? '-' }}
                    </h2>
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
        <div class="card-body p-0">

            {{-- Main Invoice Table Structure --}}
            <table class="invoice-table" style="width: 100%; border-collapse: collapse; border: 2px solid #666; ">

                {{-- Header Section with Title --}}
                <thead>
                    <tr>
                        <td colspan="8"
                            style="background-color: #d3d3d3; text-align: center; padding: 10px; font-weight: bold; border: 1px solid #666; font-size: 25px !important;">
                            {{ $documentTitle }}
                        </td>
                    </tr>
                </thead>

                {{-- From/To Header Section --}}
                <tbody>
                    <tr>
                        <td colspan="3"
                            style="border: 1px solid #666; padding: 14px; vertical-align: top; width: 40%; font-size: 16px; line-height: 1.6;">
                            <strong style="display: block; margin-bottom: 10px; font-size: 16px">Customer
                                Detail</strong>
                            <strong style="display: block; font-size: 16px"><b>Customer Code :
                                </b>{!! $document->customer?->client_code ?? 'N/A' !!}</strong>
                            @if ($showPrice)
                                <div style="margin-top: 6px;"><b>Customer Name :
                                    </b>{{ $document->customer_name ?? (optional($document->customer)->customer_name ?? '-') }}
                                </div>

                                @if ($document->phone)
                                    <div style="margin-top: 10px;"><b>Phone : </b>{{ $document->phone ?? 'N/A' }}
                                    </div>
                                @endif
                                @if ($document?->customer?->email)
                                    <div style="margin-top: 10px;"><b>Email :
                                        </b>{{ $document->customer->email ?? 'N/A' }}</div>
                                @endif
                                @if ($document?->customer?->city)
                                    <div style="margin-top: 10px;"><b>City :
                                        </b>{{ $document->customer->city->name ?? '-' }}</div>
                                @endif
                                @if ($document?->customer?->state)
                                    <div style="margin-top: 10px;"><b>State :
                                        </b>{{ $document->customer->state->name ?? '-' }}</div>
                                @endif
                                @if ($document?->customer?->pincode)
                                    <div style="margin-top: 10px;"><b>Pincode :
                                        </b>{{ $document->customer->pincode ?? '-' }}</div>
                                @endif
                            @endif
                        </td>

                        <td colspan="3"
                            style="border: 1px solid #666; padding: 14px; vertical-align: top; width: 33%; font-size: 16px; line-height: 1.6;">
                            <div style="margin-top: 6px;">
                                <strong>Billing Address:</strong><br>
                                {{ $document->billing_address ?? '-' }}
                                @if($document->customer)
                                    <br>City: {{ $document->customer->city->name ?? '' }} State:
                                    {{ $document->customer->state->name ?? '' }}, Pincode:
                                    {{ $document->customer->pincode ?? '' }}
                                @endif
                            </div>
                            @php
                                $soShipAddress = $document->shipping_address ?: $document->billing_address;
                                $soShipGst = $document->shipping_gst_no ?: ($document->gst_no ?: ($document->customer?->gst_number ?? ''));
                            @endphp
                            @if(!empty($soShipAddress))
                                <div style="margin-top: 10px;">
                                    <strong>Shipping Address:</strong><br>
                                    {{ $soShipAddress }}
                                    @if($document->customer && empty($document->shipping_customer_id))
                                        <br>City: {{ $document->customer->city->name ?? '' }} State:
                                        {{ $document->customer->state->name ?? '' }}, Pincode:
                                        {{ $document->customer->pincode ?? '' }}
                                    @endif
                                    @if(!empty($soShipGst))
                                        <br><strong>GSTIN:</strong> {{ $soShipGst }}
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td colspan="3"
                            style="border: 1px solid #666; padding: 14px; vertical-align: top; width: 33%; font-size: 16px; line-height: 1.6;">
                            <div style="margin-bottom: 10px;">
                                <strong>Order No:</strong> {{ $document->{$documentNoField} ?? '-' }}
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>Order Date:</strong>
                                {{ $document->{$documentDateField} ? \Carbon\Carbon::parse($document->{$documentDateField})->format('d-m-Y') : '-' }}
                            </div>

                            @if ($showPrice)
                                <div style="margin-bottom: 10px;">
                                    <strong>GSTIN:</strong> {{ $document->gst_no ?? '-' }}
                                </div>
                                @if ($document?->transport_by && $document?->transport_by?->name)
                                    <div style="margin-bottom: 10px;">
                                        <strong>Transport By:</strong> {{ $document->transport_by->name ?? '-' }}
                                    </div>
                                @endif
                                @if ($document?->transport_detail?->name)
                                    <div style="margin-bottom: 10px;">
                                        <strong>Transporter Detail:</strong>
                                        {{ $document->transport_detail->name ?? '-' }}
                                    </div>
                                @endif
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>


            <table class="invoice-table" style="width: 100%; border-collapse: collapse; border: 2px solid #666; ">
                <tbody>
                    {{-- Items Table Header --}}
                    <tr style="background-color: #d3d3d3;">
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 5%;">
                            <strong>Sr No.</strong>
                        </td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 10%;">
                            <strong>Product Code</strong>
                        </td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 28%;">
                            <strong>Product Description</strong>
                        </td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 7%;">
                            <strong>HSN</strong>
                        </td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 7%;">
                            <strong>Unit</strong>
                        </td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 7%;">
                            <strong>Qty</strong>
                        </td>
                        @if ($showPrice)
                            <td class="without_price"
                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px; width: 8%;">
                                <strong>Price</strong>
                            </td>
                            <td class="without_price"
                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px; width: 8%;">
                                <strong>Taxable Amount</strong>
                            </td>
                        @endif
                    </tr>


                    @php
                        $sub_total = 0;
                        $counter = 1;
                        $total_gst_percentage = 0;
                        $total_gst_amount_new = 0;
                        $row_gst_amount = 0;
                        $row_gst_percentage = 0;
                        $is_igst = 0;
                    @endphp
                    @forelse($items as $item)
                        @php
                            $price = $item->price_in_amount ?? 0;
                            $discountPercent = $item->discount_percentage ?? 0;
                            $discountFlat = $item->discount_flat ?? 0;
                            $discountAmount = $price * ($discountPercent / 100);
                            $totalDiscount = $discountAmount + $discountFlat;
                            $rate = $price - $totalDiscount;
                            $quantity = $item->quantity ?? 1;
                            $amount = $rate * $quantity;
                            $sub_total += $amount;
                            $displayQuantity =
                                strtolower($item->unit) === 'kg'
                                ? number_format($item->quantity ?? 0, 3, '.', '')
                                : (int) ($item->quantity ?? 0);

                            $cgst_amount = floatval($item->cgst_amount ?? 0);
                            $sgst_amount = floatval($item->sgst_amount ?? 0);
                            $gst_amount = floatval($item->gst_amount ?? 0);

                            $cgst_per = floatval($item->cgst_in_percentage ?? 0);
                            $sgst_per = floatval($item->sgst_in_percentage ?? 0);
                            $gst_per = floatval($item->gst_in_percentage ?? 0);

                            if ($cgst_amount > 0 && $sgst_amount > 0) {
                                $row_gst_amount = $cgst_amount + $sgst_amount;
                                $row_gst_percentage = $cgst_per + $sgst_per;
                            } else {
                                $is_igst = 1;
                                $row_gst_amount = $gst_amount;
                                $row_gst_percentage = $gst_per;
                            }
                        @endphp
                        <tr>
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                {{ $counter++ }}
                            </td>
                            @php
                                $isCombo = (int) ($item->is_combo ?? 0) === 1;
                                if ($isCombo) {
                                    $combo = \App\Models\ProductCombo::find($item->product_id);
                                    $displayPartNo = $combo ? $combo->part_no : ($item->product_code ?? '-');
                                    $displayProdName = $combo ? $combo->combo_name : ($item->product_name ?? ($item->product_description ?? '-'));
                                } else {
                                    $productModel = $item->product ?? \App\Models\Product::find($item->product_id);
                                    $displayPartNo = $productModel ? $productModel->part_no : ($item->product_code ?? '-');
                                    $displayProdName = $productModel ? ($productModel->product_name ?: $productModel->product_description) : ($item->product_name ?? ($item->product_description ?? '-'));
                                }

                                $custInfo = '';
                                $docCustomer = $document->customer ?? ($item->customer ?? null);
                                if ($docCustomer && $docCustomer->customer_name) {
                                    $cItem = \App\Models\ProductCustomerItem::where('company_id', $document->company_id ?? $item->company_id)
                                        ->where('product_id', $item->product_id)
                                        ->where('customer_name', $docCustomer->customer_name)
                                        ->whereNull('deleted_at')
                                        ->first();
                                    if ($cItem) {
                                        $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                        $custDesc = trim($cItem->customer_item_description ?? '');
                                        $custParts = array_filter([$custCode, $custDesc]);
                                        $custInfo = implode(' ', $custParts);
                                    }
                                }

                                $displayCode = $displayPartNo;
                                if ($isDhruvik && !empty($custInfo)) {
                                    $displayCode .= ' (' . $custInfo . ')';
                                } elseif (empty($displayPartNo) || $displayPartNo === '-') {
                                    $displayCode = $item->product_code ?? '-';
                                    if (!$isDhruvik && !empty($displayCode)) {
                                        $displayCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayCode);
                                    }
                                }
                            @endphp
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                {{ $displayCode }}
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                {{ $displayProdName }}
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                {{ $item->hsn_code ?? '-' }}
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                {{ $item->unit ?? '-' }}
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                {{ $displayQuantity }}
                            </td>
                            @if ($showPrice)
                                <td class="without_price"
                                    style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;text-align: center;">
                                    {{ number_format($price, 2) }}
                                </td>
                                <td class="without_price"
                                    style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;text-align: center;">
                                    {{ number_format($amount, 2) }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;"
                                colspan="{{ $showPrice ? '9' : '6' }}">No items found</td>
                        </tr>
                    @endforelse

                    @php
                        $footerRowCount = 1; // Total Taxable Amount row (always present)

                        // Add conditional rows
                        if (!empty($document->packing_forwarding_charge) && $document->packing_forwarding_charge > 0) {
                            $footerRowCount++;
                        }
                        if (!empty($document->transport_charge) && $document->transport_charge > 0) {
                            $footerRowCount++;
                        }
                        if (!empty($document->freight_charge) && $document->freight_charge > 0) {
                            $footerRowCount++;
                        }

                        // GST rows
                        if ($is_igst == 1) {
                            $footerRowCount++; // IGST row
                        } else {
                            $footerRowCount += 2; // CGST + SGST rows
                        }

                        $footerRowCount++; // Round off row
                        $footerRowCount++; // Grand Total row
                    @endphp

                    {{-- Total Row --}}
                    <tr style="background-color: #d3d3d3;">
                        <td colspan="3" style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px">
                            <strong></strong>
                        </td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px"></td>
                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px"></td>

                        @if ($showPrice)
                            <td class="without_price" style="border: 1px solid #666; padding: 10px;">&nbsp;</td>
                            <td class="without_price"
                                style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px">
                                <strong>Sub Total</strong>
                            </td>
                            <td class="without_price"
                                style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px">
                                <strong>₹ {{ number_format($sub_total, 2) }}</strong>
                            </td>
                        @endif
                    </tr>

                    {{-- Footer Section --}}
                    @if ($showPrice)
                        <tr class="without_price">
                            <td colspan="5" rowspan="{{ $footerRowCount }}"
                                style="border: 1px solid #666; padding: 14px; vertical-align: top; font-size: 16px; line-height: 1.6;">


                                <div style="margin-bottom: 14px;">
                                    <strong>Bank Details:</strong><br>
                                    {!! str_replace('</p>', '</p>' . PHP_EOL, $document->company->bank_details ?? '-') !!}
                                </div>

                                <div style="margin-bottom: 14px;">
                                    <strong>Terms & Condition:</strong><br>
                                    {!! $document->term_and_condition ?? '-' !!}
                                </div>

                                @if ($document->notes)
                                    <div style="margin-bottom: 14px;">
                                        <strong>Note:</strong><br>
                                        {!! $document->notes ?? '-' !!}
                                    </div>
                                @endif

                                <div>
                                    <strong>Grand Total In Words:</strong>
                                    {{ App\Helpers\Helper::convertToRupees($document->grand_total ?? $sub_total) }}
                                </div>
                            </td>
                            <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                <strong>Total Taxable Amount</strong>
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                <strong>₹ {{ number_format($sub_total, 2) }}</strong>
                            </td>
                        </tr>
                        @if (!empty($document->packing_forwarding_charge) && $document->packing_forwarding_charge > 0)
                            <tr>
                                <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>Packing & Forwarding</strong>
                                </td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹
                                        {{ number_format($document->packing_forwarding_charge ?? 0, 2) }}</strong>
                                </td>
                            </tr>
                        @endif
                        @if (!empty($document->transport_charge) && $document->transport_charge > 0)
                            <tr>
                                <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>Transport</strong>
                                </td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹ {{ number_format($document->transport_charge ?? 0, 2) }}</strong>
                                </td>
                            </tr>
                        @endif
                        @if (!empty($document->freight_charge) && $document->freight_charge > 0)
                            <tr>
                                <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>Packing & Forwarding</strong>
                                </td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹ {{ number_format($document->freight_charge ?? 0, 2) }}</strong>
                                </td>
                            </tr>
                        @endif
                        @if ($is_igst == 1)
                            <tr class="without_price">
                                <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>IGST</strong>
                                </td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹ {{ number_format($document->gst_amount ?? 0, 2) }}</strong>
                                </td>
                            </tr>
                        @else
                            <tr class="without_price">
                                <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>CGST</strong>
                                </td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                </td>
                            </tr>

                            <tr class="without_price">
                                <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                    <strong>SGST</strong>
                                </td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                    <strong>₹ {{ number_format($document->gst_amount / 2, 2) }}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr class="without_price">
                            <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;">
                                <strong>Round off</strong>
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;">
                                <strong>{{ $document->round_off ?? '₹0' }}</strong>
                            </td>
                        </tr>
                        <tr class="without_price">
                            <td colspan="2" style="border: 1px solid #666; padding: 10px;font-size: 16px;text-align: right;">
                                <strong>Grand Total</strong>
                            </td>
                            <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px">
                                <strong>₹ {{ $document->grand_total }}</strong>
                            </td>
                        </tr>
                        <tr class="without_price">

                        </tr>
                    @endif

                    {{-- Tax Table --}}
                    @if ($showPrice)
                        {{-- <tr class="without_price">
                            <td colspan="9" style="border: 1px solid #666; padding: 0;">
                                <table style="width: 100%; border-collapse: collapse; margin: 0; font-size: 16px;">
                                    <thead style="background-color: #d3d3d3;">
                                        <tr>

                                            <th rowspan="2"
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Taxable Value</th>
                                            <th colspan="2"
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                IGST</th>
                                            <th colspan="2"
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                CGST</th>
                                            <th colspan="2"
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                SGST</th>
                                            <th rowspan="2"
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Total Amount</th>
                                        </tr>
                                        <tr>
                                            <th
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Rate</th>
                                            <th
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Amount</th>
                                            <th
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Rate</th>
                                            <th
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Amount</th>
                                            <th
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Rate</th>
                                            <th
                                                style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                                Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>

                                            <td style="border: 1px solid #666; padding: 10px; text-align: center;">₹ {{
                                                number_format($sub_total, 2) }}</td>
                                            @if ($is_igst == 1)
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">
                                                {{ $row_gst_percentage ?? '18' }}%
                                            </td>
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">
                                                ₹ {{ number_format($document->gst_amount ?? 0, 2) }}
                                            </td>


                                            <td style="border:1px solid #666; padding:10px; text-align:center;">0.00</td>
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">₹ 0.00</td>

                                            <td style="border:1px solid #666; padding:10px; text-align:center;">0.00</td>
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">₹ 0.00</td>
                                            @else
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">0.00</td>
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">₹ 0.00</td>

                                            <td style="border:1px solid #666; padding:10px; text-align:center;">
                                                {{ number_format(($row_gst_percentage ?? 18) / 2, 2) }}
                                            </td>
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">
                                                ₹ {{ number_format(($document->gst_amount ?? 0) / 2, 2) }}
                                            </td>

                                            <td style="border:1px solid #666; padding:10px; text-align:center;">
                                                {{ number_format(($row_gst_percentage ?? 18) / 2, 2) }}
                                            </td>
                                            <td style="border:1px solid #666; padding:10px; text-align:center;">
                                                ₹ {{ number_format(($document->gst_amount ?? 0) / 2, 2) }}
                                            </td>
                                            @endif
                                            <td style="border: 1px solid #666; padding: 10px; text-align: center;">₹ {{
                                                number_format($document->grand_total ?? $sub_total, 2) }}</td>
                                        </tr>

                                    </tbody>
                                </table>
                            </td>
                        </tr> --}}
                    @endif
                </tbody>
            </table>

        </div>
    </div>
@endif