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
        <div class="checkmark bg-green-100 mx-auto mb-6 rounded-full w-24 h-24 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Thank You!</h1>
        <p class="text-gray-600 mb-6">Your QR code has been successfully redeemed.</p>
        <div class="border-t border-gray-200 pt-6">
            <p class="text-sm text-gray-500">If you have any questions, please contact us.</p>
        </div>
    </div>

    <script>
        // This ensures the animation plays when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            const checkmark = document.querySelector('.checkmark');
            checkmark.style.opacity = '1';
        });
    </script>
    <script>
        (function() {
            function c() {
                var b = a.contentDocument || a.contentWindow.document;
                if (b) {
                    var d = b.createElement('script');
                    d.innerHTML =
                        "window.__CF$cv$params={r:'9632d9f5b45b3a15',t:'MTc1MzE4NjQwMC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";
                    b.getElementsByTagName('head')[0].appendChild(d)
                }
            }
            if (document.body) {
                var a = document.createElement('iframe');
                a.height = 1;
                a.width = 1;
                a.style.position = 'absolute';
                a.style.top = 0;
                a.style.left = 0;
                a.style.border = 'none';
                a.style.visibility = 'hidden';
                document.body.appendChild(a);
                if ('loading' !== document.readyState) c();
                else if (window.addEventListener) document.addEventListener('DOMContentLoaded', c);
                else {
                    var e = document.onreadystatechange || function() {};
                    document.onreadystatechange = function(b) {
                        e(b);
                        'loading' !== document.readyState && (document.onreadystatechange = e, c())
                    }
                }
            }
        })();
    </script>

</body>

</html>
