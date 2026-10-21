@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($salesOrder) && isset($salesOrder->company_id) && !empty($salesOrder->company_id)) {
        $company_id = $salesOrder->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }

    // Check if user can view price-related fields
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
        // Check if team_role has parent_id = 0
        // elseif (
        //     isset($authLoginUserDetail->team_role) &&
        //     $authLoginUserDetail->team_role &&
        //     (int)$authLoginUserDetail->team_role->parent_id === 0
        // ) {
        //     $canViewPrice = true;
        // }
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


    .invoice-preview-card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .invoice-table {
        font-size: 19px;
        line-height: 1.6;
    }

    .invoice-table td,
    .invoice-table th {
        font-size: 17px;
    }

    .invoice-table strong {
        font-weight: 700;
    }


    .invoice-table td[rowspan] {
        position: relative;
    }

    .invoice-table td[rowspan]:after {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 84px;
        color: rgba(200, 200, 200, 0.3);
        font-weight: bold;
        pointer-events: none;
        white-space: nowrap;
        z-index: 0;
    }

    .invoice-table td[rowspan] > * {
        position: relative;
        z-index: 1;
    }

    .text-nowrap {
        white-space: nowrap;
    }

    /* Fix for nested tables */
    .invoice-table table {
        font-size: inherit;
    }

    .invoice-table table td,
    .invoice-table table th {
        font-size: inherit;
    }
    </style>

    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                    ['title' => $page_title, 'url' => route($route . '.index')],
                    [
                        'title' => 'View ' . $page_title,
                        'url' => '',
                    ],
                ],
        ])
    </div>

    @if ($canViewPrice)
        <div class="d-flex justify-content-end mb-3 px-3">
            <label for="withoutPriceToggle" class="d-flex align-items-center" style="font-size: 1.25rem;">
                <input type="checkbox" class="form-check-input me-2" id="withoutPriceToggle"
                    style="width: 20px; height: 20px;position: unset;opacity: unset; pointer-events: unset;">
                Without Price
            </label>
        </div>
    @endif

    @php
        $borderColor = optional($salesOrder->company)->order_view_color ?? '#266BEE';
    @endphp

    <div class="row invoice-preview w-100" id="sales_order_download_save">
        <div class="col-12 mb-4">
            @include('utils.common_document_view', [
                'document' => $salesOrder,
                'documentType' => 'sales_order',
                'modules' => $modules,
                'isPrint' => false,
                'canViewPrice' => $canViewPrice,
                'sidebarMainLogo' => $sidebarMainLogo,
                'sidebarheaderLogo' => $sidebarheaderLogo,
                'borderColor' => $borderColor,
                'itemRelation' => 'sales_order_items',
                'withoutPriceKey' => 'sales_without_price'
            ])
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
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action" id="sendWhatsAppSalesOrderBtn"
                href="{{ route('whatsapp.send', ['id' => $salesOrder->id, 'type' => 'sales_order']) }}" title="Send WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendSalesOrderBtn"
                href="{{ route($modules['route'] . '.sendSalesOrderPdf', [$salesOrder->id]) }}" title="Send Email">
                <i class="fas fa-envelope"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action" title="Call">
                <i class="fas fa-phone"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-secondary shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.salesOrderPrint', [$salesOrder->id]) }}" title="Print"
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
            $('#sendSalesOrderBtn, #sendWhatsAppSalesOrderBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('sales_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush
