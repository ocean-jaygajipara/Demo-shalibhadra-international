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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .checkmark {
            animation: scale 0.5s ease-in-out;
        }

        @keyframes scale {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            70% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>

</head>

<body>
    @php
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
    <div class="bg-white rounded-xl shadow-2xl p-8 m-4 max-w-md w-full text-center">
        <div class="mb-5">
            <img src="{{ $sidebarMainLogo }}" alt="" />
        </div>
        <div class="checkmark bg-red-100 mx-auto mb-6 rounded-full w-24 h-24 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Your QR code has been Expired.</h1>
        <div class="border-t border-gray-200 pt-6">
            <p class="text-sm text-gray-500">If you have any questions, please contact us.</p>
        </div>
    </div>
</body>

</html>
