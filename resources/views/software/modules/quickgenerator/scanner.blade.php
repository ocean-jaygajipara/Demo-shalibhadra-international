<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> @yield('title') | {{ env('APP_NAME', 'Ocean Infotech') }}</title>

    <meta name="description" content="" />
    <meta name="currentGuard" value="{{ isset($currentGuard) && !empty($currentGuard) ? $currentGuard : '' }}" />
    <meta name="parent_type_id" value="{{ isset($parent_type_id) && !empty($parent_type_id) ? $parent_type_id : '' }}" />

    {{-- When Companny id foudn in admin time set using yield --}}
    @if (trim($__env->yieldContent('company_id')))
        <meta name="company_id" value="{{ trim($__env->yieldContent('company_id')) ?? '' }}" />
    @else
        <meta name="company_id" value="{{ $authenticateUserDetails?->company_id ?? '' }}" />
    @endif
    <link rel="icon" type="image/x-icon" href="{{ asset('software/img/ring.png') }}" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 15px;
        }

        .login-card {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 450px;
        }

        .logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo img {
            width: 250px;
            height: auto;
        }

        .login-card h4 {
            font-weight: 600;
            text-align: center;
            margin-bottom: 10px;
        }

        .login-card p {
            font-size: 14px;
            color: #6c757d;
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-login {
            background-color: #28c76f;
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 6px;
        }

        .btn-login:hover {
            background-color: #28c76f;
            color: #fff;
        }

        .form-control:focus {
            border-color: #266BEE !important;
            box-shadow: 0 0 5px rgba(115, 103, 240, 0.4);
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    @php
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

    <div class="login-card">
        <!-- Logo Image -->
        <div class="logo">
            <img src="{{ $sidebarMainLogo }}" class="main-logo" />
        </div>
        @if (isset($show))
        <form action="{{ (isset($show)) ? route('geast.user.scanner', ['company_slug' => $company_slug, 'redemption_code_label' =>  $show?->redemption_code_label]) : '#' }}" method="POST">
            @csrf
            @isset($edit)
                @method('PUT')
            @endisset

            <input type="hidden" name="company_id" value="{{ old('company_id', $show?->company_id ?? '') }}">

            <!-- Code -->
            <div class="mb-3 d-none">
                <label for="code" class="form-label">Code</label>
                <input type="text" class="form-control" name="redemption_code_label" value="{{ old('code', $show->redemption_code_label ?? '') }}"
                    readonly id="code" placeholder="Enter your code">
            </div>

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="guest_name" value="{{ old('name') }}" id="name"
                    placeholder="Enter Your Name" required>
            </div>

            <!-- Mobile -->
            <div class="mb-3">
                <label for="mobile" class="form-label">Mobile No. <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="guest_mobile" value="{{ old('mobile') }}" id="mobile"
                    placeholder="Enter Your Mobile Number" required>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="guest_email" value="{{ old('email') }}" id="email"
                    placeholder="Enter Your Email" required>
            </div>

            <button type="submit" class="btn btn-login w-100">Submit</button>
        </form>
        @elseif(Session::get('success') && !$show)
        {{  Session::get('success') ?? '' }}
        @else

        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
