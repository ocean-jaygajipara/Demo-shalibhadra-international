<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $Invoice->invoice_order_no ?? '-' }}
        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }} - Print </title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Optional: Boxicons or other icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        .invoice-preview-card {
            border: none !important;
        }

        @media print {
            .company_dv {
                width: 100% !important;
            }

            .invoice-preview-card {
                border: none !important;
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
                border-radius: 3px !important;
            }

            .text-right {
                text-align: right !important;
            }

            .without_price .price-col,
            .without_price .rate-col,
            .without_price .qty-col,
            .without_price .amount-col,
            .without_price .discount-col {
                display: none !important;
            }

            .with_price .no_display {
                display: none !important;
            }
        }
    </style>
    <script>
        (function() {
            const hidePrices = localStorage.getItem('invoice_without_price') === 'true';
            if (hidePrices) {
                document.write('<body class="without_price">');
            } else {
                document.write('<body class="with_price">');
            }
        })();
    </script>
</head>
@php
    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($Invoice) && isset($Invoice->company_id) && !empty($Invoice->company_id)) {
        $company_id = $Invoice->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }
    $borderColor = optional($Invoice->company)->quotation_view_color ?? '#266BEE';

    // Check if user can view price-related fields
    $canViewPrice = false;
    $currentGuard = $modules['currentGuard'] ?? null;
    $authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
    $parent_type_id = isset($modules['parent_type_id']) ? (int) $modules['parent_type_id'] : 0;

    if ($currentGuard === 'admin_software') {
        // Company Admin or Super Admin
        $canViewPrice = true;
    } elseif ($currentGuard === 'team_person' && $authLoginUserDetail) {
        // Check if team_person has parent_type = 0
        if ($parent_type_id === 0) {
            $canViewPrice = true;
        }
    }
@endphp

<body style="--bs-scrollbar-width: 0px;" onload="printAndRedirect();">

    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <div class="layout-page">
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y" style="margin: 0px;padding: 0px;">
                        <div class="row invoice-preview" style="width: 1100px;">
                            <div class="col-xl-12 col-md-12 col-12 mb-md-0 mb-6">
                                @include('utils.common_document_view', [
                                    'document' => $Invoice,
                                    'documentType' => 'invoice',
                                    'modules' => $modules,
                                    'isPrint' => true,
                                    'canViewPrice' => $canViewPrice,
                                    'sidebarMainLogo' => $sidebarMainLogo,
                                    'sidebarheaderLogo' => $sidebarheaderLogo,
                                    'borderColor' => $borderColor,
                                    'itemRelation' => 'invoice_items',
                                    'withoutPriceKey' => 'invoice_without_price',
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printAndRedirect() {
            window.print();

            window.onafterprint = function() {
                setTimeout(function() {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 1);
            };
        }
    </script>
</body>

</html>
