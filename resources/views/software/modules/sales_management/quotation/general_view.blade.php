@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($quotation) && isset($quotation->company_id) && !empty($quotation->company_id)) {
        $company_id = $quotation->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }

    $authDetail = $modules['authLoginUserDetail'] ?? ($authLoginUserDetail ?? (Auth::guard('admin_software')->user() ?? Auth::guard('team_person')->user()));
    $isDhruvik = false;
    if ($authDetail) {
        $userCompanyId = (int)($authDetail->company_id ?? ($company_id ?? ($modules['company_id'] ?? 0)));
        $userTeamRoleId = (int)($authDetail->team_role_id ?? 0);
        $authName = strtoupper($authDetail->name ?? '');
        $authUsername = strtoupper($authDetail->username ?? '');
        $authEmpCode = strtoupper($authDetail->employee_code ?? '');

        if (
            (Auth::guard('admin_software')->check() && !Auth::guard('team_person')->check()) ||
            ($userCompanyId == 2 && ($userTeamRoleId == 2 || (int)$authDetail->id == 2 || $authEmpCode === 'EMP-001')) ||
            str_contains($authName, 'DHRUVIK') ||
            str_contains($authUsername, 'DHRUVIK')
        ) {
            $isDhruvik = true;
        }
    }
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/materialize/materialize.min.css') }}">
@endsection

@section('content')

    <style>
        .per {
            float: right;
        }

        .invoice-preview .invoice-preview-header,
        .invoice-edit .invoice-preview-header,
        .invoice-add .invoice-preview-header {
            background-color: var(--bs-gray-50);
        }

        .rounded {
            border-radius: var(--bs-border-radius) !important;
        }

        .card-body {
            flex: 1 1 auto;
            padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
            color: var(--bs-card-color);
        }

        .main-logo {
            margin-left: -6px;
        }

        .border-panel {
            border-top: 3px solid #266BEE !important;
            border-radius: 3px !important;
        }

        .text-right {
            text-align: right !important;
        }

        .fab-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
        }

        .fab-actions {
            pointer-events: none;
        }

        .fab-action {
            transform: scale(0);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .fab-container:hover .fab-action {
            transform: scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        .btn-md {
            padding: 0.65rem 1.25rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.3rem;
            width: 56px;
            height: 56px;
            color: #fff !important;
        }

        @media print {
            .company_dv {
                width: 100% !important;
            }

            .invoice_dv,
            .billing_dv,
            .shipping_dv,
            .quotation_dv {
                width: 25% !important;
            }

            .per {
                float: right;
            }

            .invoice-preview .invoice-preview-header,
            .invoice-edit .invoice-preview-header,
            .invoice-add .invoice-preview-header {
                background-color: #ced7e0dc !important;
            }

            .rounded {
                border-radius: var(--bs-border-radius) !important;
            }

            .card-body {
                flex: 1 1 auto;
                padding: var(--bs-card-spacer-y) var(--bs-card-spacer-x);
                color: var(--bs-card-color);
            }

            .main-logo {
                margin-left: -6px;
            }

            .border-panel {
                border-top: 3px solid #266BEE !important;
                border-radius: 3px !important;
            }

            .text-right {
                text-align: right !important;
            }
        }
    </style>

    @php
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
        // Allow dynamically for both main team persons and sub team persons
        $canViewPrice = true;
    }

    $raw = $quotation->company->address;
    $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
    $parts = explode('<br><br>', $clean, 2);

    $part1 = $parts[0] ?? '';
    $part2 = $parts[1] ?? '';
    @endphp

    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
        ])
    </div>


    <div class="d-flex justify-content-end mb-3 px-3">
        <label for="withoutPriceToggle" class="d-flex align-items-center" style="font-size: 1.25rem;">
            <input type="checkbox" class="form-check-input me-2" id="withoutPriceToggle"
                style="width: 20px; height: 20px;position: unset;opacity: unset; pointer-events: unset;">
            Without Price
        </label>
    </div>

    <div class="row invoice-preview w-100" id="quotation_download_save">
        <div class="col-12 mb-4">
            {{-- <div class="card invoice-preview-card p-4">
            <div class="card-body invoice-preview-header rounded">
                @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                <div class="text-center">
                    <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo" style="width: 60%;" />
                </div>
                @else
                <div class="text-center">
                    <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo" style="width: 60%;"/>
                </div>
                @endif
            </div> --}}
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
                                {{ $quotation->company->company_name ?? '-' }}</h2>
                            <p style="margin: 0; font-size: 14px; line-height: 1.6; text-align: left;">
                                {!! str_replace('</p>', '</p>' . PHP_EOL, $part1 ?? '-') !!}
                                <br />
                                Phone: {{ $quotation->company->whatsapp_number ?? '-' }}<br>
                                Email: {{ $quotation->company->email ?? '-' }}<br>
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
                                <td colspan="8" style="background-color: #d3d3d3; text-align: center; padding: 10px; font-weight: bold; border: 1px solid #666; font-size: 25px !important;">
                                    Quotation
                                </td>
                            </tr>
                        </thead>

                        {{-- From/To Header Section --}}
                        <tbody>
                            <tr>
                                <td colspan="3" style="border: 1px solid #666; padding: 14px; vertical-align: top; width: 40%; font-size: 16px; line-height: 1.6;">
                                    <strong style="display: block; margin-bottom: 10px; font-size: 16px">Customer Detail</strong>
                                    <strong style="display: block; font-size: 16px"><b>Customer Code : </b>{!! $quotation->customer?->client_code ?? 'N/A' !!}</strong>
                                    @if ($canViewPrice)
                                        <div style="margin-top: 6px;"><b>Customer Name : </b>{{ $quotation->customer_name ?? optional($quotation->customer)->customer_name ?? '-' }}</div>

                                        @if ($quotation->phone)
                                            <div style="margin-top: 10px;"><b>Phone : </b>{{ $quotation->phone ?? 'N/A' }}</div>
                                        @endif
                                        @if ($quotation?->customer?->email)
                                            <div style="margin-top: 10px;"><b>Email : </b>{{ $quotation->customer->email ?? 'N/A' }}</div>
                                        @endif
                                        @if ($quotation?->customer?->city)
                                            <div style="margin-top: 10px;"><b>City :
                                                </b>{{ $quotation->customer->city->name ?? '-' }}</div>
                                        @endif
                                        @if ($quotation?->customer?->state)
                                            <div style="margin-top: 10px;"><b>State :
                                                </b>{{ $quotation->customer->state->name ?? '-' }}</div>
                                        @endif
                                        @if ($quotation?->customer?->pincode)
                                            <div style="margin-top: 10px;"><b>Pincode :
                                                </b>{{ $quotation->customer->pincode ?? '-' }}</div>
                                        @endif
                                    @endif
                                </td>

                                <td colspan="3" style="border: 1px solid #666; padding: 14px; vertical-align: top; width: 33%; font-size: 16px; line-height: 1.6;">
                                    @if (trim($quotation->billing_address ?? '') === trim($quotation->shipping_address ?? ''))

                                        <div style="margin-top: 6px;">
                                            <strong>Billing Address:</strong><br>
                                            {{ $quotation->billing_address ?? '-' }}
                                        </div>

                                    @else
                                        <div style="margin-top: 10px;">
                                            <strong>Billing Address:</strong><br>
                                            {{ $quotation->billing_address ?? '-' }}
                                        </div>
                                        <div style="margin-top: 10px;">
                                            <strong>Shipping Address:</strong><br>
                                            {{ $quotation->shipping_address ?? '-' }}
                                        </div>
                                    @endif
                                </td>

                                <td colspan="3" style="border: 1px solid #666; padding: 14px; vertical-align: top; width: 33%; font-size: 16px; line-height: 1.6;">
                                    <div style="margin-bottom: 10px;">
                                        <strong>Order No:</strong> {{ $quotation->quotation_no ?? '-' }}
                                    </div>
                                    <div style="margin-bottom: 10px;">
                                        <strong>Order Date:</strong> {{ $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->format('d-m-Y') : '-' }}
                                    </div>

                                    @if ($canViewPrice)
                                        <div style="margin-bottom: 10px;">
                                            <strong>GSTIN:</strong> {{ $quotation->gst_no ?? '-' }}
                                        </div>
                                        @if ($quotation?->transport_by && $quotation?->transport_by?->name)
                                            <div style="margin-bottom: 10px;">
                                                <strong>Transport By:</strong> {{ $quotation->transport_by->name ?? '-' }}
                                            </div>
                                        @endif
                                        @if ($quotation?->transport_detail?->name)
                                            <div style="margin-bottom: 10px;">
                                                <strong>Transporter Detail:</strong> {{ $quotation->transport_detail->name ?? '-' }}
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
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 5%;"><strong>Sr No.</strong></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 10%;"><strong>Product Code</strong></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 28%;"><strong>Product Description</strong></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 7%;"><strong>HSN</strong></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 7%;"><strong>Unit</strong></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;width: 7%;"><strong>Qty</strong></td>
                                @if ($canViewPrice)
                                    <td class="without_price" style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px; width: 8%;"><strong>Price</strong></td>
                                    <td class="without_price" style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px; width: 8%;"><strong>Taxable Amount</strong></td>
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
                                @forelse($quotation->quotation_items as $item)
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
                                        $displayQuantity = strtolower($item->unit) === 'kg'
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
                                    @php
                                        $productModel = $item->product ?? \App\Models\Product::find($item->product_id);
                                        $prodPartNo = $productModel ? $productModel->part_no : ($item->product_code ?? '-');
                                        $displayProdName = $productModel ? ($productModel->product_name ?: $productModel->product_description) : ($item->product_name ?? ($item->product_description ?? '-'));

                                        $custInfo = '';
                                        $quotationCustomer = $quotation->customer ?? null;
                                        if ($quotationCustomer && $quotationCustomer->customer_name) {
                                            $cItem = \App\Models\ProductCustomerItem::where('company_id', $quotation->company_id ?? $item->company_id)
                                                ->where('product_id', $item->product_id)
                                                ->where('customer_name', $quotationCustomer->customer_name)
                                                ->whereNull('deleted_at')
                                                ->first();
                                            if ($cItem) {
                                                $custCode = trim($cItem->customer_item_code ?: ($cItem->customer_code ?? ''));
                                                $custDesc = trim($cItem->customer_item_description ?? '');
                                                $custParts = array_filter([$custCode, $custDesc]);
                                                $custInfo = implode(' ', $custParts);
                                            }
                                        }
                                        $displayProductCode = $prodPartNo;
                                        if ($isDhruvik && !empty($custInfo)) {
                                            $displayProductCode .= ' (' . $custInfo . ')';
                                        } elseif (empty($prodPartNo) || $prodPartNo === '-') {
                                            $displayProductCode = $item->product_code ?? '-';
                                            if (!$isDhruvik && !empty($displayProductCode)) {
                                                $displayProductCode = preg_replace('/\s*\([^)]*\)\s*$/', '', $displayProductCode);
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">{{ $counter++ }}</td>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">
                                            {{ $displayProductCode }}
                                        </td>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">{{ $displayProdName }}</td>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">{{ $item->hsn_code ?? '-' }}</td>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">{{ $item->unit ?? '-' }}</td>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;">{{ $displayQuantity }}</td>
                                        @if ($canViewPrice)
                                            <td class="without_price" style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;text-align: center;">{{ number_format($price, 2) }}</td>
                                            <td class="without_price" style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;text-align: center;">{{ number_format($amount, 2) }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px;" colspan="{{ $canViewPrice ? '9' : '6' }}">No items found</td>
                                    </tr>
                                @endforelse

                            @php
                                $footerRowCount = 1; // Total Taxable Amount row (always present)

                                // Add conditional rows
                                if (!empty($quotation->packing_forwarding_charge) && $quotation->packing_forwarding_charge > 0) {
                                    $footerRowCount++;
                                }
                                if (!empty($quotation->transport_charge) && $quotation->transport_charge > 0) {
                                    $footerRowCount++;
                                }
                                if (!empty($quotation->freight_charge) && $quotation->freight_charge > 0) {
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
                                <td colspan="3" style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px"><strong></strong></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px"></td>
                                <td style="border: 1px solid #666; padding: 10px; text-align: center; font-size: 16px"></td>

                                @if ($canViewPrice)
                                    <td class="without_price" style="border: 1px solid #666; padding: 10px;">&nbsp;</td>
                                    <td class="without_price" style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px"><strong>Sub Total</strong></td>
                                    <td class="without_price" style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px"><strong>₹ {{ number_format($sub_total, 2) }}</strong></td>
                                @endif
                            </tr>

                            {{-- Footer Section --}}
                            @if ($canViewPrice)
                                <tr class="without_price">
                                    <td colspan="5" rowspan="{{ $footerRowCount }}" style="border: 1px solid #666; padding: 14px; vertical-align: top; font-size: 16px; line-height: 1.6;">


                                        <div style="margin-bottom: 14px;">
                                            <strong>Bank Details:</strong><br>
                                            {!! str_replace('</p>', '</p>' . PHP_EOL, $quotation->company->bank_details ?? '-') !!}
                                        </div>

                                        <div style="margin-bottom: 14px;">
                                            <strong>Terms & Condition:</strong><br>
                                            {!! $quotation->term_and_condition ?? '-' !!}
                                        </div>

                                        @if ($quotation->notes)
                                            <div style="margin-bottom: 14px;">
                                                <strong>Note:</strong><br>
                                            {!! $quotation->notes ?? '-' !!}
                                            </div>
                                        @endif

                                        <div>
                                            <strong>Grand Total In Words:</strong> {{ App\Helpers\Helper::convertToRupees($quotation->grand_total ?? $sub_total) }}
                                        </div>
                                    </td>
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>Total Taxable Amount</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($sub_total, 2) }}</strong></td>
                                </tr>
                                @if (!empty($quotation->packing_forwarding_charge) && $quotation->packing_forwarding_charge > 0)
                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>Packing & Forwarding</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($quotation->packing_forwarding_charge ?? 0, 2) }}</strong></td>
                                </tr>
                                @endif
                                @if (!empty($quotation->transport_charge) && $quotation->transport_charge > 0)
                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>Transport</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($quotation->transport_charge ?? 0, 2) }}</strong></td>
                                </tr>
                                @endif
                                @if (!empty($quotation->freight_charge) && $quotation->freight_charge > 0)
                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>Freight</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($quotation->freight_charge ?? 0, 2) }}</strong></td>
                                </tr>
                                @endif
                                @if($is_igst == 1)
                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>IGST</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($quotation->gst_amount ?? 0, 2) }}</strong></td>
                                </tr>
                                @else

                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>CGST</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($quotation->gst_amount / 2, 2) }}</strong></td>
                                </tr>

                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>SGST</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>₹ {{ number_format($quotation->gst_amount / 2, 2) }}</strong></td>
                                </tr>
                                @endif
                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px; font-size: 16px;text-align: right;"><strong>Round off</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px;"><strong>{{ $quotation->round_off ?? '₹0' }}</strong></td>
                                </tr>
                                <tr class="without_price">
                                    <td colspan="2" style="border: 1px solid #666; padding: 10px;font-size: 16px;text-align: right;"><strong>Grand Total</strong></td>
                                    <td style="border: 1px solid #666; padding: 10px; text-align: right; font-size: 16px"><strong>₹ {{ $quotation->grand_total }}</strong></td>
                                </tr>
                                <tr class="without_price">

                                </tr>
                            @endif

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>


@endsection

@section('page_leavel_script')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        // OR using jQuery
        $(document).ready(function() {
            $(document).on('click', '.fab-toggle', function() {
                fabToggle();
            });

            function fabToggle() {
                const fabMenu = document.getElementById("fabMenu");
                fabMenu.classList.toggle("d-none");
            }
        });


        $(document).ready(function() {
            const $toggle = $('#withoutPriceToggle');
            const hideableColumns = ['without_price'];

            // Load saved state
            const withoutPrice = localStorage.getItem('quotation_without_price') === 'true';
            $toggle.prop('checked', withoutPrice);
            toggleColumns(withoutPrice);
            toggleWithPriceSection(withoutPrice); // <-- Added

            // On toggle
            $toggle.on('change', function() {
                const isChecked = $(this).is(':checked');
                localStorage.setItem('quotation_without_price', isChecked);
                toggleColumns(isChecked);
                toggleWithPriceSection(isChecked); // <-- Added
            });

            function toggleColumns(hide) {
                hideableColumns.forEach(function(cls) {
                    $('.' + cls).css('display', hide ? 'none' : '');
                });
            }

            // New function to toggle .with_price section
            function toggleWithPriceSection(show) {
                if (show) {
                    $('.with_price').show();
                } else {
                    $('.with_price').hide();
                }
            }
        });
    </script>

    </script>

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action" id="sendWhatsAppQuotationBtn"
                href="{{ route('whatsapp.send', ['id' => $quotation->id, 'type' => 'quotation']) }}" title="Send WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendQuotationBtn"
                href="{{ route($modules['route'] . '.sendQuotationPdf', [$quotation->id]) }}" title="Send Email">
                <i class="fas fa-envelope"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action" title="Call">
                <i class="fas fa-phone"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-secondary shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.quotationPrint', [$quotation->id]) }}" title="Print"
                target="_blank">
                <i class="fas fa-print"></i>
            </a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendQuotationBtn, #sendWhatsAppQuotationBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('quotation_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString();
            });
        });
    </script>
@endpush
