@extends('software.layout.app')

@php
$page_title = isset($modules['title']) ? $modules['title'] : null;
$folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
$route = isset($modules['route']) ? $modules['route'] : null;
$i = 0;

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
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/materialize/materialize.min.css') }}">
@endsection

@section('content')

    <style>
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

    @php
    $canViewPrice = false;
    $currentGuard = $modules['currentGuard'] ?? null;
    $authLoginUserDetail = $modules['authLoginUserDetail'] ?? null;
    $parent_type_id = isset($modules['parent_type_id'])
    ? (int) $modules['parent_type_id']
    : 0;

    if ($currentGuard === 'admin_software') {
        $canViewPrice = true;
    } elseif ($currentGuard === 'team_person' && $authLoginUserDetail) {
        if ($parent_type_id === 0) {
            $canViewPrice = true;
        }
    }
    @endphp

    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
        ])
    </div>

    @php
        $borderColor = optional($Invoice->company)->order_view_color ?? '#266BEE';
    @endphp

    <div class="row invoice-preview w-100" id="invoice_download_save">
        <div class="col-12 mb-4">
            @include('utils.common_document_view_invoice', [
                'document' => $Invoice,
                'documentType' => 'invoice',
                'modules' => $modules,
                'isPrint' => false,
                'canViewPrice' => $canViewPrice,
                'sidebarMainLogo' => $sidebarMainLogo,
                'sidebarheaderLogo' => $sidebarheaderLogo,
                'borderColor' => $borderColor,
                'itemRelation' => 'invoice_items',
                'withoutPriceKey' => 'invoice_without_price'
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
    </script>

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendInvoiceBtn"
                href="{{ route($modules['route'] . '.sendInvoicePdf', [$Invoice->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.invoicePrint', [$Invoice->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendInvoiceBtn').on('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush
