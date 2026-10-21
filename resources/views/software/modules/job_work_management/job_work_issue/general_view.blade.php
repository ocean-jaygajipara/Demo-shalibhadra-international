@extends('software.layout.app')

@php
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;

    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');
    $sidebarheaderLogo = '';
    if (isset($jobIssue) && isset($jobIssue->company_id) && !empty($jobIssue->company_id)) {
        $company_id = $jobIssue->company_id;
        $company = App\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
            $sidebarheaderLogo = $company?->order_header_logo_url ?? '';
        }
        // dd($sidebarheaderLogo);
    }

function formatWeight($val)
{
    if ($val === null || $val === "" || $val === ".") {
        return $val;
    }

    $num = floatval($val);
    if (!is_numeric($num)) {
        return "";
    }

    $intPart = floor($num);
    $decimal = round(($num - $intPart) * 1000);
    $decStr = str_pad($decimal, 3, "0", STR_PAD_LEFT);

    $firstDigit = intval($decStr[0]);
    $lastTwo    = intval(substr($decStr, 1));

    $newFirstDigit = $firstDigit;
    $finalDecimal = 0;

    if ($lastTwo <= 29) {
        $finalDecimal = $newFirstDigit * 100;
    } elseif ($lastTwo <= 79) {
        $finalDecimal = $newFirstDigit * 100 + 50;
    } else {
        $newFirstDigit = $firstDigit + 1;
        $finalDecimal = $newFirstDigit * 100;
    }

    $result = $intPart + ($finalDecimal / 1000);
    return number_format($result, 3, '.', '');
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
            border: 1px solid #000;
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
        <table style="width: 100%; border:1px solid #000; border-collapse: collapse;">
            <tr style="height:80px;">
                <td style="width: 20%; text-align:center; border-right:1px solid #000; vertical-align: middle;">
                    <img src="{{ asset($sidebarheaderLogo) }}" style="height:40px;">
                </td>
                <td
                    style="width: 60%; text-align:center; font-size:40px; font-weight:bold; border-right:1px solid #000; vertical-align: middle;">
                    Delivery Challan
                </td>
                <td style="width: 20%; text-align:center; font-weight:bold; vertical-align: middle;font-size: 30px;">
                    Original
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse: collapse;">
            <tr style="height:52px;">
                <td style="width:70%; font-size:17px; line-height:1.4; vertical-align: middle;">
                    Movement of inputs or partially processed goods for job-work under Rule 55 of The Central Goods And
                    Service Tax Rules, 2017 from one factory to another factory for further processing / operation.
                </td>
            </tr>
        </table>

        <table style="width:100%; border-collapse: collapse; border:1px solid #000; table-layout: fixed;">
            <tr>
                <!-- Left Side -->
                <td style="width:50%; vertical-align: top; font-size:14px; padding:0;">
                    <div style="display:flex; flex-direction:column; box-sizing:border-box;">

                        <!-- Supplier Details -->
                        <div style="padding:8px; border-bottom:1px solid #000; min-height: 140px; max-height: 150px;">
                            <div style="font-weight:bold; margin-bottom:5px;">Name and Address of Supplier / Manufacturer:
                            </div>
                            <div style="margin-bottom:5px;">
                                {{ $jobIssue->company->company_name ?? '-' }}<br>
                                {!! nl2br(strip_tags($jobIssue->company->address)) !!}

                            </div>
                            <div><b>GST NO:</b> {{ $jobIssue->company->gst_no ?? '-' }}</div>
                        </div>

                        <!-- Items List (Auto expand) -->
                        <div style="padding:8px; font-size:14px; line-height:20px;min-height: 230px;">
                            <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:16px;">
                                <div>Description</div>
                                <div style="width:120px; text-align:right;">Qty (Kgs)</div>
                            </div>
                            <div style="border-top:1px solid #000; margin:12px -9px;"></div>

                            @foreach ($jobIssue->job_work_issue_items as $item)
                                @php
                                    $gross_weight = floatval($item->gross_weight ?? 0);
                                    $bl_percentage = floatval($item->bl_percentage ?? 0);
                                    // BL weight calculation
                                    $bl_weight = ($gross_weight * $bl_percentage) / 100;
                                    // Final weight
                                    $final_weight = floatval($gross_weight) + floatval($bl_weight);
                                @endphp
                                <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
                                    <div>{{ $item->product_name }}</div>
                                    <div style="width:120px; text-align:right;">
                                        {{ formatWeight($final_weight) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Bottom Section -->
                        <div style="padding:8px; font-size:14px;">

                            <div style="margin-top:5px;">Vehicle No : <b>{{ !empty($jobIssue->vehicle_no) ? $jobIssue->vehicle_no : 'GJ 10 TW 4865' }}</b></div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="display:flex; justify-content:space-between; font-weight:bold;">
                                <div>3. Quantity (Pcs / Kgs)</div>
                                <div>{{ number_format($jobIssue->total_gross_weight, 3) }}</div>


                            </div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="display:flex; justify-content:space-between; font-weight:bold;">
                                <div>4. Tariff Classification</div>
                                <div>{{ $jobIssue->tariff_code ?? '-' }}</div>
                            </div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="font-weight:bold;">5. Estimated Value / Tax</div>

                            <div
                                style="display:flex; font-weight:bold; border-top:1px solid #000; border-bottom:1px solid #000; margin:10px -8px;">
                                <div style="flex:1; padding:5px; border-right:1px solid #000;">Total Amount</div>
                                <div style="width:120px; text-align:right; padding:5px; border-right:1px solid #000;">CGST
                                    Rs.</div>
                                <div style="width:120px; text-align:right; padding:5px; border-right:1px solid #000;">SGST
                                    Rs.</div>
                                <div style="width:120px; text-align:right; padding:5px;">IGST Rs.</div>
                            </div>

                            <div style="display:flex; border-bottom:1px solid #000; margin:10px -8px;">
                                <div style="flex:1; padding:5px; border-right:1px solid #000;">
                                    {{ number_format($jobIssue->sub_total, 2) }}
                                </div>
                                <div style="width:120px; text-align:right; padding:5px; border-right:1px solid #000;">
                                    {{ number_format($jobIssue->total_cgst_amount, 2) }}
                                </div>
                                <div style="width:120px; text-align:right; padding:5px; border-right:1px solid #000;">
                                    {{ number_format($jobIssue->total_sgst_amount, 2) }}
                                </div>
                                <div style="width:120px; text-align:right; padding:5px;">
                                    {{ number_format($jobIssue->igst_amount ?? 0, 2) }}
                                </div>
                            </div>

                            <div style="margin-top:10px; font-weight:bold;">
                                6. Date & Time of Issue :
                                <span style="font-weight:normal;">
                                    {{ \Carbon\Carbon::parse($jobIssue->job_work_issue_date)->format('d/m/Y - h:i A') }}
                                </span>
                            </div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="margin-top:10px; font-weight:bold;min-height: 82px; max-height: 82px;">
                                7. Nature of processing/Manufacturing required :
                                <span style="font-weight:normal;">{{ $jobIssue->job_work_type->name ?? '-' }}</span>
                            </div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="margin-top:10px; font-weight:bold;margin-bottom:20px;">8. Factory/Place of
                                processing:<br>
                                <span style="font-weight:normal;">
                                    {{ $jobIssue->vendor->vendor_name ?? '-' }}<br>
                                    {{ $jobIssue->vendor->address ?? '-' }}<br>
                                    GSTIN: {{ $jobIssue->vendor->gst_no ?? '-' }}
                                </span>
                            </div>

                            <div style="border-top:1px solid #000; margin:10px -8px;"></div>

                            <div style="margin-top:10px; font-weight:bold;">
                                For, <b>{{ $jobIssue->company->company_name ?? '-' }}</b><br><br>
                                Date : {{ \Carbon\Carbon::today()->format('d/m/Y') }}<br>
                                Place : {{ $jobIssue->city->city ?? '-' }}<br>
                                Authorised Signature
                            </div>
                        </div>
                    </div>
                </td>



                <!-- Right side (50%) -->
                <td style="width:50%; padding:0; vertical-align: top; font-size:14px; text-align:center;">
                    <div style="height:140px; padding:0; box-sizing:border-box;">
                        <!-- Top half (90px) -->
                        <div style="height:70px; border-bottom:1px solid #000; display:flex; box-sizing:border-box;">
                            <!-- Left vertical part (50%) -->
                            <div style="width:50%; border-right:1px solid #f8f7fa; box-sizing:border-box;">
                                <div style="padding:8px; font-size:16px; font-weight:bold; margin-bottom:5px;">
                                    To be filled by the processing unit in Original and Duplicate Challan
                                </div>
                            </div>

                            <!-- Right vertical part (50%) -->
                            <div style="width:50%; box-sizing:border-box;">
                                <div style="padding:8px; font-size:16px; font-weight:bold;">
                                    Serial Number<br>
                                    {{ $jobIssue->job_work_issue_no ?? '-' }}
                                </div>
                            </div>
                        </div>



                        <!-- Bottom half (90px) -->
                        <div style="height:70px; padding:8px; box-sizing:border-box;">
                            <div style="font-weight:bold; margin-bottom:5px;    font-size: 16px;">
                                1. Details of type, qty, date & time of processing done and return of processed goods to
                                parent factory:
                            </div>
                            {{-- <div>
                                45, Industrial Estate, GIDC, Ankleshwar, Bharuch, Gujarat - 393002
                            </div> --}}
                        </div>
                    </div>


                    <div style="height:700px; padding:0; box-sizing:border-box; position:relative;">
                        <!-- Horizontal line 40px from top -->
                        <div style="position:absolute; top:40px; left:0; width:100%; border-top:1px solid #000;"></div>

                        <!-- Content below the line -->
                        <div
                            style="display: flex; font-size:14px; height:475px; margin-top:0; border-top:1px solid #000; border-bottom:1px solid #000;">
                            <!-- Column 1: Size/Type -->
                            <div
                                style="flex:1; border-right:1px solid #000; font-size:16px; text-align:center; box-sizing:border-box; line-height:35px;">
                                <b>Size/Type</b>
                            </div>

                            <!-- Column 2: Quantity -->
                            <div
                                style="flex:1; border-right:1px solid #000; font-size:16px; text-align:center; box-sizing:border-box; line-height:35px;">
                                <b>Quantity</b>
                            </div>

                            <!-- Column 3: Date -->
                            <div
                                style="flex:1; text-align:center; font-size:16px; box-sizing:border-box; line-height:35px;">
                                <b>Date</b>
                            </div>
                        </div>
                        {{-- secion 6 --}}
                        <div>
                            <div style="margin-top:14px; font-weight:bold; text-align: left;margin-left: 20px;min-height: 50px !important; max-height:50px !important;">
                                2. Nature of Proc. / Manufacturing done

                            </div>

                        </div>
                        <div style="border-top:1px solid #000; margin:8px -1px;"></div>

                        {{-- section 7 --}}
                        <div style="min-height: 78px; max-height: 78px;">

                            <div style="margin-top:15px; font-weight:bold;text-align: left;margin-left: 20px;">
                                3. Qty of waste material / reject returned to the factory & Buring loss/Process
                                loss
                            </div>

                        </div>
                        {{-- section 8 --}}
                        <div style="border-top:1px solid #000; margin:8px -1px;;"></div>
                        <div>
                            <div
                                style="margin-top:15px;margin-bottom:15px;font-weight:bold;text-align: left;margin-left: 20px;">
                                4. Name & Address of the Processor :<br>
                                <span style="font-weight:normal;"> {{ $jobIssue->vendor->vendor_name ?? '-' }}<br>
                                    {{ $jobIssue->vendor->address ?? '-' }}<br>
                                    GSTIN: {{ $jobIssue->vendor->gst_no ?? '-' }}</span>
                            </div>
                        </div>



                        <div style="border-top:1px solid #000; 8px -1px;"></div>
                        {{-- section 9 --}}
                        <div>
                            <div style="margin-top:15px; font-weight:bold;text-align: left;margin-left: 20px;">
                                <b> 5. Signature of Processor :</b>
                            </div>
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

    </script>

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendQuotationBtn"
                href="{{ route($modules['route'] . '.SendjobWorkIssuePdf', [$jobIssue->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.jobWorkIssuePrint', [$jobIssue->id]) }}" title="Print"
                target="_blank"><i class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendQuotationBtn').on('click', function(e) {
                e.preventDefault();

                const hide = localStorage.getItem('quotation_without_price') === 'true' ? '1' : '0';
                const url = new URL(this.href);
                url.searchParams.set('without_price', hide);
                window.location.href = url.toString();
            });
        });

        $(document).ready(function() {
            const firstLoadKey = 'jobWorkIssueFirstLoad_{{ $jobIssue->id }}';
            const isFirstLoad = localStorage.getItem(firstLoadKey) !== 'done';

            if (isFirstLoad) {
                localStorage.setItem(firstLoadKey, 'done');
                let printUrl = "{{ route($modules['route'] . '.jobWorkIssuePrint', [$jobIssue->id]) }}";
                const hide = localStorage.getItem('quotation_without_price') === 'true' ? '1' : '0';
                printUrl += '?without_price=' + hide;
                window.location.href = printUrl;
            }
        });
    </script>
@endpush
