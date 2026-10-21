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
@endphp
@section('title', $page_title)
@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/materialize/materialize.min.css') }}">
@endsection

@section('content')
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 14px;
            vertical-align: top;
        }

        .no-border td {
            border: none;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .border-box {
            border: 1px solid #000;
            padding: 6px;
            margin-bottom: 8px;
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

    <div style="width: 100%; margin: auto;">
        <h3 class="text-center fw-bold">JOB WORK ORDER</h3>

        {{-- Top Section --}}
        <table style="margin-bottom: 10px;">
            <tr>
                <td style="width: 50%; border:1px solid #000;">
                   <strong>{{ optional($jobOrder->company)->company_name ?? 'Company Name' }}</strong>
                    @php
                        $raw = $jobOrder->company->address;
                        $clean = str_replace(['<br />', '<br/>'], '<br>', $raw);
                        $parts = explode('<br><br>', $clean, 2);

                        $part1 = $parts[0] ?? '';
                        $part2 = $parts[1] ?? '';
                    @endphp
                    {{-- {{ str_replace('&nbsp;', ' ', subject: strip_tags(optional($jobOrder->company)->address)) }}<br> --}}
                    {!! str_replace('</p>', '</p>' . PHP_EOL, $part1 ?? '-') !!}
                    {!! str_replace('</p>', '</p>' . PHP_EOL, $part2 ?? '-') !!}

                    {{-- GSTIN: {{ optional($jobOrder->company)->gst_no ?? '-' }} --}}
                    {{-- <b>GSTIN :</b> {{ optional($jobOrder->company)->gst_no ?? '-' }} --}}

                </td>
                <td style="width: 25%; border:1px solid #000;">
                    <b>JW. Order No. :</b> {{ $jobOrder->job_work_order_no ?? '-' }} <br>
                    <b>PO Date :</b>
                    {{ $jobOrder->job_work_order_date ? \Carbon\Carbon::parse($jobOrder->job_work_order_date)->format('d/m/Y') : '-' }}
                    <br>
                    <b>Delivery Date
                        :</b>{{ optional($jobOrder->job_work_order_items->first())->delivered_date
                            ? \Carbon\Carbon::parse($jobOrder->job_work_order_items->first()->delivered_date)->format('d/m/Y')
                            : '-' }}
                    <br>
                    <b>Payment Terms :</b> {{ $jobOrder->payment_terms ?? '-' }}
                </td>
                <td style="width: 25%; text-align:center; border:1px solid #000;">
                    @if ($jobOrder->company && $jobOrder->company->company_logo_url)
                        <img src="{{ $jobOrder->company->company_logo_url }}" alt="logo" style="max-height:60px;">
                    @else
                        <img src="{{ asset('software/img/logo.png') }}" alt="default logo" style="max-height:60px;">
                    @endif
                </td>

            </tr>
        </table>

        {{-- Vendor / Delivery Info --}}
        <table style="margin-bottom: 10px;">
            <tr>
                 <td style="width: 100%;">
                    <strong>To,</strong> <strong>{{ optional($jobOrder->vendor)->vendor_name ?? '-' }}</strong><br>
                    {{ optional($jobOrder->vendor)->address ?? '-' }}<br>

                    <b> Contact:</b> {{ $jobOrder->phone ?? ($jobOrder->mobile_number ?? '-') }} <br>
                    <b> GSTIN :</b> {{ optional($jobOrder->vendor)->gst_number ?? '-' }}
                </td>
                {{-- <td style="width: 50%; border:1px solid #000;">
                    <b>Delivery To,</b><br>
                    {{ optional($jobOrder->vendor)->shipping_address ?? '-' }} <br>

                    <b> Contact Person:</b> {{ optional($jobOrder->vendor)->vendor_name ?? '-' }} <br>
                    <b> Mobile No:</b> {{ $jobOrder->mobile_no ?? '-' }}

                </td> --}}
            </tr>
        </table>

        {{-- Items Table --}}
        {{-- Items Table --}}
        <table style="width:100%; border-collapse: collapse; margin-bottom: 10px;" border="1">
            <thead>
                <tr style="text-align:center;">
                    <th><b>Sr No</b></th>
                    <th><b>Item Description</b></th>
                    <th><b>Quantity (Pcs)</b></th>
                    <th><b>Unit</b></th>
                    <th><b>Rate</b></th>
                    <th><b>GST%</b></th>
                    <th><b>Taxable Value</b></th>
                </tr>
            </thead>

            <tbody>
                @php
                    $i = 1;
                    $total_value = 0;
                @endphp
                @foreach ($jobOrder->job_work_order_items as $item)
                    @php
                        $qty_kg = $item->alter_quantity ?? 0;
                        $qty_pc = $item->quantity ?? 0;
                        $rate = $item->rate_in_amount ?? 0;
                        $gst = $item->gst_percent ?? 18;
                        $taxable = $qty_pc * $rate;
                        $total_value += $taxable;
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $i++ }}</td>
                        <td>{{ $item->product_name ?? '-' }}</td>
                        <td style="text-align:right;">{{ number_format($qty_pc, 2) }}</td>
                        <td style="text-align:right;">{{ $item->unit ?? '-' }}</td>
                        <td style="text-align:right;">{{ number_format($rate, 2) }}</td>
                        <td style="text-align:center;">{{ number_format($gst, 2) }}</td>
                        <td style="text-align:right;">{{ number_format($taxable, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align:right;"><b>Total :</b></td>
                    <td style="text-align:right;">
                        <b>{{ number_format($jobOrder->job_work_order_items->sum('alter_quantity'), 2) }}</b>
                    </td>
                    <td style="text-align:right;">
                        <b>{{ number_format($jobOrder->job_work_order_items->sum('quantity'), 2) }}</b>
                    </td>
                    <td colspan="2" style="text-align:right;"><b>Total Assessable Value</b></td>
                    <td style="text-align:right;"><b>{{ number_format($total_value, 2) }}</b></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align:right;">CGST</td>
                    <td style="text-align:right;">{{ number_format($total_value * 0.09, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align:right;">SGST</td>
                    <td style="text-align:right;">{{ number_format($total_value * 0.09, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align:right; font-weight:bold;">Grand Total</td>
                    <td style="text-align:right; font-weight:bold;">{{ number_format($total_value * 1.18, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Remarks --}}
        <div style="border:1px solid #000; padding:5px; margin-bottom:10px;">
            <b>Remarks :</b> {{ $jobOrder->remarks ?? '-' }}
        </div>

        {{-- Terms & Signature --}}
        <table style="width:100%; margin-top:10px; border-collapse: collapse;">
            <tr>
                <td style="width:70%; border:1px solid #000; padding:5px;">
                    <b>Terms & Conditions :</b><br>
                    {!! $jobOrder->term_and_condition ?? '-' !!}
                </td>
                <td style="width:30%; border:1px solid #000; text-align:center; vertical-align:bottom; padding:5px;">
                    <div>For, {{ optional($jobOrder->company)->company_name ?? 'Company Name' }}</div>
                    @if ($jobOrder->company && $jobOrder->company->authorised_signature_url)
                        <div style="margin: 8px auto;">
                            <img src="{{ $jobOrder->company->authorised_signature_url }}" alt="Authorized Signature" style="max-height: 80px; max-width: 160px; object-fit: contain;">
                        </div>
                    @else
                        <br><br><br>
                    @endif
                    <b>Authorized Signatory</b>
                </td>
            </tr>
        </table>

    </div>

@endsection
@section('page_leavel_script')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        // FAB toggle
        $(document).on('click', '.fab-toggle', function() {
            const fabMenu = document.getElementById("fabMenu");
            fabMenu.classList.toggle("d-none");
        });

        // Without Price Toggle
        const $toggle = $('#withoutPriceToggle');
        const hideableColumns = ['without_price'];

        function fabToggle() {
            const fabMenu = document.getElementById("fabMenu");
            fabMenu.classList.toggle("d-none");
        }



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

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-danger shadow mb-2 fab-action" id="viewJobOrderPdfBtn"
                href="{{ route('job-work-order.jobOrderPdf', [$jobOrder->id]) }}" title="View PDF" target="_blank">
                <i class="fas fa-file-pdf"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action" id="sendWhatsAppJobOrderBtn"
                href="{{ route('whatsapp.send', ['id' => $jobOrder->id, 'type' => 'job_work_order']) }}" title="Send WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendQuotationBtn"
                href="{{ route($modules['route'] . '.SendjobOrderPdf', [$jobOrder->id]) }}" title="View / Send PDF" target="_blank"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action" title="Call"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-secondary shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.jobOrderPrint', [$jobOrder->id]) }}" title="Print" target="_blank"><i
                    class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendWhatsAppJobOrderBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('quotation_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString();
            });

            $('#viewJobOrderPdfBtn, #sendQuotationBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('quotation_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.open(url.toString(), '_blank');
            });
        });

        $(document).ready(function() {
            const firstLoadKey = 'jobOrderFirstLoad_{{ $jobOrder->id }}';
            const isFirstLoad = localStorage.getItem(firstLoadKey) !== 'done';

            if (isFirstLoad) {
                localStorage.setItem(firstLoadKey, 'done');
                let printUrl = "{{ route($modules['route'] . '.jobOrderPrint', [$jobOrder->id]) }}";
                const hide = localStorage.getItem('quotation_without_price') === 'true' ? '1' : '0';
                printUrl += '?without_price=' + hide;
                window.location.href = printUrl;
            }
        });
    </script>
@endpush
