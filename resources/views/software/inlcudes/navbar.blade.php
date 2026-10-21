@php
    $currentUserProfile = \App\Helpers\Helper::getLoginUser('admin_software');
    $userName = 'User Name';
    $profileIcon = asset('software/img/default/profile.png');
    $roleName = 'Admin';
    if ($currentUserProfile?->profile) {
        $profileIcon = null;
    }
    $userName = $currentUserProfile?->name;
    $u_name = $currentUserProfile?->username;
    $parent_type_id = '';
    $company_id = '';
    // dd($currentUserProfile->toArray());
    if (!$currentUserProfile && auth()->guard('team_person')->check()) {
        $currentUserProfile = auth()->guard('team_person')->user();
        $userName = $currentUserProfile->name ?? 'Team Member';
        $parent_type_id = $currentUserProfile->parent_type_id ?? '';
        $company_id = $currentUserProfile->company_id ?? '';
        $roleName = $currentUserProfile->team_role->name ?? 'Team Member';
        $u_name = $currentUserProfile->team_role->name ?? 'Team Member';
        $profileIcon = asset('software/img/default/profile.png');
    } else {
        $userName = $currentUserProfile?->name ?? 'User Name';
        if ($currentUserProfile && method_exists($currentUserProfile, 'team_role')) {
            $roleName = $currentUserProfile->team_role->name ?? 'Admin';
        }
    }
@endphp


<nav class="layout-navbar container-fuild navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="ti ti-menu-2 ti-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        {{-- <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
                    <i class="ti ti-search ti-md me-2"></i>
                    <span class="d-none d-md-inline-block text-muted">Search (Ctrl+/)</span>
                </a>
            </div>
        </div>
        <!-- /Search --> --}}

        @if (isset($mainInquiryAddBtn) && !empty($mainInquiryAddBtn))
            <a class="btn btn-primary waves-effect waves-light text-white btn-sm mt-lg-0"
                href="{{ route('inquiry.create') }}">
                <i class="menu-icon ti ti-plus"></i>
                <span class="d-none d-lg-inline">Add Inquiry</span>
            </a>
        @endif

        <ul class="navbar-nav flex-row align-items-center ms-auto">

            @if (isset($punchType) && !empty($punchType) && ($punchType == 'in' || $punchType == 'out'))
                @php $punchText = $punchType == 'in' ? 'Punch In' : 'Punch Out'; @endphp

                <span class="punch_in_time_dis">
                    @if (isset($punchInTime) && !empty($punchInTime))
                        Punch In Time : {{ $punchInTime }}
                    @endif
                </span>&nbsp;&nbsp;
            @endif
            <!-- User -->

            <li class="nav-item dropdown me-3 position-relative">
                <a class="nav-link position-relative" href="javascript:;" id="notif-bell" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="ti ti-bell ti-md"></i>
                    <span id="notif-count"
                        class="badge bg-danger rounded-pill position-absolute start-100 translate-middle p-1"
                        style="top: 12px !important;left: 32px !important"></span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0" id="notif-dropdown"
                    style="width: 360px; max-height: 500px; overflow-y: auto;" data-bs-popper="static">
                    <li
                        class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <h6 class="mb-0 fw-bold">Notifications</h6>
                        <button class="btn btn-sm text-success d-flex align-items-center gap-1" id="notif-mark-read"
                            style="font-size: 0.85rem;box-shadow: none;">
                            <i class="ti ti-check-double"></i> Mark all as read
                        </button>
                    </li>

                    <li>
                        <div id="notif-list" class="list-group list-group-flush">
                            {{-- Example notification item --}}
                            {{-- <a href="#" class="list-group-item list-group-item-action d-flex gap-3 align-items-start border-0">
                                <div class="bg-light rounded-circle p-2">
                                    <i class="ti ti-home fs-5 text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1 fw-semibold text-dark">Maintenance request update</h6>
                                        <small class="text-muted">5h ago</small>
                                    </div>
                                    <div class="text-muted small">
                                        The request for <strong>John Doe</strong> in <strong>Apartment 301</strong> was <span class="text-success fw-semibold">Completed</span>.
                                    </div>
                                </div>
                            </a> --}}

                            {{-- More items dynamically injected via JS --}}
                        </div>
                    </li>

                    <li>
                        <a href="{{ route('notification.index') }}"
                            class="dropdown-item text-center text-primary fw-medium border-top">
                            View all notifications
                        </a>
                    </li>
                </ul>

            </li>



            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ !empty($authLoginUserDetail->profile_image) ? asset($authLoginUserDetail->profile_image) : asset('software/img/default/profile.png') }}"
                            alt="profile" class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile') }}">

                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{ !empty($authLoginUserDetail->profile_image) ? asset($authLoginUserDetail->profile_image) : asset('software/img/default/profile.png') }}"
                            alt="profile" class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-medium d-block">{{ $userName ?? '' }}</span>
                                    <small class="text-muted">{{ $roleName }}</small>

                                </div>

                            </div>
                        </a>
                    </li>
                    @if (isset($parent_type_id) && $parent_type_id == 0 && isset($company_id) && !empty($company_id))
                        <li>
                            <a class="dropdown-item" href="{{ route('company.detail', $company_id) }}?tab=profile-tab">
                                <div class="flex-grow-1">
                                    <span class="fw-medium d-block">Company Details</span>
                                </div>
                            </a>
                        </li>
                    @endif
                    {{-- <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-profile-user.html">
                            <i class="ti ti-user-check me-2 ti-sm"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                    </li> --}}
                    {{-- <li>
                        <a class="dropdown-item" href="pages-account-settings-account.html">
                            <i class="ti ti-settings me-2 ti-sm"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-account-settings-billing.html">
                            <span class="d-flex align-items-center align-middle">
                                <i class="flex-shrink-0 ti ti-credit-card me-2 ti-sm"></i>
                                <span class="flex-grow-1 align-middle">Billing</span>
                                <span
                                    class="flex-shrink-0 badge badge-center rounded-pill bg-label-danger w-px-20 h-px-20">2</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-faq.html">
                            <i class="ti ti-help me-2 ti-sm"></i>
                            <span class="align-middle">FAQ</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="pages-pricing.html">
                            <i class="ti ti-currency-dollar me-2 ti-sm"></i>
                            <span class="align-middle">Pricing</span>
                        </a>
                    </li> --}}
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item logout-btn" href="{{ route('software.logout') }}"
                            onclick="event.preventDefault();
                                document.getElementById('logout-form').submit();">
                            <i class="ti ti-logout me-2 ti-sm"></i>
                            <span class="align-middle">Log Out</span>
                            <form id="logout-form" action="{{ route('software.logout') }}" method="POST"
                                class="d-none">
                                @csrf
                            </form>
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>

    {{-- <!-- Search Small Screens -->
    <div class="navbar-search-wrapper search-input-wrapper d-none">
        <input type="text" class="form-control search-input container-fuild border-0" placeholder="Search..."
            aria-label="Search..." />
        <i class="ti ti-x ti-sm search-toggler cursor-pointer"></i>
    </div> --}}
</nav>

@if (isset($is_plan_expired) && $is_plan_expired == true && isset($mainPlanExpiryDate) && !empty($mainPlanExpiryDate))
    <div id="plan-expiry-banner" class="py-0 mt-2 d-flex justify-content-between align-items-center"
        style="clear: both;">
        <div class="scrolling-text">
            <h5 class="mt-3">Your {{ $mainPlanName ?? '' }} expires on
                {{ \Carbon\Carbon::parse($mainPlanExpiryDate)->format('d-m-Y') }}.
        </div>
    </div>
@endif
