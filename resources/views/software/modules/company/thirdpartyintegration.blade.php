@extends('software.layout.app')

@php

    $page_title = isset($modules['title']) ? $modules['title'] : null;
    $folder_path = isset($modules['folder_path']) ? $modules['folder_path'] : null;
    $route = isset($modules['route']) ? $modules['route'] : null;
    $i = 0;
    if (!isset($edit)) {
        $edit = isset($thirdPartyIntegration) ? $thirdPartyIntegration : null;
    }
    // dd($modules);
    $maring_bottom = 'mb-3';
@endphp
@section('title', $page_title)


@section('page_leavel_style')

    <link rel="stylesheet" href="{{ asset('software/vendor/libs/bs-stepper/bs-stepper.css') }}" />
    <link rel="stylesheet" href="{{ asset('software/vendor/libs/flatpickr/flatpickr.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" />

    <style>
        .select2-container {
            display: block !important;
        }

        .ck-editor__editable_inline {
            min-height: 150px;
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
                    'title' => isset($edit) && $edit?->id ? 'Edit ' . $page_title : 'Create ' . $page_title,
                    'url' => '',
                ],
            ],
            'route' => $route,
            'show_add_btn' => false,
            'show_back_btn' => true,
        ])
    </div>

    <!-- Default -->
    <div class="row">
        <!-- Default Wizard -->
        <div class="col-12 mb-6">
            <div class="card p-3">
                <h4 class="mb-0">Company name :- {{ $edit?->company_name ?? '' }}</h4>
            </div>
        </div>
        <div class="col-12 mb-6">
            <div class="bs-stepper wizard-numbered mt-2">
                <div class="bs-stepper-header table-responsive">
                    <div class="line"><i class="icon-base ti tabler-chevron-right"></i></div>

                    <div class="step" data-target="#IndiaMart-Integration">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">IndiaMart</span>
                            </span>
                        </button>
                    </div>

                    <div class="step" data-target="#TradeIndia-Integration">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">TradeIndia</span>
                            </span>
                        </button>
                    </div>

                    <div class="step" data-target="#JustDial-Integration">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">JustDial</span>
                            </span>
                        </button>
                    </div>

                    <div class="step" data-target="#LinkedIn-Integration">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">4</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">LinkedIn</span>
                            </span>
                        </button>
                    </div>

                    <div class="step" data-target="#Facebook-Integration">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">5</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">Facebook</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    <form action="{{ route('company.thirdpartyintegration', $company->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="company_id" value="{{ $company?->id ?? '' }}" />
                        {{-- IndiaMart Section --}}
                        <div id="IndiaMart-Integration" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">Platform</label>
                                    <input type="text" readonly class="form-control" value="IndiaMart"
                                        name="indiamart_platform">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="indiamart_api_key"
                                        value="{{ old('indiamart_api_key', $company_social['indiamart']->api_key ?? '') }}"
                                        placeholder="IndiaMart API Key">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">API Run Time (In minutes)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="indiamart_minutes_time"
                                            value="{{ old('indiamart_minutes_time', $company_social['indiamart']->minutes_time ?? '') }}"
                                            placeholder="IndiaMart API Run Time">
                                        <span class="input-group-text">minutes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between">
                                <a href="{{ route('company.index') }}" class="btn btn-label-secondary btn-prev waves-effect"
                                    disabled="">
                                    <i class="menu-icon ti ti-chevrons-left"></i>
                                    <span class="align-middle d-sm-inline-block d-none">Back</span>
                                </a>
                                <button type="submit" id="submit"
                                    class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                            </div>
                        </div>

                        {{-- TradeIndia Section --}}
                        <div id="TradeIndia-Integration" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">Platform</label>
                                    <input type="text" readonly class="form-control" value="TradeIndia"
                                        name="tradeindia_platform">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="tradeindia_api_key"
                                        value="{{ old('tradeindia_api_key', $company_social['tradeindia']->api_key ?? '') }}"
                                        placeholder="TradeIndia API Key">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">User ID</label>
                                    <input type="text" class="form-control" name="tradeindia_user_id"
                                        value="{{ old('tradeindia_user_id', $company_social['tradeindia']->user_id ?? '') }}"
                                        placeholder="TradeIndia User ID">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">Profile ID</label>
                                    <input type="text" class="form-control" name="tradeindia_profile_id"
                                        value="{{ old('tradeindia_profile_id', $company_social['tradeindia']->profile_id ?? '') }}"
                                        placeholder="TradeIndia Profile ID">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" id="submit"
                                    class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                            </div>

                        </div>

                        {{-- JustDial Section --}}
                        <div id="JustDial-Integration" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">Platform</label>
                                    <input type="text" readonly class="form-control" value="JustDial"
                                        name="justdial_platform">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="justdial_api_key"
                                        value="{{ old('justdial_api_key', $company_social['justdial']->api_key ?? '') }}"
                                        placeholder="JustDial API Key">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" id="submit"
                                    class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                            </div>
                        </div>

                        {{-- LinkedIn Section --}}
                        <div id="LinkedIn-Integration" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">Platform</label>
                                    <input type="text" readonly class="form-control" value="LinkedIn"
                                        name="linkedin_platform">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="linkedin_api_key"
                                        value="{{ old('linkedin_api_key', $company_social['linkedin']->api_key ?? '') }}"
                                        placeholder="LinkedIn API Key">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" id="submit"
                                    class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
                            </div>
                        </div>

                        {{-- Facebook Section --}}
                        <div id="Facebook-Integration" class="content">
                            <div class="row g-6 {{ $maring_bottom }}">
                                <div class="col-md-12 col-sm-12 {{ $maring_bottom }}">
                                    <a href="{{ route('facebook.login') }}" class="btn btn-primary">
                                        <i class="fab fa-facebook"></i> Continue with Facebook
                                    </a>
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">Platform</label>
                                    <input type="text" readonly class="form-control" value="Facebook"
                                        name="facebook_platform">
                                </div>
                                <div class="col-md-4 col-sm-12 {{ $maring_bottom }}">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="facebook_api_key"
                                        value="{{ old('facebook_api_key', $company_social['facebook']->api_key ?? '') }}"
                                        placeholder="Facebook API Key">
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" id="submit"
                                    class="btn btn-success btn-submit waves-effect waves-light">Submit</button>
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
    <!-- Vendors JS -->
    <script src="{{ asset('software/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    <!-- Page JS -->

    <script src="{{ asset('software/js/form-wizard-numbered.js') }}"></script>


    <script src="{{ asset('software/vendor/libs/moment/moment.js') }}"></script>
    <script src="{{ asset('software/vendor/libs/flatpickr/flatpickr.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.js"></script>
    <!-- <script src="{{ asset('software/js/forms-pickers.js') }}"></script> -->

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
                    $('#myForm').submit();
                }, 150);
            }
        });
    </script>
@endsection
