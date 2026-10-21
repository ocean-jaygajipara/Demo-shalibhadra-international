@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($jobReceive) && isset($jobReceive->company_id) && !empty($jobReceive->company_id)) {
        $company_id = $jobReceive->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
            // dd($sidebarMainLogo);
        }
    }
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px;
        }

        td,
        th {
            /* border: 1px solid #000; */
            padding: 5px;
            vertical-align: top;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
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
@endsection

@section('content')
    <div style="width: 100%; padding: 10px;">

        <!-- Header -->
        {{-- <table style="width: 100%;border-collapse: collapse;">
            <tr style="height:80px;">
                <td style="width: 20%; text-align:center;vertical-align: middle;">
                    <img src="{{ asset('software/img/logo.png') }}" style="height:40px;">
                </td>

            </tr>
        </table> --}}

        <div>
            <div class="card" style="border: 2px solid #333; padding: 10px;">

                @for ($i = 1; $i <= 2; $i++)
                    <!-- ONE COPY START -->
                    <div style="padding: 10px;">

                        <div class="row" style="padding: 10px; align-items: center;">

                            <!-- LEFT LOGO -->
                            <div class="col-md-6">
                                <img src="{{ $sidebarheaderLogo }}" style="height: 120px;">
                            </div>

                            <!-- RIGHT LOGO -->
                            {{-- <div class="col-md-6" style="text-align: right;">
                                <img src="http://127.0.0.1:8000/software/img/logo.png" style="height: 92px;">
                            </div> --}}

                        </div>

                        <div style="text-align: center; font-size: 13px; margin-top: 5px;">
                            <h4> Plot No. 4536, GIDC, Phase III, Dared, JAMNAGAR<br>
                                Ph : 02882731337</h4>
                        </div>

                        <hr>

                        <!-- Header -->
                        <div class="row">
                            <div class="col-md-6" style="padding: 20px; text-align: left;">

                                <h5 style="margin: 0;"> <label>Recpt No:</label> {{ $jobReceive->job_work_receive_no }}</h5>
                                <h5 style="margin: 0;">{{ $jobReceive->vendor->vendor_name }}</h5>
                                <h5 style="margin: 0;"><label> Job Work Issue No:</label>
                                    {{ $jobReceive?->job_work_issue?->job_work_issue_no }}
                                    @php
                                        // dd( $jobReceive->job_work_issue->job_work_issue_no);
                                    @endphp
                                </h5>

                            </div>

                            <div class="col-md-6" style="padding: 20px; text-align: right;">
                                <h5 style="margin: 0;">
                                    <label>Date:</label>
                                    {{ \Carbon\Carbon::parse($jobReceive->job_work_receive_date)->format('d-m-Y') }}
                                </h5>
                                {{-- <h5 style="margin-top: 18px;"><label>Sub Challan No:</label> 418</h5> --}}
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div style="padding: 20px;">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 80px; text-align: center;">Sr. No</th>
                                        <th>Particulars</th>
                                        <th style="text-align: right;">Grs</th>
                                        <th style="text-align: right;">Qty</th>
                                        <th style="text-align: right;">Kg</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        $total_qty = 0;
                                        $total_kg = 0;
                                        $total_grs = 0;
                                    @endphp

                                    @foreach ($jobReceive->job_work_receive_items as $index => $item)
                                        {{-- $item->product->grosse_per_qty; --}}
                                        <tr>
                                            <td style="text-align: center;">{{ $index + 1 }}</td>

                                            <td>{{ $item->particulars ?? ($item->product_name ?? '-') }}</td>

                                            <td style="text-align: right;">
                                                {{ number_format($item->quantity / ($item->product->grosse_per_qty ?? 1), 2) }}
                                            </td>


                                            <td style="text-align: right;">
                                                {{ number_format($item->quantity ?? 0) }}
                                            </td>

                                            <td style="text-align: right;">
                                                {{ number_format($item->received_weight ?? 0, 2) }}
                                            </td>
                                        </tr>

                                        @php
                                            // $total_grs += $item->grs ?? 0;
                                            $total_qty += $item->quantity ?? 0;
                                            $total_kg += $item->received_weight ?? 0;
                                        @endphp
                                    @endforeach

                                    <!-- TOTAL ROW -->
                                    <tr style="background: #f8f9fa; font-weight:bold;">
                                        <td colspan="3">Total Weight & Qty</td>

                                        {{-- <td style="text-align: right;">{{ number_format($total_grs, 2) }}</td> --}}
                                        {{-- <td></td> --}}
                                        <td style="text-align: right;">{{ number_format($total_qty) }}</td>

                                        <td style="text-align: right;">{{ number_format($total_kg, 2) }}</td>
                                    </tr>
                                </tbody>

                            </table>

                            <!-- Received By -->
                            <div style="margin-top: 20px;padding: 10px;">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Received By:</strong>
                                    </div>
                                    <div class="col-6" style="text-align: right;">
                                        <div><strong>SHALIBHADRA INTERNATIONAL PVT LTD</strong></div>
                                        <div>Chirag</div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                    <!-- ONE COPY END -->

                    <!-- Divider between 2 copies -->
                    @if ($i == 1)
                        <hr style="border: none; margin: 75px 0;">
                    @endif
                @endfor

            </div>

        </div>






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
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendSalesOrderBtn"
                href="{{ route($modules['route'] . '.SendjobWorkReceivePdf', [$jobReceive->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.jobWorkReceivePrint', [$jobReceive->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendSalesOrderBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('sales_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString(); // redirect with param
            });
        });
    </script>
@endpush
