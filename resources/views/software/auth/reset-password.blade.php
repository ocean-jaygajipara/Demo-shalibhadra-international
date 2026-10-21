@extends('software.layout.app')

@section('title', 'Reset Password')

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/css/pages/page-auth.css') }}" />
    {{-- <link rel="stylesheet" href="{{ asset('software/vendor/fonts/iconify-icons.css') }}" /> --}}
    <link rel="stylesheet"
        href="{{ asset('https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap') }}">
    <style>
        .mb-6 {
            margin-block-end: 1.5rem !important;
        }
    </style>
@endsection

@section('content')
    <div class="authentication-wrapper authentication-cover authentication-bg">
        <div class="authentication-inner row">
            <!-- Left Illustration -->
            <div class="d-none d-lg-flex col-lg-7 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('software/img/illustrations/auth-reset-password-illustration-light.png') }}"
                        alt="reset-password" class="img-fluid my-5 auth-illustration" />
                    <img src="{{ asset('software/img/illustrations/bg-shape-image-light.png') }}" alt="background"
                        class="platform-bg" />
                </div>
            </div>

            <!-- Right Reset Form -->
            <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
                <div class="w-px-400 mx-auto mt-12 mt-5">

                    <h4 class="mb-1">Reset Password 🔒</h4>
                    <p class="mb-6">Enter your new password below</p>

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <!-- Required Hidden Fields -->
                        <input type="hidden" name="token" value="{{ request()->route('token') }}">
                        <input type="hidden" name="email" value="{{ request()->get('email') }}">

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input id="password" type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required
                                    autocomplete="new-password" placeholder="Enter new password">
                                <span class="input-group-text cursor-pointer" id="togglePassword">
                                    <i class="ti ti-eye-off"></i>
                                </span>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="password-confirm" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input id="confirmPassword" type="password" name="password_confirmation"
                                    class="form-control" required autocomplete="new-password"
                                    placeholder="Confirm password">
                                <span class="input-group-text cursor-pointer" id="toggleConfirmPassword">
                                    <i class="ti ti-eye-off"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary d-grid w-100">
                            Reset Password
                        </button>
                    </form>


                </div>
            </div>
            <!-- /Right Reset Form -->
        </div>
    </div>
@endsection

@section('page_leavel_script')
    <script src="{{ asset('software/js/pages-auth.js') }}"></script>
    <script>
        $(document).ready(function() {

            $('#togglePassword').on('click', function() {
                let input = $('#password');
                let icon = $(this).find('i');
                let type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);

                // Change icon
                if (type === 'text') {
                    icon.removeClass('ti ti-eye-off').addClass('ti ti-eye');
                } else {
                    icon.removeClass('ti ti-eye').addClass('ti ti-eye-off');
                }
            });

            $('#toggleConfirmPassword').on('click', function() {
                let input = $('#confirmPassword');
                let icon = $(this).find('i');
                let type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);

                // Change icon
                if (type === 'text') {
                    icon.removeClass('ti ti-eye-off').addClass('ti ti-eye');
                } else {
                    icon.removeClass('ti ti-eye').addClass('ti ti-eye-off');
                }
            });
        });
    </script>


@endsection
