@extends('software.layout.app')

@php
    $page_title = 'SIPL Rate Sheet Manager';
    $route = 'products';
@endphp

@section('title', $page_title)

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}">
    <style>
        .scrap-rate-card {
            background: linear-gradient(135deg, #ffffff 0%, #fefcf9 100%);
            border: 1px solid #ede4d8;
            border-left: 5px solid #ff9f43;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .scrap-rate-card:hover {
            box-shadow: 0 6px 24px rgba(255, 159, 67, 0.15);
        }
        .scrap-avatar-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #ff9f43 0%, #ff6b35 100%);
            box-shadow: 0 4px 14px rgba(255, 159, 67, 0.4);
            border-radius: 10px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .table-custom-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .table-responsive {
            overflow-x: auto !important;
            overflow-y: auto !important;
            max-height: calc(100vh - 270px) !important;
            min-height: 420px;
            width: 100%;
            position: relative;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
        }

        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .table-responsive::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .rate-excel-table {
            width: 100% !important;
            min-width: 1550px;
            margin-bottom: 0 !important;
            border-collapse: separate;
            border-spacing: 0;
        }
        .rate-excel-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .rate-excel-table th {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap !important;
            background-color: #f1f5f9 !important;
            color: #334155;
            vertical-align: middle;
            padding: 11px 12px;
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            box-shadow: inset 0 -2px 0 #cbd5e1, inset 0 1px 0 #e2e8f0, 0 2px 4px rgba(0, 0, 0, 0.05) !important;
        }
        .rate-excel-table td {
            font-size: 0.83rem;
            white-space: nowrap !important;
            vertical-align: middle;
            padding: 9px 12px;
        }
        .col-no { width: 4% !important; text-align: center; }
        .col-code { width: 14% !important; text-align: left; }
        .col-name { width: 18% !important; text-align: left; }
        .col-scrap { width: 7% !important; text-align: right; }
        .col-gross-weight { width: 7% !important; text-align: right; }
        .col-kg-gross { width: 6% !important; text-align: right; }
        .col-job-rate { width: 7% !important; text-align: right; }
        .col-labour { width: 7% !important; text-align: right; }
        .col-kgs-kg { width: 6% !important; text-align: center; }
        .col-burning { width: 7% !important; text-align: right; }
        .col-casting { width: 6% !important; text-align: right; }
        .col-cost-kg { width: 8% !important; text-align: right; }
        .col-gross-rate { width: 7% !important; text-align: right; }
        .col-pcs-rate { width: 7% !important; text-align: right; }

        .dataTables_wrapper {
            width: 100%;
        }
        .rate-card-main {
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            overflow: visible;
            background: #ffffff;
        }

        .rate-card-header-styled {
            background: #ffffff;
            padding: 14px 20px;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            position: sticky;
            top: 74px;
            z-index: 30;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
        }

        .rate-tabs-track {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 0;
            list-style: none;
        }

        .tab-pill-btn {
            display: inline-flex;
            align-items: center;
            padding: 9px 18px;
            font-size: 0.84rem;
            font-weight: 600;
            color: #64748b;
            border: none !important;
            border-radius: 9px !important;
            background: transparent;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .tab-pill-btn:hover:not(.active) {
            background: rgba(255, 255, 255, 0.7);
            color: #1e293b;
            transform: translateY(-1px);
        }

        .tab-pill-btn .tab-icon-wrapper {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-size: 1rem;
            transition: all 0.25s ease;
            background: rgba(115, 103, 240, 0.12);
            color: #7367f0;
        }

        .tab-pill-btn .tab-counter-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            margin-left: 10px;
            transition: all 0.25s ease;
            background: #e0e7ff;
            color: #4f46e5;
        }

        /* Active State - Elevated White Pill with Dynamic Theme Accent */
        .tab-pill-btn.active {
            background: #ffffff !important;
            color: #7367f0 !important;
            box-shadow: 0 4px 14px rgba(115, 103, 240, 0.18), 0 1px 3px rgba(0, 0, 0, 0.04) !important;
            transform: translateY(-1px);
        }

        .tab-pill-btn.active .tab-icon-wrapper {
            background: linear-gradient(135deg, #7367f0 0%, #8f85f3 100%);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(115, 103, 240, 0.4);
        }

        .tab-pill-btn.active .tab-counter-badge {
            background: #7367f0;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(115, 103, 240, 0.3);
        }

        /* Dynamic Palette Themes */
        .tab-pill-btn.tab-theme-0 .tab-icon-wrapper { background: rgba(115, 103, 240, 0.12); color: #7367f0; }
        .tab-pill-btn.tab-theme-0 .tab-counter-badge { background: #e0e7ff; color: #4f46e5; }
        .tab-pill-btn.tab-theme-0.active { color: #7367f0 !important; box-shadow: 0 4px 14px rgba(115, 103, 240, 0.18), 0 1px 3px rgba(0, 0, 0, 0.04) !important; }
        .tab-pill-btn.tab-theme-0.active .tab-icon-wrapper { background: linear-gradient(135deg, #7367f0 0%, #8f85f3 100%); color: #ffffff; }
        .tab-pill-btn.tab-theme-0.active .tab-counter-badge { background: #7367f0; color: #ffffff; }

        .tab-pill-btn.tab-theme-1 .tab-icon-wrapper { background: rgba(0, 207, 232, 0.14); color: #00bad1; }
        .tab-pill-btn.tab-theme-1 .tab-counter-badge { background: #cffafe; color: #0891b2; }
        .tab-pill-btn.tab-theme-1.active { color: #0099ab !important; box-shadow: 0 4px 14px rgba(0, 207, 232, 0.2), 0 1px 3px rgba(0, 0, 0, 0.04) !important; }
        .tab-pill-btn.tab-theme-1.active .tab-icon-wrapper { background: linear-gradient(135deg, #00cfe8 0%, #0099ab 100%); color: #ffffff; }
        .tab-pill-btn.tab-theme-1.active .tab-counter-badge { background: #00bad1; color: #ffffff; }

        .tab-pill-btn.tab-theme-2 .tab-icon-wrapper { background: rgba(255, 159, 67, 0.14); color: #ff9f43; }
        .tab-pill-btn.tab-theme-2 .tab-counter-badge { background: #ffedd5; color: #ea580c; }
        .tab-pill-btn.tab-theme-2.active { color: #e67e22 !important; box-shadow: 0 4px 14px rgba(255, 159, 67, 0.2), 0 1px 3px rgba(0, 0, 0, 0.04) !important; }
        .tab-pill-btn.tab-theme-2.active .tab-icon-wrapper { background: linear-gradient(135deg, #ff9f43 0%, #ff6b35 100%); color: #ffffff; }
        .tab-pill-btn.tab-theme-2.active .tab-counter-badge { background: #ff9f43; color: #ffffff; }

        .tab-pill-btn.tab-theme-3 .tab-icon-wrapper { background: rgba(40, 199, 111, 0.14); color: #28c76f; }
        .tab-pill-btn.tab-theme-3 .tab-counter-badge { background: #dcfce7; color: #16a34a; }
        .tab-pill-btn.tab-theme-3.active { color: #28c76f !important; box-shadow: 0 4px 14px rgba(40, 199, 111, 0.2), 0 1px 3px rgba(0, 0, 0, 0.04) !important; }
        .tab-pill-btn.tab-theme-3.active .tab-icon-wrapper { background: linear-gradient(135deg, #28c76f 0%, #48da89 100%); color: #ffffff; }
        .tab-pill-btn.tab-theme-3.active .tab-counter-badge { background: #28c76f; color: #ffffff; }

        .rate-meta-chip {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            color: #64748b;
            display: inline-flex;
            align-items: center;
        }
    </style>
@endsection

@section('content')
    <div class="px-1">
        <div class="d-flex flex-column flex-lg-row justify-content-lg-between align-items-start align-items-lg-center">
            @include('software.inlcudes.breadcrumb', [
                'breadcrumbArray' => [
                    ['title' => 'Products', 'url' => route('products.index')],
                    ['title' => $page_title, 'url' => ''],
                ],
            ])
            <div class="d-flex flex-wrap gap-2 mt-2 mt-lg-0">
                <a class="btn btn-outline-secondary waves-effect" href="{{ route('products.index') }}">
                    <i class="ti ti-arrow-left me-1"></i> Back to Products
                </a>
                @if(!empty($sheetData) && !empty($fileInfo['exists']))
                    <a class="btn btn-outline-success waves-effect" href="{{ route('products.rate-sheet.download') }}">
                        <i class="ti ti-download me-1"></i> Download Excel (.xlsx)
                    </a>
                    <button type="button" class="btn btn-warning waves-effect waves-light text-white" id="btn_sync_all_products">
                        <i class="ti ti-refresh me-1"></i> Sync Products with CRM DB
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show my-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-check fs-4 me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show my-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle fs-4 me-2"></i>
                <div>
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $currentScrapVal = 1000;
        if (!empty($sheetData)) {
            $firstSheet = reset($sheetData);
            if (!empty($firstSheet) && isset($firstSheet[0]['scrap_rate'])) {
                $parsed = floatval(preg_replace('/[^0-9.]/', '', $firstSheet[0]['scrap_rate']));
                if ($parsed > 0) $currentScrapVal = $parsed;
            }
        }
    @endphp

    @if(empty($sheetData) || empty($fileInfo['exists']))
        <!-- Empty State Card When No Sheet is Uploaded -->
        <div class="card my-4 border-0 shadow-sm text-center" style="border-radius: 16px; background: #ffffff;">
            <div class="card-body p-5">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background-color: #e8fadf;">
                    <i class="fa-solid fa-file-excel text-success fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Please Upload Rate Sheet First</h4>
                <p class="text-muted mb-4 fs-6 mx-auto" style="max-width: 520px;">
                    No active rate sheet found. Please upload your Excel rate sheet (.xlsx / .xls) to view and manage product weights, scrap rates, and labour charges.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    @php
                        $activeCompanyId = auth()->user()->company_id ?? 2;
                    @endphp
                    <a href="{{ route('company.detail', [$activeCompanyId]) }}?tab=profile-tab" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                        <i class="ti ti-upload me-1 fs-5"></i> Upload in Company Profile
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Global Brass Scrap Rate Card -->
        <div class="card my-3 scrap-rate-card">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-5 d-flex align-items-center">
                    <div class="scrap-avatar-icon me-3">
                        <i class="ti ti-currency-rupee fs-2"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold text-dark" style="letter-spacing: -0.3px;">Brass Scrap Rate</h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">Current Rate:</span>
                            <span class="badge bg-label-warning fw-bold fs-6 px-2 py-1" id="current_scrap_rate_display">
                                ₹ {{ number_format($currentScrapVal, 2) }} / kg
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 mt-3 mt-md-0">
                    <form id="global_scrap_rate_form" class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                        <div style="min-width: 190px;">
                            <select id="product_grade_id" name="product_grade_id" class="form-select select2">
                                <option value="">Select Grade</option>
                                @if(isset($productGrades) && count($productGrades) > 0)
                                    @foreach($productGrades as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="input-group" style="max-width: 230px;">
                            <span class="input-group-text bg-white fw-bold text-primary">₹</span>
                            <input type="number" step="0.01" min="1" id="global_scrap_rate_input" class="form-control fw-semibold" value="{{ $currentScrapVal }}" placeholder="e.g. 1000" required>
                            <span class="input-group-text bg-white text-muted">/ kg</span>
                        </div>
                        <button type="submit" class="btn btn-warning text-white waves-effect waves-light fw-semibold" id="btn_update_global_scrap" style="background: linear-gradient(135deg, #ff9f43 0%, #ff6b35 100%); border: none;">
                            <i class="ti ti-check me-1"></i> Update Rate
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Rate Sheet View / Tabs -->
    <div class="card rate-card-main">
        <div class="rate-card-header-styled">
            <ul class="nav rate-tabs-track" role="tablist">
                @php $tIdx = 0; @endphp
                @foreach ($sheetData as $sheetName => $items)
                    @php
                        $slug = \Illuminate\Support\Str::slug($sheetName);
                        $themeClass = 'tab-theme-' . ($tIdx % 4);
                        $tIdx++;
                    @endphp
                    <li class="nav-item" role="presentation">
                        <button class="nav-link tab-pill-btn {{ $themeClass }} {{ $loop->first ? 'active' : '' }}" 
                                data-bs-toggle="tab" 
                                data-bs-target="#tab-{{ $slug }}" 
                                role="tab" 
                                type="button" 
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="tab-icon-wrapper">
                                @if($loop->index % 4 == 0)
                                    <i class="ti ti-bolt"></i>
                                @elseif($loop->index % 4 == 1)
                                    <i class="ti ti-tools"></i>
                                @elseif($loop->index % 4 == 2)
                                    <i class="ti ti-layers-subtract"></i>
                                @else
                                    <i class="ti ti-file-spreadsheet"></i>
                                @endif
                            </span>
                            <span>{{ strtoupper($sheetName) }}</span>
                            <span class="tab-counter-badge">
                                {{ count($items) }}
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
            <div class="d-none d-lg-flex align-items-center gap-2">
                @php
                    $totalExcelProducts = 0;
                    foreach ($sheetData as $sItems) {
                        $totalExcelProducts += count($sItems);
                    }
                @endphp
                <div class="rate-meta-chip">
                    <i class="ti ti-layers-subtract me-1 text-primary"></i>
                    <span>Total: <strong class="text-dark">{{ $totalExcelProducts }} Products</strong> ({{ count($sheetData) }} Sheets)</span>
                </div>
            </div>
        </div>
        <div class="card-body pt-3">
            <div class="tab-content p-0">
                @foreach ($sheetData as $sheetName => $items)
                    @php
                        $slug = \Illuminate\Support\Str::slug($sheetName);
                    @endphp
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $slug }}" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover rate-excel-table rate-data-table w-100" id="table-{{ $slug }}-data" data-sheet-name="{{ $sheetName }}">
                                <thead>
                                    <tr>
                                        <th class="col-no">NO.</th>
                                        <th class="col-code">ITEM CODE</th>
                                        <th class="col-name">ITEM NAME</th>
                                        <th class="col-scrap">BRASS SCRAP RATE</th>
                                        <th class="col-gross-weight">GROSS WEIGHT</th>
                                        <th class="col-kg-gross">KG IN GROSS</th>
                                        <th class="col-job-rate">JOB WORK GROSS RATE</th>
                                        <th class="col-labour">GROSS LABOUR CHARGES</th>
                                        <th class="col-kgs-kg">KGS INTO KG</th>
                                        <th class="col-burning">BURNING LOSS KGS</th>
                                        <th class="col-casting">RAW CASTING CHARGES</th>
                                        <th class="col-cost-kg">COMPLETE ITEM COST IN KG</th>
                                        <th class="col-gross-rate">GROSS RATE</th>
                                        <th class="col-pcs-rate">PCS RATE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $row)
                                        <tr data-row-id="{{ $row['excel_row'] }}" data-sheet-name="{{ $sheetName }}" data-grade-id="{{ $row['db_product'] ? $row['db_product']->product_grade_id : '' }}">
                                            <td class="col-no text-center">{{ $row['no'] }}</td>
                                            <td class="col-code fw-semibold text-primary cell-item_code">{{ $row['item_code'] }}</td>
                                            <td class="col-name cell-item_name">{{ $row['item_name'] }}</td>
                                            <td class="col-scrap text-end font-monospace cell-scrap_rate">{{ $row['scrap_rate'] }}</td>
                                            <td class="col-gross-weight text-end cell-gross_weight">
                                                <span class="badge bg-light text-dark border font-monospace fw-bold">{{ $row['gross_weight'] }}</span>
                                            </td>
                                            <td class="col-kg-gross text-end font-monospace cell-kg_in_gross">{{ $row['kg_in_gross'] }}</td>
                                            <td class="col-job-rate text-end cell-job_work_rate font-monospace">{{ $row['job_work_rate'] }}</td>
                                            <td class="col-labour text-end font-monospace cell-gross_labour_charges">{{ $row['gross_labour_charges'] }}</td>
                                            <td class="col-kgs-kg text-center font-monospace cell-kgs_into_kg">{{ $row['kgs_into_kg'] }}</td>
                                            <td class="col-burning text-end font-monospace cell-burning_loss">{{ $row['burning_loss'] }}</td>
                                            <td class="col-casting text-end font-monospace cell-casting_charges">{{ $row['casting_charges'] }}</td>
                                            <td class="col-cost-kg text-end font-monospace fw-bold cell-complete_cost_kg">{{ $row['complete_cost_kg'] }}</td>
                                            <td class="col-gross-rate text-end font-monospace fw-bold text-primary cell-gross_rate">{{ $row['gross_rate'] }}</td>
                                            <td class="col-pcs-rate text-end font-monospace fw-bold text-success cell-pcs_rate">{{ $row['pcs_rate'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif



    <!-- Sync Log & Status Details Modal -->
    <div class="modal fade" id="syncLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-label-primary">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-list-check fs-3 me-2 text-primary"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0">Rate Sheet ⇄ CRM Products Sync Status Log</h5>
                            <small class="text-muted">Detailed synchronization report between Excel (<code>{{ $fileInfo['name'] ?? 'Rate Sheet' }}</code>) and CRM database</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border border-primary shadow-none bg-label-primary mb-0">
                                <div class="card-body p-3 text-center">
                                    <h3 class="mb-0 fw-bold text-primary">{{ $syncStats['total'] ?? 0 }}</h3>
                                    <small class="fw-semibold">Total Excel Items</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-success shadow-none bg-label-success mb-0">
                                <div class="card-body p-3 text-center">
                                    <h3 class="mb-0 fw-bold text-success">{{ $syncStats['matched'] ?? 0 }}</h3>
                                    <small class="fw-semibold">Successfully Synced & Matched</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border border-{{ ($syncStats['unmatched'] ?? 0) > 0 ? 'danger bg-label-danger' : 'secondary bg-label-secondary' }} shadow-none mb-0">
                                <div class="card-body p-3 text-center">
                                    <h3 class="mb-0 fw-bold {{ ($syncStats['unmatched'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">{{ $syncStats['unmatched'] ?? 0 }}</h3>
                                    <small class="fw-semibold">Not Found / Missing in DB</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (($syncStats['unmatched'] ?? 0) > 0)
                        <div class="alert alert-danger mb-3">
                            <h6 class="alert-heading fw-bold mb-1"><i class="ti ti-alert-triangle me-1"></i> Missing Products in CRM Database:</h6>
                            <p class="mb-2 small">These items from Excel could not be found in the CRM products master. Please add them or verify their Item Code / Name:</p>
                            <ul class="mb-0 small">
                                @foreach ($syncStats['unmatched_list'] as $unm)
                                    <li>
                                        <strong>[{{ strtoupper($unm['sheet_name']) }} - Row #{{ $unm['excel_row'] }}]</strong>
                                        Code: <code>{{ $unm['item_code'] ?: 'N/A' }}</code> | Name: <code>{{ $unm['item_name'] ?: 'N/A' }}</code>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-success d-flex align-items-center mb-3">
                            <i class="ti ti-circle-check fs-4 me-2"></i>
                            <div>
                                <strong>100% Synced!</strong> All <strong>{{ $syncStats['total'] ?? 0 }}</strong> products from <code>{{ $fileInfo['name'] ?? 'Rate Sheet' }}</code> are perfectly matched with CRM database records.
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover table-striped mb-0" id="sync_log_datatable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Sheet</th>
                                    <th>Excel Part No</th>
                                    <th>Excel Product Name</th>
                                    <th>Net Wt (1 Pc)</th>
                                    <th>Gross Wt (1 Pc)</th>
                                    <th>CRM Product Matched</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $logIdx = 1; @endphp
                                @foreach ($sheetData as $sName => $sItems)
                                    @foreach ($sItems as $item)
                                        <tr>
                                            <td>{{ $logIdx++ }}</td>
                                            <td><span class="badge bg-label-info">{{ $sName }}</span></td>
                                            <td><code class="fw-bold">{{ $item['item_code'] ?: 'N/A' }}</code></td>
                                            <td><small>{{ $item['item_name'] }}</small></td>
                                            <td><span class="badge bg-light text-dark border">{{ $item['calculated_net_wt'] }} g</span></td>
                                            <td><span class="badge bg-light text-dark border">{{ $item['calculated_gross_wt'] }} g</span></td>
                                            <td>
                                                @if ($item['db_product'])
                                                    <span class="text-success fw-semibold small">
                                                        <i class="ti ti-check me-1"></i>#{{ $item['db_product']->id }}: {{ $item['db_product']->product_name }}
                                                    </span>
                                                @else
                                                    <span class="text-danger fw-semibold small">
                                                        <i class="ti ti-x me-1"></i>Not in CRM
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item['db_product'])
                                                    <a href="{{ route('products.edit', $item['db_product']->id) }}" target="_blank" class="badge bg-success text-decoration-none">
                                                        <i class="ti ti-external-link me-1"></i> Synced (Edit)
                                                    </a>
                                                @else
                                                    <span class="badge bg-danger">Missing</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page_leavel_script')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('software/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2').select2();
            }

            let dtInstances = {};

            function initRateDataTable(tableElem) {
                let id = $(tableElem).attr('id');
                if (!id) return null;
                if (dtInstances[id]) return dtInstances[id];
                dtInstances[id] = $(tableElem).DataTable({
                    pageLength: 25,
                    responsive: false,
                    scrollX: false,
                    autoWidth: false,
                    order: [[0, 'asc']]
                });
                return dtInstances[id];
            }

            // Initialize active tab's DataTable
            $('.tab-pane.active .rate-data-table').each(function() {
                initRateDataTable(this);
            });

            // Grade filter for DataTables
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    if (!$(settings.nTable).hasClass('rate-data-table')) return true;
                    let selectedGrade = $('#product_grade_id').val();
                    if (!selectedGrade) return true;
                    let rowNode = settings.aoData[dataIndex].nTr;
                    let rowGrade = $(rowNode).attr('data-grade-id');
                    return rowGrade == selectedGrade;
                }
            );

            // Handle Tab Switching and Column Alignment dynamically for all sheet tabs
            $('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                let target = $(e.target).data('bs-target') || $(e.target).attr('href') || $(e.target).attr('data-bs-target');
                if (target) {
                    let activeTable = $(target).find('.rate-data-table');
                    if (activeTable.length) {
                        let id = activeTable.attr('id');
                        if (!dtInstances[id]) {
                            initRateDataTable(activeTable);
                        } else {
                            dtInstances[id].columns.adjust();
                        }
                    }
                }
                setTimeout(function() {
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                }, 50);
            });

            $('#product_grade_id').on('change', function() {
                Object.values(dtInstances).forEach(dt => {
                    if (dt) dt.draw();
                });
            });

            $('#sync_log_datatable').DataTable({
                pageLength: 20,
                responsive: true,
                order: [[0, 'asc']]
            });

            // Global Brass Scrap Rate Submit Handler
            $(document).on('submit', '#global_scrap_rate_form', function(e) {
                e.preventDefault();
                let newRate = $('#global_scrap_rate_input').val();
                let gradeId = $('#product_grade_id').val();
                if (!newRate || newRate <= 0) return;

                let btn = $('#btn_update_global_scrap');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

                $.ajax({
                    url: "{{ route('products.rate-sheet.update-scrap-rate') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || "{{ csrf_token() }}"
                    },
                    data: {
                        _token: "{{ csrf_token() }}",
                        scrap_rate: newRate,
                        product_grade_id: gradeId
                    },
                    success: function(res) {
                        btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Update Scrap Rate');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Success!',
                                text: res.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            alert(res.message);
                            location.reload();
                        }
                    },
                    error: function(err) {
                        btn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> Update Scrap Rate');
                        let msg = err.responseJSON?.message || 'Update failed.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg
                            });
                        } else {
                            alert(msg);
                        }
                    }
                });
            });

            // Sync All Products Handler
            $('#btn_sync_all_products').on('click', function() {
                Swal.fire({
                    title: 'Sync All Products with Rate Sheet?',
                    text: 'This will update Net Weight, Gross Weight, and Gross Per Qty (144) for all matching products from the active Rate Sheet.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ff9f43',
                    cancelButtonColor: '#82868b',
                    confirmButtonText: 'Yes, Sync Now!',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: "{{ route('products.rate-sheet.sync') }}",
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}"
                            }
                        }).then(response => {
                            return response;
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error.responseJSON?.message || error.statusText}`
                            );
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value?.status) {
                        Swal.fire({
                            title: 'Success!',
                            text: result.value.message,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                });
            });
        });
    </script>
@endpush
