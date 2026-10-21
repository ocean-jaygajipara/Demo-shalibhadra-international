@extends('software.layout.app')

@php
    $page_title = $modules['title'] ?? null;
    $folder_path = $modules['folder_path'] ?? null;
    $route = $modules['route'] ?? null;
@endphp

@section('title', 'Inspection Report')

@section('page_leavel_style')
 <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .report-container, .report-container * {
                visibility: visible;
            }
            .report-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .fab-container {
                display: none !important;
            }
        }

        .report-container {
            background: #fff;
            padding: 15px;
            max-width: 100%;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            position: relative;
            min-height: 60px;
            padding: 8px;
        }

        .header-left {
            flex: 1;
            z-index: 1;
        }

        .header-left h2 {
            margin: 0;
            font-size: 24px;
            color: #e91e63;
            font-weight: bold;
        }

        .header-left h2 span {
            font-weight: normal;
        }

        .header-center {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            text-align: center;
            z-index: 0;
        }

        .header-center .pink-bar {
            color: #000;
            text-align: center;
            padding: 4px 20px;
            font-size: 16px;
            font-weight: bold;
            white-space: nowrap;
            margin-bottom: 3px;
        }

        .header-center .address-info {
            font-size: 13px;
            line-height: 1.4;
            font-weight: 200;
            white-space: nowrap;
        }

        .header-right {
            flex: 1;
            text-align: right;
            z-index: 1;
        }

        .header-right h3 {
            margin: 0;
            font-size: 28px;
            color: #e91e63;
            font-weight: normal;
            font-style: italic;
        }

        .address-box {
            position: relative;
            border: 2px solid #000;
            padding: 8px;
            margin-bottom: 10px;
            font-size: 11px;
            line-height: 1.4;
        }

        .form-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .form-field {
            display: flex;
            gap: 5px;
        }

        .form-field label {
            font-weight: bold;
            min-width: 80px;
        }

        .form-field .underline {
            flex: 1;
            border-bottom: 1px solid #000;
            position: relative;
        }

        .sr-no-section {
            text-align: right;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .sr-no-section span {
            font-weight: bold;
            margin-right: 5px;
        }

        .sr-no-box {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 15px;
            min-width: 60px;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
            margin-top: 10px;
        }

        td, th {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th {
            background: #fff;
            font-weight: bold;
            font-size: 10px;
        }

        .text-left {
            text-align: left !important;
        }

        .param-cell {
            text-align: left;
            font-weight: normal;
        }

        .sample-header {
            writing-mode: horizontal-tb;
            font-size: 9px;
            padding: 2px;
        }

        .fab-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 1050;
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

        .remark-cell {
            min-width: 60px;
        }
    </style>
@endsection

@section('content')

<div class="report-container">
    <!-- Header Section -->
    <div class="header">
        <div class="header-left">
            <div style="width: 20%; text-align:center;vertical-align: middle;">
                <img src="{{ $QcInspection->company->company_logo_url ?? asset('software/img/logo.png') }}"
                        style="height:40px;">
            </div>
        </div>
        <div class="header-center">
            <div class="pink-bar">INSPECTION REPORT</div>
            <div class="address-info" style="margin-top: 25px !important;">
                {!! nl2br(strip_tags(str_replace('</p>', "</p>\n", optional($QcInspection->company)->address ?? '-'))) !!}


            </div>
        </div>
        <div class="header-right">
            {{-- <h3>dutux<sup>®</sup></h3> --}}
        </div>
    </div>

    <hr style="border: 1px solid #000; margin: 20px 0 10px 0;">  <!-- HR added here -->


    <!-- Form Details -->
    <div class="form-details">
        <div class="form-field">
            <label>Vendor Name :</label>
            <div class="underline">{{ $QcInspection?->vendor?->vendor_name ?? '' }}</div>
        </div>
        <div class="form-field">
            <label>Item Code:</label>
            <div class="underline">{{ $QcInspection?->product_code ?? '' }}</div>
        </div>

        <div class="form-field" style="grid-column: span 2; display: flex; align-items: center;">
            <label>Item Name:</label>
            <div class="underline" style="width: 150px;">{{ $QcInspection?->product_name ?? '' }}</div>

            <label style="margin-left: 40px;">Weight:</label>
            <div class="underline" style="width: 100px;">{{ $QcInspection?->received_weight ?? '' }}</div>

            <label style="margin-left: 40px;">Ok Weight:</label>
            <div class="underline" style="width: 100px;">{{ $QcInspection?->ok_weight ?? '' }}</div>
        </div>
    </div>

    @if ($QcInspection->product && !empty($QcInspection->product->attachments_list))
        <div class="product-attachments-section mb-3 fab-container-exclude" style="font-size: 12px; margin-top: 15px;">
            <strong>Product Attachments / Drawings:</strong>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;">
                @foreach ($QcInspection->product->attachments_list as $attach)
                    @php
                        $company = $QcInspection->product->company ?? null;
                        $company_slug = \Illuminate\Support\Str::slug($company->company_name ?? 'default-company');
                        $relativePath = "uploads/{$QcInspection->product->company_id}-{$company_slug}/product-attachments/{$attach['file_path']}";
                        $fileUrl = asset($relativePath);
                    @endphp
                    <a href="{{ $fileUrl }}" target="_blank" style="padding: 4px 8px; border: 1px solid #e91e63; color: #e91e63; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa fa-file-pdf"></i> {{ $attach['original_name'] ?? basename($attach['file_path']) }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Sr. No Section -->
    <div class="sr-no-section" style="margin-top:17px;">
        <span>QC Inspection No.</span>
        <span class="sr-no-box">{{ $QcInspection->qc_inspection_no ?? '-' }}</span>
        <div style="display: inline-block; margin-left: 10px;">
            <label style="font-weight: bold;">QC Inspection Date:</label>
            <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 100px; margin-left: 5px;">{{ $QcInspection->qc_inspection_date ? \Carbon\Carbon::parse($QcInspection->qc_inspection_date)->format('d-m-Y') : '-' }}</span>
        </div>
    </div>

    <!-- Inspection Table -->
<table>
        <thead>
            <tr>
                <th style="width: 30px; font-size: 13px; ">Sr.<br>No.</th>
                <th style="width: 100px; font-size: 13px; ">Dimension</th>
                <th style="width: 70px; font-size: 13px; ">Size</th>
                <th style="width: 70px; font-size: 13px; ">LTL</th>
                <th style="width: 70px; font-size: 13px; ">UTL</th>
                <th style="width: 90px; font-size: 13px; ">Instrument</th>
                @for($i = 1; $i <= 10; $i++)
                    <th class="sample-header" style="font-size: 13px;">Size {{ $i }}</th>
                @endfor
                <th class="remark-cell" style="font-size: 13px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @php $serial_no = 1; @endphp
            @forelse($QcInspection->material_details ?? [] as $material)
                <tr>
                    <td>{{ $serial_no++ }}</td>
                    <td class="param-cell">{{ $material->dimension ?? '-' }}</td>
                    <td>{{ $material->size ?? '-' }}</td>
                    <td>{{ $material->ltl ?? '-' }}</td>
                    <td>{{ $material->utl ?? '-' }}</td>
                    <td>{{ $material->instrument ?? '-' }}</td>
                    @for($i = 1; $i <= 10; $i++)
                        <td>{{ $material->{"size$i"} ?? '' }}</td>
                    @endfor
                    <td>{{ $material->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center text-muted">No items found</td>
                </tr>
            @endforelse
        </tbody>
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

        function fabToggle() {
            const fabMenu = document.getElementById("fabMenu");
            fabMenu.classList.toggle("d-none");
        }
    </script>

    </script>

    <!-- Floating Action Button Container -->
    <div class="fab-container position-fixed bottom-0 end-0 m-4" style="z-index: 1050;">

        <!-- FAB Actions -->
        <div class="fab-actions d-flex flex-column align-items-end" id="fabMenu">
            <a class="btn btn-md rounded-circle text-white bg-primary shadow mb-2 fab-action" id="sendQcInspectionBtn"
                href="{{ route($modules['route'] . '.QcInspectionPdf', [$QcInspection->id]) }}"><i
                    class="fas fa-envelope"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-warning shadow mb-2 fab-action"><i
                    class="fas fa-phone"></i></a>
            <a class="btn btn-md rounded-circle text-white bg-success shadow mb-2 fab-action"
                href="{{ route($modules['route'] . '.QcInspectionPrint', [$QcInspection->id]) }}" title="Print" target="_blank"><i
                    class="fas fa-print"></i></a>
        </div>

        <!-- Main FAB Button -->
        <button class="btn btn-danger rounded-circle btn-md shadow fab-toggle">
            <i class="fas fa-pen"></i>
        </button>
    </div>
    <script>
        $(document).ready(function() {
            $('#sendQcInspectionBtn').on('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                window.location.href = url.toString();
            });
        });
    </script>
@endpush
