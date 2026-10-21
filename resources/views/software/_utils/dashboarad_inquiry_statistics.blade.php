@push('push_style')
    <style>
        .stat-card {
            transition: all 0.3s ease-in-out;
            cursor: pointer;
        }

        .stat-card:hover {
            background-color: #0076ec;
            /* Bootstrap's light gray */
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            /* subtle hover shadow */
        }

        .stat-card {
            transition: background-color 0.3s ease, transform 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            background-color: #cff4fc !important;
            /* Bootstrap 5 `bg-info-subtle` */
            transform: translateY(-2px);
        }
    </style>
@endpush

@if ($modules['currentGuard'] === 'admin_software')

    <div class="row mb-1 mt-4">
        @if (isset($showNumericModule['totalCompanies']))
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalCompanyCard">
                <a href="{{ route('company.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-warning rounded">
                                    <i class="ti ti-building ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">{{ $showNumericModule['totalCompanies'] ?? '00' }}
                                </h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Company</h5>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Expired Company</span>
                                <span
                                    class="badge bg-danger px-3 py-1">{{ isset($showNumericModule['expiredCompanies']) ? $showNumericModule['expiredCompanies'] : '0' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Inactive Company</span>
                                <span
                                    class="badge bg-warning px-3 py-1">{{ isset($showNumericModule['inactiveCompanies']) ? $showNumericModule['inactiveCompanies'] : '0' }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endif

        @if (isset($showNumericModule['totalPlans']))
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalPlanMasterCard">
                <a href="{{ route('plan-master.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-warning rounded">
                                    <i class="ti ti-list-details ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">{{ $showNumericModule['totalPlans'] ?? '00' }}
                                </h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Plans</h5>
                            {{-- <small class="text-muted">Last week</small> --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Inactive Plans</span>
                                <span
                                    class="badge bg-danger px-3 py-1">{{ isset($showNumericModule['inactivePlans']) ? $showNumericModule['inactivePlans'] : '0' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>General Plans</span>
                                <span
                                    class="badge bg-success px-3 py-1">{{ isset($showNumericModule['generalPlans']) ? $showNumericModule['generalPlans'] : '0' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Private Plans</span>
                                <span
                                    class="badge bg-warning px-3 py-1">{{ isset($showNumericModule['privatePlans']) ? $showNumericModule['privatePlans'] : '0' }}</span>
                            </div>

                        </div>
                    </div>
                </a>
            </div>
        @endif
    </div>




@endif

@if (isset($marketingStatus) && count($marketingStatus) > 0)
    {{-- {{ dd('marketingStatus 30', $marketingStatus) }} --}}
    <div class="pt-0 rounded-4">
        <div class="row g-4 justify-content-left">
            @php
                $defaultColors = ['info'];
            @endphp
            @foreach ($marketingStatus as $marketing_status)
                @php
                    $colorClass = $defaultColors[$loop->index % count($defaultColors)];
                    $icon = $marketing_status['icon'] ?? 'ti ti-speakerphone';
                    $status_color = $marketing_status['status_color'] ?? '#ffffff';
                    $status_color_2 = $marketing_status['status_color_2'] ?? $status_color;
                    $text_color = $marketing_status['text_color'] ?? '#ffffff';
                @endphp
                <div class="col-6 col-sm-4 col-md-3 mt-0 col-lg-2 mt-3">
                    <a href="{{ route('inquiry.index') }}" target="_blank" class="inquiry-status-link"
                        data-selectedinquirystatusid="{{ $marketing_status['id'] }}">
                        <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 shadow-sm stat-card bg-transition"
                            style="background: linear-gradient(90deg, {{ $status_color }}, {{ $status_color_2 }}) !important; color: {{ $text_color }} !important">
                            <div class="d-flex align-items-center justify-content-center rounded-1 badge bg-label-{{ $colorClass }}"
                                style="width: 48px; height: 48px;background-color: #ffffff !important;">
                                <i class="{{ $icon }}" style="color: {{ $status_color_2 }} !important;"></i>
                            </div>
                            {{-- <div>
                                <div class="fw-bold fs-5 text-dark" style="color: {{ $text_color}} !important">{{ $marketing_status['inquiries_count'] }}</div>
                                <div class="text-uppercase small text-dark" style="color: {{ $text_color}} !important"><b>{{ $marketing_status['name'] }}</b></div>
                            </div> --}}
                            <div style="min-width: 0; flex: 1;">
                                <div class="fw-bold fs-5 text-dark" style="color: {{ $text_color }} !important">
                                    {{ $marketing_status['inquiries_count'] }}</div>
                                <div class="text-uppercase small text-dark text-wrap"
                                    style="color: {{ $text_color }} !important; word-break: break-word;">
                                    <b>{{ $marketing_status['name'] }}</b>
                                </div>
                            </div>

                        </div>
                    </a>
                </div>
            @endforeach

            {{-- @foreach ($marketingStatus as $marketing_status)
                @php
                    $colorClass = $defaultColors[$loop->index % count($defaultColors)];
                    $icon = $marketing_status['icon'] ?? 'ti ti-speakerphone';
                    $status_color = $marketing_status['status_color'] ?? '';
                @endphp
                <div class="col-6 col-sm-4 col-md-3 mt-0 col-lg-2 mt-3">
                    <div class="d-flex align-items-start gap-3 p-3 bg-white rounded-3 shadow-sm stat-card bg-transition">
                        <div class="d-flex align-items-center justify-content-center rounded-1 badge bg-label-{{ $colorClass }}"
                            style="width: 48px; height: 48px;background-color: {{ $status_color}} !important;">
                            <i class="{{ $icon }}" style="color: #ffff !important;"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-dark">{{ $marketing_status['inquiries_count'] }}</div>
                            <div class="text-uppercase text-black"><b>{{ $marketing_status['name'] }}</b></div>
                        </div>
                    </div>
                </div>
            @endforeach --}}
        </div>
    </div>
@endif

@if (isset($showNumericModule) && count($showNumericModule) > 0)
    <div class="row mb-1 mt-4">
        @if (isset($showNumericModule['totalInquiry']))
            {{-- Total Inquiry --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalInquiryCard">
                <a href="{{ route('inquiry.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-success rounded">
                                    <i class="ti ti-speakerphone ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">{{ $showNumericModule['totalInquiry'] ?? '00' }}
                                </h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Inquiry</h5>
                            {{-- <small class="text-muted">Last week</small> --}}

                        </div>
                    </div>
                </a>
            </div>
        @endif

        @if (isset($showNumericModule['totalFollowups']))
            {{-- Total Follow-Up --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalFollowupsCard">
                <a href="{{ route('followup.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-warning rounded">
                                    <i class="ti ti-calendar-check ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">
                                    {{ $showNumericModule['totalFollowups'] ?? '00' }}</h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Follow-Up</h5>
                            {{-- <small class="text-muted">Last week</small> --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Pending Follow-Up</span>
                                <span
                                    class="badge bg-warning text-dark px-3 py-1">{{ isset($showNumericModule['totalPendingFollowUp']) ? $showNumericModule['totalPendingFollowUp'] : '0' }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Res. Add Follow-Up</span>
                                <span
                                    class="badge bg-success px-3 py-1">{{ isset($showNumericModule['totalResponseAddedFollowUp']) ? $showNumericModule['totalResponseAddedFollowUp'] : '0' }}</span>
                            </div>

                        </div>
                    </div>
                </a>
            </div>
        @endif

        @if (isset($showNumericModule['totalCustomer']))
            @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
            {{-- Total Customer --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalCustomerCard">
                <a href="{{ route('customer.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-primary rounded">
                                    <i class="ti ti-users-group ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">{{ $showNumericModule['totalCustomer'] ?? '00' }}
                                </h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Customer</h5>
                            {{-- <small class="text-muted">Last week</small> --}}

                        </div>
                    </div>
                </a>
            </div>
            @endif
        @endif

        @if (isset($showNumericModule['totalVisit']))
            {{-- Total Visit --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalVisitCard">
                <a href="{{ route('visit.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-danger rounded">
                                    <i class="ti ti-user-pin ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">{{ $showNumericModule['totalVisit'] ?? '00' }}
                                </h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Visit</h5>
                            {{-- <small class="text-muted">Last week</small> --}}

                        </div>
                    </div>
                </a>
            </div>
        @endif

        @if (isset($showNumericModule['totalOrder']))
            @if ($modules['currentGuard'] === 'admin_software' || ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id'])))
            {{-- Total Order --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalOrderCard">
                <a href="{{ route('sales-order.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-info rounded">
                                    <i class="ti ti-report-analytics ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">{{ $showNumericModule['totalOrder'] ?? '00' }}
                                </h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Orders</h5>
                            <h6 class="text-success mb-0"><strong>₹
                                    {{ $showNumericModule['totalOrderAmount'] ?? '00' }}</strong></h6>
                            {{-- <small class="text-muted">Last week</small> --}}

                        </div>
                    </div>
                </a>
            </div>
            @endif
        @endif

        @if (isset($showNumericModule['totalDispatch']))
            {{-- Total Dispatch --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-4" id="totalDispatchCard">
                <a href="{{ route('dispatch_orders.index') }}" target="_blank">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div class="badge bg-label-dark rounded">
                                    <i class="ti ti-truck ti-md"></i>
                                </div>
                                <h1 class="mb-0 fw-bold text-primary">
                                    {{ $showNumericModule['totalDispatch'] ?? '00' }}</h1>
                            </div>
                            <h5 class="card-title mb-1 pt-2">Total Dispatch</h5>
                            <h6 class="text-success mb-0">
                                <strong>{{ $showNumericModule['totalDispatchItemQuantity'] ?? '0' }} Qty</strong></h6>
                            {{-- <small class="text-muted">Last week</small> --}}

                        </div>
                    </div>
                </a>
            </div>
        @endif
    </div>
@endif
{{-- <div class="container-fluid">
        <!-- Stats Section -->
        <div class="row">


        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="ti ti-list-details"></i> Order Management System</h4>
            </div>
            <div class="table-container">
                <table class="table data-table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>QA No</th>
                            <th>Date</th>
                            <th>Order Ref</th>
                            <th>Party Code</th>
                            <th>Item Name</th>
                            <th>Pending</th>
                            <th>FG Stock</th>
                            <th>Del. Date</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  --}}
