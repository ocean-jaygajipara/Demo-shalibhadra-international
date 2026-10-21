@extends('software.layout.app')
@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($packingSlip) && isset($packingSlip->company_id) && !empty($packingSlip->company_id)) {
        $company_id = $packingSlip->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }

    $invoiceNo = $packingSlip->invoice?->invoice_order_no ?? ($packingSlip->packing_slip_no ?? '-');
    $invoiceDate = $packingSlip->invoice?->invoice_order_date
        ? \Carbon\Carbon::parse($packingSlip->invoice->invoice_order_date)->format('d/m/Y')
        : ($packingSlip->packing_slip_date
            ? \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d/m/Y')
            : '-');

    $totalCartons = max(1, (int) ($packingSlip->total_no_of_carton ?? 1));
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/materialize/materialize.min.css') }}">
@endsection

@section('content')

    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ONE A4 PAGE */
        .page {
            width: 194mm;
            min-height: 297mm;
            display: grid;
            grid-template-columns: repeat(4, 97mm);
            gap: 6mm;
            page-break-after: always;
        }

        /* FIXED LABEL SIZE */
        .label-wrapper {
            width: 97mm;
            height: 70mm;
            border: 2px solid #000;
            box-sizing: border-box;
            padding: 4mm;
            display: flex;
        }

        .label-inner {
            width: 100%;
            height: 100%;
            border: 1px solid #000;
            box-sizing: border-box;
            font-size: 13px;
            line-height: 1.25;
            display: flex;
            flex-direction: column;
        }

        /* LOGO */
        .label-logo {
            text-align: center;
            padding: 8px 0 3px 0;
            border-bottom: 1px solid #000;
        }

        .label-logo img {
            max-height: 16mm;
        }

        .label-content {
            flex: 1;
        }

        .label-data {
            margin: 8px;
        }

        .label-content p {
            margin: 4px 0;
            font-weight: 600;
            color: #000;
        }

        /* FOOTER */
        .label-footer {
            border-top: 1px solid #000;
            padding: 4px 6px;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
            color: #000;
        }

        @media print {
            body {
                margin: 0;
            }
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
    </style>

    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
        ])
    </div>

    <div class="page">
        @php
            $items = $packingSlip->packing_slip_item;
        @endphp

        @if ($items && count($items) > 0)
            @foreach ($items as $item)
                @php
                    $prodName = $item->product
                        ? ($item->product->product_name ?: $item->product->product_description)
                        : $item->product_name;
                    $prodPartNo = trim($item->product?->part_no ?? '');
                    $fallbackCode = trim($item->product_code ?? ($prodName ?? '--'));

                    $custItemCode = '';
                    if (!empty($item->customerItem?->customer_item_code)) {
                        $custItemCode = trim($item->customerItem->customer_item_code);
                    } else {
                        $custName = $packingSlip->customer_name ?: ($packingSlip->customer?->customer_name ?: ($packingSlip->sales_order?->customer?->customer_name ?? ''));
                        $clientCode = $packingSlip->customer?->client_code ?? ($packingSlip->customer?->customer_code ?? '');

                        $cItemQuery = \App\Models\ProductCustomerItem::where('product_id', $item->product_id)
                            ->whereNull('deleted_at');

                        if (!empty($packingSlip->company_id)) {
                            $cItemQuery->where('company_id', $packingSlip->company_id);
                        }

                        $cItem = (clone $cItemQuery)->where(function ($q) use ($custName, $clientCode) {
                            if (!empty($custName)) {
                                $q->where('customer_name', $custName);
                            }
                            if (!empty($clientCode)) {
                                $q->orWhere('customer_code', $clientCode);
                            }
                        })->first();

                        if (!$cItem && !empty($custName)) {
                            $cItem = (clone $cItemQuery)->where('customer_name', 'like', '%' . trim($custName) . '%')->first();
                        }

                        if ($cItem && !empty($cItem->customer_item_code)) {
                            $custItemCode = trim($cItem->customer_item_code);
                        }
                    }

                    if (!empty($custItemCode)) {
                        $itemDisplay = $custItemCode;
                    } elseif (!empty($prodPartNo)) {
                        $itemDisplay = $prodPartNo;
                    } else {
                        $itemDisplay = $fallbackCode;
                    }

                    $unitDisplay = !empty($item->unit)
                        ? (strtolower($item->unit) === 'pcs' ? 'Pcs' : ucfirst($item->unit))
                        : 'Pcs';
                    $packetCount = (float) ($item->total_packet ?? 0);
                    $boxCount = (float) ($item->box_no ?? 0);
                    $qtyPc = (float) ($item->qty_pc ?? 0);
                    $qtyCtn = (float) ($item->qty_ctn ?? 0);
                    $totalQtyVal = (float) ($item->total_qty ?? 0);

                    if ($packetCount > 0 && $qtyPc > 0) {
                        $boxFormatted = (int) $packetCount == $packetCount ? (int) $packetCount : $packetCount;
                        $qtyPerFormatted =
                            (int) $qtyPc == $qtyPc ? number_format($qtyPc) : number_format($qtyPc, 2);
                        $finalTotal = $qtyCtn > 0 ? $qtyCtn : ($packetCount * $qtyPc);
                        $totalFormatted =
                            (int) $finalTotal == $finalTotal
                                ? (int) $finalTotal
                                : number_format($finalTotal, 2);
                        $qtyFormatted =
                            $boxFormatted .
                            ' Box X ' .
                            $qtyPerFormatted .
                            ' ' .
                            $unitDisplay .
                            ' = ' .
                            $totalFormatted;
                    } elseif ($boxCount > 0 && $qtyPc > 0) {
                        $boxFormatted = (int) $boxCount == $boxCount ? (int) $boxCount : $boxCount;
                        $qtyPerFormatted =
                            (int) $qtyPc == $qtyPc ? number_format($qtyPc) : number_format($qtyPc, 2);
                        $totalFormatted =
                            (int) $totalQtyVal == $totalQtyVal
                                ? (int) $totalQtyVal
                                : number_format($totalQtyVal, 2);
                        $qtyFormatted =
                            $boxFormatted .
                            ' Box X ' .
                            $qtyPerFormatted .
                            ' ' .
                            $unitDisplay .
                            ' = ' .
                            $totalFormatted;
                    } elseif ($qtyCtn > 0) {
                        $qtyFormatted = number_format($qtyCtn) . ' ' . $unitDisplay;
                    } else {
                        $totalFormatted =
                            (int) $totalQtyVal == $totalQtyVal
                                ? (int) $totalQtyVal
                                : number_format($totalQtyVal, 2);
                        $qtyFormatted = $totalFormatted . ' ' . $unitDisplay;
                    }

                    $totalLabels = max(1, (int) ($item->box_no ?? 1));
                    $startCarton = max(1, (int) ($item->no_of_carton ?? 1));
                @endphp

                @for ($c = 1; $c <= $totalLabels; $c++)
                    @php
                        $cartonNoVal = $startCarton + ($c - 1);
                    @endphp
                    <div class="label-wrapper">
                        <div class="label-inner">

                            <div class="label-logo">
                                <img src="{{ $sidebarMainLogo }}">
                            </div>

                            <div class="label-content">
                                <div class="label-data">
                                    <p><strong>ITEM :</strong> {{ $itemDisplay }}</p>
                                    <p><strong>QTY :</strong> {{ $qtyFormatted }}</p>
                                    <p><strong>INVOICE NO :</strong> {{ $invoiceNo }}</p>
                                    <p><strong>INVOICE DATE :</strong> {{ $invoiceDate }}</p>
                                    <p><strong>CARTON NO. :</strong> {{ $cartonNoVal }} / {{ $totalCartons }}</p>
                                </div>
                            </div>

                            <div class="label-footer">
                                MADE IN INDIA
                            </div>

                        </div>
                    </div>
                @endfor
            @endforeach
        @else
            @for ($i = 1; $i <= $totalCartons; $i++)
                <div class="label-wrapper">
                    <div class="label-inner">

                        <div class="label-logo">
                            <img src="{{ $sidebarMainLogo }}">
                        </div>

                        <div class="label-content">
                            <div class="label-data">
                                <p><strong>ITEM :</strong> --</p>
                                <p><strong>QTY :</strong> --</p>
                                <p><strong>INVOICE NO :</strong> {{ $invoiceNo }}</p>
                                <p><strong>INVOICE DATE :</strong> {{ $invoiceDate }}</p>
                                <p><strong>CARTON NO. :</strong> {{ $i }} / {{ $totalCartons }}</p>
                            </div>
                        </div>

                        <div class="label-footer">
                            MADE IN INDIA
                        </div>

                    </div>
                </div>
            @endfor
        @endif
    </div>

@endsection

@section('page_leavel_script')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.fab-toggle', function() {
                fabToggle();
            });

            function fabToggle() {
                const fabMenu = document.getElementById("fabMenu");
                fabMenu.classList.toggle("d-none");
            }
        });
    </script>

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">
        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.cartoon_print', [$packingSlip->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
@endpush
