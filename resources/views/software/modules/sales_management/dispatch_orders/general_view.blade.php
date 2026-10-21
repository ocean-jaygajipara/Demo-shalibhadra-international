@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($dispatch) && isset($dispatch->company_id) && !empty($dispatch->company_id)) {
        $company_id = $dispatch->company_id;
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

    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [['title' => $page_title, 'url' => '']],
        ])
    </div>

    <div class="row invoice-preview w-100" id="quotation_download_save">
        <div class="col-12 mb-4">
             <div class="card invoice-preview-card p-4">
                <div class="card-body invoice-preview-header rounded"
                    style="height: 205px; display: flex; justify-content: center; align-items: center;">
                    @if (isset($sidebarheaderLogo) && !empty($sidebarheaderLogo))
                        <img src="{{ $sidebarheaderLogo }}" class="main_logo" alt="Logo"
                            style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                    @else
                        <img src="{{ $sidebarMainLogo }}" class="main_logo" alt="Logo"
                            style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                    @endif
                </div>

                <div class="card-body px-0">
                    <div class="row">
                        <h4 style="text-align: center;font-weight: 700;margin-bottom: 22px;">
                            {{ optional($dispatch->company)->dispatch_title ?? '-' }}</h4>
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <h5><strong>Dispatch No : {{ $dispatch->dispatch_no ?? '-' }}</strong></h5>
                            <p><strong>Dispatch Date:</strong>
                                {{ $dispatch->dispatch_date ? \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d-m-Y') : '-' }}
                            </p>
                            <p><strong>GSTIN:</strong> {{ $dispatch->gst_no ?? '-' }}</p>

                            <p><strong>Transport By:</strong> {{ $dispatch->transport_by->name ?? '-' }}</p>
                            <p><strong>Transporter Detail:</strong> {{ $dispatch->transport_detail->name ?? '-' }}</p>
                        </div>
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <h5><strong>Customer Detail:</strong></h5>
                            <p><strong>Name:</strong> {{ $dispatch->customer->customer_name ?? 'N/A' }}</p>
                            <p><strong>Person:</strong> {{ $dispatch->company->person_name ?? 'N/A' }}</p>
                            <p><strong>Phone:</strong> {{ $dispatch->phone ?? 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ $dispatch->customer->email ?? 'N/A' }}</p>
                            <p><strong>Address:</strong> {{ $dispatch->address ?? 'N/A' }}</p>
                        </div>
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <h5><strong>Billing Address:</strong></h5>
                            <p>{{ $dispatch->billing_address ?? '-' }}</p>
                        </div>
                        <div class="col-xl-3 col-md-6 col-12 mb-4">
                            <h5><strong>Shipping Address:</strong></h5>
                            <p>{{ $dispatch->shipping_address ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                @php
                    $borderColor = optional($dispatch->company)->quotation_view_color ?? '#266BEE';
                @endphp

                <div class="card mt-3">
                    <div class="card-body table-responsive border-panel"
                        style="border-top: 3px solid {{ $borderColor }} !important;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Description</th>
                                    <th>HSN</th>
                                    <th>Unit</th>
                                    <th>Qty</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php $sub_total = 0; @endphp
                                 @forelse($dispatch->dispatch_items as $item)
                                    @php $sub_total += $item->amount; @endphp
                                    <tr>
                                        <td>{{ $item->product_name ?? '-' }}</td>
                                        <td>{{ $item->product_description ?? '-' }}</td>
                                        <td>{{ $item->hsn_code ?? '-' }}</td>
                                        <td>{{ $item->unit ?? '-' }}</td>
                                        <td>{{ $item->dispatch_quantity ?? '-' }}</td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No items found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-body px-0">
                    <div class="row">
                        <div class="col-12">
                            <span class="fw-medium text-heading">Note:</span>
                            <span>{{ $dispatch->remarks ?? '-' }}</span>
                        </div>
                    </div>
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
            // $(document).on('mouseenter', '.fab-toggle', function () {
            //    fabToggle();
            // });
            // $(document).on('mouseleave', '.fab-toggle', function () {
            //    fabToggle();
            // });
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
            {{-- <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="downloadQuotationBtn" href="{{ route($modules['route'] . '.sendQuotationPdf', [$dispatch->id]) }}"><i class="fas fa-envelope"></i></a> --}}
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action" href="{{ route($modules['route'] . '.dispatchPrint', [$dispatch->id]) }}" title="Print" target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script> --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script> --}}

    {{-- <script>
    document.getElementById('downloadQuotationBtn').addEventListener('click', function () {
    const element = document.getElementById('quotation_download_save');
    element.style.minHeight = '1122px';
    element.style.fontSize = '18px';

   const originalColors = [];

element.querySelectorAll('*').forEach((el, i) => {
    originalColors[i] = el.style.color;
    el.style.color = '#000000'; // Force black font
});

html2canvas(element, {
        scale: window.devicePixelRatio * 3, // Higher scale = sharper
        useCORS: true,
        scrollX: 0,
        scrollY: 0,
        windowWidth: document.documentElement.scrollWidth,
        windowHeight: document.documentElement.scrollHeight
    }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');

        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();

        const imgProps = pdf.getImageProperties(imgData);

        const pdfWidth = pageWidth;
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        let heightLeft = pdfHeight;
        let position = 0;

        // Add first page
        pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, pdfHeight);
        heightLeft -= pageHeight;

        // Add more pages if needed
        while (heightLeft > 0) {
            position = heightLeft - pdfHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, pdfHeight);
            heightLeft -= pageHeight;
        }



            const blob = pdf.output('blob');
            const formData = new FormData();
            formData.append('pdf', blob, 'quotation.pdf');
            formData.append('quotation_id', '{{ $dispatch->id }}');

            $.ajax({
                url: '{{ route("quotations.savePdf") }}',
                type: 'POST',
                data: formData,
                processData: false, // prevent jQuery from processing the data
                contentType: false, // prevent jQuery from setting content type
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (data) {
                    alert('PDF saved successfully!');
                },
                error: function (xhr, status, error) {
                    console.error('Upload error:', error);
                    alert('Failed to upload PDF.');
                }
            });
        });
    });
</script> --}}
    <script>
        // window.onload = function () {
        //   const $element = $('#quotation_download_save');

        // //   $element.css({
        // //     'width': '1826px', // Your content is wide
        // //     'margin': '0px',
        // //     'padding': '0px',
        // //     'background': 'transparent'
        // //   });

        //   const element = $element[0];

        //   const opt = {
        //     margin: [0, 0, 0, 0],
        //     filename: 'quotation.pdf',
        //     image: { type: 'jpeg', quality: 1 },
        //     html2canvas: {
        //       scale: 1, // scale down 1200px to ~792px (A4 width)
        //       useCORS: true
        //     },
        //     jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        //     pagebreak: { avoid: ['.no-break'] }
        //   };

        //   html2pdf().set(opt).from(element).save();
        // };
    </script>
@endpush
