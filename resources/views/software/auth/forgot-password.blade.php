@extends('software.layout.app')

@section('title', 'Forgot Password')

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/css/pages/page-auth.css') }}" />
    {{-- <link rel="stylesheet" href="{{ asset('software/vendor/css/demo.css') }}" /> --}}
    {{-- <link rel="stylesheet" href="{{ asset('software/vendor/css/core.css') }}" /> --}}
    {{-- <link rel="stylesheet" href="{{ asset('software/vendor/libs/pickr/pickr-themes.css') }}" /> --}}
    <link rel="stylesheet" href="{{ asset('software/vendor/fonts/iconify-icons.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap') }}">
    <style>
        .mb-6 {
            margin-block-end: 1.5rem !important;
        }
    </style>
@endsection

@section('content')
    <!-- Content -->

    @php
        // In case data is passed via session flash
        $adminFromSession = session('admin');
        $admin = $admin ?? $adminFromSession;
        $showResetForm = $showResetForm ?? session('showResetForm');
    @endphp

    <div class="authentication-wrapper authentication-cover authentication-bg">
        <div class="authentication-inner row">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-7 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('software/img/illustrations/auth-forgot-password-illustration-light.png') }}"
                        alt="auth-login-cover" class="img-fluid my-5 auth-illustration" />

                    <img src="{{ asset('software/img/illustrations/bg-shape-image-light.png') }}" alt="auth-login-cover"
                        class="platform-bg" />
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Forgot Password -->
            <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
                <div class="w-px-400 mx-auto mt-12 mt-5">


                    <h4 class="mb-1">Forgot Password? 🔒</h4>
                    <p class="mb-6">Enter your email and we’ll send you a reset link</p>
                    <!-- Alerts -->
                    @if (session('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mt-3">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('software.forgot.password.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="app_key" class="form-label">App Key</label>
                            <input type="text" class="form-control" id="app_key" name="app_key"
                                placeholder="Enter your App Key" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="Enter your registered email" required>
                        </div>
                        <button class="btn btn-primary d-grid w-100">Send Reset Link</button>
                    </form>


                    <div class="text-center mt-3">
                        <a href="{{ route('software.login') }}" class="d-flex justify-content-center">

                            <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
                            Back to login
                        </a>
                    </div>

                    {{-- @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif --}}



                </div>
            </div>
            <!-- /Forgot Password -->
        </div>
    </div>

    <!-- / Content -->
@endsection

@section('page_leavel_script')

    <script src="{{ asset('software/js/pages-auth.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#togglePassword').on('click', function() {
                let input = $('#password');
                let type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).toggleClass('tabler-eye tabler-eye-off');
            });

            $('#toggleConfirmPassword').on('click', function() {
                let input = $('#confirmPassword');
                let type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).toggleClass('tabler-eye tabler-eye-off');
            });
        });
    </script>

@endsection
