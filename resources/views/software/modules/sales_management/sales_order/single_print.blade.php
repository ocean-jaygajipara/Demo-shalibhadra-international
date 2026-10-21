<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SalesOrder {{ $salesOrder->sales_order_no ?? '-' }}
        {{ now()->setTimezone('Asia/Kolkata')->format('d M Y h:i:s A') }} - Print </title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <!-- Optional: Boxicons or other icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <style>

        @page {
            size: A4;
            margin:8mm;
        }
        @media print {

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .print-page {
                width: 100%;
                height: auto;
                padding: 0 !important;
                margin: auto;
                border: 3px solid #000 !important;
                box-sizing: border-box;
            }

            .invoice-table{
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-content {
                margin: 12mm;
                box-sizing: border-box;
            }

            .card,
            .invoice-preview-card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
            }

            .card-body {
                padding: 0 !important;
            }

            table {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse;
            }

            td, th {
                word-break: break-word;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

    </style>
    @php
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
        }
    @endphp
    <script>
        (function() {
            const hidePrices = localStorage.getItem('sales_without_price') === 'true';
            const canViewPrice = {{ $canViewPrice ? 'true' : 'false' }};

            if (!canViewPrice) {
                document.write('<body class="no-price-view">');
            } else if (hidePrices) {
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
    if (isset($salesOrder) && isset($salesOrder->company_id) && !empty($salesOrder->company_id)) {
        $company_id = $salesOrder->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
    }
    $borderColor = optional($salesOrder->company)->quotation_view_color ?? '#266BEE';

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
                                    'document' => $salesOrder,
                                    'documentType' => 'sales_order',
                                    'modules' => $modules,
                                    'isPrint' => true,
                                    'canViewPrice' => $canViewPrice,
                                    'sidebarMainLogo' => $sidebarMainLogo,
                                    'sidebarheaderLogo' => $sidebarheaderLogo,
                                    'borderColor' => $borderColor,
                                    'itemRelation' => 'sales_order_items',
                                    'withoutPriceKey' => 'sales_without_price'
                                ])




                                </div>
                            </div>
                            <!-- /Invoice -->

                        </div>
                    </div>
                </div> <!-- layout-page -->
            </div> <!-- layout-container -->
        </div> <!-- layout-wrapper -->
    </div>


    <script>
        function printAndRedirect() {
            // Print Configuration Instructions:
            // In the browser print dialog, please set:
            // - Paper size: A4
            // - Margins: Custom (1cm) - CSS sets this automatically
            // - Scale: Default (100%)
            // - Options: Background graphics = checked (enabled via CSS)
            // - Layout: Portrait (set via CSS)

            // Apply print settings before printing
            window.addEventListener('beforeprint', function() {
                // Ensure background graphics are printed
                document.body.style.printColorAdjust = 'exact';
                document.body.style.webkitPrintColorAdjust = 'exact';

                // Apply to all elements
                const allElements = document.querySelectorAll('*');
                allElements.forEach(function(el) {
                    el.style.printColorAdjust = 'exact';
                    el.style.webkitPrintColorAdjust = 'exact';
                });
            }, {
                once: true
            });

            // Trigger print dialog
            window.print();

            window.onafterprint = function() {
                setTimeout(function() {
                    window.location.href = "{{ route($modules['route'] . '.index') }}";
                }, 1);
            };
        }

        // Apply print settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure background graphics are printed
            document.body.style.printColorAdjust = 'exact';
            document.body.style.webkitPrintColorAdjust = 'exact';
        });
    </script>
</body>

</html>
