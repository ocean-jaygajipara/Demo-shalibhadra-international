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

        @foreach ($packingSlip->packing_slip_item as $item)
            @php
                $totalLabels = max(1, (int)($item->box_no ?? 1));
            @endphp

            @for ($i = 1; $i <= $totalLabels; $i++)
                <div class="label-wrapper">
                    <div class="label-inner">

                        <div class="label-logo">
                            <img src="{{ $sidebarMainLogo }}">
                        </div>

                        <div class="label-content">
                            <div class="label-data">
                                @php
                                    $prodName = $item->product ? ($item->product->product_name ?: $item->product->product_description) : $item->product_name;
                                    $prodPartNo = $item->product?->part_no ?? $item->product_code;
                                    $psCustomer = $packingSlip->customer ?? ($packingSlip->sales_order->customer ?? null);
                                    $custItemCode = '';
                                    if ($psCustomer && $psCustomer->customer_name) {
                                        $cItem = \App\Models\ProductCustomerItem::where('company_id', $packingSlip->company_id ?? $item->company_id)
                                            ->where('product_id', $item->product_id)
                                            ->where('customer_name', $psCustomer->customer_name)
                                            ->whereNull('deleted_at')
                                            ->first();
                                        if ($cItem) {
                                            $custItemCode = trim($cItem->customer_item_code ?? '');
                                        }
                                    }
                                @endphp
                                <p><strong>ITEM :</strong> {{ $prodName }}</p>
                                <p><strong>CODE :</strong> {{ $prodPartNo }}</p>

                                @if (!empty($custItemCode))
                                    <p>
                                        <strong>Cust. Item CODE :</strong>
                                        {{ $custItemCode }}
                                    </p>
                                @else
                                    <p style="visibility:hidden;">&nbsp;</p>
                                @endif

                                @php
                                    $unitDisplay = !empty($item->unit) ? (strtolower($item->unit) === 'pcs' ? 'Pcs' : ucfirst($item->unit)) : 'Pcs';
                                    $boxCount = (float)($item->box_no ?? 0);
                                    $qtyCtn = (float)($item->qty_ctn ?? 0);
                                    $totalQtyVal = (float)($item->total_qty ?? 0);
                                    $totalQtyFormatted = (int)$totalQtyVal == $totalQtyVal ? (int)$totalQtyVal : number_format($totalQtyVal, 2);
                                    if ($boxCount > 0 && $qtyCtn > 0) {
                                        $boxFormatted = (int)$boxCount == $boxCount ? (int)$boxCount : $boxCount;
                                        $qtyText = $boxFormatted . ' Box X ' . number_format($qtyCtn) . ' ' . $unitDisplay . ' = ' . $totalQtyFormatted;
                                    } else {
                                        $qtyText = number_format($totalQtyVal) . ' ' . $unitDisplay;
                                    }
                                @endphp
                                <p><strong>QTY :</strong> {{ $qtyText }}</p>

                                <p>
                                    <strong>P.S. NO :</strong> {{ $packingSlip->packing_slip_no }}
                                    &nbsp;
                                    <strong>DTD :</strong>
                                    {{ \Carbon\Carbon::parse($packingSlip->packing_slip_date)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="label-footer">
                            MADE IN INDIA
                        </div>

                    </div>
                </div>
            @endfor
        @endforeach

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
            const withoutPrice = localStorage.getItem('sales_without_price') === 'true';
            $toggle.prop('checked', withoutPrice);
            toggleColumns(withoutPrice);
            toggleWithPriceSection(withoutPrice); // <-- Added

            // On toggle
            $toggle.on('change', function() {
                const isChecked = $(this).is(':checked');
                localStorage.setItem('sales_without_price', isChecked);
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

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.lable_print', [$packingSlip->id]) }}" title="Print" target="_blank"><i
                    class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendpackingSlipBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('sales_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush
