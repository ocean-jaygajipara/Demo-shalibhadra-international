@extends('software.layout.app')

@php
    $i = 0;
    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $form_route = isset($modules['form_route']) ? $modules['form_route'] : null;
    $company_id = isset($modules['company_id']) ? $modules['company_id'] : null;
    $authLoginUserDetail = isset($modules['authLoginUserDetail']) ? $modules['authLoginUserDetail'] : null;
    $loginUserId = isset($authLoginUserDetail?->id) ? $authLoginUserDetail?->id : null;
    $parent_type_id = isset($modules['parent_type_id']) ? $modules['parent_type_id'] : null;

    $maring_bottom = 'mb-3';
@endphp

@section('title', $page_title)


@section('page_leavel_style')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" />

    <style>
        .select2-container {
            display: block !important;
        }

        .bootstrap-tagsinput {
            width: 100%;
            min-height: 40px;
            padding: 6px 10px;
            line-height: 22px;
            border: 1px solid #ccc;
            border-radius: 0.25rem;
            background: #fff;
        }

        .bootstrap-tagsinput .tag {
            margin-right: 2px;
            color: white;
            background-color: #29c3c0;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
        }
    </style>

@endsection

@section('content')
    <div class="d-flex justify-content-lg-between px-1">
        @include('software.inlcudes.breadcrumb', [
            'breadcrumbArray' => [
                ['title' => $page_title, 'url' => route($route . '.index')],
                [
                    'title' => 'Mail Setting',
                    'url' => '',
                ],
            ],
            'route' => $route,
            'show_add_btn' => false,
            'show_back_btn' => true,
        ])
    </div>

    <!-- Default -->
    <div class="row my-3">
        <!-- Default Wizard -->
        <div class="col-12 mb-6">
            <div class="card ">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between ">
                        <h4 class="mb-0">Company name :- {{ $company?->company_name ?? '' }}</h4>
                        <h4 class="mb-0">Mail Setting</h4>
                    </div>
                </div>

                <div class="card-body mt-3">
                    <form id="myEmailSettingForm" action="{{ $form_route ?? '#' }}" method="POST"
                        enctype="multipart/form-data">
                        <input type="hidden" name="company_id" value="{{ $company?->id ?? '' }}" />
                        @csrf

                        <!-- Mail Setting -->
                        <div class="row g-7 {{ $maring_bottom }}">

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="mailer">Mailer</label>
                                <input type="text" id="mailer" name="mailer"
                                    class="form-control @error('mailer') is-invalid @enderror"
                                    value="{{ old('mailer', $mail_setting->mailer ?? '') }}" placeholder="Mailer" />
                                @error('mailer')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="host">Host</label>
                                <input type="text" id="host" name="host"
                                    class="form-control @error('host') is-invalid @enderror"
                                    value="{{ old('host', $mail_setting?->host ?? '') }}" placeholder="Host" />
                                @error('host')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="app_key">Port</label>
                                <input type="text" id="port" name="port"
                                    class="form-control @error('port') is-invalid @enderror"
                                    value="{{ old('port', $mail_setting?->port ?? '') }}" placeholder="Port" />
                                @error('port')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" id="username" name="username"
                                    class="form-control @error('username') is-invalid @enderror"
                                    value="{{ old('host', $mail_setting?->username ?? '') }}" placeholder="Username" />
                                @error('username')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>


                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="mail_password">Password</label>
                                <input type="text" id="mail_password" name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    value="{{ old('host', $mail_setting?->password ?? '') }}" placeholder="Password" />
                                @error('password')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="encryption">Encryption</label>
                                {{-- <input type="text" id="encryption" name="encryption" class="form-control @error('encryption') is-invalid @enderror" value="{{ $mail_setting->encryption ?? '' }}" placeholder="Max. Customer Count" onkeypress="return isNumber(event)" {{ $modules['loginType'] == 'admin_software' ? '' : 'disabled' }} /> --}}

                                <select id="encryption" name="encryption"
                                    class="form-select @error('encryption') is-invalid @enderror">
                                    <option value="tls"
                                        {{ old('encryption', $mail_setting?->encryption) == 'tls' ? 'selected' : '' }}>TLS
                                    </option>
                                    <option value="ssl"
                                        {{ old('encryption', $mail_setting?->encryption) == 'ssl' ? 'selected' : '' }}>SSL
                                    </option>
                                </select>
                                @error('encryption')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="from_address">From Address</label>
                                <input type="text" id="from_address" name="from_address"
                                    class="form-control @error('from_address') is-invalid @enderror"
                                    value="{{ old('from_address', $mail_setting->from_address ?? '') }}"
                                    placeholder="From Address" />
                                @error('from_address')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="from_name">From Name</label>
                                <input type="text" id="from_name" name="from_name"
                                    class="form-control @error('from_name') is-invalid @enderror"
                                    value="{{ old('from_name', $mail_setting->from_name ?? '') }}"
                                    placeholder="From Name" />
                                @error('from_name')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                <label class="form-label" for="bcc">BCC <small class="muted">Press Enter add More
                                        than One BCC
                                        Mail</small></label>
                                <input type="text" id="bcc" name="bcc" data-role="tagsinput"
                                    class="input-tags form-control  @error('bcc') is-invalid @enderror"
                                    value="{{ old('bcc', $mail_setting->bcc ?? '') }}" />
                                @error('bcc')
                                    <span class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <!-- Submit and Cancel Buttons -->
                            <div class="col-sm-12 text-center {{ $maring_bottom }}">
                                <button type="submit" class="btn btn-success mt-1 mb-1">
                                    {{ isset($mail_setting) && isset($mail_setting?->id) ? 'Update' : 'Submit' }}
                                </button>
                                <button type="submit" name="btn_submit" value="submit_and_exit"
                                    class="btn btn-primary mt-1 mb-1">
                                    Submit and Exit
                                </button>
                                <a href="{{ route($route . '.index') }}" class="btn btn-danger mt-1 mb-1">Cancel</a>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Default Wizard -->
    </div>
@endsection

@section('page_leavel_script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.js"></script>

    <script>
        $('#bcc').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // stop form submit

                const input = $(this);
                const value = input.val().trim();

                if (value) {
                    input.tagsinput('add', value);
                    input.val('');
                }

                // wait a bit then submit
                setTimeout(() => {
                    $('#myEmailSettingForm').submit();
                }, 150);
            }
        });
    </script>
@endsection
