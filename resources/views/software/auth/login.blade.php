@extends('software.layout.app')

@section('title', 'Admin Login')

@section('page_leavel_style')
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/@form-validation/form-validation.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/css/pages/page-auth.css') }}" />
@endsection

@section('content')
    <!-- Content -->

    <div class="authentication-wrapper authentication-cover authentication-bg">
        <div class="authentication-inner row">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-7 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('software/img/illustrations/auth-login-illustration-light.png') }}"
                        alt="auth-login-cover" class="img-fluid my-5 auth-illustration" />

                    <img src="{{ asset('software/img/illustrations/bg-shape-image-light.png') }}" alt="auth-login-cover"
                        class="platform-bg" />
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
                <div class="w-px-400 mx-auto">
                    <!-- Logo -->
                    @if ($errors->has('plan_expired'))
                        <div class="alert alert-danger">
                            {{ $errors->first('plan_expired') }}
                        </div>
                    @endif
                    @if ($errors->has('company_inactive'))
                        <div class="alert alert-danger">
                            {{ $errors->first('company_inactive') }}
                        </div>
                    @endif

                    <div class="app-brand mb-4">
                        <a href="{{ route('software.login') }}" class="app-brand-link gap-2">
                            <img class="w-100" src="{{ asset('software/img/logo.png') }}" />
                            {{-- <span class="app-brand-logo demo">
                            <svg width="32" height="22" viewBox="0 0 32 22" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z"
                                    fill="#266BEE" />
                                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                                    d="M7.69824 16.4364L12.5199 3.23696L16.5541 7.25596L7.69824 16.4364Z"
                                    fill="#161616" />
                                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8.07751 15.9175L13.9419 4.63989L16.5849 7.28475L8.07751 15.9175Z"
                                    fill="#161616" />
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M7.77295 16.3566L23.6563 0H32V6.88383C32 6.88383 31.8262 9.17836 30.6591 10.4057L19.7824 22H13.6938L7.77295 16.3566Z"
                                    fill="#266BEE" />
                            </svg>
                        </span> --}}
                        </a>
                    </div>
                    <!-- /Logo -->
                    <h3 class="mb-1">Welcome to {{ env('APP_NAME') }} 👋</h3>
                    {{-- <p class="mb-4">Please sign-in to your account and start the adventure</p> --}}

                    <form id="formAuthentication" class="mb-3" action="{{ route('software.submit.login') }}"
                        method="post">
                        @csrf
                        <div class="mb-3">
                            <label for="app_key" class="form-label">App Key</label>
                            <input type="text" class="form-control" id="app_key" name="app_key"
                                placeholder="Enter your app Key" autofocus value="{{ old('app_key') }}" />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Username or Email or Phone</label>
                            <input type="text" class="form-control" id="email" name="username"
                                placeholder="Enter your email or username" autofocus value="{{ old('username') }}" />
                        </div>
                        <div class="mb-3 form-password-toggle">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Password</label>
                            </div>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            <div class="text-end">
                                <a href="{{ route('software.forgot.password') }}">

                                    <small>Forgot Password?</small>
                                </a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember-me" />
                                <label class="form-check-label" for="remember-me"> Remember Me </label>
                            </div>
                        </div>
                        <button class="btn btn-primary d-grid w-100">Sign in</button>
                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted">Need to restore or set up the database?</span>
                        <a href="{{ route('software.db-import') }}" class="d-block mt-1 fw-semibold text-primary d-flex align-items-center justify-content-center gap-1">
                            <i class="ti ti-database-import fs-5"></i> Database Import Utility
                        </a>
                    </div>

                    {{-- <p class="text-center">
                    <span>New on our platform?</span>
                    <a href="auth-register-cover.html">
                        <span>Create an account</span>
                    </a>
                </p>

                <div class="divider my-4">
                    <div class="divider-text">or</div>
                </div>

                <div class="d-flex justify-content-center">
                    <a href="javascript:;" class="btn btn-icon btn-label-facebook me-3">
                        <i class="tf-icons fa-brands fa-facebook-f fs-5"></i>
                    </a>

                    <a href="javascript:;" class="btn btn-icon btn-label-google-plus me-3">
                        <i class="tf-icons fa-brands fa-google fs-5"></i>
                    </a>

                    <a href="javascript:;" class="btn btn-icon btn-label-twitter">
                        <i class="tf-icons fa-brands fa-twitter fs-5"></i>
                    </a>
                </div> --}}
                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

    <!-- / Content -->
@endsection

@section('page_leavel_script')
    <script src="{{ asset('software/js/pages-auth.js') }}"></script>
    <script>
        $(document).ready(function () {
            localStorage.removeItem('fcm_token');
        });
    </script>
@endsection
