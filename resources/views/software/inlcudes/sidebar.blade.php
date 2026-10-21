<!-- Menu -->
@php
    $sidebar_active = Route::current()->getName();
    $sidebar_active_full_name = Route::currentRouteName();
    $expand = explode('.', $sidebar_active);
    $sidebar_active = count($expand) > 0 ? $expand[0] : '';

    $module_menu = [];
    if (isset($modules) && count($modules) > 0) {
        // isset($module_menu['company_id'])
        $module_menu = \App\Helpers\Helper::getModuleMenu('active', ['platform' => 'panel']);
    } elseif (Auth::guard('admin_software')->check()) {
        $module_menu = \App\Helpers\Helper::getModuleMenu('all', ['platform' => 'panel']);
    }
    // dd("L-24", $sidebar_active, $module_menu->toArray());

    $dashboard_main_menu = ['software', 'roles', 'permissions'];
    $company_id = null;
    $sidebarMainLogo = asset('software/img/logo.png');
    $sidebarSmallLogo = asset('software/img/ring.png');

    if (isset($modules) && isset($modules['company_id'])) {
        $company_id = $modules['company_id'];
        $company = app\Helpers\Helper::getCompanyDetailById($company_id);
        if ($company && $company?->id) {
            $sidebarMainLogo = $company?->company_logo_url ?? $sidebarMainLogo;
            $sidebarSmallLogo = $company?->company_favicon_url ?? $sidebarSmallLogo;
        }
    }
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('software.dashboard') }}" class="app-brand-link">

            <img src="{{ $sidebarMainLogo }}" class="main-logo" />
            <img src="{{ $sidebarSmallLogo }}" class="small-logo" />
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
            <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner mt-1 mb-5">
        @if (Auth::guard('team_person')->check() || Auth::guard('admin_software')->check() || Auth::guard('customer')->check())
            @foreach ($module_menu as $row_menu)
                <li class="menu-item {{ in_array($sidebar_active, $row_menu->main_menu_routes) ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon {{ $row_menu->menu_icon }}"></i>
                        <div data-i18n="{{ $row_menu->name }}">{{ $row_menu->name }}</div>
                    </a>
                    <ul class="menu-sub">
                        @foreach ($row_menu->sub_menu as $sub_menu)
                            @php
                                // $active = ($sub_menu->route_name == $sidebar_active_full_name) ? 'active' : '';

                                $active = '';
                                if ($sub_menu->route_name) {
                                    if ($sub_menu->route_name === 'products.rate-sheet.index') {
                                        $active = (request()->routeIs('products.rate-sheet.*') || request()->is('*products/rate-sheet*')) ? 'active' : '';
                                    } elseif ($sub_menu->route_name === 'products.index') {
                                        $active = (request()->routeIs('products.*') && !request()->routeIs('products.rate-sheet.*') && !request()->is('*products/rate-sheet*')) ? 'active' : '';
                                    } else {
                                        $getRouteName = explode('.', $sub_menu->route_name);
                                        $firstPart = $getRouteName[0];
                                        $active = request()->routeIs($firstPart . '.*') ? 'active' : '';
                                    }
                                }
                                $menuLink = (!empty($sub_menu->route_name) && Route::has($sub_menu->route_name)) ? route($sub_menu->route_name) : null;

                                if(!$menuLink && $sub_menu?->url){
                                    $menuLink = URL($sub_menu?->url);
                                }
                            @endphp

                            <li class="menu-item {{ $active }}">
                                {{-- !empty($sub_menu->route_name) ? route($sub_menu->route_name) : 'javascript:void(0);' --}}
                                <a href="{{ $menuLink }}"
                                    class="menu-link">
                                    <div>{{ $sub_menu->name }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        @elseif (Auth::guard('admin_software')->check())
            <ul class="menu-inner py-1 {{ isset($module_menu) && count($module_menu) != 0 ? '' : 'd-none' }}">
                <!-- Dashboards -->
                <li class="menu-item {{ in_array($sidebar_active, $dashboard_main_menu) ? 'active open' : '' }} ">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-smart-home"></i>
                        <div data-i18n="Dashboards">Dashboards </div>
                        {{-- <div class="badge bg-primary rounded-pill ms-auto">5</div> --}}
                    </a>
                    <ul class="menu-sub">

                        <li class="menu-item">
                            <a href="{{ route('software.dashboard') }}" class="menu-link">
                                <div data-i18n="Dashboard">Dashboard</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('tracking-dashboard.index') }}" class="menu-link">
                                <div data-i18n="Tracking Dashboard">Tracking Dashboard</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="{{ route('reports.index') }}" class="menu-link">
                                <div data-i18n="Report">Report</div>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Marketing - Start --}}
                <li
                    class="menu-item {{ $sidebar_active == 'marketing-status' || $sidebar_active == 'inquiry' || $sidebar_active == 'followup' || $sidebar_active == 'marketing-source-of-inquiry' || $sidebar_active == 'marketing-follow-up-reason' ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-speakerphone"></i>
                        <div data-i18n="Marketing">Marketing</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item {{ $sidebar_active == 'inquiry' ? 'active' : '' }}">
                            <a href="{{ route('inquiry.index') }}" class="menu-link">
                                <div data-i18n="Inquiry">Inquiry</div>
                            </a>
                        </li>

                        <li class="menu-item {{ $sidebar_active == 'followup' ? 'active' : '' }}">
                            <a href="{{ route('followup.index') }}" class="menu-link">
                                <div data-i18n="Followup">Followup</div>
                            </a>
                        </li>

                        <li class="menu-item {{ $sidebar_active == 'marketing-source-of-inquiry' ? 'active' : '' }}">
                            <a href="{{ route('marketing-source-of-inquiry.index') }}" class="menu-link">
                                <div data-i18n="Source Of Inquiry">Source Of Inquiry</div>
                            </a>
                        </li>

                        <li class="menu-item {{ $sidebar_active == 'marketing-follow-up-reason' ? 'active' : '' }}">
                            <a href="{{ route('marketing-follow-up-reason.index') }}" class="menu-link">
                                <div data-i18n="Follow Up Reason">Follow Up Reason</div>
                            </a>
                        </li>

                        <li class="menu-item {{ $sidebar_active == 'marketing-status' ? 'active' : '' }}">
                            <a href="{{ route('marketing-status.index') }}" class="menu-link">
                                <div data-i18n="Marketing Status">Marketing Status</div>
                            </a>
                        </li>

                        <li class="menu-item {{ $sidebar_active == 'import-inquiry' ? 'active' : '' }}">
                            <a href="{{ route('import-inquiry.index') }}" class="menu-link">
                                <div data-i18n="Import Inquiry">Import Inquiry </div>
                            </a>
                        </li>

                    </ul>
                </li>
                {{-- Marketing - End --}}

                {{-- Customer - Start --}}
                <li
                    class="menu-item {{ $sidebar_active == 'customer' || $sidebar_active == 'customer-type' || $sidebar_active == 'visit' || $sidebar_active == 'complaintype' || $sidebar_active == 'complain' || $sidebar_active == 'import-customer' ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon icon-base ti ti-user-cog"></i>
                        <div data-i18n="Customer">Customer</div>

                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item {{ $sidebar_active == 'customer-type' ? 'active' : '' }}">
                            <a href="{{ route('customer-type.index') }}" class="menu-link">
                                <div data-i18n="Customer Type">Customer Type</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $sidebar_active == 'customer' ? 'active' : '' }}">
                            <a href="{{ route('customer.index') }}" class="menu-link">
                                <div data-i18n="Customer">Customer</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $sidebar_active == 'visit' ? 'active' : '' }}">
                            <a href="{{ route('visit.index') }}" class="menu-link">
                                <div data-i18n="Visit">Visit</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $sidebar_active == 'complaintype' ? 'active' : '' }}">
                            <a href="{{ route('complaintype.index') }}" class="menu-link">
                                <div data-i18n="Complain Type">Complain Type</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $sidebar_active == 'complain' ? 'active' : '' }}">
                            <a href="{{ route('complain.index') }}" class="menu-link">
                                <div data-i18n="Complain">Complain</div>
                            </a>
                        </li>
                        <li class="menu-item {{ $sidebar_active == 'import-customer' ? 'active' : '' }}">
                            <a href="{{ route('import-customer.index') }}" class="menu-link">
                                <div data-i18n="Import Customer">Import Customer</div>
                            </a>
                        </li>

                    </ul>
                </li>
                {{-- Customer - End --}}
        @endif

        {{-- <li class="menu-item">
            <div class="divider">
                <div class="divider-text">Static Menu</div>
            </div>
        </li>
        <li class="menu-item {{ $sidebar_active == '#' ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-mail"></i>
                <div data-i18n="Static Menu">Static Menu</div>
            </a>
            <ul class="menu-sub">
            </ul>
        </li> --}}

        @if (Auth::guard('admin_software')->check())
            <li class="menu-item">
                <div class="divider">
                    <div class="divider-text">Only Office</div>
                </div>
            </li>
            {{-- Admin  Setting  - Start --}}
            <li
                class="menu-item {{ $sidebar_active == 'plan-master' || $sidebar_active == 'company' || $sidebar_active == 'team-role' ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon icon-base ti ti-settings-star"></i>
                    {{-- <i class="ti ti-settings-spark"></i> --}}
                    <div data-i18n="Company Setting">Company Setting </div>
                    {{-- <div class="badge bg-primary rounded-pill ms-auto">5</div> --}}
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ $sidebar_active == 'plan-master' ? 'active' : '' }}">
                        <a href="{{ route('plan-master.index') }}" class="menu-link">
                            <div data-i18n="Plan Master">Plan Master</div>
                        </a>
                    </li>
                    <li class="menu-item {{ $sidebar_active == 'company' ? 'active' : '' }}">
                        <a href="{{ route('company.index') }}" class="menu-link">
                            <div data-i18n="Register Company">Register Company</div>
                        </a>
                    </li>
                    <li class="menu-item {{ $sidebar_active == 'team-role' ? 'active' : '' }}">
                        <a href="{{ route('team-role.index') }}" class="menu-link">
                            <div data-i18n="Team Role">Team Role</div>
                        </a>
                    </li>
                </ul>
            </li>
            {{-- Admin  Setting  - End --}}

            {{-- Manage Email --}}
            <li class="menu-item {{ $sidebar_active == '#' ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    {{-- <i class="menu-icon icon-base fa fa-envelope"></i> --}}
                    <i class="menu-icon icon-base ti ti-settings"></i>
                    <div data-i18n="Settings">Settings</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ $sidebar_active == 'manage-email' ? 'active' : '' }}">
                        <a href="{{ route('manage-email.index') }}" class="menu-link">
                            <div data-i18n="Manage Email">Manage Email</div>
                        </a>
                    </li>
                    <li class="menu-item {{ $sidebar_active == 'user' ? 'active' : '' }}">
                        <a href="{{ route('user.index') }}" class="menu-link">
                            <div data-i18n="Admin User">Admin User </div>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- <li class="menu-item {{ $sidebar_active == '#' ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    {{-- <i class="menu-icon icon-base fa fa-user-shield"></i> --}}
                    {{-- <i class="menu-icon icon-base fa fa-user-check"></i>
                    <div data-i18n="Accounts">Accounts</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ $sidebar_active == 'account-ledger' ? 'active' : '' }}">
                        <a href="{{ route('account-ledger.index') }}" class="menu-link">
                            <div data-i18n="Account Ledger">Account Ledger</div>
                        </a>
                    </li>

                    <li class="menu-item {{ $sidebar_active == 'payment-receipt' ? 'active' : '' }}">
                        <a href="{{ route('payment-receipt.index') }}" class="menu-link">
                            <div data-i18n="Payment Receipt">Payment Receipt</div>
                        </a>
                    </li>
                    <li class="menu-item {{ $sidebar_active == 'outstanding-report' ? 'active' : '' }}">
                        <a href="{{ route('outstanding-report.index') }}" class="menu-link">
                            <div data-i18n="Out Standing">Out Standing</div>
                        </a>
                    </li> --}}

                {{-- </ul> --}}

            {{-- </li> --}}
        @endif
    </ul>
</aside>
